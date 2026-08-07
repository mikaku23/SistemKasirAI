<?php

namespace App\Http\Services;


use App\Support\AuditTrail;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockBatches;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    use AuditTrail;

    public function indexData(): array
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $adjustments = $this->activeAdjustments();

        return [
            'stockAdjustments' => $adjustments,
            'stockAdjustmentStats' => $this->stats($adjustments),
        ];
    }

    public function referenceData(): array
    {
        return [
            'products' => Product::query()
                ->with(['category', 'unit'])
                ->orderBy('name')
                ->get(),
            'locations' => Location::query()
                ->orderBy('name')
                ->get(),
        ];
    }

    public function activeAdjustments(): Collection
    {
        return StockAdjustment::query()
            ->with(['product.category', 'product.unit', 'stockBatch', 'location', 'user'])
            ->orderByDesc('adjusted_at')
            ->orderByDesc('id')
            ->get();
    }

    public function stats(?Collection $adjustments = null): array
    {
        $adjustments = $adjustments ?? $this->activeAdjustments();

        return [
            'total' => StockAdjustment::query()->count(),
            'pending' => $adjustments->where('review_status', 'pending_review')->count(),
            'matched' => $adjustments->where('review_status', 'matched')->count(),
            'systemCorrect' => $adjustments->where('review_status', 'system_correct')->count(),
            'systemUpdated' => $adjustments->where('review_status', 'system_updated')->count(),
            'overage' => $adjustments->where('difference_qty', '>', 0)->count(),
            'shortage' => $adjustments->where('difference_qty', '<', 0)->count(),
        ];
    }

    public function store(array $data, ?User $user = null): StockAdjustment
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($payload, $user) {
            $product = Product::query()
                ->lockForUpdate()
                ->with(['unit', 'category'])
                ->findOrFail($payload['product_id']);

            $referenceBatch = $this->selectReferenceBatch($product->id);
            $systemQty = $this->stockTotalForProduct($product->id);
            $physicalQty = $payload['physical_qty'];
            $differenceQty = $physicalQty - $systemQty;

            $adjustment = StockAdjustment::create([
                'product_id' => $product->id,
                'stock_batch_id' => $referenceBatch?->id,
                'location_id' => $payload['location_id'] ?? $product->location_id,
                'user_id' => $user?->id ?? auth()->id(),
                'system_qty' => $systemQty,
                'physical_qty' => $physicalQty,
                'difference_qty' => $differenceQty,
                'adjustment_type' => $differenceQty >= 0 ? 'plus' : 'minus',
                'reason' => $payload['reason'],
                'adjusted_at' => now(),
                'metadata' => [
                    'comparison' => $differenceQty === 0 ? 'match' : ($differenceQty > 0 ? 'overage' : 'shortage'),
                    'review_status' => $differenceQty === 0 ? 'matched' : 'pending_review',
                    'review_status_label' => $differenceQty === 0 ? 'Data cocok dengan sistem' : ($differenceQty > 0 ? 'Stok fisik lebih' : 'Stok fisik kurang'),
                    'review_action_label' => $differenceQty === 0 ? 'Tidak ada perubahan' : ($differenceQty > 0 ? 'Tambah batch baru atau abaikan input' : 'Kurangi batch terdekat atau abaikan input'),
                    'system_action_text' => $differenceQty === 0
                        ? 'Stok dipertahankan.'
                        : ($differenceQty > 0 ? 'Selisih positif menunggu konfirmasi penambahan batch.' : 'Selisih negatif menunggu konfirmasi pengurangan stok.'),
                    'system_qty_before' => $systemQty,
                    'physical_qty_input' => $physicalQty,
                    'difference_qty' => $differenceQty,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'location_name' => optional(Location::find($payload['location_id'] ?? $product->location_id))->name,
                    'reference_batch' => $referenceBatch ? [
                        'id' => $referenceBatch->id,
                        'batch_code' => $referenceBatch->batch_code,
                        'lot_number' => $referenceBatch->lot_number,
                        'qty_remaining' => (int) $referenceBatch->qty_remaining,
                        'expired_at' => optional($referenceBatch->expired_at)->format('Y-m-d'),
                    ] : null,
                    'created_by' => $user?->id ?? auth()->id(),
                    'created_by_name' => $user?->name ?? auth()->user()?->name,
                    'created_at' => now()->toIso8601String(),
                ],
            ]);

            return $adjustment->fresh()->load(['product.category', 'product.unit', 'stockBatch', 'location', 'user']);
        });
    }
public function confirmSystemCorrect(StockAdjustment $adjustment, ?User $user = null): StockAdjustment
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        return DB::transaction(function () use ($adjustment, $user) {
            $adjustment = StockAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);

            $metadata = $adjustment->metadata ?? [];
            $metadata['review_status'] = 'system_correct';
            $metadata['review_status_label'] = 'Input pengecekan ditolak, stok sistem dipertahankan';
            $metadata['review_action_label'] = 'Sistem benar';
            $metadata['system_action_text'] = 'Tidak ada perubahan stok.';
            $metadata['reviewed_by'] = $user?->id ?? auth()->id();
            $metadata['reviewed_by_name'] = $user?->name ?? auth()->user()?->name;
            $metadata['reviewed_at'] = now()->toIso8601String();

            $adjustment->forceFill([
                'metadata' => $metadata,
            ])->save();

            return $adjustment->fresh()->load(['product.category', 'product.unit', 'stockBatch', 'location', 'user']);
        });
    }

    public function applyCorrection(StockAdjustment $adjustment, ?User $user = null): StockAdjustment
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        return DB::transaction(function () use ($adjustment, $user) {
            $adjustment = StockAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);

            $product = Product::query()
                ->lockForUpdate()
                ->with(['unit', 'category'])
                ->findOrFail($adjustment->product_id);

            $locationId = $adjustment->location_id ?? $product->location_id;
            $currentStock = $this->stockTotalForProduct($product->id);
            $physicalQty = (int) round((float) $adjustment->physical_qty);
            $delta = $physicalQty - $currentStock;

            if ($delta === 0) {
                $metadata = $adjustment->metadata ?? [];
                $metadata['review_status'] = 'matched';
                $metadata['review_status_label'] = 'Data cocok dengan sistem';
                $metadata['review_action_label'] = 'Tidak ada perubahan';
                $metadata['system_action_text'] = 'Stok dipertahankan.';
                $metadata['reviewed_by'] = $user?->id ?? auth()->id();
                $metadata['reviewed_by_name'] = $user?->name ?? auth()->user()?->name;
                $metadata['reviewed_at'] = now()->toIso8601String();

                $adjustment->forceFill(['metadata' => $metadata])->save();

                return $adjustment->fresh()->load(['product.category', 'product.unit', 'stockBatch', 'location', 'user']);
            }

            $changedBatches = $delta > 0
                ? $this->increaseStock($product, $delta, $locationId, $adjustment, $user)
                : $this->decreaseStock($product, abs($delta), $locationId, $adjustment, $user);

            $newStock = $this->stockTotalForProduct($product->id);
            $this->refreshProductStock($product->id);

            $metadata = $adjustment->metadata ?? [];
            $metadata['review_status'] = 'system_updated';
            $metadata['review_status_label'] = $delta > 0
                ? 'Stok sistem ditambah dari batch manual'
                : 'Stok sistem dikurangi mengikuti hasil pengecekan';
            $metadata['review_action_label'] = $delta > 0
                ? 'Batch tambahan dibuat'
                : 'Batch tambahan dihapus dulu, lalu batch terdekat disesuaikan';
            $metadata['system_action_text'] = $delta > 0
                ? 'Selisih positif disimpan sebagai batch tambahan.'
                : 'Selisih negatif mengurangi batch tambahan lebih dulu, lalu batch terdekat.';
            $metadata['system_qty_revalidated'] = $currentStock;
            $metadata['system_qty_after'] = $newStock;
            $metadata['applied_delta'] = $delta;
            $metadata['applied_batches'] = $changedBatches;
            $metadata['deleted_batches'] = array_values(array_filter($changedBatches, fn (array $item): bool => (bool) data_get($item, 'deleted', false)));
            $metadata['reviewed_by'] = $user?->id ?? auth()->id();
            $metadata['reviewed_by_name'] = $user?->name ?? auth()->user()?->name;
            $metadata['reviewed_at'] = now()->toIso8601String();

            $adjustment->forceFill([
                'stock_batch_id' => $adjustment->stock_batch_id ?: ($changedBatches[0]['batch_id'] ?? null),
                'system_qty' => $currentStock,
                'difference_qty' => $delta,
                'adjustment_type' => $delta >= 0 ? 'plus' : 'minus',
                'metadata' => $metadata,
            ])->save();

            return $adjustment->fresh()->load(['product.category', 'product.unit', 'stockBatch', 'location', 'user']);
        });
    }


    public function payload(StockAdjustment $adjustment): array
    {
        $adjustment->loadMissing(['product.category', 'product.unit', 'stockBatch', 'location', 'user']);

        $referenceBatch = $adjustment->stockBatch;

        return [
            'id' => $adjustment->id,
            'adjustment_code' => $adjustment->adjustment_code,
            'product_id' => $adjustment->product_id,
            'product_name' => optional($adjustment->product)->name,
            'product_sku' => optional($adjustment->product)->sku,
            'category_name' => optional(optional($adjustment->product)->category)->name,
            'unit_name' => optional(optional($adjustment->product)->unit)->name,
            'location_id' => $adjustment->location_id,
            'location_name' => optional($adjustment->location)->name,
            'batch_id' => $referenceBatch?->id,
            'batch_code' => $referenceBatch?->batch_code,
            'batch_lot_number' => $referenceBatch?->lot_number,
            'batch_qty_remaining' => $referenceBatch?->qty_remaining !== null ? (int) $referenceBatch->qty_remaining : null,
            'batch_expired_at' => optional($referenceBatch?->expired_at)->format('Y-m-d'),
            'system_qty' => (int) $adjustment->system_qty,
            'physical_qty' => (int) $adjustment->physical_qty,
            'difference_qty' => (int) $adjustment->difference_qty,
            'difference_label' => $adjustment->difference_label,
            'difference_sign' => $adjustment->difference_qty > 0 ? 'plus' : ($adjustment->difference_qty < 0 ? 'minus' : 'neutral'),
            'difference_direction_label' => $adjustment->difference_direction_label,
            'review_status' => $adjustment->review_status,
            'review_status_label' => $adjustment->review_status_label,
            'review_status_class' => $adjustment->review_status_class,
            'review_action_label' => $adjustment->review_action_label,
            'system_action_text' => $adjustment->system_action_text,
            'reason' => $adjustment->reason,
            'checker_name' => optional($adjustment->user)->name,
            'checked_at' => optional($adjustment->adjusted_at)->format('d M Y H:i'),
            'raw_adjusted_at' => optional($adjustment->adjusted_at)->toIso8601String(),
            'metadata' => $adjustment->metadata ?? [],
            'confirm_url' => route('stock-adjustments.confirm-system', $adjustment->id),
            'apply_url' => route('stock-adjustments.apply-correction', $adjustment->id),
            'show_url' => route('stock-adjustments.show', $adjustment->id),
        ];
    }

    protected function normalizePayload(array $data): array
    {
        return [
            'product_id' => $this->normalizeInteger($data['product_id'] ?? null),
            'location_id' => $this->nullableInteger($data['location_id'] ?? null),
            'physical_qty' => $this->normalizeInteger($data['physical_qty'] ?? null),
            'reason' => trim((string) ($data['reason'] ?? 'Pengecekan stok manual')),
        ];
    }

    protected function stockTotalForProduct(int $productId): int
    {
        return (int) StockBatches::query()
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->sum('qty_remaining');
    }

    protected function selectReferenceBatch(int $productId): ?StockBatches
    {
        return $this->sortableBatches($productId)
            ->first();
    }

    protected function sortableBatches(int $productId): Collection
    {
        return StockBatches::query()
            ->with('product')
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->where('qty_remaining', '>', 0)
            ->get()
            ->sort(function (StockBatches $left, StockBatches $right) {
                return $this->batchSortKey($left) <=> $this->batchSortKey($right);
            })
            ->values();
    }

    protected function sortableBatchesForDecrease(int $productId): Collection
    {
        return StockBatches::query()
            ->with('product')
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->where('qty_remaining', '>', 0)
            ->get()
            ->sort(function (StockBatches $left, StockBatches $right) {
                return $this->correctionBatchSortKey($left) <=> $this->correctionBatchSortKey($right);
            })
            ->values();
    }

    protected function isAdjustmentGeneratedBatch(StockBatches $batch): bool
    {
        return data_get($batch->metadata, 'source') === 'stock_adjustment';
    }

    protected function correctionBatchSortKey(StockBatches $batch): array
    {
        $isAdjustment = $this->isAdjustmentGeneratedBatch($batch) ? 0 : 1;
        $adjustmentRecency = $this->isAdjustmentGeneratedBatch($batch)
            ? -((int) optional($batch->created_at)->timestamp)
            : PHP_INT_MAX;
        $daysLeft = $batch->expiry_days_left;
        $expiryRank = $daysLeft === null ? PHP_INT_MAX : (int) $daysLeft;
        $productionRank = $batch->production_date ? Carbon::parse($batch->production_date)->timestamp : PHP_INT_MAX;
        $receivedRank = $batch->received_at ? Carbon::parse($batch->received_at)->timestamp : PHP_INT_MAX;

        return [$isAdjustment, $adjustmentRecency, $expiryRank, $productionRank, $receivedRank, $batch->id];
    }
protected function increaseStock(Product $product, int $quantity, ?int $locationId, StockAdjustment $adjustment, ?User $user = null): array
    {
        $createdBatch = $this->createAdjustmentBatch($product, $quantity, $locationId, $adjustment, $user);

        return [[
            'batch_id' => $createdBatch->id,
            'batch_code' => $createdBatch->batch_code,
            'quantity' => $quantity,
            'before' => 0,
            'after' => (int) $createdBatch->qty_remaining,
            'direction' => 'in',
            'source' => 'stock_adjustment',
            'deleted' => false,
        ]];
    }

    protected function decreaseStock(Product $product, int $quantity, ?int $locationId, StockAdjustment $adjustment, ?User $user = null): array
    {
        $batches = $this->sortableBatchesForDecrease($product->id);
        $remaining = $quantity;
        $changes = [];

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $before = (int) $batch->qty_remaining;
            if ($before <= 0) {
                continue;
            }

            $take = min($before, $remaining);
            $after = $before - $take;
            $isAdjustmentBatch = $this->isAdjustmentGeneratedBatch($batch);

            $batch->forceFill([
                'qty_remaining' => $after,
                'status' => $this->resolveBatchStatus($batch, $after),
            ])->save();

            $this->createMovement(
                product: $product,
                batch: $batch,
                movementType: 'write_off',
                quantity: $take,
                locationId: $locationId,
                user: $user,
                adjustment: $adjustment,
                direction: 'out',
                notes: $isAdjustmentBatch
                    ? 'Koreksi stok manual: batch tambahan dari pengecekan sebelumnya dikurangi lebih dulu.'
                    : 'Koreksi stok manual: batch dengan expiry terdekat dikurangi.'
            );

            $deleted = false;

            if ($after <= 0 && $isAdjustmentBatch) {
                $batch->forceFill([
                    'status' => 'finished',
                ])->save();
                $batch->delete();
                $deleted = true;
            }

            $changes[] = [
                'batch_id' => $batch->id,
                'batch_code' => $batch->batch_code,
                'quantity' => $take,
                'before' => $before,
                'after' => $after,
                'direction' => 'out',
                'source' => $isAdjustmentBatch ? 'stock_adjustment' : 'stock_batch',
                'deleted' => $deleted,
            ];

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'physical_qty' => 'Stok sistem belum cukup untuk dikoreksi sebesar ' . $remaining . ' pcs. Cek ulang data batch terlebih dahulu.',
            ]);
        }

        return $changes;
    }

    protected function createAdjustmentBatch(Product $product, int $quantity, ?int $locationId, StockAdjustment $adjustment, ?User $user = null): StockBatches
    {
        $batch = StockBatches::create([
            'product_id' => $product->id,
            'supplier_id' => $product->supplier_id,
            'location_id' => $locationId ?? $product->location_id,
            'received_by' => $user?->id ?? auth()->id(),
            'batch_code' => $this->temporaryCode('ADJ'),
            'lot_number' => $this->temporaryCode('LOT-ADJ'),
            'qty_received' => $quantity,
            'qty_remaining' => $quantity,
            'purchase_price' => 0,
            'production_date' => null,
            'expired_at' => null,
            'received_at' => now()->toDateString(),
            'status' => 'active',
            'notes' => 'Batch tambahan dari pengecekan manual.',
            'metadata' => [
                'source' => 'stock_adjustment',
                'source_label' => 'Pengecekan manual',
                'adjustment_id' => $adjustment->id,
                'adjustment_code' => $adjustment->adjustment_code,
                'direction' => 'in',
                'added_at' => now()->toIso8601String(),
                'added_by' => $user?->id ?? auth()->id(),
                'added_by_name' => $user?->name ?? auth()->user()?->name,
                'expiry_mode' => 'none',
                'production_date' => null,
                'expired_at' => null,
                'shelf_life_days' => null,
                'expiry_warning_days' => null,
                'expiry_grace_days' => null,
            ],
        ]);

        $this->createMovement(
            product: $product,
            batch: $batch,
            movementType: 'adjustment',
            quantity: $quantity,
            locationId: $locationId,
            user: $user,
            adjustment: $adjustment,
            direction: 'in',
            notes: 'Selisih positif ditambahkan sebagai batch baru.'
        );

        return $batch;
    }

    protected function createMovement(
        Product $product,
        StockBatches $batch,
        string $movementType,
        int $quantity,
        ?int $locationId,
        ?User $user,
        StockAdjustment $adjustment,
        string $direction,
        string $notes
    ): void {
        StockMovement::create([
            'product_id' => $product->id,
            'stock_batch_id' => $batch->id,
            'location_id' => $locationId ?? $batch->location_id,
            'user_id' => $user?->id ?? auth()->id(),
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'unit_cost' => 0,
            'reference_type' => StockAdjustment::class,
            'reference_id' => $adjustment->id,
            'movement_at' => now(),
            'notes' => $notes,
            'metadata' => [
                'source' => 'stock_adjustment',
                'adjustment_code' => $adjustment->adjustment_code,
                'adjustment_id' => $adjustment->id,
                'direction' => $direction,
                'system_qty' => (int) $adjustment->system_qty,
                'physical_qty' => (int) $adjustment->physical_qty,
                'difference_qty' => (int) $adjustment->difference_qty,
                'product_name' => $product->name,
                'batch_code' => $batch->batch_code,
            ],
        ]);
    }

    protected function refreshProductStock(int $productId): int
    {
        $stock = $this->stockTotalForProduct($productId);

        Product::query()
            ->whereKey($productId)
            ->update([
                'stock_on_hand' => $stock > 0 ? $stock : null,
            ]);

        app(ProductService::class)->refreshExpirySnapshot($productId);

        return $stock;
    }

    protected function resolveBatchStatus(StockBatches $batch, int $qtyRemaining): string
    {

        if ($qtyRemaining <= 0) {
            return 'finished';
        }

        $status = $batch->expiry_status;

        if (in_array($status, ['expired'], true)) {
            return 'expired';
        }

        if (in_array($status, ['expiring_soon', 'expires_today', 'grace_period'], true)) {
            return 'near_expired';
        }

        return 'active';
    }

    protected function batchSortKey(StockBatches $batch): array
    {
        $daysLeft = $batch->expiry_days_left;
        $expiryRank = $daysLeft === null ? PHP_INT_MAX : (int) $daysLeft;
        $productionRank = $batch->production_date ? Carbon::parse($batch->production_date)->timestamp : PHP_INT_MAX;
        $receivedRank = $batch->received_at ? Carbon::parse($batch->received_at)->timestamp : PHP_INT_MAX;

        return [$expiryRank, $productionRank, $receivedRank, $batch->id];
    }

    protected function temporaryCode(string $prefix): string
    {
        return sprintf('%s-%s-%s', $prefix, now()->format('YmdHisv'), Str::upper(Str::random(6)));
    }

    protected function normalizeInteger(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^0-9\-]/', '', $value) ?? '';

        return $value === '' ? null : (int) $value;
    }

    protected function nullableInteger(mixed $value): ?int
    {
        return $this->normalizeInteger($value);
    }
}

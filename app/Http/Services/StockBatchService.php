<?php

namespace App\Http\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockBatches;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StockBatchService
{
    public function indexData(): array
    {
        $activeBatches = $this->activeBatches();

        return [
            'stockBatches' => $activeBatches,
            'trashedStockBatches' => $this->trashedBatches(),
            'stockBatchStats' => $this->stats($activeBatches),
        ];
    }

    public function referenceData(): array
    {
        return [
            'products' => Product::query()
                ->with(['category', 'unit', 'supplier'])
                ->orderBy('name')
                ->get(),
            'suppliers' => Supplier::query()
                ->orderBy('name')
                ->get(),
            'locations' => Location::query()
                ->orderBy('name')
                ->get(),
            'users' => User::query()
                ->orderBy('name')
                ->get(),
        ];
    }

    public function activeBatches(): Collection
    {
        return StockBatches::query()
            ->with(['product.category', 'product.unit', 'supplier', 'location', 'receiver'])
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->get();
    }

    public function trashedBatches(): Collection
    {
        return StockBatches::onlyTrashed()
            ->with(['product.category', 'product.unit', 'supplier', 'location', 'receiver'])
            ->orderByDesc('deleted_at')
            ->orderByDesc('id')
            ->get();
    }

    public function stats(?Collection $activeBatches = null): array
    {
        $activeBatches = $activeBatches ?? $this->activeBatches();

        return [
            'total' => StockBatches::query()->count(),
            'active' => $activeBatches->where('status', 'active')->count(),
            'expiringSoon' => $activeBatches->whereIn('status', ['expiring_soon', 'expires_today', 'grace_period'])->count(),
            'expired' => $activeBatches->where('status', 'expired')->count(),
            'depleted' => $activeBatches->where('status', 'depleted')->count(),
            'qty_received' => (int) $activeBatches->sum(fn (StockBatches $batch) => (int) $batch->qty_received),
            'qty_remaining' => (int) $activeBatches->sum(fn (StockBatches $batch) => (int) $batch->qty_remaining),
            'trashed' => StockBatches::onlyTrashed()->count(),
        ];
    }

    public function store(array $data): StockBatches
    {
        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($payload) {
            $product = Product::query()
                ->lockForUpdate()
                ->with(['supplier'])
                ->findOrFail($payload['product_id']);

            $payload['supplier_id'] = $payload['supplier_id'] ?? $product->supplier_id;
            $payload['location_id'] = $payload['location_id'] ?? $product->location_id;
            $payload['qty_remaining'] = $payload['qty_remaining'] ?? $payload['qty_received'];
            $payload['batch_code'] = $this->temporaryCode('BCH');
            $payload['lot_number'] = $this->temporaryCode('LOT');

            [$resolvedExpiredAt, $metadata, $status] = $this->resolveSmartExpiry($product, $payload);

            $payload['expired_at'] = $resolvedExpiredAt;
            $payload['metadata'] = $metadata;
            $payload['status'] = $status;

            $batch = StockBatches::create($payload);

            $this->applySmartIdentity($batch, $product);

            $this->createMovement(
                batch: $batch->fresh(),
                movementType: 'IN',
                quantity: (int) $payload['qty_received'],
                unitCost: (int) ($payload['purchase_price'] ?? 0),
                notes: 'Penerimaan stok batch baru.',
                metadata: [
                    'action' => 'stock_in',
                    'qty_received' => (int) $payload['qty_received'],
                ],
            );

            $this->syncProductStock((int) $batch->product_id);

            return $batch->refresh()->load(['product.category', 'product.unit', 'supplier', 'location', 'receiver']);
        });
    }

    public function update(StockBatches $stockBatch, array $data): StockBatches
    {
        $original = $stockBatch->fresh();
        $payload = $this->normalizePayload($data, $original);

        return DB::transaction(function () use ($stockBatch, $original, $payload) {
            $product = Product::query()
                ->lockForUpdate()
                ->with(['supplier'])
                ->findOrFail($payload['product_id']);

            $payload['supplier_id'] = $payload['supplier_id'] ?? $product->supplier_id;
            $payload['location_id'] = $payload['location_id'] ?? $product->location_id;

            // Qty received dan qty remaining adalah hasil proses barang masuk.
            // Saat edit batch, nilainya dipertahankan agar perubahan stok tetap lewat stock movement / adjustment.
            $payload['qty_received'] = (int) $original->qty_received;
            $payload['qty_remaining'] = (int) $original->qty_remaining;

            [$resolvedExpiredAt, $metadata, $status] = $this->resolveSmartExpiry($product, $payload, $original);

            $payload['expired_at'] = $resolvedExpiredAt;
            $payload['metadata'] = $metadata;
            $payload['status'] = $status;

            $originalProductId = (int) $original->product_id;

            $stockBatch->fill($payload);
            $stockBatch->save();

            $this->applySmartIdentity($stockBatch, $product);

            $this->syncProductStock((int) $stockBatch->product_id);

            if ($originalProductId !== (int) $stockBatch->product_id) {
                $this->syncProductStock($originalProductId);
            }

            return $stockBatch->refresh()->load(['product.category', 'product.unit', 'supplier', 'location', 'receiver']);
        });
    }

    public function trash(StockBatches $stockBatch): void
    {
        if ((int) $stockBatch->qty_remaining > 0) {
            throw ValidationException::withMessages([
                'stock_batch' => 'Batch dengan stok aktif tidak bisa dihapus. Kosongkan sisa batch terlebih dahulu melalui proses yang sah.',
            ]);
        }

        $productId = (int) $stockBatch->product_id;
        $stockBatch->delete();

        $this->syncProductStock($productId);
    }

    public function restore(int $id): StockBatches
    {
        $stockBatch = StockBatches::onlyTrashed()->findOrFail($id);
        $stockBatch->restore();

        $product = Product::query()->with(['supplier'])->find($stockBatch->product_id);
        if ($product) {
            $this->applySmartIdentity($stockBatch, $product);
        }

        $this->syncProductStock((int) $stockBatch->product_id);

        return $stockBatch->refresh()->load(['product.category', 'product.unit', 'supplier', 'location', 'receiver']);
    }

    public function forceDelete(int $id): void
    {
        $stockBatch = StockBatches::onlyTrashed()->findOrFail($id);

        if ((int) $stockBatch->qty_remaining > 0) {
            throw ValidationException::withMessages([
                'stock_batch' => 'Batch dengan stok aktif tidak bisa dihapus permanen. Kosongkan batch terlebih dahulu.',
            ]);
        }

        $productId = (int) $stockBatch->product_id;
        $stockBatch->forceDelete();

        $this->syncProductStock($productId);
    }

    public function payload(StockBatches $stockBatch): array
    {
        $stockBatch->loadMissing(['product.category', 'product.unit', 'supplier', 'location', 'receiver']);

        $receivedAt = $stockBatch->received_at ? Carbon::parse($stockBatch->received_at) : null;
        $productionDate = $stockBatch->production_date ? Carbon::parse($stockBatch->production_date) : null;

        return [
            'id' => $stockBatch->id,
            'product_id' => $stockBatch->product_id,
            'product_name' => optional($stockBatch->product)->name,
            'product_category' => optional(optional($stockBatch->product)->category)->name,
            'product_unit' => optional(optional($stockBatch->product)->unit)->name,
            'product_sku' => optional($stockBatch->product)->sku,
            'product_stock_on_hand' => optional($stockBatch->product)->stock_on_hand,
            'supplier_id' => $stockBatch->supplier_id,
            'supplier_name' => optional($stockBatch->supplier)->name,
            'supplier_code' => optional($stockBatch->supplier)->code,
            'location_id' => $stockBatch->location_id,
            'location_name' => optional($stockBatch->location)->name,
            'received_by' => $stockBatch->received_by,
            'receiver_name' => optional($stockBatch->receiver)->name,
            'added_at' => optional($stockBatch->received_at)->format('d M Y'),
            'added_by_id' => $stockBatch->received_by,
            'added_by_name' => optional($stockBatch->receiver)->name,
            'batch_code' => $stockBatch->batch_code,
            'lot_number' => $stockBatch->lot_number,
            'batch_code_pattern' => $this->batchCodePattern($receivedAt, (int) $stockBatch->qty_received, $productionDate),
            'lot_number_pattern' => $this->lotNumberPattern(optional($stockBatch->product)->sku, optional($stockBatch->supplier)->code, $receivedAt),
            'qty_received' => (int) $stockBatch->qty_received,
            'qty_remaining' => (int) $stockBatch->qty_remaining,
            'purchase_price' => (int) $stockBatch->purchase_price,
            'production_date' => optional($stockBatch->production_date)->format('Y-m-d'),
            'expired_at' => optional($stockBatch->expired_at)->format('Y-m-d'),
            'received_at' => optional($stockBatch->received_at)->format('Y-m-d'),
            'expiry_mode' => data_get($stockBatch->metadata, 'expiry_mode', 'none'),
            'expiry_mode_label' => $stockBatch->expiry_mode_label,
            'expiry_warning_days' => (int) data_get($stockBatch->metadata, 'expiry_warning_days', 30),
            'expiry_grace_days' => (int) data_get($stockBatch->metadata, 'expiry_grace_days', 0),
            'shelf_life_days' => data_get($stockBatch->metadata, 'shelf_life_days'),
            'status' => $stockBatch->status,
            'status_label' => $stockBatch->status_label,
            'status_class' => $stockBatch->expiry_status_class,
            'expiry_status' => $stockBatch->expiry_status,
            'expiry_status_label' => $stockBatch->expiry_status_label,
            'expiry_summary' => $stockBatch->expiry_summary,
            'notes' => $stockBatch->notes,
            'updated_at' => optional($stockBatch->updated_at)->format('d M Y H:i'),
            'deleted_at' => optional($stockBatch->deleted_at)->format('d M Y H:i'),
        ];
    }

    protected function normalizePayload(array $data, ?StockBatches $existingBatch = null): array
    {
        $qtyReceived = $this->normalizeInteger($data['qty_received'] ?? null);
        $qtyRemaining = array_key_exists('qty_remaining', $data)
            ? $this->normalizeInteger($data['qty_remaining'])
            : null;

        $purchasePrice = $this->normalizeInteger($data['purchase_price'] ?? null);

        return [
            'product_id' => $this->normalizeInteger($data['product_id'] ?? null) ?? ($existingBatch?->product_id),
            'supplier_id' => $this->nullableInteger($data['supplier_id'] ?? null) ?? ($existingBatch?->supplier_id),
            'location_id' => $this->nullableInteger($data['location_id'] ?? null) ?? ($existingBatch?->location_id),
            'received_by' => $this->nullableInteger($data['received_by'] ?? null) ?? ($existingBatch?->received_by ?? auth()->id()),
            'qty_received' => $qtyReceived ?? ($existingBatch ? (int) $existingBatch->qty_received : null),
            'qty_remaining' => $qtyRemaining,
            'purchase_price' => $purchasePrice ?? 0,
            'production_date' => $this->normalizeDate($data['production_date'] ?? null),
            'expired_at' => $this->normalizeDate($data['expired_at'] ?? null),
            'received_at' => $this->normalizeDate($data['received_at'] ?? null) ?? now()->toDateString(),
            'expiry_mode' => $this->normalizeExpiryMode($data['expiry_mode'] ?? 'none'),
            'shelf_life_days' => $this->nullableInteger($data['shelf_life_days'] ?? null),
            'expiry_warning_days' => $this->nullableInteger($data['expiry_warning_days'] ?? null) ?? 30,
            'expiry_grace_days' => $this->nullableInteger($data['expiry_grace_days'] ?? null) ?? 0,
            'notes' => trim((string) ($data['notes'] ?? '')),
        ];
    }

    protected function resolveSmartExpiry(Product $product, array $payload, ?StockBatches $existingBatch = null): array
    {
        $qtyRemaining = (int) ($payload['qty_remaining'] ?? $existingBatch?->qty_remaining ?? $payload['qty_received'] ?? 0);
        $expiryMode = (string) ($payload['expiry_mode'] ?? 'none');

        $metadata = [
            'expiry_mode' => $expiryMode,
            'expiry_warning_days' => (int) ($payload['expiry_warning_days'] ?? $product->expiry_warning_days ?? 30),
            'expiry_grace_days' => (int) ($payload['expiry_grace_days'] ?? $product->expiry_grace_days ?? 0),
            'shelf_life_days' => $payload['shelf_life_days'] ?? $product->shelf_life_days,
        ];

        $productionDate = $payload['production_date']
            ? Carbon::parse($payload['production_date'])->startOfDay()
            : ($existingBatch?->production_date ? Carbon::parse($existingBatch->production_date)->startOfDay() : null);

        $expiredAt = $payload['expired_at']
            ? Carbon::parse($payload['expired_at'])->startOfDay()
            : ($existingBatch?->expired_at ? Carbon::parse($existingBatch->expired_at)->startOfDay() : null);

        if (!$product->tracks_expiry || $expiryMode === 'none') {
            return [null, $metadata, $qtyRemaining > 0 ? 'no_tracking' : 'depleted'];
        }

        if ($expiryMode === 'shelf_life') {
            $shelfLifeDays = (int) ($metadata['shelf_life_days'] ?? 0);

            if ($productionDate && $shelfLifeDays > 0) {
                $expiredAt = $productionDate->copy()->addDays($shelfLifeDays)->startOfDay();
            }
        }

        $status = $this->determineStatus(
            qtyRemaining: (float) $qtyRemaining,
            expiredAt: $expiredAt,
            metadata: $metadata
        );

        return [$expiredAt?->toDateString(), $metadata, $status];
    }

    protected function determineStatus(float $qtyRemaining, ?Carbon $expiredAt, array $metadata): string
    {
        if ($qtyRemaining <= 0) {
            return 'depleted';
        }

        if (!$expiredAt) {
            return 'unknown';
        }

        $daysLeft = now()->startOfDay()->diffInDays($expiredAt->copy()->startOfDay(), false);
        $warningDays = max(0, (int) ($metadata['expiry_warning_days'] ?? 30));
        $graceDays = max(0, (int) ($metadata['expiry_grace_days'] ?? 0));

        if ($daysLeft < 0) {
            return abs($daysLeft) <= $graceDays ? 'grace_period' : 'expired';
        }

        if ($daysLeft === 0) {
            return 'expires_today';
        }

        if ($daysLeft <= $warningDays) {
            return 'expiring_soon';
        }

        return 'active';
    }

    protected function applySmartIdentity(StockBatches $batch, Product $product): void
    {
        $batch->loadMissing(['supplier']);

        $batch->forceFill([
            'batch_code' => $this->buildBatchCode($batch),
            'lot_number' => $this->buildLotNumber($batch, $product),
        ])->saveQuietly();
    }

    protected function createMovement(
        StockBatches $batch,
        string $movementType,
        int $quantity,
        int $unitCost = 0,
        string $notes = '',
        array $metadata = []
    ): void {
        StockMovement::create([
            'product_id' => $batch->product_id,
            'stock_batch_id' => $batch->id,
            'location_id' => $batch->location_id,
            'user_id' => $batch->received_by ?? auth()->id(),
            'movement_type' => $movementType,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'reference_type' => StockBatches::class,
            'reference_id' => $batch->id,
            'movement_at' => $batch->received_at ?? now(),
            'notes' => $notes,
            'metadata' => array_merge([
                'batch_code' => $batch->batch_code,
                'lot_number' => $batch->lot_number,
            ], $metadata),
        ]);
    }

    protected function syncProductStock(int $productId): void
    {
        $stockOnHand = StockBatches::query()
            ->where('product_id', $productId)
            ->whereNull('deleted_at')
            ->sum('qty_remaining');

        Product::query()
            ->whereKey($productId)
            ->update([
                'stock_on_hand' => max(0, (int) round((float) $stockOnHand)),
            ]);

        app(ProductService::class)->refreshExpirySnapshot($productId);
    }

    protected function buildBatchCode(StockBatches $batch): string
    {
        $receivedDate = $this->formatDateCode($batch->received_at ?? now(), 'dmy');
        $productionDate = $batch->production_date
            ? $this->formatDateCode($batch->production_date, 'dMy')
            : 'NOPROD';

        return sprintf(
            'BCH-%s-%s-%s-%s',
            $batch->id,
            $receivedDate,
            max(1, (int) $batch->qty_received),
            $productionDate
        );
    }

    protected function buildLotNumber(StockBatches $batch, Product $product): string
    {
        $skuPrefix = $this->productSkuPrefix($product->sku ?: $product->barcode ?: $product->name, $product->id);
        $supplierCode = optional($batch->supplier)->code
            ?: optional($product->supplier)->code
            ?: 'NOSUP';
        $supplierPrefix = $this->firstSegment($supplierCode, 'NOSUP');
        $receivedDate = $this->formatDateCode($batch->received_at ?? now(), 'dmy');

        return sprintf('LOT-%s-%s-%s', $skuPrefix, $supplierPrefix, $receivedDate);
    }

    protected function batchCodePattern(?Carbon $receivedAt, int $qtyReceived, ?Carbon $productionDate): string
    {
        return sprintf(
            'BCH-{ID}-%s-%s-%s',
            $this->formatDateCode($receivedAt ?? now(), 'dmy'),
            max(1, $qtyReceived),
            $productionDate ? $this->formatDateCode($productionDate, 'dMy') : 'NOPROD'
        );
    }

    protected function lotNumberPattern(?string $sku, ?string $supplierCode, ?Carbon $receivedAt): string
    {
        return sprintf(
            'LOT-%s-%s-%s',
            $this->productSkuPrefix($sku ?: '', 0),
            $this->firstSegment($supplierCode ?: 'NOSUP', 'NOSUP'),
            $this->formatDateCode($receivedAt ?? now(), 'dmy')
        );
    }

    protected function productSkuPrefix(string $value, int $productId = 0): string
    {
        $normalized = $this->normalizeCode($value);
        if ($normalized === '') {
            return $productId > 0 ? 'PRD-' . $productId : 'PRD';
        }

        $parts = array_values(array_filter(explode('-', $normalized), static fn ($part) => $part !== ''));

        if (empty($parts)) {
            return $productId > 0 ? 'PRD-' . $productId : 'PRD';
        }

        $prefix = implode('-', array_slice($parts, 0, 2));

        return $prefix !== '' ? $prefix : ($productId > 0 ? 'PRD-' . $productId : 'PRD');
    }

    protected function firstSegment(?string $value, string $fallback = 'NOSUP'): string
    {
        $normalized = $this->normalizeCode((string) $value);
        if ($normalized === '') {
            return $fallback;
        }

        $parts = array_values(array_filter(explode('-', $normalized), static fn ($part) => $part !== ''));
        $segment = $parts[0] ?? '';

        return $segment !== '' ? $segment : $fallback;
    }

    protected function formatDateCode(Carbon|string $date, string $format = 'dmy'): string
    {
        return strtoupper(Carbon::parse($date)->format($format));
    }

    protected function normalizeCode(string $value): string
    {
        $value = Str::upper(trim($value));
        $value = preg_replace('/[^A-Z0-9-]/', '', $value) ?? '';
        $value = trim($value, '-');

        return $value;
    }

    protected function temporaryCode(string $prefix): string
    {
        return sprintf('%s-TMP-%s', $prefix, Str::upper(Str::random(10)));
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

    protected function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    protected function normalizeExpiryMode(mixed $value): string
    {
        $value = trim((string) $value);

        return in_array($value, ['none', 'fixed_date', 'shelf_life'], true) ? $value : 'none';
    }
}

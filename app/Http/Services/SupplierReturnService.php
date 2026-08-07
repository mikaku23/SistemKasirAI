<?php

namespace App\Http\Services;


use App\Support\AuditTrail;
use App\Models\Location;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\Returns;
use App\Models\StockBatches;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupplierReturnService
{
    use AuditTrail;

    public function indexData(): array
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $returns = $this->activeReturns();

        return [
            'returns' => $returns,
            'trashedReturns' => $this->trashedReturns(),
            'returnStats' => $this->stats($returns),
        ];
    }

    public function referenceData(): array
    {
        $stockBatches = StockBatches::query()
            ->with(['product.category', 'product.unit', 'supplier', 'location'])
            ->whereNull('deleted_at')
            ->where('qty_remaining', '>', 0)
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->get();

        $productIds = $stockBatches->pluck('product_id')->filter()->unique()->values();

        return [
            'suppliers' => Supplier::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'locations' => Location::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'products' => Product::query()
                ->with(['category', 'unit', 'supplier', 'location'])
                ->where('is_active', true)
                ->whereIn('id', $productIds->isEmpty() ? [0] : $productIds->all())
                ->orderBy('name')
                ->get(),
            'stockBatches' => $stockBatches,
        ];
    }

    public function activeReturns(): Collection
    {
        return Returns::query()
            ->with(['supplier', 'location', 'user', 'items.product.category', 'items.stockBatch', 'stockMovements.stockBatch'])
            ->where('return_type', 'supplier')
            ->orderByDesc('return_at')
            ->orderByDesc('id')
            ->get();
    }

    public function trashedReturns(): Collection
    {
        return Returns::onlyTrashed()
            ->with(['supplier', 'location', 'user', 'items.product.category', 'items.stockBatch', 'stockMovements.stockBatch'])
            ->where('return_type', 'supplier')
            ->orderByDesc('deleted_at')
            ->orderByDesc('id')
            ->get();
    }

    public function stats(?Collection $activeReturns = null): array
    {
        $activeReturns = $activeReturns ?? $this->activeReturns();

        return [
            'total' => Returns::query()->where('return_type', 'supplier')->count(),
            'completed' => $activeReturns->where('status', 'completed')->count(),
            'draft' => $activeReturns->where('status', 'draft')->count(),
            'approved' => $activeReturns->where('status', 'approved')->count(),
            'rejected' => $activeReturns->where('status', 'rejected')->count(),
            'qty_returned' => (int) $activeReturns->sum(fn (Returns $return) => (int) $return->items->sum('quantity')),
            'total_amount' => (int) $activeReturns->sum(fn (Returns $return) => (float) $return->total_amount),
            'trashed' => Returns::onlyTrashed()->where('return_type', 'supplier')->count(),
        ];
    }

    public function showData(Returns $return): array
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $return->loadMissing(['supplier', 'location', 'user', 'items.product.category', 'items.stockBatch', 'stockMovements.stockBatch']);

        return [
            'returnRecord' => $return,
            'summary' => [
                'item_count' => $return->items->count(),
                'qty_returned' => (int) $return->items->sum('quantity'),
                'total_amount' => (int) $return->total_amount,
                'completed_at' => optional($return->updated_at)->format('d M Y H:i'),
            ],
            'backUrl' => route('supplier-returns.index'),
        ];
    }

    public function store(array $data, ?User $user = null): Returns
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($payload, $user) {
            $supplier = Supplier::query()->lockForUpdate()->findOrFail($payload['supplier_id']);
            $location = Location::query()->lockForUpdate()->findOrFail($payload['location_id']);

            $return = Returns::create([
                'return_code' => $this->temporaryCode('RET'),
                'return_type' => 'supplier',
                'location_id' => $location->id,
                'user_id' => $user?->id ?? auth()->id(),
                'supplier_id' => $supplier->id,
                'reference_type' => null,
                'reference_id' => null,
                'status' => 'completed',
                'total_amount' => 0,
                'return_at' => Carbon::parse($payload['return_at']),
                'reason' => $payload['reason'],
                'metadata' => [
                    'module' => 'supplier_return',
                    'mode' => 'full_batch_archival',
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->name,
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'created_by' => $user?->id ?? auth()->id(),
                    'created_by_name' => $user?->name ?? auth()->user()?->name,
                    'created_at' => now()->toIso8601String(),
                ],
            ]);

            $items = [];
            $affectedProductIds = [];
            $totalAmount = 0;
            $qtyReturned = 0;

            foreach ($payload['items'] as $row) {
                $batch = StockBatches::query()
                    ->lockForUpdate()
                    ->with(['product', 'supplier', 'location'])
                    ->whereNull('deleted_at')
                    ->findOrFail($row['stock_batch_id']);

                $productId = (int) $row['product_id'];
                $submittedQty = (int) $row['quantity'];
                $batchQty = (int) $batch->qty_remaining;
                $unitPrice = (int) ($row['unit_price'] ?? (int) round((float) $batch->purchase_price));
                $subtotal = $batchQty * $unitPrice;

                if ((int) $batch->product_id !== $productId) {
                    throw ValidationException::withMessages([
                        'items' => 'Batch yang dipilih tidak sesuai dengan produk pada item return.',
                    ]);
                }

                if ((int) $batch->supplier_id !== (int) $supplier->id) {
                    throw ValidationException::withMessages([
                        'items' => 'Batch yang dipilih bukan milik supplier yang dipilih.',
                    ]);
                }

                if ((int) $batch->location_id !== (int) $location->id) {
                    throw ValidationException::withMessages([
                        'items' => 'Batch yang dipilih bukan berasal dari location yang dipilih.',
                    ]);
                }

                if ($submittedQty !== $batchQty) {
                    throw ValidationException::withMessages([
                        'items' => 'Qty return harus sama dengan qty batch aktif karena batch akan diarsipkan penuh.',
                    ]);
                }

                $snapshot = $this->captureBatchSnapshot($batch, $supplier, $location, $batchQty);

                $returnItem = ReturnItem::create([
                    'return_id' => $return->id,
                    'product_id' => $productId,
                    'stock_batch_id' => $batch->id,
                    'quantity' => $batchQty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'notes' => $row['notes'] ?? null,
                ]);

                $batch->forceFill([
                    'metadata' => array_merge($batch->metadata ?? [], [
                        'archived_by_return_id' => $return->id,
                        'archived_by_return_code' => $return->return_code,
                        'archived_at' => now()->toIso8601String(),
                        'archived_qty' => $batchQty,
                        'archived_snapshot' => $snapshot,
                    ]),
                ])->saveQuietly();

                StockMovement::create([
                    'product_id' => $productId,
                    'stock_batch_id' => $batch->id,
                    'location_id' => $location->id,
                    'user_id' => $user?->id ?? auth()->id(),
                    'movement_type' => 'return_out',
                    'quantity' => $batchQty,
                    'unit_cost' => $unitPrice,
                    'reference_type' => Returns::class,
                    'reference_id' => $return->id,
                    'movement_at' => Carbon::parse($payload['return_at']),
                    'notes' => 'Supplier return batch archived: ' . ($return->return_code ?? '-'),
                    'metadata' => [
                        'return_id' => $return->id,
                        'return_code' => $return->return_code,
                        'supplier_id' => $supplier->id,
                        'supplier_name' => $supplier->name,
                        'location_id' => $location->id,
                        'location_name' => $location->name,
                        'product_id' => $productId,
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'lot_number' => $batch->lot_number,
                        'state_before' => 'active',
                        'state_after' => 'archived',
                        'batch_qty' => $batchQty,
                        'before_qty' => $batchQty,
                        'after_qty' => 0,
                        'snapshot' => $snapshot,
                        'item_id' => $returnItem->id,
                        'item_notes' => $returnItem->notes,
                    ],
                ]);

                $items[] = [
                    'return_item_id' => $returnItem->id,
                    'batch_id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'lot_number' => $batch->lot_number,
                    'product_id' => $productId,
                    'product_name' => optional($batch->product)->name,
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->name,
                    'location_id' => $location->id,
                    'location_name' => $location->name,
                    'quantity' => $batchQty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'before_qty' => $batchQty,
                    'after_qty' => 0,
                    'batch_snapshot' => $snapshot,
                    'archived_at' => now()->toIso8601String(),
                ];

                $totalAmount += $subtotal;
                $qtyReturned += $batchQty;
                $affectedProductIds[$productId] = true;

                $batch->delete();
            }

            $return->forceFill([
                'total_amount' => $totalAmount,
                'metadata' => array_merge($return->metadata ?? [], [
                    'items' => $items,
                    'qty_returned' => $qtyReturned,
                    'total_amount' => $totalAmount,
                    'archived_batch_count' => count($items),
                ]),
            ])->save();

            $this->refreshProducts(array_keys($affectedProductIds));

            return $return->fresh()->load(['supplier', 'location', 'user', 'items.product.category', 'items.stockBatch', 'stockMovements.stockBatch']);
        });
    }

    public function trash(Returns $return, ?User $user = null): void
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $return->loadMissing(['supplier', 'location', 'user', 'items.product.category', 'items.stockBatch']);

        DB::transaction(function () use ($return, $user) {
            $lockedReturn = Returns::query()->lockForUpdate()->with(['items.stockBatch', 'supplier', 'location'])->findOrFail($return->id);

            $affectedProductIds = [];

            foreach ($lockedReturn->items as $item) {
                $batch = StockBatches::query()
                    ->lockForUpdate()
                    ->withTrashed()
                    ->findOrFail($item->stock_batch_id);

                $snapshot = $this->captureBatchSnapshot($batch, $lockedReturn->supplier, $lockedReturn->location, (int) $item->quantity);

                if ($batch->trashed()) {
                    $batch->restore();
                }

                $batch->forceFill([
                    'metadata' => array_merge($batch->metadata ?? [], [
                        'restored_from_return_id' => $lockedReturn->id,
                        'restored_from_return_code' => $lockedReturn->return_code,
                        'restored_at' => now()->toIso8601String(),
                        'restored_snapshot' => $snapshot,
                    ]),
                ])->saveQuietly();

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'stock_batch_id' => $batch->id,
                    'location_id' => $lockedReturn->location_id,
                    'user_id' => $user?->id ?? auth()->id(),
                    'movement_type' => 'return_in',
                    'quantity' => (int) $item->quantity,
                    'unit_cost' => (int) $item->unit_price,
                    'reference_type' => Returns::class,
                    'reference_id' => $lockedReturn->id,
                    'movement_at' => now(),
                    'notes' => 'Supplier return restored from recycle: ' . ($lockedReturn->return_code ?? '-'),
                    'metadata' => [
                        'return_id' => $lockedReturn->id,
                        'return_code' => $lockedReturn->return_code,
                        'supplier_id' => $lockedReturn->supplier_id,
                        'supplier_name' => optional($lockedReturn->supplier)->name,
                        'location_id' => $lockedReturn->location_id,
                        'location_name' => optional($lockedReturn->location)->name,
                        'product_id' => $item->product_id,
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'lot_number' => $batch->lot_number,
                        'state_before' => 'archived',
                        'state_after' => 'active',
                        'batch_qty' => (int) $item->quantity,
                        'quantity' => (int) $item->quantity,
                        'snapshot' => $snapshot,
                        'restored_from_trash' => true,
                    ],
                ]);

                $affectedProductIds[$item->product_id] = true;
            }

            $metadata = $lockedReturn->metadata ?? [];
            $metadata['trashed_at'] = now()->toIso8601String();
            $metadata['trashed_by'] = $user?->id ?? auth()->id();
            $metadata['trashed_by_name'] = $user?->name ?? auth()->user()?->name;

            $lockedReturn->forceFill(['metadata' => $metadata])->save();
            $lockedReturn->delete();

            $this->refreshProducts(array_keys($affectedProductIds));
        });
    }

    public function restore(int $id, ?User $user = null): Returns
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        return DB::transaction(function () use ($id, $user) {
            $return = Returns::onlyTrashed()
                ->with(['supplier', 'location', 'user', 'items.product.category', 'items.stockBatch'])
                ->lockForUpdate()
                ->findOrFail($id);

            $affectedProductIds = [];

            foreach ($return->items as $item) {
                $batch = StockBatches::query()
                    ->lockForUpdate()
                    ->withTrashed()
                    ->findOrFail($item->stock_batch_id);

                $snapshot = $this->captureBatchSnapshot($batch, $return->supplier, $return->location, (int) $item->quantity);

                $batch->forceFill([
                    'metadata' => array_merge($batch->metadata ?? [], [
                        'archived_by_return_id' => $return->id,
                        'archived_by_return_code' => $return->return_code,
                        'archived_at' => now()->toIso8601String(),
                        'archived_snapshot' => $snapshot,
                    ]),
                ])->saveQuietly();

                if (! $batch->trashed()) {
                    $batch->delete();
                }

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'stock_batch_id' => $batch->id,
                    'location_id' => $return->location_id,
                    'user_id' => $user?->id ?? auth()->id(),
                    'movement_type' => 'return_out',
                    'quantity' => (int) $item->quantity,
                    'unit_cost' => (int) $item->unit_price,
                    'reference_type' => Returns::class,
                    'reference_id' => $return->id,
                    'movement_at' => now(),
                    'notes' => 'Supplier return re-applied from recycle: ' . ($return->return_code ?? '-'),
                    'metadata' => [
                        'return_id' => $return->id,
                        'return_code' => $return->return_code,
                        'supplier_id' => $return->supplier_id,
                        'supplier_name' => optional($return->supplier)->name,
                        'location_id' => $return->location_id,
                        'location_name' => optional($return->location)->name,
                        'product_id' => $item->product_id,
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'lot_number' => $batch->lot_number,
                        'state_before' => 'active',
                        'state_after' => 'archived',
                        'batch_qty' => (int) $item->quantity,
                        'quantity' => (int) $item->quantity,
                        'snapshot' => $snapshot,
                        'restored_from_recycle' => true,
                    ],
                ]);

                $affectedProductIds[$item->product_id] = true;
            }

            $metadata = $return->metadata ?? [];
            $metadata['restored_at'] = now()->toIso8601String();
            $metadata['restored_by'] = $user?->id ?? auth()->id();
            $metadata['restored_by_name'] = $user?->name ?? auth()->user()?->name;

            $return->restore();
            $return->forceFill(['metadata' => $metadata])->save();

            $this->refreshProducts(array_keys($affectedProductIds));

            return $return->fresh()->load(['supplier', 'location', 'user', 'items.product.category', 'items.stockBatch', 'stockMovements.stockBatch']);
        });
    }

    public function forceDelete(int $id): void
    {
        $this->auditActivity(__FUNCTION__);
        $this->auditSystem('info', class_basename(static::class), __FUNCTION__ . ' dipanggil');

        $return = Returns::onlyTrashed()->findOrFail($id);
        $return->forceDelete();
    }

    protected function normalizePayload(array $data): array
    {
        $items = collect($data['items'] ?? [])
            ->map(function (array $row): array {
                return [
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'stock_batch_id' => (int) ($row['stock_batch_id'] ?? 0),
                    'quantity' => (int) ($row['quantity'] ?? 0),
                    'unit_price' => isset($row['unit_price']) && $row['unit_price'] !== '' ? (int) $row['unit_price'] : null,
                    'notes' => isset($row['notes']) ? trim((string) $row['notes']) : null,
                ];
            })
            ->filter(fn (array $row): bool => $row['product_id'] > 0 && $row['stock_batch_id'] > 0 && $row['quantity'] > 0)
            ->values()
            ->all();

        return [
            'supplier_id' => (int) ($data['supplier_id'] ?? 0),
            'location_id' => (int) ($data['location_id'] ?? 0),
            'return_at' => trim((string) ($data['return_at'] ?? now()->format('Y-m-d H:i:s'))),
            'reason' => trim((string) ($data['reason'] ?? '')),
            'items' => $items,
        ];
    }

    protected function captureBatchSnapshot(StockBatches $batch, ?Supplier $supplier = null, ?Location $location = null, ?int $qty = null): array
    {
        $batch->loadMissing(['product.category', 'product.unit', 'supplier', 'location']);

        return [
            'batch_id' => $batch->id,
            'batch_code' => $batch->batch_code,
            'lot_number' => $batch->lot_number,
            'product_id' => $batch->product_id,
            'product_name' => optional($batch->product)->name,
            'product_sku' => optional($batch->product)->sku,
            'product_category' => optional(optional($batch->product)->category)->name,
            'product_unit' => optional(optional($batch->product)->unit)->name,
            'supplier_id' => $supplier?->id ?? $batch->supplier_id,
            'supplier_name' => $supplier?->name ?? optional($batch->supplier)->name,
            'location_id' => $location?->id ?? $batch->location_id,
            'location_name' => $location?->name ?? optional($batch->location)->name,
            'qty_received' => (int) $batch->qty_received,
            'qty_remaining' => $qty ?? (int) $batch->qty_remaining,
            'purchase_price' => (int) $batch->purchase_price,
            'production_date' => optional($batch->production_date)->toDateString(),
            'expired_at' => optional($batch->expired_at)->toDateString(),
            'received_at' => optional($batch->received_at)->toDateString(),
            'status' => $batch->status,
            'expiry_status' => $batch->expiry_status,
        ];
    }

    protected function refreshProducts(array $productIds): void
    {
        collect($productIds)
            ->filter()
            ->unique()
            ->each(function ($productId): void {
                app(ProductService::class)->refreshStockSnapshot((int) $productId);
            });
    }

    protected function temporaryCode(string $prefix): string
    {
        return sprintf('%s-%s-%s', $prefix, now()->format('YmdHisv'), Str::upper(Str::random(6)));
    }
}

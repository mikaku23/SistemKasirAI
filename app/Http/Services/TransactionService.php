<?php

namespace App\Http\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockBatches;
use App\Models\StockMovement;
use App\Models\TaxSetting;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function indexData(): array
    {
        return [
            'transactions' => $this->activeTransactions(),
            'transactionStats' => $this->stats(),
        ];
    }

    public function referenceData(): array
    {
        $taxSettings = TaxSetting::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return [
            'locations' => Location::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'products' => Product::query()
                ->with(['category', 'unit', 'supplier', 'location'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'taxSettings' => $taxSettings,
            'defaultTaxSetting' => $taxSettings->firstWhere('is_default', true) ?: $taxSettings->first(),
        ];
    }

    public function activeTransactions(): Collection
    {
        return Transaction::query()
            ->with(['location', 'cashier', 'taxSetting', 'items.product', 'items.stockBatch', 'stockMovements.stockBatch'])
            ->withCount('items')
            ->orderByDesc('transaction_at')
            ->get();
    }

    public function stats(): array
    {
        return [
            'total' => Transaction::query()->count(),
            'today' => Transaction::query()->whereDate('transaction_at', today())->count(),
            'success' => Transaction::query()->where('status', 'success')->count(),
            'waiting' => Transaction::query()->where('status', 'waiting')->count(),
            'failed' => Transaction::query()->where('status', 'failed')->count(),
        ];
    }

    public function store(array $data, ?User $user = null): Transaction
    {
        $payload = $this->normalizePayload($data);
        $product = Product::query()->with(['unit', 'category'])->findOrFail($payload['product_id']);
        $taxSetting = TaxSetting::query()->where('is_active', true)->findOrFail($payload['tax_setting_id']);

        $saleableStock = $this->saleableStockForProductLocation($product->id, $payload['location_id']);

        if ($payload['quantity'] > $saleableStock) {
            $shortage = $payload['quantity'] - $saleableStock;

            throw ValidationException::withMessages([
                'quantity' => "Stok produk {$product->name} tidak mencukupi. Kurang {$shortage} pcs.",
            ]);
        }

        $unitPrice = max(0, (int) $product->sale_price);
        $promoDiscountPerUnit = max(0, (int) $product->effective_discount_amount);
        $grossSubtotal = $unitPrice * $payload['quantity'];
        $discountTotal = min($grossSubtotal, $promoDiscountPerUnit * $payload['quantity']);
        $netSubtotal = max(0, $grossSubtotal - $discountTotal);
        $taxAmount = $this->calculateTaxAmount($taxSetting, $netSubtotal);
        $totalAmount = max(0, $netSubtotal + $taxAmount);

        $paidAmount = $payload['payment_method'] === 'cash'
            ? max(0, (int) $payload['paid_amount'])
            : $totalAmount;

        if ($payload['payment_method'] === 'cash' && $paidAmount < $totalAmount) {
            $lack = $totalAmount - $paidAmount;

            throw ValidationException::withMessages([
                'paid_amount' => 'Uang pelanggan kurang Rp ' . number_format($lack, 0, ',', '.'),
            ]);
        }

        $status = 'success';
        $changeAmount = max(0, $paidAmount - $totalAmount);
        $transactionAt = Carbon::parse($payload['transaction_at']);

        return DB::transaction(function () use (
            $payload,
            $product,
            $taxSetting,
            $unitPrice,
            $promoDiscountPerUnit,
            $grossSubtotal,
            $discountTotal,
            $taxAmount,
            $totalAmount,
            $paidAmount,
            $status,
            $changeAmount,
            $transactionAt,
            $user
        ) {
            $transaction = Transaction::create([
                'transaction_code' => $this->temporaryCode(),
                'location_id' => $payload['location_id'],
                'cashier_id' => $payload['cashier_id'] ?? $user?->id,
                'tax_setting_id' => $taxSetting->id,
                'customer_name' => $payload['customer_name'] ?: null,
                'customer_phone' => $payload['customer_phone'] ?: null,
                'shift' => $payload['shift'],
                'subtotal' => $grossSubtotal,
                'discount_amount' => $discountTotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_method' => $payload['payment_method'],
                'status' => $status,
                'transaction_at' => $transactionAt,
                'notes' => $payload['notes'] ?: null,
                'metadata' => [
                    'source' => 'pos',
                    'inventory_applied' => false,
                    'item_count' => 1,
                    'total_quantity' => $payload['quantity'],
                    'product_name' => $product->name,
                    'promo_discount_per_unit' => $promoDiscountPerUnit,
                    'tax_name' => $taxSetting->name,
                    'tax_type' => $taxSetting->tax_type,
                    'tax_value' => $taxSetting->tax_value,
                ],
            ]);

            $allocations = $this->allocateBatches($product, $payload['location_id'], $payload['quantity']);

            if ($allocations['remaining'] > 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok produk {$product->name} tidak mencukupi. Kurang {$allocations['remaining']} pcs.",
                ]);
            }

            $firstBatchId = null;

            foreach ($allocations['items'] as $allocatedBatch) {
                /** @var StockBatches $batch */
                $batch = $allocatedBatch['batch'];
                $qty = (int) $allocatedBatch['quantity'];

                if ($firstBatchId === null) {
                    $firstBatchId = $batch->id;
                }

                $batch->forceFill([
                    'qty_remaining' => max(0, (int) $batch->qty_remaining - $qty),
                    'status' => max(0, (int) $batch->qty_remaining - $qty) <= 0 ? 'depleted' : 'active',
                ])->save();

                StockMovement::create([
                    'product_id' => $product->id,
                    'stock_batch_id' => $batch->id,
                    'location_id' => (int) $payload['location_id'],
                    'user_id' => $user?->id,
                    'movement_type' => 'out',
                    'quantity' => $qty,
                    'unit_cost' => (int) $batch->purchase_price,
                    'reference_type' => Transaction::class,
                    'reference_id' => $transaction->id,
                    'movement_at' => $transactionAt,
                    'notes' => 'Barang keluar via POS - ' . $transaction->transaction_code,
                    'metadata' => [
                        'source' => 'transaction',
                        'transaction_code' => $transaction->transaction_code,
                        'product_name' => $product->name,
                        'unit_price' => $unitPrice,
                        'promo_discount_per_unit' => $promoDiscountPerUnit,
                        'total_discount' => $discountTotal,
                        'status' => $status,
                    ],
                ]);
            }

            TransactionItem::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'stock_batch_id' => $firstBatchId,
                'quantity' => $payload['quantity'],
                'unit_price' => $unitPrice,
                'discount_amount' => $discountTotal,
                'subtotal' => $totalAmount,
            ]);

            $this->syncProductStockSnapshot($product->id);
            $product->forceFill(['last_sold_at' => $transactionAt])->save();

            $transaction->forceFill([
                'transaction_code' => $this->generateTransactionCode($transaction->refresh()),
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'inventory_applied' => true,
                    'item_count' => 1,
                    'total_quantity' => $payload['quantity'],
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'promo_discount_per_unit' => $promoDiscountPerUnit,
                    'batch_allocations' => collect($allocations['items'])->map(fn ($item) => [
                        'batch_id' => $item['batch']->id,
                        'batch_code' => $item['batch']->batch_code,
                        'quantity' => (int) $item['quantity'],
                        'expired_at' => optional($item['batch']->expired_at)->format('Y-m-d'),
                    ])->values()->all(),
                ]),
            ])->saveQuietly();

            return $transaction->refresh()->load([
                'location',
                'cashier',
                'taxSetting',
                'items.product',
                'items.stockBatch',
                'stockMovements.stockBatch',
            ]);
        });
    }

    public function payload(Transaction $transaction): array
    {
        $transaction->loadMissing([
            'location',
            'cashier',
            'taxSetting',
            'items.product',
            'items.stockBatch',
            'stockMovements.stockBatch',
        ]);

        $item = $transaction->items->first();

        return [
            'id' => $transaction->id,
            'transaction_code' => $transaction->transaction_code,
            'location' => optional($transaction->location)->name,
            'cashier' => optional($transaction->cashier)->name,
            'tax_setting' => optional($transaction->taxSetting)->name,
            'product_name' => optional($item?->product)->name,
            'quantity' => (int) optional($item)->quantity,
            'subtotal' => (int) $transaction->subtotal,
            'discount_amount' => (int) $transaction->discount_amount,
            'tax_amount' => (int) $transaction->tax_amount,
            'total_amount' => (int) $transaction->total_amount,
            'paid_amount' => (int) $transaction->paid_amount,
            'change_amount' => (int) $transaction->change_amount,
            'status' => $transaction->status,
            'status_label' => $transaction->status_label,
            'status_class' => $transaction->status_class,
            'shift_label' => $transaction->shift_label,
            'payment_method_label' => $transaction->payment_method_label,
            'transaction_at' => optional($transaction->transaction_at)->format('d M Y H:i'),
        ];
    }

    protected function normalizePayload(array $data): array
    {
        return [
            'location_id' => (int) ($data['location_id'] ?? 0),
            'tax_setting_id' => (int) ($data['tax_setting_id'] ?? 0),
            'cashier_id' => isset($data['cashier_id']) ? (int) $data['cashier_id'] : null,
            'product_id' => (int) ($data['product_id'] ?? 0),
            'quantity' => max(1, (int) ($data['quantity'] ?? 1)),
            'customer_name' => trim((string) ($data['customer_name'] ?? '')),
            'customer_phone' => trim((string) ($data['customer_phone'] ?? '')),
            'shift' => trim((string) ($data['shift'] ?? 'morning')),
            'payment_method' => trim((string) ($data['payment_method'] ?? 'cash')),
            'paid_amount' => max(0, (int) ($data['paid_amount'] ?? 0)),
            'transaction_at' => trim((string) ($data['transaction_at'] ?? now()->format('Y-m-d H:i:s'))),
            'notes' => trim((string) ($data['notes'] ?? '')),
        ];
    }

    protected function calculateTaxAmount(TaxSetting $taxSetting, int $baseAmount): int
    {
        if ($taxSetting->tax_type === 'percent') {
            return (int) round(($baseAmount * (int) $taxSetting->tax_value) / 100);
        }

        return max(0, (int) $taxSetting->tax_value);
    }

    protected function saleableStockForProductLocation(int $productId, int $locationId): int
    {
        $locationBatches = $this->saleableBatchesForProductLocation($productId, $locationId);

        if ($locationBatches->isNotEmpty()) {
            return (int) $locationBatches->sum('qty_remaining');
        }

        $fallbackBatches = $this->saleableBatchesForProduct($productId);

        if ($fallbackBatches->isNotEmpty()) {
            return (int) $fallbackBatches->sum('qty_remaining');
        }

        $product = Product::query()->find($productId);

        return max(0, (int) optional($product)->stock_on_hand);
    }

    protected function allocateBatches(Product $product, int $locationId, int $quantity): array
    {
        $batches = $this->saleableBatchesForProductLocation($product->id, $locationId);

        if ($batches->isEmpty()) {
            $batches = $this->saleableBatchesForProduct($product->id);
        }

        $sorted = $batches->sort(function (StockBatches $left, StockBatches $right) {
            return $this->batchSortKey($left) <=> $this->batchSortKey($right);
        })->values();

        $remaining = $quantity;
        $items = [];

        foreach ($sorted as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $available = max(0, (int) $batch->qty_remaining);

            if ($available <= 0) {
                continue;
            }

            $take = min($available, $remaining);

            if ($take > 0) {
                $items[] = [
                    'batch' => $batch,
                    'quantity' => $take,
                ];
                $remaining -= $take;
            }
        }

        return [
            'items' => $items,
            'remaining' => $remaining,
        ];
    }

    protected function saleableBatchesForProductLocation(int $productId, int $locationId): Collection
    {
        return StockBatches::query()
            ->where('product_id', $productId)
            ->where('location_id', $locationId)
            ->where('qty_remaining', '>', 0)
            ->get()
            ->filter(fn (StockBatches $batch) => $this->isSaleableBatch($batch))
            ->values();
    }

    protected function saleableBatchesForProduct(int $productId): Collection
    {
        return StockBatches::query()
            ->where('product_id', $productId)
            ->where('qty_remaining', '>', 0)
            ->get()
            ->filter(fn (StockBatches $batch) => $this->isSaleableBatch($batch))
            ->values();
    }

    protected function isSaleableBatch(StockBatches $batch): bool
    {
        return ! in_array($batch->expiry_status, ['expired', 'grace_period', 'depleted'], true);
    }

    protected function batchSortKey(StockBatches $batch): array
    {
        $daysLeft = $batch->expiry_days_left;
        $expiryRank = $daysLeft === null ? PHP_INT_MAX : max(-999999, (int) $daysLeft);
        $productionRank = $batch->production_date ? Carbon::parse($batch->production_date)->timestamp : PHP_INT_MAX;
        $receivedRank = $batch->received_at ? Carbon::parse($batch->received_at)->timestamp : PHP_INT_MAX;

        return [$expiryRank, $productionRank, $receivedRank, $batch->id];
    }

    protected function syncProductStockSnapshot(int $productId): void
    {
        $stock = (int) StockBatches::query()
            ->where('product_id', $productId)
            ->where('qty_remaining', '>', 0)
            ->get()
            ->sum('qty_remaining');

        Product::query()
            ->whereKey($productId)
            ->update(['stock_on_hand' => $stock]);
    }

    protected function generateTransactionCode(Transaction $transaction): string
    {
        $cashierId = (int) ($transaction->cashier_id ?: 0);
        $shift = strtoupper((string) ($transaction->shift ?: 'NA'));
        $dateCode = strtoupper($transaction->transaction_at?->format('dMy') ?? now()->format('dMy'));

        return sprintf(
            'TXN-%s-%s-%s-%s-%s',
            $transaction->id,
            $cashierId,
            $shift,
            $dateCode,
            $this->statusCode($transaction->status)
        );
    }

    protected function statusCode(?string $status): string
    {
        return match ($status) {
            'success' => 'SUC',
            'waiting' => 'WAIT',
            'failed' => 'FAIL',
            default => 'UNK',
        };
    }

    protected function temporaryCode(): string
    {
        return 'TMP-' . now()->format('YmdHisv') . '-' . Str::upper(Str::random(6));
    }
}

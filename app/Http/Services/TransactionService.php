<?php

namespace App\Http\Services;

use App\Models\DiscountSetting;
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
        $taxSettings = TaxSetting::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $discountSettings = DiscountSetting::query()
            ->where('is_active', true)
            ->where(function ($query) { $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()); })
            ->where(function ($query) { $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', now()->toDateString()); })
            ->orderByDesc('is_default')
            ->orderByDesc('priority')
            ->orderBy('minimum_total_amount')
            ->get();

        return [
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->with(['category', 'unit', 'supplier', 'location'])->where('is_active', true)->orderBy('name')->get(),
            'taxSettings' => $taxSettings,
            'defaultTaxSetting' => $taxSettings->firstWhere('is_default', true) ?: $taxSettings->first(),
            'discountSettings' => $discountSettings,
            'defaultDiscountSetting' => $discountSettings->firstWhere('is_default', true) ?: $discountSettings->first(),
        ];
    }


    public function findProductByBarcode(string $barcode): ?array
{
    $normalized = $this->normalizeBarcodeLookup($barcode);

    if ($normalized === '') {
        return null;
    }

    $product = Product::query()
        ->with(['unit', 'category', 'supplier', 'location'])
        ->where('barcode', $normalized)
        ->where('is_active', true)
        ->first();

    if (! $product) {
        return null;
    }

    return [
        'id' => $product->id,
        'barcode' => $product->barcode,
        'name' => $product->name,
        'sale_price' => (int) $product->sale_price,
        'effective_discount_amount' => (int) $product->effective_discount_amount,
        'stock_on_hand' => (int) $product->stock_on_hand,
        'unit_label' => optional($product->unit)->symbol ?? optional($product->unit)->name ?? '-',
        'category_name' => optional($product->category)->name,
        'image_url' => $product->image ? asset('storage/' . $product->image) : null,
    ];
}

    public function activeTransactions(): Collection
    {
        return Transaction::query()->with(['location', 'cashier', 'taxSetting', 'discountSetting', 'items.product', 'items.stockBatch', 'stockMovements.stockBatch'])->withCount('items')->orderByDesc('transaction_at')->get();
    }

    public function stats(): array
    {
        return [
            'total' => Transaction::query()->count(),
            'today' => Transaction::query()->whereDate('transaction_at', today())->count(),
            'paid' => Transaction::query()->where('status', 'paid')->count(),
            'draft' => Transaction::query()->where('status', 'draft')->count(),
            'cancelled' => Transaction::query()->where('status', 'cancelled')->count(),
            'refunded' => Transaction::query()->where('status', 'refunded')->count(),
        ];
    }

    public function store(array $data, ?User $user = null): Transaction
    {
        $payload = $this->normalizePayload($data);
        $taxSetting = TaxSetting::query()->where('is_active', true)->findOrFail($payload['tax_setting_id']);
        $transactionAt = Carbon::parse($payload['transaction_at']);
        $items = $this->normalizeItems($payload['items']);

        if (empty($items)) {
            throw ValidationException::withMessages(['items' => 'Minimal 1 product harus dipilih.']);
        }

        $productIds = collect($items)->pluck('product_id')->unique()->values();
        $products = Product::query()->with(['unit', 'category'])->whereIn('id', $productIds)->where('is_active', true)->get()->keyBy('id');

        foreach ($productIds as $productId) {
            if (! $products->has($productId)) {
                throw ValidationException::withMessages(['items' => 'Ada product yang tidak valid atau tidak aktif.']);
            }
        }

        $requiredByProduct = collect($items)->groupBy('product_id')->map(fn ($rows) => (int) $rows->sum('quantity'));
        foreach ($requiredByProduct as $productId => $requiredQty) {
            $product = $products->get($productId);
            $saleableStock = $this->saleableStockForProductLocation($productId, $payload['location_id']);
            if ($requiredQty > $saleableStock) {
                $shortage = $requiredQty - $saleableStock;
                throw ValidationException::withMessages(['items' => "Stok produk {$product->name} tidak mencukupi. Kurang {$shortage} pcs."]);
            }
        }

        $grossSubtotal = 0;
        $itemDiscountTotal = 0;
        $lineSummaries = [];
        foreach ($items as $row) {
            $product = $products->get($row['product_id']);
            $qty = (int) $row['quantity'];
            $unitPrice = max(0, (int) $product->sale_price);
            $promoPerUnit = max(0, (int) $product->effective_discount_amount);
            $lineGross = $unitPrice * $qty;
            $lineDiscount = min($lineGross, $promoPerUnit * $qty);
            $lineNet = max(0, $lineGross - $lineDiscount);
            $grossSubtotal += $lineGross;
            $itemDiscountTotal += $lineDiscount;
            $lineSummaries[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'promo_discount_per_unit' => $promoPerUnit,
                'line_item_discount' => $lineDiscount,
                'line_subtotal' => $lineNet,
            ];
        }

        $subtotalAfterItemDiscount = max(0, $grossSubtotal - $itemDiscountTotal);
        $discountSetting = $this->resolveApplicableDiscountSetting($subtotalAfterItemDiscount, $transactionAt);
        $transactionDiscountAmount = $this->calculateDiscountAmount($discountSetting, $subtotalAfterItemDiscount);
        $taxBase = max(0, $subtotalAfterItemDiscount - $transactionDiscountAmount);
        $taxAmount = $this->calculateTaxAmount($taxSetting, $taxBase);
        $totalAmount = max(0, $taxBase + $taxAmount);

        $paidAmount = $payload['payment_method'] === 'cash' ? max(0, (int) $payload['paid_amount']) : $totalAmount;
        if ($payload['payment_method'] === 'cash' && $paidAmount < $totalAmount) {
            $lack = $totalAmount - $paidAmount;
            throw ValidationException::withMessages(['paid_amount' => 'Uang pelanggan kurang Rp ' . number_format($lack, 0, ',', '.')]);
        }

        $status = 'paid';
        $changeAmount = max(0, $paidAmount - $totalAmount);

        return DB::transaction(function () use ($payload, $items, $products, $lineSummaries, $taxSetting, $discountSetting, $grossSubtotal, $itemDiscountTotal, $subtotalAfterItemDiscount, $transactionDiscountAmount, $taxBase, $taxAmount, $totalAmount, $paidAmount, $status, $changeAmount, $transactionAt, $user) {
            $transaction = Transaction::create([
                'transaction_code' => $this->temporaryCode(),
                'location_id' => $payload['location_id'],
                'cashier_id' => $payload['cashier_id'] ?? $user?->id,
                'tax_setting_id' => $taxSetting->id,
                'discount_setting_id' => $discountSetting?->id,
                'customer_name' => $payload['customer_name'] ?: null,
                'customer_phone' => $payload['customer_phone'] ?: null,
                'shift' => $payload['shift'],
                'subtotal' => $grossSubtotal,
                'discount_amount' => $transactionDiscountAmount,
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
                    'item_count' => count($lineSummaries),
                    'item_discount_total' => $itemDiscountTotal,
                    'subtotal_after_item_discount' => $subtotalAfterItemDiscount,
                    'transaction_discount_amount' => $transactionDiscountAmount,
                    'tax_base' => $taxBase,
                    'customer_paid' => $paidAmount,
                    'change_amount' => $changeAmount,
                    'tax_name' => $taxSetting->name,
                    'tax_type' => $taxSetting->tax_type,
                    'tax_value' => $taxSetting->tax_value,
                    'discount_setting' => $discountSetting ? [
                        'id' => $discountSetting->id,
                        'name' => $discountSetting->name,
                        'code' => $discountSetting->code,
                        'discount_type' => $discountSetting->discount_type,
                        'discount_value' => $discountSetting->discount_value,
                        'minimum_total_amount' => $discountSetting->minimum_total_amount,
                    ] : null,
                    'items' => $lineSummaries,
                ],
            ]);

            $persistedItems = [];
            $transactionBatchAllocations = [];

            foreach ($items as $row) {
                $product = $products->get($row['product_id']);
                $qty = (int) $row['quantity'];
                $allocations = $this->allocateBatches($product, $payload['location_id'], $qty);
                if ($allocations['remaining'] > 0) {
                    throw ValidationException::withMessages(['items' => "Stok produk {$product->name} tidak mencukupi. Kurang {$allocations['remaining']} pcs."]); }

                $firstBatchId = null;
                $batchAllocations = [];
                $unitPrice = max(0, (int) $product->sale_price);
                $promoPerUnit = max(0, (int) $product->effective_discount_amount);
                $lineGross = $unitPrice * $qty;
                $lineItemDiscount = min($lineGross, $promoPerUnit * $qty);
                $lineNet = max(0, $lineGross - $lineItemDiscount);

                foreach ($allocations['items'] as $allocatedBatch) {
                    $batch = $allocatedBatch['batch'];
                    $takeQty = (int) $allocatedBatch['quantity'];
                    $newRemaining = max(0, (int) $batch->qty_remaining - $takeQty);
                    if ($firstBatchId === null) $firstBatchId = $batch->id;

                    $batch->forceFill([
                        'qty_remaining' => $newRemaining,
                        'status' => $newRemaining <= 0 ? 'finished' : ($batch->expiry_status === 'expiring_soon' || $batch->expiry_status === 'expires_today' ? 'near_expired' : 'active'),
                    ])->save();

                    StockMovement::create([
                        'product_id' => $product->id,
                        'stock_batch_id' => $batch->id,
                        'location_id' => (int) ($batch->location_id ?: $payload['location_id']),
                        'user_id' => $user?->id,
                        'movement_type' => 'out',
                        'quantity' => $takeQty,
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
                            'promo_discount_per_unit' => $promoPerUnit,
                            'line_item_discount' => $lineItemDiscount,
                            'line_subtotal' => $lineNet,
                            'status' => $status,
                        ],
                    ]);

                    $batchAllocations[] = [
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'lot_number' => $batch->lot_number,
                        'quantity' => $takeQty,
                        'expired_at' => optional($batch->expired_at)->format('Y-m-d'),
                    ];
                }

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'stock_batch_id' => $firstBatchId,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $lineItemDiscount,
                    'subtotal' => $lineNet,
                ]);

                $product->forceFill(['last_sold_at' => $transactionAt])->save();
                $persistedItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'promo_discount_per_unit' => $promoPerUnit,
                    'line_item_discount' => $lineItemDiscount,
                    'line_subtotal' => $lineNet,
                    'batch_allocations' => $batchAllocations,
                ];
                $transactionBatchAllocations[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'batch_allocations' => $batchAllocations,
                ];
            }

            foreach ($products->keys() as $productId) {
                $this->syncProductStockSnapshot((int) $productId);
            }

            $transaction->forceFill([
                'transaction_code' => $this->generateTransactionCode($transaction->refresh()),
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'inventory_applied' => true,
                    'item_count' => count($persistedItems),
                    'items' => $persistedItems,
                    'batch_allocations' => $transactionBatchAllocations,
                    'item_discount_total' => $itemDiscountTotal,
                    'subtotal_after_item_discount' => $subtotalAfterItemDiscount,
                    'transaction_discount_amount' => $transactionDiscountAmount,
                    'customer_paid' => $paidAmount,
                    'change_amount' => $changeAmount,
                    'tax_name' => $taxSetting->name,
                    'tax_type' => $taxSetting->tax_type,
                    'tax_value' => $taxSetting->tax_value,
                    'discount_setting' => $discountSetting ? [
                        'id' => $discountSetting->id,
                        'name' => $discountSetting->name,
                        'code' => $discountSetting->code,
                        'discount_type' => $discountSetting->discount_type,
                        'discount_value' => $discountSetting->discount_value,
                        'minimum_total_amount' => $discountSetting->minimum_total_amount,
                    ] : null,
                    'source' => 'pos',
                ]),
            ])->saveQuietly();

            return $transaction->refresh()->load(['location', 'cashier', 'taxSetting', 'discountSetting', 'items.product', 'items.stockBatch', 'stockMovements.stockBatch']);
        });
    }

    public function payload(Transaction $transaction): array
    {
        $transaction->loadMissing(['location', 'cashier', 'taxSetting', 'discountSetting', 'items.product', 'items.stockBatch', 'stockMovements.stockBatch']);

        return [
            'id' => $transaction->id,
            'transaction_code' => $transaction->transaction_code,
            'location' => optional($transaction->location)->name,
            'cashier' => optional($transaction->cashier)->name,
            'tax_setting' => optional($transaction->taxSetting)->name,
            'discount_setting' => optional($transaction->discountSetting)->name,
            'discount_setting_code' => optional($transaction->discountSetting)->code,
            'items' => $transaction->items->map(fn (TransactionItem $item) => [
                'product_name' => optional($item->product)->name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (int) $item->unit_price,
                'discount_amount' => (int) $item->discount_amount,
                'subtotal' => (int) $item->subtotal,
            ])->values()->all(),
            'subtotal' => (int) $transaction->subtotal,
            'item_discount_total' => (int) $transaction->item_discount_total,
            'transaction_discount_amount' => (int) $transaction->discount_amount,
            'discount_amount' => (int) $transaction->discount_amount,
            'tax_amount' => (int) $transaction->tax_amount,
            'total_amount' => (int) $transaction->total_amount,
            'paid_amount' => (int) $transaction->paid_amount,
            'change_amount' => (int) $transaction->change_amount,
            'customer_paid' => (int) $transaction->paid_amount,
            'status' => $transaction->status,
            'status_label' => $transaction->status_label,
            'status_class' => $transaction->status_class,
            'shift_label' => $transaction->shift_label,
            'payment_method_label' => $transaction->payment_method_label,
            'transaction_at' => optional($transaction->transaction_at)->format('d M Y H:i'),
        ];
    }


    protected function normalizeBarcodeLookup(string $barcode): string
{
    $barcode = preg_replace('/\D/', '', trim($barcode)) ?? '';

    return $barcode === '' ? '' : $this->normalizeBarcode($barcode);
}

    protected function normalizePayload(array $data): array
    {
        return [
            'location_id' => (int) ($data['location_id'] ?? 0),
            'tax_setting_id' => (int) ($data['tax_setting_id'] ?? 0),
            'cashier_id' => isset($data['cashier_id']) ? (int) $data['cashier_id'] : null,
            'items' => $data['items'] ?? [],
            'customer_name' => trim((string) ($data['customer_name'] ?? '')),
            'customer_phone' => trim((string) ($data['customer_phone'] ?? '')),
            'shift' => trim((string) ($data['shift'] ?? 'morning')),
            'payment_method' => trim((string) ($data['payment_method'] ?? 'cash')),
            'paid_amount' => max(0, (int) ($data['paid_amount'] ?? 0)),
            'transaction_at' => trim((string) ($data['transaction_at'] ?? now()->format('Y-m-d H:i:s'))),
            'notes' => trim((string) ($data['notes'] ?? '')),
        ];
    }

    protected function normalizeItems(array $items): array
    {
        $grouped = [];
        foreach ($items as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $quantity = max(1, (int) ($row['quantity'] ?? 0));
            if (! $productId) continue;
            $grouped[$productId] = ($grouped[$productId] ?? 0) + $quantity;
        }

        return collect($grouped)->map(fn (int $quantity, int $productId) => ['product_id' => $productId, 'quantity' => $quantity])->values()->all();
    }

    protected function calculateTaxAmount(TaxSetting $taxSetting, int $baseAmount): int
    {
        return $taxSetting->tax_type === 'percent'
            ? (int) round(($baseAmount * (int) $taxSetting->tax_value) / 100)
            : max(0, (int) $taxSetting->tax_value);
    }

    protected function calculateDiscountAmount(?DiscountSetting $setting, int $baseAmount): int
    {
        if (! $setting || $baseAmount <= 0) return 0;
        if ($setting->discount_type === 'percent') {
            return max(0, (int) round(($baseAmount * max(0, (int) $setting->discount_value)) / 100));
        }
        return min($baseAmount, max(0, (int) $setting->discount_value));
    }

    protected function resolveApplicableDiscountSetting(int $eligibleAmount, Carbon $transactionAt): ?DiscountSetting
    {
        return DiscountSetting::query()
            ->where('is_active', true)
            ->where(function ($query) use ($transactionAt) { $query->whereNull('starts_at')->orWhere('starts_at', '<=', $transactionAt); })
            ->where(function ($query) use ($transactionAt) { $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $transactionAt->toDateString()); })
            ->where('minimum_total_amount', '<=', $eligibleAmount)
            ->orderByDesc('is_default')
            ->orderByDesc('priority')
            ->orderByDesc('minimum_total_amount')
            ->orderByDesc('discount_value')
            ->orderByDesc('id')
            ->first();
    }

    protected function saleableStockForProductLocation(int $productId, int $locationId): int
    {
        $localStock = (int) $this->saleableBatchesForProductLocation($productId, $locationId)->sum('qty_remaining');

        if ($localStock > 0) {
            return $localStock;
        }

        return (int) $this->saleableBatchesForProduct($productId)->sum('qty_remaining');
    }

    protected function allocateBatches(Product $product, int $locationId, int $quantity): array
    {
        $batches = $this->saleableBatchesForProductLocation($product->id, $locationId);
        if ($batches->sum('qty_remaining') < $quantity) {
            $globalBatches = $this->saleableBatchesForProduct($product->id);
            if ($globalBatches->sum('qty_remaining') >= $quantity) {
                $batches = $globalBatches;
            }
        }

        $sorted = $batches->sort(function (StockBatches $left, StockBatches $right) {
            return $this->batchSortKey($left) <=> $this->batchSortKey($right);
        })->values();

        $remaining = $quantity; $items = [];
        foreach ($sorted as $batch) {
            if ($remaining <= 0) break;
            $available = max(0, (int) $batch->qty_remaining);
            if ($available <= 0) continue;
            $take = min($available, $remaining);
            if ($take > 0) { $items[] = ['batch' => $batch, 'quantity' => $take]; $remaining -= $take; }
        }

        return ['items' => $items, 'remaining' => $remaining];
    }

    protected function saleableBatchesForProductLocation(int $productId, int $locationId): Collection
    {
        return StockBatches::query()->where('product_id', $productId)->where('location_id', $locationId)->where('qty_remaining', '>', 0)->get()->filter(fn (StockBatches $batch) => $this->isSaleableBatch($batch))->values();
    }

    protected function saleableBatchesForProduct(int $productId): Collection
    {
        return StockBatches::query()->where('product_id', $productId)->where('qty_remaining', '>', 0)->get()->filter(fn (StockBatches $batch) => $this->isSaleableBatch($batch))->values();
    }

    protected function isSaleableBatch(StockBatches $batch): bool
    {
        return (int) $batch->qty_remaining > 0 && ! in_array($batch->status, ['expired', 'finished'], true) && ! in_array($batch->expiry_status, ['expired', 'grace_period', 'depleted'], true);
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
        $stock = (int) StockBatches::query()->where('product_id', $productId)->where('qty_remaining', '>', 0)->sum('qty_remaining');
        Product::query()->whereKey($productId)->update(['stock_on_hand' => $stock]);
    }

    protected function generateTransactionCode(Transaction $transaction): string
    {
        $cashierId = (int) ($transaction->cashier_id ?: 0);
        $shift = strtoupper((string) ($transaction->shift ?: 'NA'));
        $dateCode = strtoupper($transaction->transaction_at?->format('dMy') ?? now()->format('dMy'));
        return sprintf('TXN-%s-%s-%s-%s-%s', $transaction->id, $cashierId, $shift, $dateCode, $this->statusCode($transaction->status));
    }

    protected function statusCode(?string $status): string
    {
        return match ($status) {
            'paid' => 'PAI',
            'draft' => 'DRA',
            'cancelled' => 'CXL',
            'refunded' => 'RFD',
            default => 'UNK',
        };
    }

    protected function temporaryCode(): string
    {
        return 'TMP-' . now()->format('YmdHisv') . '-' . Str::upper(Str::random(6));
    }
}

<?php

namespace App\Http\Services;

use App\Models\Location;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LogTcService
{
    public function indexData(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        $query = $this->buildFilteredQuery($filters);

        $transactions = (clone $query)
            ->paginate(15)
            ->withQueryString();

        $transactions->getCollection()->transform(function (Transaction $transaction) {
            $transaction->setAttribute('log_finance', $this->calculateTransactionFinance($transaction));

            return $transaction;
        });

        $allTransactions = (clone $query)->get();

        return [
            'filters' => $filters,
            'transactions' => $transactions,
            'summary' => $this->summarizeTransactions($allTransactions),
            'periodOptions' => $this->periodOptions(),
            'statusOptions' => $this->statusOptions(),
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(),
            'cashiers' => User::query()->orderBy('name')->get(),
            'activePeriodLabel' => $this->activePeriodLabel($filters),
            'activeFilterLabel' => $this->activeFilterLabel($filters),
        ];
    }

    public function showData(Transaction $transaction): array
    {
        $transaction->loadMissing([
            'location',
            'cashier',
            'taxSetting',
            'discountSetting',
            'items.product',
            'items.stockBatch',
            'stockMovements.product',
            'stockMovements.stockBatch',
            'stockMovements.location',
            'stockMovements.user',
        ]);

        return [
            'transaction' => $transaction,
            'finance' => $this->calculateTransactionFinance($transaction),
            'backUrl' => route('log-tc.index'),
        ];
    }

    protected function buildFilteredQuery(array $filters): Builder
    {
        $query = Transaction::query()
            ->with([
                'location',
                'cashier',
                'taxSetting',
                'discountSetting',
                'items.product',
                'items.stockBatch',
                'stockMovements.product',
                'stockMovements.stockBatch',
            ])
            ->withCount('items')
            ->orderByDesc('transaction_at')
            ->orderByDesc('id');

        $range = $this->resolveDateRange($filters);

        if ($range['start'] !== null) {
            $query->where('transaction_at', '>=', $range['start']);
        }

        if ($range['end'] !== null) {
            $query->where('transaction_at', '<=', $range['end']);
        }

        if (($search = $filters['q'] ?? '') !== '') {
            $term = '%' . $search . '%';

            $query->where(function (Builder $builder) use ($term) {
                $builder->where('transaction_code', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('customer_phone', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhereHas('location', function (Builder $locationQuery) use ($term) {
                        $locationQuery->where('name', 'like', $term)
                            ->orWhere('code', 'like', $term);
                    })
                    ->orWhereHas('cashier', function (Builder $cashierQuery) use ($term) {
                        $cashierQuery->where('name', 'like', $term)
                            ->orWhere('username', 'like', $term);
                    })
                    ->orWhereHas('items.product', function (Builder $productQuery) use ($term) {
                        $productQuery->where('name', 'like', $term)
                            ->orWhere('sku', 'like', $term)
                            ->orWhere('barcode', 'like', $term);
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', (int) $filters['location_id']);
        }

        if (! empty($filters['cashier_id'])) {
            $query->where('cashier_id', (int) $filters['cashier_id']);
        }

        return $query;
    }

    protected function summarizeTransactions(Collection $transactions): array
    {
        $summary = [
            'transaction_count' => $transactions->count(),
            'gross_subtotal' => 0,
            'item_discount_total' => 0,
            'transaction_discount_total' => 0,
            'net_revenue_total' => 0,
            'tax_total' => 0,
            'total_billed' => 0,
            'cogs_total' => 0,
            'gross_profit_total' => 0,
            'loss_total' => 0,
            'margin_percent' => 0,
        ];

        foreach ($transactions as $transaction) {
            $finance = $this->calculateTransactionFinance($transaction);

            $summary['gross_subtotal'] += $finance['gross_subtotal'];
            $summary['item_discount_total'] += $finance['item_discount_total'];
            $summary['transaction_discount_total'] += $finance['transaction_discount_total'];
            $summary['net_revenue_total'] += $finance['net_revenue_total'];
            $summary['tax_total'] += $finance['tax_total'];
            $summary['total_billed'] += $finance['total_billed'];
            $summary['cogs_total'] += $finance['cogs_total'];
        }

        $summary['gross_profit_total'] = $summary['net_revenue_total'] - $summary['cogs_total'];
        $summary['loss_total'] = max(0, 0 - $summary['gross_profit_total']);
        $summary['margin_percent'] = $summary['net_revenue_total'] > 0
            ? round(($summary['gross_profit_total'] / $summary['net_revenue_total']) * 100, 2)
            : 0;

        return $summary;
    }

    protected function calculateTransactionFinance(Transaction $transaction): array
    {
        $transaction->loadMissing([
            'items.product',
            'items.stockBatch',
            'stockMovements.product',
            'stockMovements.stockBatch',
        ]);

        $grossSubtotal = (int) round((float) ($transaction->subtotal ?? 0));
        $itemDiscountTotal = (int) data_get($transaction->metadata, 'item_discount_total', 0);
        $transactionDiscountTotal = (int) round((float) ($transaction->discount_amount ?? 0));
        $subtotalAfterItemDiscount = max(0, $grossSubtotal - $itemDiscountTotal);
        $netRevenueTotal = max(0, $subtotalAfterItemDiscount - $transactionDiscountTotal);
        $taxTotal = (int) round((float) ($transaction->tax_amount ?? 0));
        $totalBilled = (int) round((float) ($transaction->total_amount ?? 0));

        $stockMovements = $transaction->stockMovements;
        $cogsTotal = (int) $stockMovements->sum(fn ($movement) => (int) round(((float) $movement->unit_cost) * ((float) $movement->quantity)));

        $productMovements = $stockMovements->groupBy('product_id');
        $lineItems = [];
        $remainingTxDiscount = $transactionDiscountTotal;
        $totalItems = $transaction->items->count();

        foreach ($transaction->items->values() as $index => $item) {
            $quantity = (int) round((float) $item->quantity);
            $unitPrice = (int) round((float) $item->unit_price);
            $grossLine = $unitPrice * $quantity;
            $itemDiscount = (int) round((float) $item->discount_amount);
            $netBeforeTxDiscount = max(0, (int) round((float) $item->subtotal));

            $txDiscountShare = 0;
            if ($transactionDiscountTotal > 0 && $subtotalAfterItemDiscount > 0) {
                $txDiscountShare = (int) round(($netBeforeTxDiscount / $subtotalAfterItemDiscount) * $transactionDiscountTotal);
            }

            if ($index === $totalItems - 1) {
                $txDiscountShare = max(0, $remainingTxDiscount);
            } else {
                $remainingTxDiscount = max(0, $remainingTxDiscount - $txDiscountShare);
            }

            $productMovementRows = $productMovements->get($item->product_id, collect());
            $lineCogs = (int) $productMovementRows->sum(fn ($movement) => (int) round(((float) $movement->unit_cost) * ((float) $movement->quantity)));
            $netRevenueLine = max(0, $netBeforeTxDiscount - $txDiscountShare);
            $profitLine = $netRevenueLine - $lineCogs;

            $batchLabels = $productMovementRows
                ->map(function ($movement) {
                    $batchCode = optional($movement->stockBatch)->batch_code ?: '-';
                    $lotNumber = optional($movement->stockBatch)->lot_number ?: '-';
                    return trim($batchCode . ' / ' . $lotNumber);
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            $lineItems[] = [
                'product_id' => $item->product_id,
                'product_name' => optional($item->product)->name ?? '-',
                'batch_count' => $productMovementRows->pluck('stock_batch_id')->filter()->unique()->count(),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'gross_line' => $grossLine,
                'item_discount' => $itemDiscount,
                'transaction_discount_share' => $txDiscountShare,
                'net_before_tx_discount' => $netBeforeTxDiscount,
                'net_revenue_line' => $netRevenueLine,
                'cogs_line' => $lineCogs,
                'profit_line' => $profitLine,
                'loss_line' => max(0, 0 - $profitLine),
                'margin_percent' => $netRevenueLine > 0 ? round(($profitLine / $netRevenueLine) * 100, 2) : 0,
                'batch_labels' => $batchLabels,
            ];
        }

        $grossProfitTotal = $netRevenueTotal - $cogsTotal;
        $lossTotal = max(0, 0 - $grossProfitTotal);
        $marginPercent = $netRevenueTotal > 0 ? round(($grossProfitTotal / $netRevenueTotal) * 100, 2) : 0;

        $batchRows = $stockMovements
            ->map(function ($movement) {
                $totalCost = (int) round(((float) $movement->unit_cost) * ((float) $movement->quantity));

                return [
                    'created_at' => $movement->created_at,
                    'movement_type' => $movement->movement_type,
                    'product_name' => optional($movement->product)->name ?? '-',
                    'batch_code' => optional($movement->stockBatch)->batch_code ?? '-',
                    'lot_number' => optional($movement->stockBatch)->lot_number ?? '-',
                    'quantity' => (int) round((float) $movement->quantity),
                    'unit_cost' => (int) round((float) $movement->unit_cost),
                    'total_cost' => $totalCost,
                    'location_name' => optional($movement->location)->name ?? '-',
                    'notes' => $movement->notes ?: '-',
                ];
            })
            ->values();

        return [
            'gross_subtotal' => $grossSubtotal,
            'item_discount_total' => $itemDiscountTotal,
            'transaction_discount_total' => $transactionDiscountTotal,
            'subtotal_after_item_discount' => $subtotalAfterItemDiscount,
            'net_revenue_total' => $netRevenueTotal,
            'tax_total' => $taxTotal,
            'total_billed' => $totalBilled,
            'cogs_total' => $cogsTotal,
            'gross_profit_total' => $grossProfitTotal,
            'loss_total' => $lossTotal,
            'margin_percent' => $marginPercent,
            'line_items' => $lineItems,
            'batch_rows' => $batchRows,
        ];
    }

    protected function normalizeFilters(array $filters): array
    {
        $period = (string) Arr::get($filters, 'period', 'all');
        $dateFrom = Arr::get($filters, 'date_from');
        $dateTo = Arr::get($filters, 'date_to');

        if ($dateFrom || $dateTo) {
            $period = 'custom';
        }

        return [
            'q' => trim((string) Arr::get($filters, 'q', '')),
            'period' => in_array($period, ['all', 'day', 'week', 'month', 'year', 'custom'], true) ? $period : 'all',
            'date_from' => trim((string) $dateFrom),
            'date_to' => trim((string) $dateTo),
            'location_id' => $this->nullableInt(Arr::get($filters, 'location_id')),
            'cashier_id' => $this->nullableInt(Arr::get($filters, 'cashier_id')),
            'status' => trim((string) Arr::get($filters, 'status', '')),
        ];
    }

    protected function resolveDateRange(array $filters): array
    {
        return match ($filters['period'] ?? 'all') {
            'day' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
            ],
            'month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
            ],
            'custom' => $this->resolveCustomRange($filters),
            default => [
                'start' => null,
                'end' => null,
            ],
        };
    }

    protected function resolveCustomRange(array $filters): array
    {
        $dateFrom = $this->parseDate($filters['date_from'] ?? null);
        $dateTo = $this->parseDate($filters['date_to'] ?? null);

        if ($dateFrom === null && $dateTo === null) {
            $dateFrom = now()->startOfDay();
            $dateTo = now()->endOfDay();
        } elseif ($dateFrom !== null && $dateTo === null) {
            $dateTo = $dateFrom->copy()->endOfDay();
        } elseif ($dateFrom === null && $dateTo !== null) {
            $dateFrom = $dateTo->copy()->startOfDay();
        }

        return [
            'start' => $dateFrom?->startOfDay(),
            'end' => $dateTo?->endOfDay(),
        ];
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function nullableInt(mixed $value): ?int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return (int) $value;
    }

    public function periodOptions(): array
    {
        return [
            'all' => 'Semua data',
            'day' => 'Hari ini',
            'week' => 'Minggu ini',
            'month' => 'Bulan ini',
            'year' => 'Tahun ini',
            'custom' => 'Rentang waktu',
        ];
    }

    public function statusOptions(): array
    {
        return [
            'paid' => 'Paid',
            'draft' => 'Draft',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
        ];
    }

    protected function activePeriodLabel(array $filters): string
    {
        $period = $filters['period'] ?? 'all';

        return match ($period) {
            'day' => 'Hari ini',
            'week' => 'Minggu ini',
            'month' => 'Bulan ini',
            'year' => 'Tahun ini',
            'custom' => $this->customRangeLabel($filters),
            default => 'Semua data',
        };
    }

    protected function customRangeLabel(array $filters): string
    {
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        if ($from && $to) {
            return 'Rentang ' . Carbon::parse($from)->format('d M Y') . ' - ' . Carbon::parse($to)->format('d M Y');
        }

        if ($from) {
            return 'Mulai ' . Carbon::parse($from)->format('d M Y');
        }

        if ($to) {
            return 'Sampai ' . Carbon::parse($to)->format('d M Y');
        }

        return 'Rentang waktu';
    }

    protected function activeFilterLabel(array $filters): string
    {
        $parts = [
            'Periode: ' . $this->activePeriodLabel($filters),
        ];

        if (! empty($filters['status'])) {
            $parts[] = 'Status: ' . Str::headline($filters['status']);
        }

        if (! empty($filters['location_id'])) {
            $location = Location::query()->find($filters['location_id']);
            $parts[] = 'Lokasi: ' . ($location?->name ?? '-');
        }

        if (! empty($filters['cashier_id'])) {
            $cashier = User::query()->find($filters['cashier_id']);
            $parts[] = 'Kasir: ' . ($cashier?->name ?? '-');
        }

        if (($filters['q'] ?? '') !== '') {
            $parts[] = 'Pencarian: "' . $filters['q'] . '"';
        }

        return implode(' · ', $parts);
    }
}

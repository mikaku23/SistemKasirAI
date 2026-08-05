<?php

namespace App\Http\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    public function indexData(array $filters = []): array
    {
        $filters = $this->normalizeFilters($filters);
        $rawMode = $this->shouldUseRawMode($filters);

        $baseQuery = $this->baseQuery();
        $filteredRawQuery = $this->applyFilters(clone $baseQuery, $filters);
        $groupedQuery = $this->groupedQuery($filters);

        $stats = $this->stats($filteredRawQuery, $groupedQuery);
        $movementTypes = $this->movementTypeOptions();
        $periodOptions = $this->periodOptions();

        return [
            'filters' => $filters,
            'rawMode' => $rawMode,
            'stockMovements' => $rawMode
                ? $filteredRawQuery->paginate(20)->withQueryString()
                : $groupedQuery->paginate(20)->withQueryString(),
            'stockMovementStats' => $stats,
            'movementTypes' => $movementTypes,
            'periodOptions' => $periodOptions,
            'products' => Product::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(),
            'locations' => Location::query()->orderBy('name')->get(),
            'activeFiltersLabel' => $this->activeFiltersLabel($filters, $rawMode),
        ];
    }

    public function showData(StockMovement $stockMovement): array
    {
        $stockMovement->loadMissing(['product.category', 'stockBatch', 'location', 'user', 'reference']);

        $groupDate = optional($stockMovement->created_at)->toDateString()
            ?? optional($stockMovement->movement_at)->toDateString()
            ?? now()->toDateString();

        $groupedMovements = StockMovement::query()
            ->with(['product.category', 'stockBatch', 'location', 'user', 'reference'])
            ->where('movement_type', $stockMovement->movement_type)
            ->whereDate('created_at', $groupDate)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $groupSummary = [
            'date' => Carbon::parse($groupDate)->format('d M Y'),
            'movement_type' => $stockMovement->movement_type,
            'movement_type_label' => $this->movementTypeLabel($stockMovement->movement_type),
            'movement_type_class' => $this->movementTypeClass($stockMovement->movement_type),
            'entries_count' => $groupedMovements->count(),
            'total_quantity' => (float) $groupedMovements->sum('quantity'),
            'total_value' => (int) $groupedMovements->sum(fn (StockMovement $movement) => ((float) $movement->unit_cost) * ((float) $movement->quantity)),
            'latest_at' => optional($groupedMovements->first()?->created_at)->format('d M Y H:i'),
            'distinct_products' => $groupedMovements->pluck('product_id')->unique()->count(),
            'distinct_users' => $groupedMovements->pluck('user_id')->filter()->unique()->count(),
        ];

        return [
            'stockMovement' => $stockMovement,
            'groupedMovements' => $groupedMovements,
            'groupSummary' => $groupSummary,
            'movementTypes' => $this->movementTypeOptions(),
            'backUrl' => route('stock-movements.index'),
        ];
    }

    protected function baseQuery(): Builder
    {
        return StockMovement::query()
            ->with(['product.category', 'stockBatch', 'location', 'user', 'reference'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $range = $this->resolveDateRange($filters);

        if ($range['start'] !== null) {
            $query->where('created_at', '>=', $range['start']);
        }

        if ($range['end'] !== null) {
            $query->where('created_at', '<=', $range['end']);
        }

        if (($search = $filters['q'] ?? '') !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $term = '%' . $search . '%';

                $builder->where('notes', 'like', $term)
                    ->orWhere('movement_type', 'like', $term)
                    ->orWhereHas('product', function (Builder $productQuery) use ($term) {
                        $productQuery->where('name', 'like', $term)
                            ->orWhere('sku', 'like', $term)
                            ->orWhere('barcode', 'like', $term);
                    })
                    ->orWhereHas('stockBatch', function (Builder $batchQuery) use ($term) {
                        $batchQuery->where('batch_code', 'like', $term)
                            ->orWhere('lot_number', 'like', $term);
                    })
                    ->orWhereHas('location', function (Builder $locationQuery) use ($term) {
                        $locationQuery->where('name', 'like', $term)
                            ->orWhere('code', 'like', $term);
                    })
                    ->orWhereHas('user', function (Builder $userQuery) use ($term) {
                        $userQuery->where('name', 'like', $term)
                            ->orWhere('username', 'like', $term);
                    });
            });
        }

        if (! empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', (int) $filters['location_id']);
        }

        return $query;
    }

    protected function groupedQuery(array $filters): Builder
    {
        $query = StockMovement::query();
        $range = $this->resolveDateRange($filters);

        if ($range['start'] !== null) {
            $query->where('created_at', '>=', $range['start']);
        }

        if ($range['end'] !== null) {
            $query->where('created_at', '<=', $range['end']);
        }

        if (! empty($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }

        if (! empty($filters['product_id'])) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['location_id'])) {
            $query->where('location_id', (int) $filters['location_id']);
        }

        return $query
            ->selectRaw('movement_type')
            ->selectRaw('DATE(created_at) as movement_date')
            ->selectRaw('COUNT(*) as entries_count')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(quantity * unit_cost) as total_value')
            ->selectRaw('MAX(created_at) as latest_created_at')
            ->selectRaw('MAX(id) as representative_id')
            ->groupBy('movement_type', DB::raw('DATE(created_at)'))
            ->orderByDesc('movement_date')
            ->orderBy('movement_type');
    }

    protected function stats(Builder $rawQuery, Builder $groupedQuery): array
    {
        $rawCountQuery = clone $rawQuery;
        $groupCountQuery = clone $groupedQuery;

        return [
            'total_logs' => $rawCountQuery->count(),
            'grouped_rows' => $groupCountQuery->get()->count(),
            'total_quantity' => (float) $rawCountQuery->sum('quantity'),
            'distinct_products' => (clone $rawQuery)->distinct()->count('product_id'),
            'in_count' => (clone $rawQuery)->whereIn('movement_type', ['in', 'transfer_in', 'return_in'])->count(),
            'out_count' => (clone $rawQuery)->whereIn('movement_type', ['out', 'transfer_out', 'return_out', 'write_off'])->count(),
        ];
    }

    protected function normalizeFilters(array $filters): array
    {
        return [
            'q' => trim((string) Arr::get($filters, 'q', '')),
            'movement_type' => trim((string) Arr::get($filters, 'movement_type', '')),
            'product_id' => $this->nullableInt(Arr::get($filters, 'product_id')),
            'user_id' => $this->nullableInt(Arr::get($filters, 'user_id')),
            'location_id' => $this->nullableInt(Arr::get($filters, 'location_id')),
            'period' => in_array((string) Arr::get($filters, 'period', 'all'), ['all', 'day', 'week', 'month', 'year', 'custom'], true)
                ? (string) Arr::get($filters, 'period', 'all')
                : 'all',
            'mode' => in_array((string) Arr::get($filters, 'mode', 'grouped'), ['grouped', 'raw'], true)
                ? (string) Arr::get($filters, 'mode', 'grouped')
                : 'grouped',
            'date_from' => Arr::get($filters, 'date_from'),
            'date_to' => Arr::get($filters, 'date_to'),
        ];
    }

    protected function shouldUseRawMode(array $filters): bool
    {
        if (($filters['mode'] ?? 'grouped') === 'raw') {
            return true;
        }

        if (($filters['q'] ?? '') !== '') {
            return true;
        }

        if (! empty($filters['movement_type'])) {
            return true;
        }

        if (! empty($filters['product_id'])) {
            return true;
        }

        if (! empty($filters['user_id'])) {
            return true;
        }

        if (! empty($filters['location_id'])) {
            return true;
        }

        return ($filters['period'] ?? 'all') !== 'all';
    }

    protected function resolveDateRange(array $filters): array
    {
        $period = $filters['period'] ?? 'all';

        return match ($period) {
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

    public function movementTypeOptions(): array
    {
        return [
            'in' => ['label' => 'In', 'class' => 'status-pill--success'],
            'out' => ['label' => 'Out', 'class' => 'status-pill--danger'],
            'adjustment' => ['label' => 'Adjustment', 'class' => 'status-pill--warning'],
            'transfer_in' => ['label' => 'Transfer In', 'class' => 'status-pill--success'],
            'transfer_out' => ['label' => 'Transfer Out', 'class' => 'status-pill--danger'],
            'return_in' => ['label' => 'Return In', 'class' => 'status-pill--success'],
            'return_out' => ['label' => 'Return Out', 'class' => 'status-pill--danger'],
            'write_off' => ['label' => 'Write Off', 'class' => 'status-pill--muted'],
        ];
    }

    public function periodOptions(): array
    {
        return [
            'all' => 'Semua data',
            'day' => 'Hari ini',
            'week' => 'Minggu ini',
            'month' => 'Bulan ini',
            'year' => 'Tahun ini',
            'custom' => 'Rentang kustom',
        ];
    }

    public function movementTypeLabel(?string $type): string
    {
        return $this->movementTypeOptions()[$type]['label'] ?? ucfirst((string) $type);
    }

    public function movementTypeClass(?string $type): string
    {
        return $this->movementTypeOptions()[$type]['class'] ?? 'status-pill--muted';
    }

    protected function activeFiltersLabel(array $filters, bool $rawMode): string
    {
        if (! $rawMode) {
            return 'Grouped view aktif. Data dikelompokkan berdasarkan movement type dan tanggal.';
        }

        $labels = [];

        if (($filters['q'] ?? '') !== '') {
            $labels[] = 'search: ' . $filters['q'];
        }

        if (! empty($filters['movement_type'])) {
            $labels[] = 'movement: ' . $this->movementTypeLabel($filters['movement_type']);
        }

        if (! empty($filters['product_id'])) {
            $labels[] = 'product #' . (int) $filters['product_id'];
        }

        if (! empty($filters['user_id'])) {
            $labels[] = 'user #' . (int) $filters['user_id'];
        }

        if (! empty($filters['location_id'])) {
            $labels[] = 'location #' . (int) $filters['location_id'];
        }

        if (($filters['period'] ?? 'all') !== 'all') {
            $labels[] = 'period: ' . strtoupper((string) $filters['period']);
        }

        if (($filters['period'] ?? 'all') === 'custom') {
            $labels[] = 'custom range';
        }

        if ($labels === []) {
            return 'Raw view aktif.';
        }

        return 'Raw view aktif karena filter detail dipakai: ' . implode(' · ', $labels) . '.';
    }
}

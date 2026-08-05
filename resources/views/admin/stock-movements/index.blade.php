@extends('template-admin.layout')

@section('title', 'Stock Movement Log')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $stockMovements = $stockMovements ?? [];
    $stockMovementStats = $stockMovementStats ?? [
        'total_logs' => 0,
        'grouped_rows' => 0,
        'total_quantity' => 0,
        'distinct_products' => 0,
        'in_count' => 0,
        'out_count' => 0,
    ];
    $movementTypes = $movementTypes ?? [];
    $periodOptions = $periodOptions ?? [];
    $products = $products ?? [];
    $users = $users ?? [];
    $locations = $locations ?? [];

    $formatQty = function ($value) {
        if ($value === null || $value === '') {
            return '-';
        }

        $float = (float) $value;
        $decimals = abs($float - round($float)) < 0.00001 ? 0 : 2;

        return number_format($float, $decimals, ',', '.');
    };

    $money = function ($value) {
        return 'Rp ' . number_format((int) $value, 0, ',', '.');
    };
@endphp

<section class="page-card glass-card stock-movement-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">STOCK MOVEMENTS</p>
            <h2>Log Mutasi Stok</h2>
            <p>{{ $activeFiltersLabel ?? 'Data log mutasi stok.' }}</p>
        </div>

        <div class="page-card__actions">
            <span class="mono-chip">
                {{ $rawMode ? 'Raw view' : 'Grouped view' }}
            </span>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card glass-card">
            <span>Total Logs</span>
            <strong>{{ $stockMovementStats['total_logs'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Grouped Rows</span>
            <strong>{{ $stockMovementStats['grouped_rows'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Total Quantity</span>
            <strong>{{ $formatQty($stockMovementStats['total_quantity']) }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Distinct Products</span>
            <strong>{{ $stockMovementStats['distinct_products'] }}</strong>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">FILTER</p>
                <h3>Search & filter log</h3>
            </div>

            <a href="{{ route('stock-movements.index') }}" class="btn btn--secondary">
                Reset
            </a>
        </div>

        <form method="GET" action="{{ route('stock-movements.index') }}" class="wizard-form page-form" style="margin-top: 0;">
            <div class="wizard-form-grid">
                <label class="form-field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari product, user, batch, notes..." autocomplete="off">
                </label>

                <label class="form-field">
                    <span>Movement Type</span>
                    <select name="movement_type">
                        <option value="">Semua type</option>
                        @foreach ($movementTypes as $key => $type)
                            <option value="{{ $key }}" {{ request('movement_type') === (string) $key ? 'selected' : '' }}>
                                {{ $type['label'] }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>Product</span>
                    <select name="product_id">
                        <option value="">Semua product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" {{ (string) request('product_id') === (string) $product->id ? 'selected' : '' }}>
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>User</span>
                    <select name="user_id">
                        <option value="">Semua user</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ (string) request('user_id') === (string) $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>Location</span>
                    <select name="location_id">
                        <option value="">Semua location</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" {{ (string) request('location_id') === (string) $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>Period</span>
                    <select name="period">
                        @foreach ($periodOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('period', 'all') === (string) $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>Date From</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}">
                </label>

                <label class="form-field">
                    <span>Date To</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}">
                </label>
            </div>

            <div class="wizard-actions" style="margin-top: 16px;">
                <div class="wizard-actions__right">
                    <button class="btn btn--primary" type="submit">
                        Apply Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">LOG DATA</p>
                <h3>{{ $rawMode ? 'Data detail per baris' : 'Data dikelompokkan per type dan tanggal' }}</h3>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="stockMovementsTable" data-page-size="10">
                <thead>
                    @if ($rawMode)
                        <tr>
                            <th>#</th>
                            <th>Created At</th>
                            <th>Movement</th>
                            <th>Product</th>
                            <th>Batch / Lot</th>
                            <th>User</th>
                            <th>Location</th>
                            <th>Qty</th>
                            <th>Unit Cost</th>
                            <th>Reference</th>
                            <th>Notes</th>
                            <th class="th-actions">Actions</th>
                        </tr>
                    @else
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Movement</th>
                            <th>Entries</th>
                            <th>Total Qty</th>
                            <th>Total Value</th>
                            <th>Latest</th>
                            <th class="th-actions">Actions</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @if ($rawMode)
                        @forelse ($stockMovements as $movement)
                            <tr
                                data-status="{{ $movement->movement_type }}"
                                data-search-text="{{ strtolower(trim(
                                    ($movement->product->name ?? '') . ' ' .
                                    ($movement->stockBatch->batch_code ?? '') . ' ' .
                                    ($movement->stockBatch->lot_number ?? '') . ' ' .
                                    ($movement->user->name ?? '') . ' ' .
                                    ($movement->location->name ?? '') . ' ' .
                                    ($movement->notes ?? '') . ' ' .
                                    ($movement->movement_type ?? '')
                                )) }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $movement->created_at ? $movement->created_at->format('d M Y H:i') : '-' }}</td>
                                <td>
                                    <span class="status-pill {{ $movementTypes[$movement->movement_type]['class'] ?? 'status-pill--muted' }}">
                                        {{ $movementTypes[$movement->movement_type]['label'] ?? $movement->movement_type }}
                                    </span>
                                </td>
                                <td class="td-strong">
                                    {{ $movement->product->name ?? '-' }}
                                </td>
                                <td>
                                    <div style="display:grid; gap:4px;">
                                        <span class="mono-chip">{{ $movement->stockBatch->batch_code ?? '-' }}</span>
                                        <small class="text-muted">{{ $movement->stockBatch->lot_number ?? '-' }}</small>
                                    </div>
                                </td>
                                <td>{{ $movement->user->name ?? '-' }}</td>
                                <td>{{ $movement->location->name ?? '-' }}</td>
                                <td>{{ $formatQty($movement->quantity) }}</td>
                                <td>{{ $money($movement->unit_cost) }}</td>
                                <td>
                                    @php
                                        $referenceLabel = $movement->reference_type
                                            ? class_basename($movement->reference_type) . ' #' . ($movement->reference_id ?? '-')
                                            : '-';
                                    @endphp
                                    <span class="mono-chip">{{ $referenceLabel }}</span>
                                </td>
                                <td class="td-description">{{ $movement->notes ?: '-' }}</td>
                                <td class="td-actions">
                                    <div class="inline-actions">
                                        <a href="{{ route('stock-movements.show', $movement->id) }}" class="icon-btn" aria-label="Show movement">
                                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr data-empty-row>
                                <td colspan="12">
                                    <div class="empty-state">
                                        <div class="empty-state__icon">
                                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                        </div>
                                        <strong>Belum ada data mutasi stok.</strong>
                                        <p>Gunakan filter yang lebih spesifik atau buat transaksi/barang masuk terlebih dahulu.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    @else
                        @forelse ($stockMovements as $group)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($group->movement_date)->format('d M Y') }}</td>
                                <td>
                                    <span class="status-pill {{ $movementTypes[$group->movement_type]['class'] ?? 'status-pill--muted' }}">
                                        {{ $movementTypes[$group->movement_type]['label'] ?? $group->movement_type }}
                                    </span>
                                </td>
                                <td>{{ (int) $group->entries_count }}</td>
                                <td>{{ $formatQty($group->total_quantity) }}</td>
                                <td>{{ $money($group->total_value) }}</td>
                                <td>{{ $group->latest_created_at ? \Illuminate\Support\Carbon::parse($group->latest_created_at)->format('d M Y H:i') : '-' }}</td>
                                <td class="td-actions">
                                    <div class="inline-actions">
                                        <a href="{{ route('stock-movements.show', $group->representative_id) }}" class="icon-btn" aria-label="Show grouped movement">
                                            <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr data-empty-row>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-state__icon">
                                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                        </div>
                                        <strong>Belum ada data mutasi stok.</strong>
                                        <p>Tekan tombol Apply Filter atau tunggu transaksi baru tercatat.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    @endif
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#stockMovementsTable">
            <button type="button" class="btn btn--secondary table-pagination__btn" data-page-action="prev">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                Back
            </button>

            <div class="table-pagination__info" data-page-info>
                Showing 0-0 of 0
            </div>

            <button type="button" class="btn btn--secondary table-pagination__btn" data-page-action="next">
                Next
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

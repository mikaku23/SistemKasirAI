@extends('template-admin.layout')

@section('title', 'Barang Masuk / Stock Batches')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/finance-summary.css') }}">
@endsection

@section('content')
@php
    $stockBatches = $stockBatches ?? [];
    $stockBatchStats = $stockBatchStats ?? [
        'total' => 0,
        'active' => 0,
        'expiringSoon' => 0,
        'expired' => 0,
        'depleted' => 0,
        'qty_received' => 0,
        'qty_remaining' => 0,
        'trashed' => 0,
    ];

    $batchCollection = collect($stockBatches);

    $stockFinanceStats = [
        'purchase_total' => (int) $batchCollection->sum(fn ($batch) => (int) data_get($batch->metadata, 'financial_snapshot.purchase_total', (int) round(((float) ($batch->purchase_price ?? 0)) * (int) ($batch->qty_received ?? 0)))),
        'expected_revenue_total' => (int) $batchCollection->sum(fn ($batch) => (int) data_get($batch->metadata, 'financial_snapshot.expected_revenue_total', (int) round(((float) optional($batch->product)->sale_price) * (int) ($batch->qty_received ?? 0)))),
        'expected_profit_total' => (int) $batchCollection->sum(fn ($batch) => (int) data_get($batch->metadata, 'financial_snapshot.expected_profit_total', 0)),
        'sold_revenue_total' => (int) $batchCollection->sum(fn ($batch) => (int) data_get($batch->metadata, 'financial_snapshot.sold_revenue_total', 0)),
        'sold_cogs_total' => (int) $batchCollection->sum(fn ($batch) => (int) data_get($batch->metadata, 'financial_snapshot.sold_cogs_total', 0)),
        'realized_profit_total' => (int) $batchCollection->sum(fn ($batch) => (int) data_get($batch->metadata, 'financial_snapshot.realized_profit_total', 0)),
    ];

    $formatQty = function ($value) {
        if ($value === null || $value === '') {
            return '-';
        }

        $float = (float) $value;
        $decimals = abs($float - round($float)) < 0.00001 ? 0 : 2;

        return number_format($float, $decimals, ',', '.');
    };
@endphp

<section class="page-card glass-card product-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">STOCK BATCHES</p>
            <h2>Barang Masuk</h2>
            <p>Mencatat penerimaan stok per batch/lot, lengkap dengan expiry, sisa stok, modal, dan laba.</p>
        </div>

        <div class="page-card__actions">
            <label class="search-box" for="stockBatchSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="stockBatchSearch"
                    placeholder="Search batch..."
                    data-table-search-target="#stockBatchesTable">
            </label>

            <label class="filter-box" for="stockBatchStatusFilter">
                <span>Status</span>
                <select id="stockBatchStatusFilter" data-table-filter-target="#stockBatchesTable">
                    <option value="">Semua status</option>
                    <option value="active">Active</option>
                    <option value="expiring_soon">Expiring Soon</option>
                    <option value="expires_today">Expires Today</option>
                    <option value="grace_period">Grace Period</option>
                    <option value="expired">Expired</option>
                    <option value="depleted">Depleted</option>
                    <option value="no_tracking">No Tracking</option>
                </select>
            </label>

            <a href="{{ route('stock-batches.recycle') }}" class="btn btn--ghost">
                <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                Recycle
            </a>

            <a href="{{ route('stock-batches.create') }}" class="btn btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Tambah Batch
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card glass-card">
            <span>Total Batch</span>
            <strong>{{ $stockBatchStats['total'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Active</span>
            <strong>{{ $stockBatchStats['active'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Expiring Soon</span>
            <strong>{{ $stockBatchStats['expiringSoon'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Expired</span>
            <strong>{{ $stockBatchStats['expired'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Depleted</span>
            <strong>{{ $stockBatchStats['depleted'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Qty Remaining</span>
            <strong>{{ $stockBatchStats['qty_remaining'] }}</strong>
        </div>
    </div>

<div class="table-card glass-card finance-summary-card" style="margin-top: 1rem;">
    <div class="table-card__head finance-summary-card__head">
        <div>
            <p class="eyebrow">BATCH STOK & MODAL</p>
            <h3>Ringkasan finansial batch</h3>
            <p>Akumulasi modal, estimasi omzet, laba, dan rugi dari semua batch yang tampil di halaman ini.</p>
        </div>
    </div>

    <div class="finance-summary-table-wrap">
        <table class="finance-summary-table">
            <thead>
                <tr>
                    <th>Indikator</th>
                    <th class="finance-summary-table__value">Nilai</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="finance-summary-table__label">Total Modal Batch</td>
                    <td class="finance-summary-table__value is-neutral">Rp {{ number_format($stockFinanceStats['purchase_total'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="finance-summary-table__label">Estimasi Omzet</td>
                    <td class="finance-summary-table__value is-neutral">Rp {{ number_format($stockFinanceStats['expected_revenue_total'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="finance-summary-table__label">Estimasi Laba</td>
                    <td class="finance-summary-table__value is-positive">Rp {{ number_format($stockFinanceStats['expected_profit_total'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="finance-summary-table__label">Sold Revenue</td>
                    <td class="finance-summary-table__value is-neutral">Rp {{ number_format($stockFinanceStats['sold_revenue_total'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="finance-summary-table__label">Sold COGS</td>
                    <td class="finance-summary-table__value is-negative">Rp {{ number_format($stockFinanceStats['sold_cogs_total'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="finance-summary-table__label">Realized Profit</td>
                    <td class="finance-summary-table__value is-positive">Rp {{ number_format($stockFinanceStats['realized_profit_total'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>


    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">STOCK BATCHES</p>
                <h3>Tabel barang masuk</h3>
            </div>

            <button
                class="btn btn--secondary"
                type="button"
                data-action="export-table"
                data-target="#stockBatchesTable">
                Export
            </button>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="stockBatchesTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Batch Code</th>
                        <th>Lot</th>
                        <th>Qty Received</th>
                        <th>Qty Remaining</th>
                        <th>Expiry</th>
                        <th>Status</th>
                        <th>Ditambahkan Pada</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stockBatches as $stockBatch)
                        <tr
                            data-status="{{ $stockBatch->status }}"
                            data-search-text="{{ strtolower(trim(
                                ($stockBatch->batch_code ?? '') . ' ' .
                                ($stockBatch->lot_number ?? '') . ' ' .
                                (optional($stockBatch->product)->name ?? '') . ' ' .
                                (optional(optional($stockBatch->product)->category)->name ?? '') . ' ' .
                                (optional($stockBatch->supplier)->name ?? '') . ' ' .
                                (optional($stockBatch->location)->name ?? '') . ' ' .
                                (optional($stockBatch->receiver)->name ?? '') . ' ' .
                                ($stockBatch->status_label ?? '')
                            )) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">
                                <div style="display: grid; gap: 4px;">
                                    <strong>{{ optional($stockBatch->product)->name ?? '-' }}</strong>
                                    <small class="text-muted">
                                        {{ optional(optional($stockBatch->product)->category)->name ?? '-' }}
                                        ·
                                        {{ optional(optional($stockBatch->product)->unit)->name ?? '-' }}
                                    </small>
                                </div>
                            </td>
                            <td><span class="mono-chip">{{ $stockBatch->batch_code }}</span></td>
                            <td>{{ $stockBatch->lot_number ?: '-' }}</td>
                            <td>{{ $formatQty($stockBatch->qty_received) }}</td>
                            <td>{{ $formatQty($stockBatch->qty_remaining) }}</td>
                            <td>
                                <div style="display: grid; gap: 4px;">
                                    <span class="status-pill {{ $stockBatch->expiry_status_class }}">
                                        {{ $stockBatch->expiry_status_label }}
                                    </span>
                                    <small class="text-muted">{{ $stockBatch->expiry_summary }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="status-pill {{ $stockBatch->expiry_status_class }}">
                                    {{ $stockBatch->status_label }}
                                </span>
                            </td>
                            <td>{{ $stockBatch->received_at ? $stockBatch->received_at->format('d M Y') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a
                                        href="{{ route('stock-batches.show', $stockBatch->id) }}"
                                        class="icon-btn"
                                        aria-label="Show batch">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <a
                                        href="{{ route('stock-batches.edit', $stockBatch->id) }}"
                                        class="icon-btn"
                                        aria-label="Edit batch">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>

                                    <form
                                        action="{{ route('stock-batches.destroy', $stockBatch->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus batch?"
                                        data-confirm-message="Batch aktif tidak boleh dihapus. Jika qty remaining masih ada, sistem akan menolak penghapusan."
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-trash">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Delete batch">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="10">
                                <div class="empty-state">
                                    <div class="empty-state__icon">
                                        <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i>
                                    </div>
                                    <strong>Belum ada data batch.</strong>
                                    <p>Tekan tombol <b>Tambah Batch</b> untuk mencatat barang masuk pertama.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#stockBatchesTable">
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

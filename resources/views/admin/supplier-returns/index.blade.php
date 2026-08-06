@extends('template-admin.layout')

@section('title', 'Supplier Returns')

@section('css')

<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">

<style>
    .supplier-return-page .mono-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .supplier-return-page .table-meta strong {
        display: block;
        font-size: 14px;
        font-weight: 600;
    }

    .supplier-return-page .table-meta small {
        display: block;
        margin-top: 2px;
        color: rgba(229, 231, 235, 0.72);
        font-size: 12px;
    }

    .supplier-return-page .table-actions .inline-actions {
        display: inline-flex;
        gap: 8px;
        align-items: center;
    }

    .supplier-return-page .empty-state {
        display: grid;
        place-items: center;
        gap: 8px;
        padding: 32px 16px;
        text-align: center;
    }

    .supplier-return-page .empty-state__icon {
        width: 54px;
        height: 54px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.06);
        color: rgba(229, 231, 235, 0.9);
    }

    .supplier-return-page .td-strong {
        font-weight: 600;
    }

    .supplier-return-page .table-responsive {
        overflow-x: auto;
    }
</style>

@endsection

@section('content')
@php
$returns = $returns ?? [];
$returnStats = $returnStats ?? [
'total' => 0,
'completed' => 0,
'draft' => 0,
'approved' => 0,
'rejected' => 0,
'qty_returned' => 0,
'total_amount' => 0,
'trashed' => 0,
];
@endphp

<section class="page-card glass-card supplier-return-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">SUPPLIER RETURNS</p>
            <h2>Return Produk ke Supplier</h2>
            <p>Menampilkan retur supplier lengkap dengan histori stok, lokasi, total qty, total nilai, dan audit movement.</p>
        </div>


    <div class="page-card__actions">
        <label class="search-box" for="supplierReturnSearch">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input
                type="search"
                id="supplierReturnSearch"
                placeholder="Search return..."
                data-table-search-target="#supplierReturnsTable">
        </label>

        <label class="filter-box" for="supplierReturnStatusFilter">
            <span>Status</span>
            <select id="supplierReturnStatusFilter" data-table-filter-target="#supplierReturnsTable">
                <option value="">Semua status</option>
                <option value="completed">Completed</option>
                <option value="draft">Draft</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </label>

        <a href="{{ route('supplier-returns.recycle') }}" class="btn btn--ghost">
            <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
            Recycle
        </a>

        <a href="{{ route('supplier-returns.create') }}" class="btn btn--primary">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            Return Baru
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card glass-card">
        <span>Total</span>
        <strong>{{ $returnStats['total'] }}</strong>
    </div>
    <div class="stat-card glass-card">
        <span>Completed</span>
        <strong>{{ $returnStats['completed'] }}</strong>
    </div>
    <div class="stat-card glass-card">
        <span>Qty Returned</span>
        <strong>{{ $returnStats['qty_returned'] }}</strong>
    </div>
    <div class="stat-card glass-card">
        <span>Total Amount</span>
        <strong>Rp {{ number_format((int) $returnStats['total_amount'], 0, ',', '.') }}</strong>
    </div>
    <div class="stat-card glass-card">
        <span>Recycle</span>
        <strong>{{ $returnStats['trashed'] }}</strong>
    </div>
</div>

<div class="table-card glass-card">
    <div class="table-card__head">
        <div>
            <p class="eyebrow">RETURN LIST</p>
            <h3>Tabel data supplier return</h3>
        </div>

        <button
            class="btn btn--secondary"
            type="button"
            data-action="export-table"
            data-target="#supplierReturnsTable"
            data-export-name="supplier-returns">
            Export
        </button>
    </div>

    <div class="table-responsive">
        <table class="data-table data-table--compact" id="supplierReturnsTable" data-page-size="10">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Return Code</th>
                    <th>Supplier</th>
                    <th>Location</th>
                    <th>Qty</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Return At</th>
                    <th class="th-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($returns as $returnRecord)
                    @php
                        $searchText = strtolower(trim(
                            ($returnRecord->return_code ?? '') . ' ' .
                            (optional($returnRecord->supplier)->name ?? '') . ' ' .
                            (optional($returnRecord->location)->name ?? '') . ' ' .
                            ($returnRecord->reason ?? '') . ' ' .
                            ($returnRecord->status ?? '') . ' ' .
                            (optional($returnRecord->user)->name ?? '')
                        ));
                    @endphp
                    <tr
                        data-return-row
                        data-status="{{ $returnRecord->status }}"
                        data-search-text="{{ $searchText }}">
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <span class="mono-chip">{{ $returnRecord->return_code }}</span>
                        </td>
                        <td>
                            <div class="table-meta">
                                <strong>{{ optional($returnRecord->supplier)->name ?? '-' }}</strong>
                                <small>{{ optional($returnRecord->supplier)->code ?? '-' }}</small>
                            </div>
                        </td>
                        <td>{{ optional($returnRecord->location)->name ?? '-' }}</td>
                        <td>{{ (int) $returnRecord->items->sum('quantity') }}</td>
                        <td>Rp {{ number_format((int) $returnRecord->total_amount, 0, ',', '.') }}</td>
                        <td>
                            <span class="status-pill {{ $returnRecord->status_class }}">
                                {{ $returnRecord->status }}
                            </span>
                        </td>
                        <td>
                            {{ optional($returnRecord->return_at)->format('d M Y H:i') ?? '-' }}
                        </td>
                        <td class="td-actions">
                            <div class="inline-actions">
                                <a
                                    href="{{ route('supplier-returns.show', $returnRecord->id) }}"
                                    class="icon-btn"
                                    aria-label="Show return"
                                    title="Detail">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </a>

                                <form
                                    action="{{ route('supplier-returns.destroy', $returnRecord->id) }}"
                                    method="POST"
                                    class="inline-form"
                                    data-confirm-form
                                    data-confirm-title="Pindahkan return ke recycle?"
                                    data-confirm-message="Return ini akan dipindahkan ke recycle bin dan stok akan dipulihkan kembali."
                                    data-confirm-variant="danger"
                                    data-confirm-icon="fa-solid fa-trash">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-btn icon-btn--danger" aria-label="Recycle return" title="Recycle">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="9">
                            <div class="empty-state">
                                <div class="empty-state__icon">
                                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                </div>
                                <strong>Belum ada supplier return.</strong>
                                <p>Tekan tombol <b>Return Baru</b> untuk membuat retur supplier pertama.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="table-pagination" data-table-pagination-target="#supplierReturnsTable">
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

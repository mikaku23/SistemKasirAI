@extends('template-admin.layout')

@section('title', 'Recycle Supplier Returns')

@section('css')

<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">

<style>
    .supplier-return-page .inline-actions {
        display: inline-flex;
        gap: 8px;
        align-items: center;
    }

    .supplier-return-page .td-strong {
        font-weight: 600;
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

    .supplier-return-page .table-responsive {
        overflow-x: auto;
    }
</style>

@endsection

@section('content')
@php
$trashedReturns = $trashedReturns ?? [];
@endphp

<section class="page-card glass-card product-page supplier-return-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">SUPPLIER RETURNS</p>
            <h2>Recycle Bin</h2>
            <p>Data supplier return yang dihapus sementara. Restore akan mengembalikan data sekaligus menerapkan ulang logika stok.</p>
        </div>


    <div class="page-card__actions">
        <a href="{{ route('supplier-returns.index') }}" class="btn btn--secondary">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Kembali
        </a>
    </div>
</div>

<div class="table-card glass-card">
    <div class="table-card__head">
        <div>
            <p class="eyebrow">RECYCLE</p>
            <h3>Return yang dihapus sementara</h3>
        </div>

        <label class="search-box" for="supplierReturnRecycleSearch">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input
                type="search"
                id="supplierReturnRecycleSearch"
                placeholder="Search deleted return..."
                data-table-search-target="#supplierReturnsRecycleTable">
        </label>
    </div>

    <div class="table-responsive">
        <table class="data-table data-table--compact" id="supplierReturnsRecycleTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Return Code</th>
                    <th>Supplier</th>
                    <th>Location</th>
                    <th>Qty</th>
                    <th>Total Amount</th>
                    <th>Deleted At</th>
                    <th class="th-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($trashedReturns as $returnRecord)
                    @php
                        $searchText = strtolower(trim(
                            ($returnRecord->return_code ?? '') . ' ' .
                            (optional($returnRecord->supplier)->name ?? '') . ' ' .
                            (optional($returnRecord->location)->name ?? '') . ' ' .
                            ($returnRecord->reason ?? '') . ' ' .
                            ($returnRecord->status ?? '')
                        ));
                    @endphp
                    <tr data-search-text="{{ $searchText }}">
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
                        <td class="td-strong">{{ optional($returnRecord->location)->name ?? '-' }}</td>
                        <td>{{ (int) $returnRecord->items->sum('quantity') }}</td>
                        <td>Rp {{ number_format((int) $returnRecord->total_amount, 0, ',', '.') }}</td>
                        <td>{{ optional($returnRecord->deleted_at)->format('d M Y H:i') ?? '-' }}</td>
                        <td>
                            <div class="inline-actions">
                                <form
                                    action="{{ route('supplier-returns.restore', $returnRecord->id) }}"
                                    method="POST"
                                    class="inline-form"
                                    data-confirm-form
                                    data-confirm-title="Pulihkan return?"
                                    data-confirm-message="Return ini akan dikembalikan dari recycle bin. Lanjutkan pemulihan?"
                                    data-confirm-variant="info"
                                    data-confirm-icon="fa-solid fa-trash-arrow-up">
                                    @csrf
                                    <button type="submit" class="icon-btn icon-btn--success" aria-label="Restore return">
                                        <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                    </button>
                                </form>

                                <form
                                    action="{{ route('supplier-returns.forceDelete', $returnRecord->id) }}"
                                    method="POST"
                                    class="inline-form"
                                    data-confirm-form
                                    data-confirm-title="Hapus permanen?"
                                    data-confirm-message="Return ini akan dihapus permanen dan tidak bisa dipulihkan lagi. Lanjutkan?"
                                    data-confirm-variant="danger"
                                    data-confirm-icon="fa-solid fa-triangle-exclamation">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="icon-btn icon-btn--danger" aria-label="Force delete return">
                                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr data-empty-row>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-state__icon">
                                    <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                </div>
                                <strong>Recycle bin masih kosong.</strong>
                                <p>Supplier return yang dihapus sementara akan muncul di sini.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


</section>
@endsection

@section('js')

<script src="{{ asset('assets/js/layout.js') }}"></script>

@endsection

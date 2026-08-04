@extends('template-admin.layout')

@section('title', 'Recycle Batch Stok')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $trashedStockBatches = $trashedStockBatches ?? [];

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
            <h2>Recycle Bin</h2>
            <p>Daftar batch yang sudah dihapus sementara. Batch bisa dipulihkan atau dihapus permanen.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('stock-batches.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RECYCLE</p>
                <h3>Data batch terhapus</h3>
            </div>

            <label class="search-box" for="stockBatchRecycleSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="stockBatchRecycleSearch"
                    placeholder="Search deleted batch..."
                    data-table-search-target="#stockBatchesRecycleTable">
            </label>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="stockBatchesRecycleTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Batch Code</th>
                        <th>Qty Remaining</th>
                        <th>Deleted At</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trashedStockBatches as $stockBatch)
                        <tr data-search-text="{{ strtolower(trim(
                            ($stockBatch->batch_code ?? '') . ' ' .
                            ($stockBatch->lot_number ?? '') . ' ' .
                            (optional($stockBatch->product)->name ?? '') . ' ' .
                            (optional($stockBatch->supplier)->name ?? '')
                        )) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">
                                <div style="display: grid; gap: 4px;">
                                    <strong>{{ optional($stockBatch->product)->name ?? '-' }}</strong>
                                    <small class="text-muted">{{ optional(optional($stockBatch->product)->category)->name ?? '-' }}</small>
                                </div>
                            </td>
                            <td><span class="mono-chip">{{ $stockBatch->batch_code }}</span></td>
                            <td>{{ $formatQty($stockBatch->qty_remaining) }}</td>
                            <td>{{ $stockBatch->deleted_at ? $stockBatch->deleted_at->format('d M Y H:i') : '-' }}</td>
                            <td>
                                <div class="inline-actions">
                                    <form
                                        action="{{ route('stock-batches.restore', $stockBatch->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Pulihkan batch?"
                                        data-confirm-message="Batch ini akan dikembalikan dari recycle bin. Lanjutkan pemulihan?"
                                        data-confirm-variant="info"
                                        data-confirm-icon="fa-solid fa-trash-arrow-up">
                                        @csrf
                                        <button type="submit" class="icon-btn icon-btn--success" aria-label="Restore batch">
                                            <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                        </button>
                                    </form>

                                    <form
                                        action="{{ route('stock-batches.forceDelete', $stockBatch->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus permanen?"
                                        data-confirm-message="Batch ini akan dihapus permanen dan tidak bisa dipulihkan lagi. Lanjutkan?"
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-triangle-exclamation">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Force delete batch">
                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state__icon">
                                        <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                    </div>
                                    <strong>Recycle bin masih kosong.</strong>
                                    <p>Batch yang dihapus sementara akan muncul di sini.</p>
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

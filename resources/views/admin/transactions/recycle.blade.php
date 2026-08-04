@extends('template-admin.layout')

@section('title', 'Recycle Transaksi')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $trashedTransactions = $trashedTransactions ?? [];
@endphp

<section class="page-card glass-card transaction-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">TRANSACTIONS</p>
            <h2>Recycle Bin</h2>
            <p>Daftar transaksi yang sudah dihapus sementara. Data bisa dipulihkan atau dihapus permanen.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('transactions.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RECYCLE</p>
                <h3>Data transaksi terhapus</h3>
            </div>

            <label class="search-box" for="transactionRecycleSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="transactionRecycleSearch"
                    placeholder="Search deleted transaction..."
                    data-table-search-target="#transactionsRecycleTable">
            </label>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="transactionsRecycleTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Location</th>
                        <th>Cashier</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Deleted At</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trashedTransactions as $transaction)
                        <tr
                            data-search-text="{{ strtolower(trim(
                                ($transaction->transaction_code ?? '') . ' ' .
                                (optional($transaction->location)->name ?? '') . ' ' .
                                (optional($transaction->cashier)->name ?? '') . ' ' .
                                ($transaction->status ?? '')
                            )) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong"><span class="mono-chip">{{ $transaction->transaction_code }}</span></td>
                            <td>{{ optional($transaction->location)->name ?? '-' }}</td>
                            <td>{{ optional($transaction->cashier)->name ?? '-' }}</td>
                            <td>{{ $transaction->items_count ?? $transaction->items->count() }}</td>
                            <td>Rp {{ number_format((int) $transaction->total_amount, 0, ',', '.') }}</td>
                            <td>
                                <span class="status-pill {{ $transaction->status_class ?? 'status-pill--muted' }}">
                                    {{ $transaction->status_label ?? ucfirst((string) $transaction->status) }}
                                </span>
                            </td>
                            <td>{{ $transaction->deleted_at ? $transaction->deleted_at->format('d M Y H:i') : '-' }}</td>
                            <td>
                                <div class="inline-actions">
                                    <form
                                        action="{{ route('transactions.restore', $transaction->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Pulihkan transaksi?"
                                        data-confirm-message="Transaksi ini akan dikembalikan dari recycle bin dan stok disesuaikan kembali. Lanjutkan?"
                                        data-confirm-variant="info"
                                        data-confirm-icon="fa-solid fa-trash-arrow-up">
                                        @csrf
                                        <button type="submit" class="icon-btn icon-btn--success" aria-label="Restore transaction">
                                            <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                        </button>
                                    </form>

                                    <form
                                        action="{{ route('transactions.forceDelete', $transaction->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus permanen?"
                                        data-confirm-message="Transaksi ini akan dihapus permanen. Lanjutkan?"
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-triangle-exclamation">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Force delete transaction">
                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
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
                                        <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                    </div>
                                    <strong>Recycle bin masih kosong.</strong>
                                    <p>Transaksi yang dihapus sementara akan muncul di sini.</p>
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

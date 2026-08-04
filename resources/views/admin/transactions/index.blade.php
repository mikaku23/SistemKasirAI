@extends('template-admin.layout')

@section('title', 'Daftar Transaksi')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $transactions = $transactions ?? [];
    $transactionStats = $transactionStats ?? [
        'total' => 0,
        'today' => 0,
        'paid' => 0,
        'draft' => 0,
        'cancelled' => 0,
        'refunded' => 0,
    ];
@endphp

<section class="page-card glass-card transaction-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">TRANSACTIONS</p>
            <h2>Daftar Transaksi</h2>
            <p>Diskon promo item, diskon transaksi otomatis, pajak, total tagihan, dan kembalian.</p>
        </div>

        <div class="page-card__actions">
            <label class="search-box" for="transactionSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="transactionSearch"
                    placeholder="Search transaction..."
                    data-table-search-target="#transactionsTable">
            </label>

            <label class="filter-box" for="transactionStatusFilter">
                <span>Status</span>
                <select id="transactionStatusFilter" data-table-filter-target="#transactionsTable">
                    <option value="">Semua status</option>
                    <option value="paid">Paid</option>
                    <option value="draft">Draft</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="refunded">Refunded</option>
                </select>
            </label>

            <a href="{{ route('transactions.create') }}" class="btn btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Input Transaksi
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card glass-card">
            <span>Total</span>
            <strong>{{ $transactionStats['total'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Today</span>
            <strong>{{ $transactionStats['today'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Paid</span>
            <strong>{{ $transactionStats['paid'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Draft</span>
            <strong>{{ $transactionStats['draft'] }}</strong>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">TRANSACTIONS</p>
                <h3>Tabel data transaksi</h3>
            </div>

            <button
                class="btn btn--secondary"
                type="button"
                data-action="export-table"
                data-target="#transactionsTable">
                Export
            </button>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="transactionsTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Location</th>
                        <th>Cashier</th>
                        <th>Items</th>
                        <th>Disc Promo</th>
                        <th>Disc Tx</th>
                        <th>Tax</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Change</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr
                            data-transaction-row
                            data-status="{{ $transaction->status }}"
                            data-search-text="{{ strtolower(trim(
                                ($transaction->transaction_code ?? '') . ' ' .
                                (optional($transaction->location)->name ?? '') . ' ' .
                                (optional($transaction->cashier)->name ?? '') . ' ' .
                                ($transaction->items_count ?? optional($transaction->items)->count() ?? 0) . ' ' .
                                ($transaction->status_label ?? '') . ' ' .
                                ($transaction->transaction_at ? $transaction->transaction_at->format('d M Y H:i') : '')
                            )) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">{{ $transaction->transaction_code }}</td>
                            <td>{{ optional($transaction->location)->name ?? '-' }}</td>
                            <td>{{ optional($transaction->cashier)->name ?? '-' }}</td>
                            <td>{{ $transaction->items_count ?? ($transaction->items ? $transaction->items->count() : 0) }}</td>
                            <td>Rp {{ number_format((int) data_get($transaction->metadata, 'item_discount_total', 0), 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((int) $transaction->discount_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((int) $transaction->tax_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((int) $transaction->total_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((int) $transaction->paid_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((int) $transaction->change_amount, 0, ',', '.') }}</td>
                            <td>
                                <span class="status-pill {{ $transaction->status_class }}">
                                    {{ $transaction->status_label }}
                                </span>
                            </td>
                            <td>{{ $transaction->transaction_at ? $transaction->transaction_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a
                                        href="{{ route('transactions.show', $transaction->id) }}"
                                        class="icon-btn"
                                        aria-label="Show transaction">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <a
                                        href="{{ route('transactions.print', $transaction->id) }}"
                                        class="icon-btn"
                                        aria-label="Print transaction">
                                        <i class="fa-solid fa-print" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="14">
                                <div class="empty-state">
                                    <div class="empty-state__icon">
                                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    </div>
                                    <strong>Belum ada transaksi.</strong>
                                    <p>Tekan tombol <b>Input Transaksi</b> untuk membuat data pertama.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#transactionsTable">
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

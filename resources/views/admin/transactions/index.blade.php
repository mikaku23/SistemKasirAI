@extends('template-admin.layout')

@section('title', 'Daftar Transaksi')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $transactions = $transactions ?? [];
    $transactionStats = $transactionStats ?? ['total' => 0, 'today' => 0, 'success' => 0, 'waiting' => 0, 'failed' => 0];
@endphp

<section class="page-card glass-card transaction-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">TRANSACTIONS</p>
            <h2>Daftar Transaksi</h2>
            <p>Transaksi barang keluar, kembalian pelanggan, status, dan akses print struk.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('transactions.create') }}" class="btn btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Transaksi Baru
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card glass-card"><span>Total</span><strong>{{ $transactionStats['total'] }}</strong></div>
        <div class="stat-card glass-card"><span>Today</span><strong>{{ $transactionStats['today'] }}</strong></div>
        <div class="stat-card glass-card"><span>Success</span><strong>{{ $transactionStats['success'] }}</strong></div>
        <div class="stat-card glass-card"><span>Waiting</span><strong>{{ $transactionStats['waiting'] }}</strong></div>
        <div class="stat-card glass-card"><span>Failed</span><strong>{{ $transactionStats['failed'] }}</strong></div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">TRANSACTIONS</p>
                <h3>Tabel transaksi</h3>
            </div>

            <button class="btn btn--secondary" type="button" data-action="export-table" data-target="#transactionsTable">
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
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Diterima</th>
                        <th>Kembalian</th>
                        <th>Status</th>
                        <th>At</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        @php
                            $item = $transaction->items->first();
                            $qtyTotal = (int) ($item?->quantity ?? 0);
                        @endphp
                        <tr data-status="{{ $transaction->status }}" data-search-text="{{ strtolower(trim(
                            ($transaction->transaction_code ?? '') . ' ' .
                            (optional($transaction->location)->name ?? '') . ' ' .
                            (optional($transaction->cashier)->name ?? '') . ' ' .
                            (optional($item?->product)->name ?? '') . ' ' .
                            ($transaction->customer_name ?? '') . ' ' .
                            ($transaction->payment_method ?? '') . ' ' .
                            ($transaction->status ?? '')
                        )) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong"><span class="mono-chip">{{ $transaction->transaction_code }}</span></td>
                            <td>{{ optional($transaction->location)->name ?? '-' }}</td>
                            <td>{{ optional($transaction->cashier)->name ?? '-' }}</td>
                            <td>{{ optional($item?->product)->name ?? '-' }}</td>
                            <td>{{ $qtyTotal }}</td>
                            <td>Rp {{ number_format((int) $transaction->total_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((int) $transaction->paid_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((int) $transaction->change_amount, 0, ',', '.') }}</td>
                            <td>
                                <span class="status-pill {{ $transaction->status_class ?? 'status-pill--muted' }}">
                                    {{ $transaction->status_label ?? ucfirst((string) $transaction->status) }}
                                </span>
                            </td>
                            <td>{{ $transaction->transaction_at ? $transaction->transaction_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a href="{{ route('transactions.show', $transaction->id) }}" class="icon-btn" aria-label="Show transaction">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <a href="{{ route('transactions.print', $transaction->id) }}" class="icon-btn" aria-label="Print receipt">
                                        <i class="fa-solid fa-print" aria-hidden="true"></i>
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
                                    <strong>Belum ada data transaksi.</strong>
                                    <p>Tekan tombol <b>Transaksi Baru</b> untuk input penjualan pertama.</p>
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

@extends('template-admin.layout')

@section('title', 'Detail Transaksi')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $transaction = $transaction ?? null;
    $items = $transaction?->items ?? collect();
@endphp

<section class="page-card glass-card transaction-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">TRANSACTIONS</p>
            <h2>Detail Transaksi</h2>
            <p>Data transaksi, item, pajak, diskon, uang diterima, dan kembalian pelanggan.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('transactions.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>

            <a href="{{ route('transactions.print', $transaction->id) }}" class="btn btn--primary">
                <i class="fa-solid fa-print" aria-hidden="true"></i>
                Print Struk
            </a>
        </div>
    </div>

    <div class="form-alert form-alert--info">
        <strong>Status Transaksi:</strong>
        <span class="status-pill {{ $transaction->status_class ?? 'status-pill--muted' }}">{{ $transaction->status_label ?? ucfirst((string) $transaction->status) }}</span>
    </div>

    <div class="detail-card glass-card">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Transaction Code</span>
                <input type="text" value="{{ $transaction->transaction_code }}" disabled>
            </label>

            <label class="form-field">
                <span>Location</span>
                <input type="text" value="{{ optional($transaction->location)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Cashier</span>
                <input type="text" value="{{ optional($transaction->cashier)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Tax Setting</span>
                <input type="text" value="{{ optional($transaction->taxSetting)->name ?? '-' }} ({{ optional($transaction->taxSetting)->display_value ?? '-' }})" disabled>
            </label>

            <label class="form-field">
                <span>Shift</span>
                <input type="text" value="{{ $transaction->shift_label ?? $transaction->shift }}" disabled>
            </label>

            <label class="form-field">
                <span>Payment Method</span>
                <input type="text" value="{{ $transaction->payment_method_label ?? $transaction->payment_method }}" disabled>
            </label>

            <label class="form-field">
                <span>Customer Name</span>
                <input type="text" value="{{ $transaction->customer_name ?: '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Customer Phone</span>
                <input type="text" value="{{ $transaction->customer_phone ?: '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Transaction At</span>
                <input type="text" value="{{ $transaction->transaction_at ? $transaction->transaction_at->format('d M Y H:i') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Subtotal</span>
                <input type="text" value="Rp {{ number_format((int) $transaction->subtotal, 0, ',', '.') }}" disabled>
            </label>

            <label class="form-field">
                <span>Total Diskon</span>
                <input type="text" value="Rp {{ number_format((int) $transaction->discount_amount, 0, ',', '.') }}" disabled>
            </label>

            <label class="form-field">
                <span>Total Pajak</span>
                <input type="text" value="Rp {{ number_format((int) $transaction->tax_amount, 0, ',', '.') }}" disabled>
            </label>

            <label class="form-field">
                <span>Total Tagihan</span>
                <input type="text" value="Rp {{ number_format((int) $transaction->total_amount, 0, ',', '.') }}" disabled>
            </label>

            <label class="form-field">
                <span>Uang Diterima Pelanggan</span>
                <input type="text" value="Rp {{ number_format((int) $transaction->paid_amount, 0, ',', '.') }}" disabled>
            </label>

            <label class="form-field">
                <span>Kembalian Uang Pelanggan</span>
                <input type="text" value="Rp {{ number_format((int) $transaction->change_amount, 0, ',', '.') }}" disabled>
            </label>

            <label class="form-field form-field--full">
                <span>Notes</span>
                <textarea rows="3" disabled>{{ $transaction->notes ?: '-' }}</textarea>
            </label>
        </div>
    </div>

    <div class="table-card glass-card" style="margin-top: 16px;">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">ITEMS</p>
                <h3>Rincian item transaksi</h3>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Batch</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Promo / Pcs</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">{{ optional($item->product)->name ?? '-' }}</td>
                            <td>{{ optional($item->stockBatch)->batch_code ?? '-' }}</td>
                            <td>{{ (int) $item->quantity }}</td>
                            <td>Rp {{ number_format((int) $item->unit_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((int) $item->discount_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((int) $item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-state__icon">
                                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    </div>
                                    <strong>Item transaksi belum tersedia.</strong>
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

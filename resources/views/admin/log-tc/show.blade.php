@extends('template-admin.layout')

@section('title', 'Detail Log TC')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/log-tc.css') }}">
@endsection

@section('content')
@php
    $transaction = $transaction ?? null;
    $finance = $finance ?? [
        'gross_subtotal' => 0,
        'item_discount_total' => 0,
        'transaction_discount_total' => 0,
        'subtotal_after_item_discount' => 0,
        'net_revenue_total' => 0,
        'tax_total' => 0,
        'total_billed' => 0,
        'cogs_total' => 0,
        'gross_profit_total' => 0,
        'loss_total' => 0,
        'margin_percent' => 0,
        'line_items' => [],
        'batch_rows' => [],
    ];

    $money = function ($value) {
        return 'Rp ' . number_format((int) round((float) $value), 0, ',', '.');
    };
@endphp

<section class="page-card glass-card log-tc-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">LOG-TC</p>
            <h2>Detail Perhitungan Transaksi</h2>
            <p>Menampilkan alur hitung omzet, diskon, pajak, modal, dan laba untuk satu transaksi.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ $backUrl ?? route('log-tc.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
            <a href="{{ route('transactions.show', $transaction->id) }}" class="btn btn--primary">
                <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                Detail Transaksi
            </a>
        </div>
    </div>

    <div class="form-alert form-alert--info">
        <strong>Status:</strong>
        <span class="status-pill {{ $transaction->status_class }}">{{ $transaction->status_label }}</span>
    </div>

    <div class="table-card glass-card log-tc-summary-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RINGKASAN TRANSAKSI</p>
                <h3>{{ $transaction->transaction_code }}</h3>
                <p class="section-note">
                    {{ optional($transaction->location)->name ?? '-' }}
                    ·
                    {{ $transaction->transaction_at ? $transaction->transaction_at->format('d M Y H:i') : '-' }}
                </p>
            </div>
        </div>

        <div class="log-tc-summary-table-wrap">
            <table class="finance-summary-table">
                <thead>
                    <tr>
                        <th>Indikator</th>
                        <th class="finance-summary-table__value">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="finance-summary-table__label">Omzet Kotor</td>
                        <td class="finance-summary-table__value is-neutral">{{ $money($finance['gross_subtotal']) }}</td>
                    </tr>
                    <tr>
                        <td class="finance-summary-table__label">Total Diskon Item</td>
                        <td class="finance-summary-table__value is-negative">{{ $money($finance['item_discount_total']) }}</td>
                    </tr>
                    <tr>
                        <td class="finance-summary-table__label">Diskon Transaksi</td>
                        <td class="finance-summary-table__value is-negative">{{ $money($finance['transaction_discount_total']) }}</td>
                    </tr>
                    <tr>
                        <td class="finance-summary-table__label">Omzet Bersih</td>
                        <td class="finance-summary-table__value is-neutral">{{ $money($finance['net_revenue_total']) }}</td>
                    </tr>
                    <tr>
                        <td class="finance-summary-table__label">Total Pajak</td>
                        <td class="finance-summary-table__value is-neutral">{{ $money($finance['tax_total']) }}</td>
                    </tr>
                    <tr>
                        <td class="finance-summary-table__label">Total Tagihan</td>
                        <td class="finance-summary-table__value is-neutral">{{ $money($finance['total_billed']) }}</td>
                    </tr>
                    <tr>
                        <td class="finance-summary-table__label">Modal Pokok</td>
                        <td class="finance-summary-table__value is-negative">{{ $money($finance['cogs_total']) }}</td>
                    </tr>
                    <tr>
                        <td class="finance-summary-table__label">Laba Kotor</td>
                        <td class="finance-summary-table__value {{ $finance['gross_profit_total'] >= 0 ? 'is-positive' : 'is-negative' }}">
                            {{ $money($finance['gross_profit_total']) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="finance-summary-table__label">Rugi</td>
                        <td class="finance-summary-table__value is-negative">{{ $money($finance['loss_total']) }}</td>
                    </tr>
                    <tr>
                        <td class="finance-summary-table__label">Margin Laba</td>
                        <td class="finance-summary-table__value is-neutral">{{ number_format((float) $finance['margin_percent'], 2, ',', '.') }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="detail-card glass-card" style="margin-bottom: 1rem;">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Cashier</span>
                <input type="text" value="{{ optional($transaction->cashier)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Location</span>
                <input type="text" value="{{ optional($transaction->location)->name ?? '-' }}" disabled>
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
                <span>Payment Method</span>
                <input type="text" value="{{ $transaction->payment_method_label }}" disabled>
            </label>

            <label class="form-field">
                <span>Shift</span>
                <input type="text" value="{{ $transaction->shift_label }}" disabled>
            </label>

            <label class="form-field">
                <span>Tax Setting</span>
                <input type="text" value="{{ optional($transaction->taxSetting)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Discount Setting</span>
                <input type="text" value="{{ optional($transaction->discountSetting)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Paid Amount</span>
                <input type="text" value="{{ $money($transaction->paid_amount) }}" disabled>
            </label>

            <label class="form-field">
                <span>Change Amount</span>
                <input type="text" value="{{ $money($transaction->change_amount) }}" disabled>
            </label>

            <label class="form-field">
                <span>Notes</span>
                <textarea rows="3" disabled>{{ $transaction->notes ?: '-' }}</textarea>
            </label>
        </div>
    </div>

    <div class="table-card glass-card log-tc-table-card" style="margin-bottom: 1rem;">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RINCIAN ITEM</p>
                <h3>Perhitungan per produk</h3>
                <p class="section-note">Diskon transaksi dialokasikan proporsional ke masing-masing item agar total tetap konsisten.</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact log-tc-detail-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th>Qty</th>
                        <th>Harga Jual</th>
                        <th>Omzet Item</th>
                        <th>Diskon Item</th>
                        <th>Diskon Tx</th>
                        <th>Omzet Bersih</th>
                        <th>Modal</th>
                        <th>Laba</th>
                        <th>Margin</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($finance['line_items'] as $line)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">
                                <div style="display:grid; gap:4px;">
                                    <strong>{{ $line['product_name'] }}</strong>
                                    <small class="text-muted">
                                        {{ $line['batch_count'] }} batch
                                        @if (! empty($line['batch_labels']))
                                            · {{ implode(', ', $line['batch_labels']) }}
                                        @endif
                                    </small>
                                </div>
                            </td>
                            <td>{{ number_format((float) $line['quantity'], 0, ',', '.') }}</td>
                            <td>{{ $money($line['unit_price']) }}</td>
                            <td>{{ $money($line['gross_line']) }}</td>
                            <td class="is-negative">{{ $money($line['item_discount']) }}</td>
                            <td class="is-negative">{{ $money($line['transaction_discount_share']) }}</td>
                            <td>{{ $money($line['net_revenue_line']) }}</td>
                            <td class="is-negative">{{ $money($line['cogs_line']) }}</td>
                            <td class="{{ $line['profit_line'] >= 0 ? 'is-positive' : 'is-negative' }}">
                                {{ $money($line['profit_line']) }}
                            </td>
                            <td>{{ number_format((float) $line['margin_percent'], 2, ',', '.') }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">
                                <div class="empty-state">
                                    <strong>Belum ada item transaksi.</strong>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-card glass-card log-tc-table-card" style="margin-bottom: 1rem;">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">ALOKASI BATCH</p>
                <h3>Batch yang benar-benar terpakai</h3>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact log-tc-detail-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Produk</th>
                        <th>Batch Code</th>
                        <th>Lot</th>
                        <th>Qty Keluar</th>
                        <th>Modal / Pcs</th>
                        <th>Total Modal</th>
                        <th>Lokasi</th>
                        <th>Waktu</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($finance['batch_rows'] as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">{{ $row['product_name'] }}</td>
                            <td><span class="mono-chip">{{ $row['batch_code'] }}</span></td>
                            <td>{{ $row['lot_number'] }}</td>
                            <td>{{ number_format((float) $row['quantity'], 0, ',', '.') }}</td>
                            <td>{{ $money($row['unit_cost']) }}</td>
                            <td>{{ $money($row['total_cost']) }}</td>
                            <td>{{ $row['location_name'] }}</td>
                            <td>{{ $row['created_at'] ? $row['created_at']->format('d M Y H:i') : '-' }}</td>
                            <td class="td-description">{{ $row['notes'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <strong>Belum ada alokasi batch tercatat.</strong>
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
<script src="{{ asset('assets/js/log-tc.js') }}"></script>
@endsection

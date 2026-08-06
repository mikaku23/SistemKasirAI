@extends('template-admin.layout')

@section('title', 'Log TC')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/log-tc.css') }}">
@endsection

@section('content')
@php
    $transactions = $transactions ?? [];
    $summary = $summary ?? [
        'transaction_count' => 0,
        'gross_subtotal' => 0,
        'item_discount_total' => 0,
        'transaction_discount_total' => 0,
        'net_revenue_total' => 0,
        'tax_total' => 0,
        'total_billed' => 0,
        'cogs_total' => 0,
        'gross_profit_total' => 0,
        'loss_total' => 0,
        'margin_percent' => 0,
    ];

    $periodOptions = $periodOptions ?? [];
    $statusOptions = $statusOptions ?? [];
    $locations = $locations ?? [];
    $cashiers = $cashiers ?? [];

    $money = function ($value) {
        return 'Rp ' . number_format((int) round((float) $value), 0, ',', '.');
    };
@endphp

<section class="page-card glass-card log-tc-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">LOG-TC</p>
            <h2>Log Perhitungan Transaksi</h2>
            <p>Ringkasan omzet, diskon, pajak, modal, laba, dan rugi yang dihitung dari transaksi yang tampil pada filter aktif.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('transactions.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Ke Transaksi
            </a>

            <a href="{{ route('log-tc.index') }}" class="btn btn--ghost">
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                Reset
            </a>
        </div>
    </div>

    <div class="table-card glass-card log-tc-filter-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">FILTER</p>
                <h3>Filter perhitungan transaksi</h3>
                <p>Gunakan periode harian, mingguan, bulanan, tahunan, atau rentang waktu khusus.</p>
            </div>

            <div class="log-tc-filter-meta">
                <span class="filter-chip">
                    Periode aktif: <strong>{{ $activePeriodLabel ?? 'Semua data' }}</strong>
                </span>
            </div>
        </div>

        <form method="GET" action="{{ route('log-tc.index') }}" class="wizard-form page-form log-tc-filter-form">
            <div class="wizard-form-grid">
                <label class="form-field">
                    <span>Pencarian</span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode, customer, kasir, catatan..." autocomplete="off">
                </label>

                <label class="form-field">
                    <span>Periode</span>
                    <select name="period" data-log-tc-period>
                        @foreach ($periodOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('period', 'all') === (string) $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field" data-log-tc-custom-range>
                    <span>Tanggal Mulai</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}">
                </label>

                <label class="form-field" data-log-tc-custom-range>
                    <span>Tanggal Akhir</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}">
                </label>

                <label class="form-field">
                    <span>Lokasi</span>
                    <select name="location_id">
                        <option value="">Semua lokasi</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" {{ (string) request('location_id') === (string) $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>Kasir</span>
                    <select name="cashier_id">
                        <option value="">Semua kasir</option>
                        @foreach ($cashiers as $cashier)
                            <option value="{{ $cashier->id }}" {{ (string) request('cashier_id') === (string) $cashier->id ? 'selected' : '' }}>
                                {{ $cashier->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field">
                    <span>Status</span>
                    <select name="status">
                        <option value="">Semua status</option>
                        @foreach ($statusOptions as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === (string) $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="form-field">
                    <span>Aksi cepat</span>
                    <div class="page-card__actions log-tc-filter-actions">
                        <button type="submit" class="btn btn--primary">Terapkan Filter</button>
                        <a href="{{ route('log-tc.index') }}" class="btn btn--secondary">Reset Filter</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="table-card glass-card log-tc-summary-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RINGKASAN</p>
                <h3>Ringkasan perhitungan finansial</h3>
                <p class="section-note">{{ $activeFilterLabel ?? 'Semua data' }}</p>
            </div>
        </div>

        <div class="log-tc-summary-table-wrap">
            <table class="finance-summary-table finance-summary-table--centered">
                <thead>
                    <tr>
                        <th>Indikator</th>
                        <th>Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td class="finance-summary-table__label">Jumlah Transaksi</td><td class="finance-summary-table__value is-neutral">{{ $summary['transaction_count'] }}</td></tr>
                    <tr><td class="finance-summary-table__label">Omzet Kotor</td><td class="finance-summary-table__value is-neutral">{{ $money($summary['gross_subtotal']) }}</td></tr>
                    <tr><td class="finance-summary-table__label">Total Diskon Item</td><td class="finance-summary-table__value is-negative">{{ $money($summary['item_discount_total']) }}</td></tr>
                    <tr><td class="finance-summary-table__label">Diskon Transaksi</td><td class="finance-summary-table__value is-negative">{{ $money($summary['transaction_discount_total']) }}</td></tr>
                    <tr><td class="finance-summary-table__label">Omzet Bersih</td><td class="finance-summary-table__value is-neutral">{{ $money($summary['net_revenue_total']) }}</td></tr>
                    <tr><td class="finance-summary-table__label">Total Pajak</td><td class="finance-summary-table__value is-neutral">{{ $money($summary['tax_total']) }}</td></tr>
                    <tr><td class="finance-summary-table__label">Total Tagihan</td><td class="finance-summary-table__value is-neutral">{{ $money($summary['total_billed']) }}</td></tr>
                    <tr><td class="finance-summary-table__label">Total Modal Pokok</td><td class="finance-summary-table__value is-negative">{{ $money($summary['cogs_total']) }}</td></tr>
                    <tr><td class="finance-summary-table__label">Laba Kotor</td><td class="finance-summary-table__value {{ (int) $summary['gross_profit_total'] >= 0 ? 'is-positive' : 'is-negative' }}">{{ $money($summary['gross_profit_total']) }}</td></tr>
                    <tr><td class="finance-summary-table__label">Rugi</td><td class="finance-summary-table__value is-negative">{{ $money($summary['loss_total']) }}</td></tr>
                    <tr><td class="finance-summary-table__label">Margin Laba</td><td class="finance-summary-table__value is-neutral">{{ number_format((float) $summary['margin_percent'], 2, ',', '.') }}%</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-card glass-card log-tc-table-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">LOG TRANSAKSI</p>
                <h3>Daftar transaksi dan hasil hitungannya</h3>
            </div>
            <button class="btn btn--secondary" type="button" data-action="export-table" data-target="#logTcTable">Export</button>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact log-tc-detail-table" id="logTcTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th><th>Kode</th><th>Tanggal</th><th>Lokasi</th><th>Kasir</th><th>Omzet Kotor</th><th>Diskon</th><th>Omzet Bersih</th><th>Pajak</th><th>Modal</th><th>Laba</th><th>Margin</th><th>Status</th><th class="th-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        @php
                            $finance = data_get($transaction, 'log_finance', []);
                            $discountTotal = (int) data_get($finance, 'item_discount_total', 0) + (int) data_get($finance, 'transaction_discount_total', 0);
                        @endphp
                        <tr data-search-text="{{ strtolower(trim(($transaction->transaction_code ?? '') . ' ' . (optional($transaction->location)->name ?? '') . ' ' . (optional($transaction->cashier)->name ?? '') . ' ' . ($transaction->customer_name ?? '') . ' ' . ($transaction->status_label ?? ''))) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">{{ $transaction->transaction_code }}</td>
                            <td>{{ $transaction->transaction_at ? $transaction->transaction_at->format('d M Y H:i') : '-' }}</td>
                            <td>{{ optional($transaction->location)->name ?? '-' }}</td>
                            <td>{{ optional($transaction->cashier)->name ?? '-' }}</td>
                            <td>{{ $money(data_get($finance, 'gross_subtotal', 0)) }}</td>
                            <td>{{ $money($discountTotal) }}</td>
                            <td>{{ $money(data_get($finance, 'net_revenue_total', 0)) }}</td>
                            <td>{{ $money(data_get($finance, 'tax_total', 0)) }}</td>
                            <td>{{ $money(data_get($finance, 'cogs_total', 0)) }}</td>
                            <td class="{{ (int) data_get($finance, 'gross_profit_total', 0) >= 0 ? 'is-positive' : 'is-negative' }}">{{ $money(data_get($finance, 'gross_profit_total', 0)) }}</td>
                            <td>{{ number_format((float) data_get($finance, 'margin_percent', 0), 2, ',', '.') }}%</td>
                            <td><span class="status-pill {{ $transaction->status_class }}">{{ $transaction->status_label }}</span></td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a href="{{ route('log-tc.show', $transaction->id) }}" class="icon-btn" aria-label="Show log tc"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                    <a href="{{ route('transactions.show', $transaction->id) }}" class="icon-btn" aria-label="Show transaction"><i class="fa-solid fa-receipt" aria-hidden="true"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="14">
                                <div class="empty-state">
                                    <div class="empty-state__icon"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></div>
                                    <strong>Belum ada data log TC.</strong>
                                    <p>Silakan ubah filter periode atau input transaksi terlebih dahulu.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#logTcTable">
            <button type="button" class="btn btn--secondary table-pagination__btn" data-page-action="prev"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Back</button>
            <div class="table-pagination__info" data-page-info>Showing 0-0 of 0</div>
            <button type="button" class="btn btn--secondary table-pagination__btn" data-page-action="next">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
<script src="{{ asset('assets/js/log-tc.js') }}"></script>
@endsection

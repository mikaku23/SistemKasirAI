@extends('template-admin.layout')

@section('title', 'Promo Setting')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $promoProducts = $promoProducts ?? [];
    $promoStats = $promoStats ?? ['total' => 0, 'active' => 0, 'scheduled' => 0, 'expired' => 0, 'inactive' => 0];
@endphp

<section class="page-card glass-card product-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">PROMO SETTINGS</p>
            <h2>Daftar Promo Product</h2>
            <p>Promo berlaku dalam rentang waktu tertentu, lalu status akan otomatis menjadi inactive setelah periode berakhir.</p>
        </div>

        <div class="page-card__actions">
            <label class="search-box" for="promoSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="promoSearch"
                    placeholder="Search promo..."
                    data-table-search-target="#promoTable">
            </label>

            <label class="filter-box" for="promoStatusFilter">
                <span>Status</span>
                <select id="promoStatusFilter" data-table-filter-target="#promoTable">
                    <option value="">Semua status</option>
                    <option value="active">Active</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="expired">Expired</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>

            <a href="{{ route('promo-settings.create') }}" class="btn btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Set Promo
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">PROMO SETTINGS</p>
                <h3>Tabel promo product</h3>
            </div>

            <button
                class="btn btn--secondary"
                type="button"
                data-action="export-table"
                data-target="#promoTable">
                Export
            </button>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="promoTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Sale Price</th>
                        <th>Promo / Pcs</th>
                        <th>Effective Price</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($promoProducts as $product)
                        <tr
                            data-status="{{ $product->promo_status }}"
                            data-search-text="{{ strtolower(trim(
                                ($product->name ?? '') . ' ' .
                                (optional($product->category)->name ?? '') . ' ' .
                                (optional($product->unit)->name ?? '') . ' ' .
                                ($product->sku ?? '') . ' ' .
                                ($product->promo_status_label ?? '') . ' ' .
                                ($product->promo_period_label ?? '')
                            )) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">{{ $product->name }}</td>
                            <td>{{ optional($product->category)->name ?? '-' }}</td>
                            <td>Rp {{ number_format((int) $product->sale_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((int) $product->promo_discount_amount, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((int) $product->promo_effective_price, 0, ',', '.') }}</td>
                            <td>{{ $product->promo_period_label }}</td>
                            <td>
                                <span class="status-pill {{ $product->promo_status_class }}">
                                    {{ $product->promo_status_label }}
                                </span>
                            </td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a href="{{ route('promo-settings.show', ['product' => $product->id]) }}" class="icon-btn" aria-label="Show promo">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <form
                                        action="{{ route('promo-settings.destroy', ['product' => $product->id]) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus promo?"
                                        data-confirm-message="Semua konfigurasi promo pada product ini akan direset permanen."
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-trash">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Delete promo">
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
                                    <strong>Belum ada promo product.</strong>
                                    <p>Tekan tombol <b>Set Promo</b> untuk membuat promo pertama.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#promoTable">
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

@extends('template-admin.layout')

@section('title', 'Detail Promo')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $promo = $promo ?? [];
@endphp

<section class="page-card glass-card product-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">PROMO SETTINGS</p>
            <h2>Detail Promo Product</h2>
            <p>Seluruh detail promo ditampilkan dalam mode baca saja.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('promo-settings.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>

            <a href="{{ route('promo-settings.edit', ['product' => $product->id]) }}" class="btn btn--primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                Edit Promo
            </a>
        </div>
    </div>

    <div class="stats-grid" style="margin-bottom: 16px;">
        <div class="stat-card glass-card">
            <span>Status</span>
            <strong>{{ $promo['promo_status_label'] ?? $product->promo_status_label }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Period</span>
            <strong>{{ $promo['promo_period_label'] ?? $product->promo_period_label }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Effective Price</span>
            <strong>Rp {{ number_format((int) ($promo['promo_effective_price'] ?? $product->promo_effective_price), 0, ',', '.') }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Remaining Days</span>
            <strong>{{ $promo['promo_remaining_days'] ?? $product->promo_remaining_days ?? '-' }}</strong>
        </div>
    </div>

    <div class="detail-card glass-card">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Product</span>
                <input type="text" value="{{ $product->name }}" disabled>
            </label>

            <label class="form-field">
                <span>Category</span>
                <input type="text" value="{{ optional($product->category)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Sale Price</span>
                <input type="text" value="Rp {{ number_format((int) $product->sale_price, 0, ',', '.') }}" disabled>
            </label>

            <label class="form-field">
                <span>Promo Discount / Pcs</span>
                <input type="text" value="Rp {{ number_format((int) $product->promo_discount_amount, 0, ',', '.') }}" disabled>
            </label>

            <label class="form-field">
                <span>Promo Status</span>
                <input type="text" value="{{ $promo['promo_status_label'] ?? $product->promo_status_label }}" disabled>
            </label>

            <label class="form-field">
                <span>Promo Period</span>
                <input type="text" value="{{ $promo['promo_period_label'] ?? $product->promo_period_label }}" disabled>
            </label>

            <label class="form-field">
                <span>Promo Starts At</span>
                <input type="text" value="{{ $product->promo_starts_at ? $product->promo_starts_at->format('d M Y H:i') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Promo Ends At</span>
                <input type="text" value="{{ $product->promo_ends_at ? $product->promo_ends_at->format('d M Y H:i') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Effective Discount</span>
                <input type="text" value="Rp {{ number_format((int) ($promo['effective_discount_amount'] ?? $product->effective_discount_amount), 0, ',', '.') }}" disabled>
            </label>

            <label class="form-field">
                <span>Effective Price</span>
                <input type="text" value="Rp {{ number_format((int) ($promo['promo_effective_price'] ?? $product->promo_effective_price), 0, ',', '.') }}" disabled>
            </label>

            <label class="form-field form-field--full">
                <span>Promo Notes</span>
                <textarea rows="4" disabled>{{ data_get($product->promo_metadata, 'managed_by', '-') }}</textarea>
            </label>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

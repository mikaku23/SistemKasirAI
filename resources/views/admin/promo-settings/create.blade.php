@extends('template-admin.layout')

@section('title', 'Set Promo')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php $products = $products ?? []; @endphp
<section class="page-card glass-card">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">PROMO SETTINGS</p>
            <h2>Set Promo Product</h2>
            <p>Pilih product dan tentukan diskon promo per pcs.</p>
        </div>
        <div class="page-card__actions">
            <a href="{{ route('promo-settings.index') }}" class="btn btn--secondary"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="form-alert form-alert--danger">
            <strong>Periksa kembali data yang diisi.</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('promo-settings.store') }}" method="POST" class="page-form">
        @csrf
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Product</span>
                <select name="product_id" required>
                    <option value="">Pilih product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} — Rp {{ number_format((int) $product->sale_price, 0, ',', '.') }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="form-field">
                <span>Promo Discount / Pcs</span>
                <input type="number" name="promo_discount_amount" value="{{ old('promo_discount_amount', 0) }}" min="0" step="1" required>
            </label>
            <label class="form-field">
                <span>Status</span>
                <select name="promo_discount_is_active" required>
                    <option value="1" {{ old('promo_discount_is_active', 1) == 1 ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('promo_discount_is_active', 1) == 0 ? 'selected' : '' }}>Inactive</option>
                </select>
            </label>
        </div>

        <div class="wizard-actions" style="margin-top:16px;">
            <button class="btn btn--primary" type="submit">Simpan Promo</button>
        </div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

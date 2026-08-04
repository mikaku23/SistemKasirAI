@extends('template-admin.layout')

@section('title', 'Edit Promo')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
<section class="page-card glass-card">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">PROMO SETTINGS</p>
            <h2>Edit Promo Product</h2>
            <p>Atur promo aktif pada product terpilih.</p>
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

    <form action="{{ route('promo-settings.update', $product->id) }}" method="POST" class="page-form">
        @csrf
        @method('PUT')
        <input type="hidden" name="id" value="{{ $product->id }}">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Product</span>
                <input type="text" value="{{ $product->name }}" disabled>
            </label>
            <label class="form-field">
                <span>Sale Price</span>
                <input type="text" value="Rp {{ number_format((int) $product->sale_price, 0, ',', '.') }}" disabled>
            </label>
            <label class="form-field">
                <span>Promo Discount / Pcs</span>
                <input type="number" name="promo_discount_amount" value="{{ old('promo_discount_amount', (int) $product->promo_discount_amount) }}" min="0" step="1" required>
            </label>
            <label class="form-field">
                <span>Status</span>
                <select name="promo_discount_is_active" required>
                    <option value="1" {{ old('promo_discount_is_active', $product->promo_discount_is_active ? 1 : 0) == 1 ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('promo_discount_is_active', $product->promo_discount_is_active ? 1 : 0) == 0 ? 'selected' : '' }}>Inactive</option>
                </select>
            </label>
        </div>
        <div class="wizard-actions" style="margin-top:16px;">
            <button class="btn btn--primary" type="submit">Update Promo</button>
        </div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

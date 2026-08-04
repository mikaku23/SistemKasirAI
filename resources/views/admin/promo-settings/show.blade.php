@extends('template-admin.layout')

@section('title', 'Detail Promo')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
<section class="page-card glass-card">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">PROMO SETTINGS</p>
            <h2>Detail Promo Product</h2>
            <p>Detail promo yang dipakai otomatis saat transaksi.</p>
        </div>
        <div class="page-card__actions">
            <a href="{{ route('promo-settings.index') }}" class="btn btn--secondary"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
            <a href="{{ route('promo-settings.edit', $product->id) }}" class="btn btn--primary"><i class="fa-solid fa-pen-to-square"></i> Edit Promo</a>
        </div>
    </div>

    <div class="detail-card glass-card">
        <div class="wizard-form-grid">
            <label class="form-field"><span>Product</span><input type="text" value="{{ $product->name }}" disabled></label>
            <label class="form-field"><span>Category</span><input type="text" value="{{ optional($product->category)->name ?? '-' }}" disabled></label>
            <label class="form-field"><span>Sale Price</span><input type="text" value="Rp {{ number_format((int) $product->sale_price, 0, ',', '.') }}" disabled></label>
            <label class="form-field"><span>Promo Discount / Pcs</span><input type="text" value="Rp {{ number_format((int) $product->promo_discount_amount, 0, ',', '.') }}" disabled></label>
            <label class="form-field"><span>Promo Status</span><input type="text" value="{{ $product->promo_discount_is_active ? 'Active' : 'Inactive' }}" disabled></label>
            <label class="form-field"><span>Effective Price</span><input type="text" value="Rp {{ number_format(max(0, (int) $product->sale_price - (int) $product->effective_discount_amount), 0, ',', '.') }}" disabled></label>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

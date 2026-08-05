@extends('template-admin.layout')

@section('title', 'Detail Produk')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $barcodeSvg = null;

    if ($product->barcode && class_exists(\Picqer\Barcode\BarcodeGeneratorSVG::class)) {
        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        $barcodeSvg = $generator->getBarcode(
            $product->barcode,
            \Picqer\Barcode\BarcodeGeneratorSVG::TYPE_CODE_128,
            2,
            60
        );
    }
@endphp

<section class="page-card glass-card product-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">PRODUCTS</p>
            <h2>Detail Produk</h2>
            <p>Seluruh data ditampilkan dalam mode baca saja.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('products.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>

            <a href="{{ route('products.edit', $product->id) }}" class="btn btn--primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                Edit Produk
            </a>
        </div>
    </div>

    <div class="form-alert form-alert--info">
        <strong>Mode baca saja.</strong>
        <span>Gunakan tombol edit jika ingin mengubah data produk ini.</span>
    </div>

    <div class="detail-card glass-card">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Category</span>
                <input type="text" value="{{ optional($product->category)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Unit</span>
                <input type="text" value="{{ optional($product->unit)->name ?? '-' }} ({{ optional($product->unit)->symbol ?? '-' }})" disabled>
            </label>

            <label class="form-field">
                <span>Supplier</span>
                <input type="text" value="{{ optional($product->supplier)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Location</span>
                <input type="text" value="{{ optional($product->location)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Name</span>
                <input type="text" value="{{ $product->name }}" disabled>
            </label>

            <label class="form-field">
                <span>Slug</span>
                <input type="text" value="{{ $product->slug }}" disabled>
            </label>

            <label class="form-field form-field--full">
                <span>Barcode</span>
                <input type="text" value="{{ $product->barcode ?: '-' }}" disabled>
                @if ($barcodeSvg)
                    <div class="detail-media detail-media--barcode" style="margin-top: 12px;">
                        {!! $barcodeSvg !!}
                    </div>
                @else
                    <small>Barcode image belum tersedia. Pastikan package barcode sudah di-install.</small>
                @endif
            </label>

            <label class="form-field">
                <span>SKU</span>
                <input type="text" value="{{ $product->sku ?: '-' }}" disabled>
            </label>

            <label class="form-field form-field--full">
                <span>Short Description</span>
                <textarea rows="3" disabled>{{ $product->short_description ?: '-' }}</textarea>
            </label>

            <label class="form-field form-field--full">
                <span>Description</span>
                <textarea rows="4" disabled>{{ $product->description ?: '-' }}</textarea>
            </label>

            <label class="form-field form-field--full">
                <span>Image</span>
                @if ($product->image)
                    <div class="detail-media">
                        <img
                            src="{{ Storage::disk('public')->url($product->image) }}"
                            alt="{{ $product->name }}"
                            style="max-width: 220px; border-radius: 16px; display: block;">
                        <small>{{ $product->image }}</small>
                    </div>
                @else
                    <input type="text" value="-" disabled>
                @endif
            </label>

            <label class="form-field form-field--full">
                <span>Search Keywords</span>
                <textarea rows="3" disabled>{{ is_array($product->search_keywords) ? implode(', ', $product->search_keywords) : '-' }}</textarea>
            </label>

            <label class="form-field">
                <span>Purchase Price</span>
                <input type="text" value="{{ $product->purchase_price !== null ? 'Rp ' . number_format((float) $product->purchase_price, 0, ',', '.') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Sale Price</span>
                <input type="text" value="{{ $product->sale_price !== null ? 'Rp ' . number_format((float) $product->sale_price, 0, ',', '.') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Min Stock</span>
                <input type="text" value="{{ $product->min_stock !== null ? number_format((int) $product->min_stock, 0, ',', '.') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Stock On Hand (otomatis dari batch)</span>
                <input type="text" value="{{ $product->stock_on_hand !== null ? number_format((int) $product->stock_on_hand, 0, ',', '.') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Tracks Expiry</span>
                <input type="text" value="{{ $product->tracks_expiry ? 'Yes' : 'No' }}" disabled>
            </label>

            <label class="form-field">
                <span>Expiry Type</span>
                <input type="text" value="{{ $product->expiry_type_label }}" disabled>
            </label>

            <label class="form-field">
                <span>Production Date</span>
                <input type="text" value="{{ $product->production_date ? $product->production_date->format('d M Y') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Expired At</span>
                <input type="text" value="{{ $product->expired_at ? $product->expired_at->format('d M Y') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Shelf Life Days</span>
                <input type="text" value="{{ $product->shelf_life_days !== null ? number_format((int) $product->shelf_life_days, 0, ',', '.') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Expiry Warning Days</span>
                <input type="text" value="{{ $product->expiry_warning_days !== null ? number_format((int) $product->expiry_warning_days, 0, ',', '.') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Expiry Grace Days</span>
                <input type="text" value="{{ $product->expiry_grace_days !== null ? number_format((int) $product->expiry_grace_days, 0, ',', '.') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Resolved Expiry</span>
                <input type="text" value="{{ $product->resolved_expiry_at ? \Illuminate\Support\Carbon::parse($product->resolved_expiry_at)->format('d M Y') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Expiry Status</span>
                <input type="text" value="{{ $product->expiry_status_label }}" disabled>
            </label>

            <label class="form-field form-field--full">
                <span>Expiry Summary</span>
                <textarea rows="2" disabled>{{ $product->expiry_summary }}</textarea>
            </label>

            <label class="form-field">
                <span>Popularity Score</span>
                <input type="text" value="{{ $product->popularity_score !== null ? number_format((float) $product->popularity_score, 2, ',', '.') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Last Sold At</span>
                <input type="text" value="{{ $product->last_sold_at ? $product->last_sold_at->format('d M Y H:i') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Featured</span>
                <input type="text" value="{{ $product->is_featured ? 'Yes' : 'No' }}" disabled>
            </label>

            <label class="form-field">
                <span>Available Online</span>
                <input type="text" value="{{ $product->is_available_online ? 'Yes' : 'No' }}" disabled>
            </label>

            <label class="form-field">
                <span>Status</span>
                <input type="text" value="{{ $product->is_active ? 'Active' : 'Inactive' }}" disabled>
            </label>

            <label class="form-field">
                <span>Updated At</span>
                <input type="text" value="{{ $product->updated_at ? $product->updated_at->format('d M Y H:i') : '-' }}" disabled>
            </label>
        </div>
    </div>
</section>
@endsection

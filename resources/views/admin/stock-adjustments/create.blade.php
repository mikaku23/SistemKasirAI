@extends('template-admin.layout')

@section('title', 'Pengecekan Stok Baru')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $products = $products ?? [];
    $locations = $locations ?? [];
@endphp

<section class="page-card glass-card">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">STOCK CHECK</p>
            <h2>Input Pengecekan Stok</h2>
            <p>Pilih produk, isi stok fisik, lalu simpan. Sistem akan mengambil batch acuan otomatis dan menunggu tindakan verifikasi bila terjadi selisih.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('stock-adjustments.index') }}" class="btn btn--secondary">Kembali</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="form-alert form-alert--danger">
            <strong>Periksa kembali input Anda.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('stock-adjustments.store') }}" method="POST" class="page-form">
        @csrf

        <div class="form-grid form-grid--2">
            <label class="form-field">
                <span>Produk</span>
                <select name="product_id" required>
                    <option value="">Pilih produk</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}{{ $product->sku ? ' — ' . $product->sku : '' }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="form-field">
                <span>Lokasi</span>
                <select name="location_id">
                    <option value="">Otomatis dari product</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="form-field">
                <span>Stok fisik</span>
                <input type="number" name="physical_qty" value="{{ old('physical_qty') }}" min="0" step="1" placeholder="0" required>
            </label>

            <label class="form-field">
                <span>Catatan</span>
                <textarea name="reason" rows="4" placeholder="Contoh: Pengecekan rak utama">{{ old('reason', 'Pengecekan stok manual') }}</textarea>
            </label>
        </div>

        <div class="page-card__actions" style="margin-top: 18px;">
            <button type="submit" class="btn btn--primary">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                Simpan pengecekan
            </button>
        </div>
    </form>
</section>
@endsection

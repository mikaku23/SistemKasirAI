@extends('template-admin.layout')

@section('title', 'Detail Location')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $locationUsage = $locationUsage ?? ['users' => 0, 'products' => 0, 'stock_batches' => 0, 'stock_movements' => 0, 'stock_adjustments' => 0, 'stock_opnames' => 0, 'transactions' => 0, 'returns' => 0];
@endphp

<section class="page-card glass-card location-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">LOCATIONS</p>
            <h2>Detail Location</h2>
            <p>Seluruh data location ditampilkan dalam mode baca saja, termasuk ringkasan relasi yang memakai location ini.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('locations.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>

            <a href="{{ route('locations.edit', $location->id) }}" class="btn btn--primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                Edit Location
            </a>
        </div>
    </div>

    <div class="form-alert form-alert--info">
        <strong>Mode baca saja.</strong>
        <span>Gunakan tombol edit jika ingin mengubah data location ini.</span>
    </div>

    <div class="detail-card glass-card">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Users</span>
                <input type="text" value="{{ $locationUsage['users'] }}" disabled>
            </label>
            <label class="form-field">
                <span>Products</span>
                <input type="text" value="{{ $locationUsage['products'] }}" disabled>
            </label>
            <label class="form-field">
                <span>Stock Batches</span>
                <input type="text" value="{{ $locationUsage['stock_batches'] }}" disabled>
            </label>
            <label class="form-field">
                <span>Transactions</span>
                <input type="text" value="{{ $locationUsage['transactions'] }}" disabled>
            </label>
            <label class="form-field">
                <span>Name</span>
                <input type="text" value="{{ $location->name }}" disabled>
            </label>

            <label class="form-field">
                <span>Code</span>
                <input type="text" value="{{ $location->code }}" disabled>
            </label>

            <label class="form-field form-field--full">
                <span>Address</span>
                <textarea rows="4" disabled>{{ $location->address ?: '-' }}</textarea>
            </label>

            <label class="form-field">
                <span>Phone</span>
                <input type="text" value="{{ $location->phone ?: '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Status</span>
                <input type="text" value="{{ $location->is_active ? 'Active' : 'Inactive' }}" disabled>
            </label>

            <label class="form-field">
                <span>Updated At</span>
                <input type="text" value="{{ $location->updated_at ? $location->updated_at->format('d M Y H:i') : '-' }}" disabled>
            </label>
        </div>
    </div>
</section>
@endsection

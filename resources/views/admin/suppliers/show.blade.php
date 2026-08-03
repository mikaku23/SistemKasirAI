@extends('template-admin.layout')

@section('title', 'Detail Supplier')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $supplier = $supplier ?? [];
@endphp

<section class="page-card glass-card role-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">SUPPLIERS</p>
            <h2>Detail Supplier</h2>
            <p>Seluruh data ditampilkan dalam mode baca saja.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('suppliers.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>

            <a href="{{ route('suppliers.edit', $supplier['id']) }}" class="btn btn--primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                Edit Supplier
            </a>
        </div>
    </div>

    <div class="profile-chip" style="margin-bottom: 0.25rem;">
        <div class="avatar avatar--large">{{ strtoupper(substr($supplier['name'] ?? 'S', 0, 1)) }}</div>
        <div>
            <strong>{{ $supplier['name'] ?? '-' }}</strong>
            <small>{{ $supplier['code'] ?? '-' }}</small>
        </div>
    </div>

    <div class="form-alert form-alert--info">
        <strong>Mode baca saja.</strong>
        <span>Gunakan tombol edit jika ingin mengubah data supplier ini.</span>
    </div>

    <div class="detail-card glass-card">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Name</span>
                <input type="text" value="{{ $supplier['name'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Code</span>
                <input type="text" value="{{ $supplier['code'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Phone</span>
                <input type="text" value="{{ $supplier['phone'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Email</span>
                <input type="text" value="{{ $supplier['email'] ?? '-' }}" disabled>
            </label>

            <label class="form-field form-field--full">
                <span>Address</span>
                <textarea rows="4" disabled>{{ $supplier['address'] ?? '-' }}</textarea>
            </label>

            <label class="form-field form-field--full">
                <span>Notes</span>
                <textarea rows="4" disabled>{{ $supplier['notes'] ?? '-' }}</textarea>
            </label>

            <label class="form-field">
                <span>Status</span>
                <input type="text" value="{{ ($supplier['is_active'] ?? 1) ? 'Active' : 'Inactive' }}" disabled>
            </label>

            <label class="form-field">
                <span>Updated At</span>
                <input type="text" value="{{ $supplier['updated_at'] ?? '-' }}" disabled>
            </label>
        </div>
    </div>
</section>
@endsection

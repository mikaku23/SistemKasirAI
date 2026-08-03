@extends('template-admin.layout')

@section('title', 'Tambah Supplier')

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
            <h2>Create Supplier</h2>
            <p>Isi data supplier bertahap. Draft tersimpan di browser saat berpindah langkah.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('suppliers.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="form-alert form-alert--danger">
            <strong>Periksa kembali data yang diisi.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        action="{{ route('suppliers.store') }}"
        method="POST"
        class="wizard-form page-form"
        data-step-form
        data-draft-key="suppliers:create">
        @csrf


        <div class="stepper">
            <span class="step active" data-step-indicator="1">1</span>
            <span class="step" data-step-indicator="2">2</span>
            <span class="step" data-step-indicator="3">3</span>
        </div>

        <div class="wizard-body">

            <section class="wizard-step active" data-step="1">
                <div class="wizard-step__head">
                    <h4>Basic Identity</h4>
                    <p>Isi nama supplier dan kode uniknya.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Name</span>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Nama supplier"
                            required
                            autocomplete="off">
                    </label>

                    <label class="form-field">
                        <span>Code</span>
                        <input
                            type="text"
                            name="code"
                            value="{{ old('code') }}"
                            placeholder="Biarkan kosong untuk otomatis"
                            autocomplete="off">
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="wizard-step__head">
                    <h4>Contact & Notes</h4>
                    <p>Lengkapi informasi kontak dan alamat supplier.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Phone</span>
                        <input
                            type="text"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="08xxxxxxxxxx"
                            autocomplete="off">
                    </label>

                    <label class="form-field">
                        <span>Email</span>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="supplier@email.com"
                            autocomplete="off">
                    </label>

                    <label class="form-field form-field--full">
                        <span>Address</span>
                        <textarea
                            name="address"
                            rows="4"
                            placeholder="Alamat supplier">{{ old('address') }}</textarea>
                    </label>

                    <label class="form-field form-field--full">
                        <span>Notes</span>
                        <textarea
                            name="notes"
                            rows="4"
                            placeholder="Catatan tambahan">{{ old('notes') }}</textarea>
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="3">
                <div class="wizard-step__head">
                    <h4>Status & Review</h4>
                    <p>Cek ulang data supplier sebelum disimpan.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Status</span>
                        <select name="is_active" required>
                            <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active') === 0 || old('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </label>
                </div>

                <div class="review-grid">
                    <div class="review-item">
                        <span>Name</span>
                        <strong data-review-field="name">{{ old('name', '-') ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Code</span>
                        <strong data-review-field="code">{{ old('code', '-') ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Phone</span>
                        <strong data-review-field="phone">{{ old('phone', '-') ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Email</span>
                        <strong data-review-field="email">{{ old('email', '-') ?: '-' }}</strong>
                    </div>
                    <div class="review-item review-item--full">
                        <span>Address</span>
                        <p data-review-field="address">{{ old('address', '-') ?: '-' }}</p>
                    </div>
                    <div class="review-item review-item--full">
                        <span>Notes</span>
                        <p data-review-field="notes">{{ old('notes', '-') ?: '-' }}</p>
                    </div>
                    <div class="review-item">
                        <span>Status</span>
                        <strong data-review-field="status" data-review-source="is_active">{{ old('is_active', 1) == 1 ? 'Active' : 'Inactive' }}</strong>
                    </div>
                </div>
            </section>

        </div>

        <div class="wizard-actions">
            <button class="btn btn--secondary" type="button" data-step-action="prev">Back</button>

            <div class="wizard-actions__right">
                <button class="btn btn--ghost" type="button" data-step-action="skip">Skip</button>
                <button class="btn btn--primary" type="button" data-step-action="next">Next</button>
                <button class="btn btn--primary" type="submit" data-step-submit hidden>
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Simpan Supplier
                </button>
            </div>
        </div>
    </form>
</section>

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

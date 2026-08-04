@extends('template-admin.layout')

@section('title', 'Create Location')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
<section class="page-card glass-card location-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">LOCATIONS</p>
            <h2>Create Location</h2>
            <p>Isi data location bertahap. Kode bisa dikosongkan untuk digenerate otomatis dari nama location.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('locations.index') }}" class="btn btn--secondary">
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
        action="{{ route('locations.store') }}"
        method="POST"
        class="wizard-form page-form"
        data-step-form
        data-draft-key="locations:create">
        @csrf

        <div class="stepper">
            <span class="step active" data-step-indicator="1">1</span>
            <span class="step" data-step-indicator="2">2</span>
            <span class="step" data-step-indicator="3">3</span>
        </div>

        <div class="wizard-body">
            <section class="wizard-step active" data-step="1">
                <div class="wizard-step__head">
                    <h4>Identity</h4>
                    <p>Identitas location utama untuk operasional stok dan transaksi.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Name</span>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Gudang Pusat"
                            required
                            autocomplete="off">
                    </label>

                    <label class="form-field">
                        <span>Code</span>
                        <input
                            type="text"
                            name="code"
                            value="{{ old('code') }}"
                            placeholder="LOC-GP-001"
                            autocomplete="off">
                        <small>Kosongkan agar sistem membuat code dari nama location.</small>
                    </label>

                    <label class="form-field">
                        <span>Status</span>
                        <select name="is_active" required>
                            <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active') === 0 || old('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="wizard-step__head">
                    <h4>Contact & Address</h4>
                    <p>Tambahkan alamat dan nomor kontak location bila tersedia.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field form-field--full">
                        <span>Address</span>
                        <textarea
                            name="address"
                            rows="4"
                            placeholder="Alamat location...">{{ old('address') }}</textarea>
                    </label>

                    <label class="form-field">
                        <span>Phone</span>
                        <input
                            type="text"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="08xxxxxxxxxx"
                            autocomplete="off">
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="3">
                <div class="wizard-step__head">
                    <h4>Review & Submit</h4>
                    <p>Periksa kembali data sebelum disimpan ke database.</p>
                </div>

                <div class="review-grid">
                    <div class="review-item">
                        <span>Name</span>
                        <strong data-review-field="name">{{ old('name', '-') }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Code</span>
                        <strong data-review-field="code">{{ old('code') ?: 'AUTO' }}</strong>
                    </div>
                    <div class="review-item review-item--full">
                        <span>Address</span>
                        <p data-review-field="address">{{ old('address') ?: '-' }}</p>
                    </div>
                    <div class="review-item">
                        <span>Phone</span>
                        <strong data-review-field="phone">{{ old('phone') ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Status</span>
                        <strong data-review-field="status">{{ old('is_active', 1) == 1 ? 'Active' : 'Inactive' }}</strong>
                    </div>
                </div>
            </section>
        </div>

        <div class="wizard-actions">
            <button class="btn btn--secondary" type="button" data-step-action="prev">Back</button>

            <div class="wizard-actions__right">
                <button class="btn btn--primary" type="button" data-step-action="next">Next</button>
                <button class="btn btn--primary" type="submit" data-step-submit hidden>
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Simpan Location
                </button>
            </div>
        </div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

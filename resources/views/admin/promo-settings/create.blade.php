@extends('template-admin.layout')

@section('title', 'Create Promo')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $products = $products ?? [];
    $defaultStart = old('promo_starts_at', now()->format('Y-m-d\TH:i'));
    $defaultEnd = old('promo_ends_at', now()->addDays(7)->format('Y-m-d\TH:i'));
@endphp

<section class="page-card glass-card product-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">PROMO SETTINGS</p>
            <h2>Create Promo Product</h2>
            <p>Isi promo bertahap. Promo berjalan dari waktu mulai sampai waktu selesai dan akan otomatis nonaktif setelah lewat batas akhir.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('promo-settings.index') }}" class="btn btn--secondary">
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
        action="{{ route('promo-settings.store') }}"
        method="POST"
        class="wizard-form page-form"
        data-step-form
        data-draft-key="promo-settings:create">
        @csrf

        <div class="stepper">
            <span class="step active" data-step-indicator="1">1</span>
            <span class="step" data-step-indicator="2">2</span>
            <span class="step" data-step-indicator="3">3</span>
        </div>

        <div class="wizard-body">
            <section class="wizard-step active" data-step="1">
                <div class="wizard-step__head">
                    <h4>Promo Identity</h4>
                    <p>Pilih product dan tentukan nilai promo per pcs.</p>
                </div>

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
                        <input
                            type="number"
                            name="promo_discount_amount"
                            value="{{ old('promo_discount_amount', 0) }}"
                            min="0"
                            step="1"
                            required
                            placeholder="7500">
                    </label>

                    <label class="form-field">
                        <span>Status</span>
                        <select name="promo_discount_is_active" required>
                            <option value="1" {{ old('promo_discount_is_active', 1) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('promo_discount_is_active') === 0 || old('promo_discount_is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="wizard-step__head">
                    <h4>Period & Rules</h4>
                    <p>Promo mulai dari tanggal yang ditentukan di sistem dan berakhir sesuai jadwal yang diinput.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Promo Starts At</span>
                        <input type="datetime-local" name="promo_starts_at" value="{{ old('promo_starts_at', $defaultStart) }}" required>
                    </label>

                    <label class="form-field">
                        <span>Promo Ends At</span>
                        <input type="datetime-local" name="promo_ends_at" value="{{ old('promo_ends_at', $defaultEnd) }}" required>
                    </label>

                    <div class="form-field form-field--full">
                        <span>Smart Rule</span>
                        <div class="form-alert form-alert--info">
                            <strong>Auto inactive</strong>
                            <p>Jika waktu selesai promo sudah lewat, sistem akan menonaktifkan promo ini secara otomatis.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="wizard-step" data-step="3">
                <div class="wizard-step__head">
                    <h4>Review & Submit</h4>
                    <p>Periksa kembali data sebelum disimpan.</p>
                </div>

                <div class="review-grid">
                    <div class="review-item">
                        <span>Product</span>
                        <strong data-review-field="product">{{ old('product_id', '-') }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Promo / Pcs</span>
                        <strong data-review-field="discount">{{ old('promo_discount_amount', '-') }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Status</span>
                        <strong data-review-field="status">{{ old('promo_discount_is_active', 1) == 1 ? 'Active' : 'Inactive' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Starts At</span>
                        <strong data-review-field="starts_at">{{ old('promo_starts_at', $defaultStart) }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Ends At</span>
                        <strong data-review-field="ends_at">{{ old('promo_ends_at', $defaultEnd) }}</strong>
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
                    Simpan Promo
                </button>
            </div>
        </div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

@extends('template-admin.layout')

@section('title', 'Edit Promo')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $promo = $promo ?? [];
@endphp

<section class="page-card glass-card product-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">PROMO SETTINGS</p>
            <h2>Edit Promo Product</h2>
            <p>Ubah promo product tanpa mengganggu harga dasar product.</p>
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
       action="{{ route('promo-settings.update', ['product' => $product->id]) }}"
        class="wizard-form page-form"
        data-step-form
        data-draft-key="promo-settings:edit:{{ $product->id }}"
        data-confirm-form
        data-confirm-title="Simpan perubahan?"
        data-confirm-message="Data promo yang diubah akan disimpan ke database. Lanjutkan?"
        data-confirm-variant="warn"
        data-confirm-icon="fa-solid fa-floppy-disk">
        @csrf
        @method('PUT')

        <div class="stepper">
            <span class="step active" data-step-indicator="1">1</span>
            <span class="step" data-step-indicator="2">2</span>
            <span class="step" data-step-indicator="3">3</span>
        </div>

        <div class="wizard-body">
            <section class="wizard-step active" data-step="1">
                <div class="wizard-step__head">
                    <h4>Promo Identity</h4>
                    <p>Product dikunci agar histori promo tetap konsisten.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Product</span>
                        <input type="text" value="{{ $product->name }}" disabled>
                    </label>

                    <label class="form-field">
                        <span>Promo Discount / Pcs</span>
                        <input
                            type="number"
                            name="promo_discount_amount"
                            value="{{ old('promo_discount_amount', (int) $product->promo_discount_amount) }}"
                            min="0"
                            step="1"
                            required>
                    </label>

                    <label class="form-field">
                        <span>Status</span>
                        <select name="promo_discount_is_active" required>
                            <option value="1" {{ old('promo_discount_is_active', $product->promo_discount_is_active ? 1 : 0) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('promo_discount_is_active', $product->promo_discount_is_active ? 1 : 0) == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="wizard-step__head">
                    <h4>Period & Rules</h4>
                    <p>Sesuaikan rentang promo agar otomatis nonaktif saat masa promonya lewat.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Promo Starts At</span>
                        <input
                            type="datetime-local"
                            name="promo_starts_at"
                            value="{{ old('promo_starts_at', $promo['promo_starts_at'] ?? optional($product->promo_starts_at)->format('Y-m-d\TH:i')) }}"
                            required>
                    </label>

                    <label class="form-field">
                        <span>Promo Ends At</span>
                        <input
                            type="datetime-local"
                            name="promo_ends_at"
                            value="{{ old('promo_ends_at', $promo['promo_ends_at'] ?? optional($product->promo_ends_at)->format('Y-m-d\TH:i')) }}"
                            required>
                    </label>

                    <div class="form-field form-field--full">
                        <span>Smart Rule</span>
                        <div class="form-alert form-alert--info">
                            <strong>Status otomatis</strong>
                            <p>Jika tanggal akhir sudah terlewati, promo akan otomatis dinonaktifkan oleh sistem.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="wizard-step" data-step="3">
                <div class="wizard-step__head">
                    <h4>Review & Submit</h4>
                    <p>Pastikan hasil perubahan sudah benar sebelum disimpan.</p>
                </div>

                <div class="review-grid">
                    <div class="review-item">
                        <span>Product</span>
                        <strong data-review-field="product">{{ $product->name }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Promo / Pcs</span>
                        <strong data-review-field="discount">{{ old('promo_discount_amount', (int) $product->promo_discount_amount) }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Status</span>
                        <strong data-review-field="status">{{ old('promo_discount_is_active', $product->promo_discount_is_active ? 1 : 0) == 1 ? 'Active' : 'Inactive' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Starts At</span>
                        <strong data-review-field="starts_at">{{ old('promo_starts_at', $promo['promo_starts_at'] ?? optional($product->promo_starts_at)->format('Y-m-d\TH:i')) }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Ends At</span>
                        <strong data-review-field="ends_at">{{ old('promo_ends_at', $promo['promo_ends_at'] ?? optional($product->promo_ends_at)->format('Y-m-d\TH:i')) }}</strong>
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
                    Update Promo
                </button>
            </div>
        </div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

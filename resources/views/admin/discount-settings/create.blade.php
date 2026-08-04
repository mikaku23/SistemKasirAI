@extends('template-admin.layout')

@section('title', 'Create Diskon')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
<section class="page-card glass-card discount-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">DISCOUNT SETTINGS</p>
            <h2>Create Diskon</h2>
            <p>Diskon berlaku otomatis sejak data disimpan. Jika masa berakhir, status akan turun menjadi inactive secara sistem.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('discount-settings.index') }}" class="btn btn--secondary">
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
        action="{{ route('discount-settings.store') }}"
        method="POST"
        class="wizard-form page-form"
        data-step-form
        data-draft-key="discount-settings:create">
        @csrf

        <div class="stepper">
            <span class="step active" data-step-indicator="1">1</span>
            <span class="step" data-step-indicator="2">2</span>
            <span class="step" data-step-indicator="3">3</span>
        </div>

        <div class="wizard-body">
            <section class="wizard-step active" data-step="1">
                <div class="wizard-step__head">
                    <h4>Basic Info</h4>
                    <p>Identity awal untuk promo diskon transaksi.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Name</span>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Promo Weekend"
                            required
                            autocomplete="off">
                    </label>

                    <label class="form-field">
                        <span>Code</span>
                        <input
                            type="text"
                            value="DISC-{{ strtoupper(now()->format('My')) }}-001"
                            readonly
                            aria-readonly="true">
                        <small>Kode dibuat otomatis oleh sistem.</small>
                    </label>

                    <label class="form-field form-field--full">
                        <span>Catatan Periode</span>
                        <input
                            type="text"
                            value="Berlaku sejak data disimpan hingga tanggal berakhir yang dipilih."
                            readonly
                            aria-readonly="true">
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="wizard-step__head">
                    <h4>Discount Rule</h4>
                    <p>Atur tipe diskon, batas minimum transaksi, dan masa berlaku promo.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Discount Type</span>
                        <select name="discount_type" required>
                            <option value="percent" {{ old('discount_type', 'percent') === 'percent' ? 'selected' : '' }}>Percent</option>
                            <option value="fixed" {{ old('discount_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Discount Value</span>
                        <input
                            type="number"
                            name="discount_value"
                            value="{{ old('discount_value') }}"
                            min="1"
                            step="1"
                            placeholder="15"
                            required>
                        <small>Jika percent, maksimal 100. Jika fixed, isi nominal rupiah.</small>
                    </label>

                    <label class="form-field">
                        <span>Minimum Transaction</span>
                        <input
                            type="number"
                            name="minimum_total_amount"
                            value="{{ old('minimum_total_amount') }}"
                            min="0"
                            step="1"
                            placeholder="50000"
                            required>
                        <small>Diskon aktif jika total transaksi lebih dari sama dengan nilai ini.</small>
                    </label>

                    <label class="form-field">
                        <span>Ends At</span>
                        <input
                            type="date"
                            name="ends_at"
                            value="{{ old('ends_at') }}"
                            required>
                        <small>Tanggal mulai otomatis dari saat data dibuat.</small>
                    </label>

                    <label class="form-field">
                        <span>Priority</span>
                        <input
                            type="number"
                            name="priority"
                            value="{{ old('priority', 0) }}"
                            min="0"
                            step="1"
                            placeholder="0">
                        <small>Semakin besar nilainya, semakin diprioritaskan.</small>
                    </label>

                    <label class="form-field form-field--full">
                        <span>Description</span>
                        <textarea
                            name="description"
                            rows="4"
                            placeholder="Misal: promo akhir pekan untuk transaksi minimal 50 ribu">{{ old('description') }}</textarea>
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
                        <span>Nama Diskon</span>
                        <strong data-review-field="name">{{ old('name', '-') }}</strong>
                    </div>

                    <div class="review-item">
                        <span>Code</span>
                        <strong data-review-field="code">DISC-{{ strtoupper(now()->format('My')) }}-001</strong>
                    </div>

                    <div class="review-item">
                        <span>Discount Type</span>
                        <strong data-review-field="discount_type">{{ old('discount_type', 'percent') === 'percent' ? 'Percent' : 'Fixed Amount' }}</strong>
                    </div>

                    <div class="review-item">
                        <span>Discount Value</span>
                        <strong data-review-field="discount_value">{{ old('discount_value', '-') }}</strong>
                    </div>

                    <div class="review-item">
                        <span>Minimum Transaction</span>
                        <strong data-review-field="minimum_total_amount">{{ old('minimum_total_amount') ? 'Rp ' . number_format((int) old('minimum_total_amount'), 0, ',', '.') : 'Rp 0' }}</strong>
                    </div>

                    <div class="review-item">
                        <span>Ends At</span>
                        <strong data-review-field="ends_at">{{ old('ends_at', '-') }}</strong>
                    </div>

                    <div class="review-item">
                        <span>Priority</span>
                        <strong data-review-field="priority">{{ old('priority', 0) }}</strong>
                    </div>

                    <div class="review-item">
                        <span>Default Promo</span>
                        <strong data-review-field="is_default">{{ old('is_default', 0) == 1 ? 'Yes' : 'No' }}</strong>
                    </div>

                    <div class="review-item">
                        <span>Status</span>
                        <strong data-review-field="is_active">{{ old('is_active', 1) == 1 ? 'Active' : 'Inactive' }}</strong>
                    </div>

                    <div class="review-item review-item--full">
                        <span>Description</span>
                        <p data-review-field="description">{{ old('description') ?: '-' }}</p>
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
                    Simpan Diskon
                </button>
            </div>
        </div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

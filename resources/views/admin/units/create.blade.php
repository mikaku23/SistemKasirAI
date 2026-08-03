@extends('template-admin.layout')

@section('title', 'Create Unit')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
<section class="page-card glass-card unit-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">UNITS</p>
            <h2>Create Unit</h2>
            <p>Isi data unit bertahap. Draft tersimpan di browser saat berpindah langkah.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('units.index') }}" class="btn btn--secondary">
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
        action="{{ route('units.store') }}"
        method="POST"
        class="wizard-form page-form"
        data-step-form
        data-draft-key="units:create">
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
                    <p>Identity awal untuk unit.</p>
                </div>

                <div class="wizard-form-grid">

<label class="form-field">
    <span>Name</span>
    <input
        type="text"
        name="name"
        value="{{ old('name') }}"
        placeholder="Kilogram"
        required
        autocomplete="off">
</label>

<label class="form-field">
    <span>Symbol</span>
    <input
        type="text"
        name="symbol"
        value="{{ old('symbol') }}"
        placeholder="kg"
        required
        autocomplete="off">
</label>

                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="wizard-step__head">
                    <h4>Description & Status</h4>
                    <p>Tambahkan deskripsi singkat dan status aktif unit.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field form-field--full">
                        <span>Description</span>
                        <textarea
                            name="description"
                            rows="4"
                            placeholder="Description...">{{ old('description') }}</textarea>
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

            <section class="wizard-step" data-step="3">
                <div class="wizard-step__head">
                    <h4>Review & Submit</h4>
                    <p>Periksa kembali data sebelum disimpan ke database.</p>
                </div>

                <div class="review-grid">

<div class="review-item">
    <span>Nama Unit</span>
    <strong data-review-field="name">{{ old('name', '-') }}</strong>
</div>
<div class="review-item">
    <span>Symbol</span>
    <strong data-review-field="symbol">{{ old('symbol', '-') }}</strong>
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
                <button class="btn btn--ghost" type="button" data-step-action="skip">Skip</button>
                <button class="btn btn--primary" type="button" data-step-action="next">Next</button>
                <button class="btn btn--primary" type="submit" data-step-submit hidden>
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Simpan Unit
                </button>
            </div>
        </div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

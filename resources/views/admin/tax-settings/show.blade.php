@extends('template-admin.layout')

@section('title', 'Detail Setting Pajak')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
<section class="page-card glass-card">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">TAX SETTINGS</p>
            <h2>Detail Setting Pajak</h2>
            <p>Seluruh data ditampilkan dalam mode baca saja.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('tax-settings.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>

            <a href="{{ route('tax-settings.edit', $taxSetting->id) }}" class="btn btn--primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                Edit
            </a>
        </div>
    </div>

    <div class="detail-card glass-card">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Name</span>
                <input type="text" value="{{ $taxSetting->name }}" disabled>
            </label>

            <label class="form-field">
                <span>Code</span>
                <input type="text" value="{{ $taxSetting->code }}" disabled>
            </label>

            <label class="form-field">
                <span>Tax Type</span>
                <input type="text" value="{{ $taxSetting->tax_type_label }}" disabled>
            </label>

            <label class="form-field">
                <span>Tax Value</span>
                <input type="text" value="{{ $taxSetting->display_value }}" disabled>
            </label>

            <label class="form-field">
                <span>Default</span>
                <input type="text" value="{{ $taxSetting->is_default ? 'Yes' : 'No' }}" disabled>
            </label>

            <label class="form-field">
                <span>Status</span>
                <input type="text" value="{{ $taxSetting->is_active ? 'Active' : 'Inactive' }}" disabled>
            </label>

            <label class="form-field">
                <span>Updated At</span>
                <input type="text" value="{{ $taxSetting->updated_at ? $taxSetting->updated_at->format('d M Y H:i') : '-' }}" disabled>
            </label>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

@extends('template-admin.layout')

@section('title', 'Edit Setting Pajak')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
<section class="page-card glass-card">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">TAX SETTINGS</p>
            <h2>Edit Setting Pajak</h2>
            <p>Ubah konfigurasi pajak yang digunakan transaksi.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('tax-settings.index') }}" class="btn btn--secondary">
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

    <form action="{{ route('tax-settings.update', $taxSetting->id) }}" method="POST" class="page-form">
        @csrf
        @method('PUT')

        <input type="hidden" name="id" value="{{ $taxSetting->id }}">

        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Name</span>
                <input type="text" name="name" value="{{ old('name', $taxSetting->name) }}" placeholder="PPN 11%" required autocomplete="off">
            </label>

            <label class="form-field">
                <span>Code</span>
                <input type="text" name="code" value="{{ old('code', $taxSetting->code) }}" placeholder="PPN11" autocomplete="off">
            </label>

            <label class="form-field">
                <span>Tax Type</span>
                <select name="tax_type" required>
                    <option value="fixed" {{ old('tax_type', $taxSetting->tax_type) === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                    <option value="percent" {{ old('tax_type', $taxSetting->tax_type) === 'percent' ? 'selected' : '' }}>Percent</option>
                </select>
            </label>

            <label class="form-field">
                <span>Tax Value</span>
                <input type="number" name="tax_value" value="{{ old('tax_value', $taxSetting->tax_value) }}" min="0" step="1" placeholder="0" required>
            </label>

            <label class="form-field">
                <span>Default</span>
                <select name="is_default" required>
                    <option value="1" {{ old('is_default', $taxSetting->is_default ? 1 : 0) == 1 ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ old('is_default', $taxSetting->is_default ? 1 : 0) == 0 ? 'selected' : '' }}>No</option>
                </select>
            </label>

            <label class="form-field">
                <span>Status</span>
                <select name="is_active" required>
                    <option value="1" {{ old('is_active', $taxSetting->is_active ? 1 : 0) == 1 ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ old('is_active', $taxSetting->is_active ? 1 : 0) == 0 ? 'selected' : '' }}>Inactive</option>
                </select>
            </label>
        </div>

        <div class="wizard-actions" style="margin-top: 16px;">
            <a href="{{ route('tax-settings.index') }}" class="btn btn--secondary">Back</a>
            <button type="submit" class="btn btn--primary">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                Update Pajak
            </button>
        </div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

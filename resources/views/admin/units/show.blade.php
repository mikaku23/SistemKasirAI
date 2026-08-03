@extends('template-admin.layout')

@section('title', 'Detail Unit')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
<section class="page-card glass-card unit-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">UNITS</p>
            <h2>Detail Unit</h2>
            <p>Seluruh data ditampilkan dalam mode baca saja.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('units.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>

            <a href="{{ route('units.edit', $unit->id) }}" class="btn btn--primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                Edit Unit
            </a>
        </div>
    </div>

    <div class="form-alert form-alert--info">
        <strong>Mode baca saja.</strong>
        <span>Gunakan tombol edit jika ingin mengubah data unit ini.</span>
    </div>

    <div class="detail-card glass-card">
        <div class="wizard-form-grid">

<label class="form-field">
    <span>Name</span>
    <input type="text" value="{{ $unit->name }}" disabled>
</label>

<label class="form-field">
    <span>Symbol</span>
    <input type="text" value="{{ $unit->symbol }}" disabled>
</label>

<label class="form-field">
    <span>Status</span>
    <input type="text" value="{{ $unit->is_active ? 'Active' : 'Inactive' }}" disabled>
</label>

<label class="form-field">
    <span>Updated At</span>
    <input type="text" value="{{ $unit->updated_at ? $unit->updated_at->format('d M Y H:i') : '-' }}" disabled>
</label>

        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

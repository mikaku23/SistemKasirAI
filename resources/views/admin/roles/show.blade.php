@extends('template-admin.layout')

@section('title', 'Detail Role')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
<section class="page-card glass-card role-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">ROLES</p>
            <h2>Detail Role</h2>
            <p>Seluruh data ditampilkan dalam mode baca saja.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('roles.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>

            <a href="{{ route('roles.edit', $role->id) }}" class="btn btn--primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                Edit Role
            </a>
        </div>
    </div>

    <div class="form-alert form-alert--info">
        <strong>Mode baca saja.</strong>
        <span>Gunakan tombol edit jika ingin mengubah data role ini.</span>
    </div>

    <div class="detail-card glass-card">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Name</span>
                <input type="text" value="{{ $role->name }}" disabled>
            </label>

            <label class="form-field">
                <span>Slug</span>
                <input type="text" value="{{ $role->slug }}" disabled>
            </label>

            <label class="form-field form-field--full">
                <span>Description</span>
                <textarea rows="4" disabled>{{ $role->description }}</textarea>
            </label>

            <label class="form-field">
                <span>Status</span>
                <input type="text" value="{{ $role->is_active ? 'Active' : 'Inactive' }}" disabled>
            </label>

            <label class="form-field">
                <span>Updated At</span>
                <input type="text" value="{{ $role->updated_at ? $role->updated_at->format('d M Y H:i') : '-' }}" disabled>
            </label>
        </div>
    </div>
</section>
@endsection

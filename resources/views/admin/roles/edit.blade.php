@extends('template-admin.layout')

@section('title', 'Edit Role')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
<section class="page-card glass-card role-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">ROLES</p>
            <h2>Edit Role</h2>
            <p>Ubah data role dengan alur bertahap sebelum disimpan kembali.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('roles.index') }}" class="btn btn--secondary">
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
        action="{{ route('roles.update', $role->id) }}"
        method="POST"
        class="wizard-form page-form"
        data-step-form
        data-draft-key="roles:edit:{{ $role->id }}"
        data-confirm-form
        data-confirm-title="Simpan perubahan?"
        data-confirm-message="Data role yang diubah akan disimpan ke database. Lanjutkan?"
        data-confirm-variant="warn"
        data-confirm-icon="fa-solid fa-floppy-disk">
        @csrf
        @method('PUT')

        <input type="hidden" name="id" value="{{ $role->id }}">

        <div class="stepper">
            <span class="step active" data-step-indicator="1">1</span>
            <span class="step" data-step-indicator="2">2</span>
            <span class="step" data-step-indicator="3">3</span>
        </div>

        <div class="wizard-body">
            <section class="wizard-step active" data-step="1">
                <div class="wizard-step__head">
                    <h4>Basic Info</h4>
                    <p>Ubah identity role dengan aman.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Name</span>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name', $role->name) }}"
                            placeholder="Admin"
                            required
                            autocomplete="off"
                            data-autoslug-source="name"
                            data-autoslug-target="slug">
                    </label>

                    <label class="form-field">
                        <span>Slug</span>
                        <input
                            type="text"
                            name="slug"
                            value="{{ old('slug', $role->slug) }}"
                            placeholder="admin"
                            required
                            autocomplete="off">
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="wizard-step__head">
                    <h4>Description & Status</h4>
                    <p>Sesuaikan deskripsi dan status aktif role.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field form-field--full">
                        <span>Description</span>
                        <textarea
                            name="description"
                            rows="4"
                            placeholder="Role description...">{{ old('description', $role->description) }}</textarea>
                    </label>

                    <label class="form-field">
                        <span>Status</span>
                        <select name="is_active" required>
                            <option value="1" {{ old('is_active', $role->is_active ? 1 : 0) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', $role->is_active ? 1 : 0) == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="3">
                <div class="wizard-step__head">
                    <h4>Review & Submit</h4>
                    <p>Pastikan hasil perubahan sudah benar sebelum disimpan.</p>
                </div>

                <div class="review-grid">
                    <div class="review-item">
                        <span>Nama Role</span>
                        <strong data-review-field="name">{{ old('name', $role->name) }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Slug</span>
                        <strong data-review-field="slug">{{ old('slug', $role->slug) }}</strong>
                    </div>
                    <div class="review-item review-item--full">
                        <span>Description</span>
                        <p data-review-field="description">{{ old('description', $role->description) ?: '-' }}</p>
                    </div>
                    <div class="review-item">
                        <span>Status</span>
                        <strong data-review-field="status">{{ old('is_active', $role->is_active ? 1 : 0) == 1 ? 'Active' : 'Inactive' }}</strong>
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
                    Update Role
                </button>
            </div>
        </div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

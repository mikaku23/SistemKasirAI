@extends('template-admin.layout')

@section('title', 'Edit User')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $user = $user ?? [];
    $roles = $roles ?? [];
    $locations = $locations ?? [];
@endphp

<section class="page-card glass-card role-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">USERS</p>
            <h2>Edit User</h2>
            <p>Ubah data user bertahap sebelum disimpan kembali.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('users.index') }}" class="btn btn--secondary">
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
        action="{{ route('users.update', $user['id']) }}"
        method="POST"
        enctype="multipart/form-data"
        class="wizard-form page-form"
        data-step-form
        data-draft-key="users:edit:{{ $user['id'] }}"
        data-confirm-form
        data-confirm-title="Simpan perubahan user?"
        data-confirm-message="Data user yang diubah akan disimpan ke database. Lanjutkan?"
        data-confirm-variant="warn"
        data-confirm-icon="fa-solid fa-floppy-disk"
>
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
                    <h4>Basic Identity</h4>
                    <p>Pilih role, lokasi, nama, username, dan avatar user.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Role</span>
                        <select name="role_id" required>
                            <option value="">Pilih role</option>
                            @forelse ($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $user['role_id'] ?? '') == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @empty
                                <option value="" disabled>Tidak ada data role</option>
                            @endforelse
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Location</span>
                        <select name="location_id">
                            <option value="">Pilih lokasi</option>
                            @forelse ($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id', $user['location_id'] ?? '') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @empty
                                <option value="" disabled>Tidak ada data lokasi</option>
                            @endforelse
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Name</span>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name', $user['name'] ?? '') }}"
                            placeholder="Nama user"
                            required
                            autocomplete="off"
                            data-autoslug-source="name"
                            data-autoslug-target="username">
                    </label>

                    <label class="form-field">
                        <span>Username</span>
                        <input
                            type="text"
                            name="username"
                            value="{{ old('username', $user['username'] ?? '') }}"
                            placeholder="username"
                            required
                            autocomplete="off">
                    </label>

                    <label class="form-field form-field--full">
                        <span>Avatar</span>
                        <input
                            type="file"
                            name="avatar"
                            accept=".jpg,.jpeg,.png,.webp"
                            data-draft-skip="1">
                        <small class="muted">Biarkan kosong jika avatar lama tidak ingin diganti.</small>
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="wizard-step__head">
                    <h4>Access & Contact</h4>
                    <p>Lengkapi email, nomor HP, dan kredensial user.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Email</span>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email', $user['email'] ?? '') }}"
                            placeholder="user@email.com"
                            autocomplete="off">
                    </label>

                    <label class="form-field">
                        <span>No HP</span>
                        <input
                            type="text"
                            name="no_hp"
                            value="{{ old('no_hp', $user['no_hp'] ?? '') }}"
                            placeholder="08xxxxxxxxxx"
                            required
                            autocomplete="off">
                    </label>

                    <label class="form-field">
                        <span>Password</span>
                        <input
                            type="password"
                            name="password"
                            value=""
                            placeholder="Kosongkan jika tidak diubah"
                            autocomplete="new-password"
                            data-draft-skip="1"
                            data-review-mask="1">
                    </label>

                    <label class="form-field">
                        <span>Security Question</span>
                        <input
                            type="text"
                            name="security_question"
                            value="{{ old('security_question', $user['security_question'] ?? '') }}"
                            placeholder="Pertanyaan keamanan"
                            autocomplete="off">
                    </label>

                    <label class="form-field form-field--full">
                        <span>Security Answer</span>
                        <input
                            type="text"
                            name="security_answer"
                            value="{{ old('security_answer', $user['security_answer'] ?? '') }}"
                            placeholder="Jawaban keamanan"
                            autocomplete="off"
                            data-draft-skip="1"
                            data-review-mask="1">
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="3">
                <div class="wizard-step__head">
                    <h4>System Data & Review</h4>
                    <p>Tambahkan data sistem dan cek ringkasan sebelum disimpan.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Last Login At</span>
                        <input
                            type="datetime-local"
                            name="last_login_at"
                            value="{{ old('last_login_at', $user['last_login_at_input'] ?? '') }}"
                            autocomplete="off">
                    </label>

                    <label class="form-field">
                        <span>Last Password Changed At</span>
                        <input
                            type="datetime-local"
                            name="last_password_changed_at"
                            value="{{ old('last_password_changed_at', $user['last_password_changed_at_input'] ?? '') }}"
                            autocomplete="off">
                    </label>

                    <label class="form-field">
                        <span>Status</span>
                        <select name="is_active" required>
                            <option value="1" {{ old('is_active', $user['is_active'] ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', $user['is_active'] ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </label>
                </div>

                <div class="review-grid">
                    <div class="review-item">
                        <span>Role</span>
                        <strong data-review-field="role_name" data-review-source="role_id">{{ $user['role_name'] ?? '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Location</span>
                        <strong data-review-field="location_name" data-review-source="location_id">{{ $user['location_name'] ?? '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Name</span>
                        <strong data-review-field="name">{{ $user['name'] ?? '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Username</span>
                        <strong data-review-field="username">{{ $user['username'] ?? '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Email</span>
                        <strong data-review-field="email">{{ $user['email'] ?? '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>No HP</span>
                        <strong data-review-field="no_hp">{{ $user['no_hp'] ?? '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Avatar</span>
                        <strong data-review-field="avatar_name" data-review-source="avatar">-</strong>
                    </div>
                    <div class="review-item">
                        <span>Last Login</span>
                        <strong data-review-field="last_login_at" data-review-source="last_login_at">-</strong>
                    </div>
                    <div class="review-item">
                        <span>Password Changed</span>
                        <strong data-review-field="last_password_changed_at" data-review-source="last_password_changed_at">-</strong>
                    </div>
                    <div class="review-item">
                        <span>Status</span>
                        <strong data-review-field="status" data-review-source="is_active">{{ ($user['is_active'] ?? 1) ? 'Active' : 'Inactive' }}</strong>
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
                    Update User
                </button>
            </div>
        </div>
    </form>
</section>

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

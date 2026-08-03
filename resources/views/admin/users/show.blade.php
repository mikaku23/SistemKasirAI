@extends('template-admin.layout')

@section('title', 'Detail User')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $user = $user ?? [];
@endphp

<section class="page-card glass-card role-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">USERS</p>
            <h2>Detail User</h2>
            <p>Seluruh data ditampilkan dalam mode baca saja.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('users.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>

            <a href="{{ route('users.edit', $user['id']) }}" class="btn btn--primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                Edit User
            </a>
        </div>
    </div>

    <div class="profile-chip" style="margin-bottom: 0.25rem;">
        @if (!empty($user['avatar_url']))
            <div
                class="avatar avatar--large"
                style="background-image:url('{{ $user['avatar_url'] }}'); background-size:cover; background-position:center;"></div>
        @else
            <div class="avatar avatar--large">{{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}</div>
        @endif

        <div>
            <strong>{{ $user['name'] ?? '-' }}</strong>
            <small>{{ $user['role_name'] ?? '-' }} • {{ $user['location_name'] ?? '-' }}</small>
        </div>
    </div>

    <div class="form-alert form-alert--info">
        <strong>Mode baca saja.</strong>
        <span>Gunakan tombol edit jika ingin mengubah data user ini.</span>
    </div>

    <div class="detail-card glass-card">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Role</span>
                <input type="text" value="{{ $user['role_name'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Location</span>
                <input type="text" value="{{ $user['location_name'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Name</span>
                <input type="text" value="{{ $user['name'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Username</span>
                <input type="text" value="{{ $user['username'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Email</span>
                <input type="text" value="{{ $user['email'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>No HP</span>
                <input type="text" value="{{ $user['no_hp'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Security Question</span>
                <input type="text" value="{{ $user['security_question'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Security Answer</span>
                <input type="text" value="{{ ($user['security_answer'] ?? '') !== '' ? 'Tersimpan' : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Last Login At</span>
                <input type="text" value="{{ $user['last_login_at_input'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Last Password Changed At</span>
                <input type="text" value="{{ $user['last_password_changed_at_input'] ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Status</span>
                <input type="text" value="{{ ($user['is_active'] ?? 1) ? 'Active' : 'Inactive' }}" disabled>
            </label>

            <label class="form-field">
                <span>Updated At</span>
                <input type="text" value="{{ $user['updated_at'] ?? '-' }}" disabled>
            </label>
        </div>
    </div>
</section>
@endsection

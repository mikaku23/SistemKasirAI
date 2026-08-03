@extends('template-admin.layout')

@section('title', 'Recycle Users')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $trashedUsers = $trashedUsers ?? [];
@endphp

<section class="page-card glass-card role-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">USERS</p>
            <h2>Recycle Bin</h2>
            <p>Daftar user yang sudah dihapus sementara. User bisa dipulihkan atau dihapus permanen.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('users.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RECYCLE</p>
                <h3>Data user terhapus</h3>
            </div>

            <label class="search-box" for="userRecycleSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="userRecycleSearch"
                    placeholder="Search deleted user..."
                    data-table-search-target="#usersRecycleTable">
            </label>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="usersRecycleTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Deleted At</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trashedUsers as $user)
                        <tr
                            data-search-text="{{ strtolower(trim(
                                ($user->name ?? '') . ' ' .
                                ($user->username ?? '') . ' ' .
                                ($user->role?->name ?? '') . ' ' .
                                ($user->email ?? '') . ' ' .
                                ($user->no_hp ?? '')
                            )) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="profile-chip">
                                    @if ($user->avatar)
                                        <div
                                            class="avatar"
                                            style="background-image:url('{{ asset('storage/' . $user->avatar) }}'); background-size:cover; background-position:center;"></div>
                                    @else
                                        <div class="avatar">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</div>
                                    @endif

                                    <div>
                                        <strong>{{ $user->name }}</strong>
                                        <small>{{ $user->email ?: ($user->no_hp ?: '-') }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="mono-chip">{{ $user->username }}</span></td>
                            <td>{{ $user->role?->name ?? '-' }}</td>
                            <td>{{ $user->deleted_at ? $user->deleted_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <form
                                        action="{{ route('users.restore', $user->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Pulihkan user?"
                                        data-confirm-message="User ini akan dikembalikan dari recycle bin. Lanjutkan pemulihan?"
                                        data-confirm-variant="info"
                                        data-confirm-icon="fa-solid fa-trash-arrow-up">
                                        @csrf
                                        <button type="submit" class="icon-btn icon-btn--success" aria-label="Restore user">
                                            <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                        </button>
                                    </form>

                                    <form
                                        action="{{ route('users.forceDelete', $user->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus permanen?"
                                        data-confirm-message="User ini akan dihapus permanen dan tidak bisa dipulihkan lagi. Lanjutkan?"
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-triangle-exclamation">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Force delete user">
                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state__icon">
                                        <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                    </div>
                                    <strong>Recycle bin masih kosong.</strong>
                                    <p>User yang dihapus sementara akan muncul di sini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#usersRecycleTable">
            <button class="btn btn--secondary table-pagination__btn" type="button" data-page-action="prev">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                Back
            </button>

            <div class="table-pagination__info" data-page-info>Showing 0-0 of 0</div>

            <button class="btn btn--secondary table-pagination__btn" type="button" data-page-action="next">
                Next
                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

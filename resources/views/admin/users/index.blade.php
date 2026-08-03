@extends('template-admin.layout')

@section('title', 'Daftar Users')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $users = $users ?? [];
@endphp

<section class="page-card glass-card role-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">USERS</p>
            <h2>Daftar Users</h2>
            <p>Menampilkan seluruh data users dengan aksi lihat, edit, hapus, dan akses ke recycle bin.</p>
        </div>

        <div class="page-card__actions">
            <label class="search-box" for="userSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="userSearch"
                    placeholder="Search user..."
                    data-table-search-target="#usersTable">
            </label>

            <label class="filter-box" for="userStatusFilter">
                <span>Status</span>
                <select id="userStatusFilter" data-table-filter-target="#usersTable">
                    <option value="">Semua status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>

            <a href="{{ route('users.recycle') }}" class="btn btn--ghost">
                <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                Recycle
            </a>

            <a href="{{ route('users.create') }}" class="btn btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Tambah User
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">USERS</p>
                <h3>Tabel data users</h3>
            </div>

            <button
                class="btn btn--secondary"
                type="button"
                data-action="export-table"
                data-target="#usersTable"
                data-export-name="users">
                Export
            </button>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="usersTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Location</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr
                            data-user-row
                            data-status="{{ $user->is_active ? 'active' : 'inactive' }}"
                            data-search-text="{{ strtolower(trim(
                                ($user->name ?? '') . ' ' .
                                ($user->username ?? '') . ' ' .
                                ($user->role?->name ?? '') . ' ' .
                                ($user->location?->name ?? '') . ' ' .
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
                            <td>{{ $user->location?->name ?? '-' }}</td>
                            <td>
                                <div class="table-meta">
                                    <strong>{{ $user->no_hp }}</strong>
                                    <small>{{ $user->email ?: '-' }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="status-pill {{ $user->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $user->updated_at ? $user->updated_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a href="{{ route('users.show', $user->id) }}" class="icon-btn" aria-label="Show user">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <a href="{{ route('users.edit', $user->id) }}" class="icon-btn" aria-label="Edit user">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>

                                    <form
                                        action="{{ route('users.destroy', $user->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus user?"
                                        data-confirm-message="User ini akan dipindahkan ke recycle bin. Lanjutkan penghapusan?"
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-trash-can">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Delete user">
                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-state__icon">
                                        <i class="fa-solid fa-users" aria-hidden="true"></i>
                                    </div>
                                    <strong>Belum ada data users.</strong>
                                    <p>Data user yang ditambahkan akan tampil di sini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#usersTable">
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

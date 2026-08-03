@extends('template-admin.layout')

@section('title', 'Daftar Roles')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $roles = $roles ?? [];
@endphp

<section class="page-card glass-card role-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">ROLES</p>
            <h2>Daftar Roles</h2>
            <p>Menampilkan seluruh data roles dengan aksi lihat, edit, hapus, dan akses ke recycle bin.</p>
        </div>

        <div class="page-card__actions">
            <label class="search-box" for="roleSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="roleSearch"
                    placeholder="Search role..."
                    data-table-search-target="#rolesTable">
            </label>

            <label class="filter-box" for="roleStatusFilter">
                <span>Status</span>
                <select id="roleStatusFilter" data-table-filter-target="#rolesTable">
                    <option value="">Semua status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>

            <a href="{{ route('roles.recycle') }}" class="btn btn--ghost">
                <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                Recycle
            </a>

            <a href="{{ route('roles.create') }}" class="btn btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Tambah Role
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">ROLES</p>
                <h3>Tabel data roles</h3>
            </div>

            <button
                class="btn btn--secondary"
                type="button"
                data-action="export-table"
                data-target="#rolesTable">
                Export
            </button>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="rolesTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr
                            data-role-row
                            data-status="{{ $role->is_active ? 'active' : 'inactive' }}"
                            data-search-text="{{ strtolower(trim(($role->name ?? '') . ' ' . ($role->slug ?? '') . ' ' . ($role->description ?? '') . ' ' . ($role->is_active ? 'active' : 'inactive'))) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">{{ $role->name }}</td>
                            <td><span class="mono-chip">{{ $role->slug }}</span></td>
                            <td class="td-description">{{ $role->description ?: '-' }}</td>
                            <td>
                                <span class="status-pill {{ $role->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                    {{ $role->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $role->updated_at ? $role->updated_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a
                                        href="{{ route('roles.show', $role->id) }}"
                                        class="icon-btn"
                                        aria-label="Show role">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <a
                                        href="{{ route('roles.edit', $role->id) }}"
                                        class="icon-btn"
                                        aria-label="Edit role">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>

                                    <form
                                        action="{{ route('roles.destroy', $role->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus role?"
                                        data-confirm-message="Role ini akan dipindahkan ke recycle bin. Lanjutkan proses hapus?"
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-trash">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Delete role">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-state__icon">
                                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    </div>
                                    <strong>Belum ada data roles.</strong>
                                    <p>Tekan tombol <b>Tambah Role</b> untuk membuat data pertama.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#rolesTable">
            <button type="button" class="btn btn--secondary table-pagination__btn" data-page-action="prev">
                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                Back
            </button>

            <div class="table-pagination__info" data-page-info>
                Showing 0-0 of 0
            </div>

            <button type="button" class="btn btn--secondary table-pagination__btn" data-page-action="next">
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

@extends('template-admin.layout')

@section('title', 'Daftar Suppliers')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $suppliers = $suppliers ?? [];
@endphp

<section class="page-card glass-card role-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">SUPPLIERS</p>
            <h2>Daftar Suppliers</h2>
            <p>Menampilkan seluruh data supplier dengan aksi lihat, edit, hapus, dan akses ke recycle bin.</p>
        </div>

        <div class="page-card__actions">
            <label class="search-box" for="supplierSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="supplierSearch"
                    placeholder="Search supplier..."
                    data-table-search-target="#suppliersTable">
            </label>

            <label class="filter-box" for="supplierStatusFilter">
                <span>Status</span>
                <select id="supplierStatusFilter" data-table-filter-target="#suppliersTable">
                    <option value="">Semua status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>

            <a href="{{ route('suppliers.recycle') }}" class="btn btn--ghost">
                <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                Recycle
            </a>

            <a href="{{ route('suppliers.create') }}" class="btn btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Tambah Supplier
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">SUPPLIERS</p>
                <h3>Tabel data suppliers</h3>
            </div>

            <button
                class="btn btn--secondary"
                type="button"
                data-action="export-table"
                data-target="#suppliersTable"
                data-export-name="suppliers">
                Export
            </button>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="suppliersTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Supplier</th>
                        <th>Code</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr
                            data-supplier-row
                            data-status="{{ $supplier->is_active ? 'active' : 'inactive' }}"
                            data-search-text="{{ strtolower(trim(
                                ($supplier->name ?? '') . ' ' .
                                ($supplier->code ?? '') . ' ' .
                                ($supplier->phone ?? '') . ' ' .
                                ($supplier->email ?? '') . ' ' .
                                ($supplier->address ?? '') . ' ' .
                                ($supplier->notes ?? '')
                            )) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="profile-chip">
                                    <div class="avatar">{{ strtoupper(substr($supplier->name ?? 'S', 0, 1)) }}</div>
                                    <div>
                                        <strong>{{ $supplier->name }}</strong>
                                        <small>{{ $supplier->address ?: ($supplier->phone ?: '-') }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="mono-chip">{{ $supplier->code }}</span></td>
                            <td>
                                <div class="table-meta">
                                    <strong>{{ $supplier->phone ?: '-' }}</strong>
                                    <small>{{ $supplier->address ?: '-' }}</small>
                                </div>
                            </td>
                            <td>{{ $supplier->email ?: '-' }}</td>
                            <td>
                                <span class="status-pill {{ $supplier->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                    {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $supplier->updated_at ? $supplier->updated_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a href="{{ route('suppliers.show', $supplier->id) }}" class="icon-btn" aria-label="Show supplier">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <a href="{{ route('suppliers.edit', $supplier->id) }}" class="icon-btn" aria-label="Edit supplier">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>

                                    <form
                                        action="{{ route('suppliers.destroy', $supplier->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus supplier?"
                                        data-confirm-message="Supplier ini akan dipindahkan ke recycle bin. Lanjutkan penghapusan?"
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-trash-can">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Delete supplier">
                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-state__icon">
                                        <i class="fa-solid fa-truck-field" aria-hidden="true"></i>
                                    </div>
                                    <strong>Belum ada data suppliers.</strong>
                                    <p>Data supplier yang ditambahkan akan tampil di sini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#suppliersTable">
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

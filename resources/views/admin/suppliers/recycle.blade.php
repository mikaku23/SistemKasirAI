@extends('template-admin.layout')

@section('title', 'Recycle Suppliers')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $trashedSuppliers = $trashedSuppliers ?? [];
@endphp

<section class="page-card glass-card role-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">SUPPLIERS</p>
            <h2>Recycle Bin</h2>
            <p>Daftar supplier yang sudah dihapus sementara. Supplier bisa dipulihkan atau dihapus permanen.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('suppliers.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RECYCLE</p>
                <h3>Data supplier terhapus</h3>
            </div>

            <label class="search-box" for="supplierRecycleSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="supplierRecycleSearch"
                    placeholder="Search deleted supplier..."
                    data-table-search-target="#suppliersRecycleTable">
            </label>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="suppliersRecycleTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Supplier</th>
                        <th>Code</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Deleted At</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trashedSuppliers as $supplier)
                        <tr
                            data-search-text="{{ strtolower(trim(
                                ($supplier->name ?? '') . ' ' .
                                ($supplier->code ?? '') . ' ' .
                                ($supplier->phone ?? '') . ' ' .
                                ($supplier->email ?? '') . ' ' .
                                ($supplier->address ?? '') . ' ' .
                                ($supplier->notes ?? '')
                            )) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $supplier->name }}</td>
                            <td><span class="mono-chip">{{ $supplier->code }}</span></td>
                            <td>{{ $supplier->phone ?: '-' }}</td>
                            <td>{{ $supplier->email ?: '-' }}</td>
                            <td>{{ $supplier->deleted_at ? $supplier->deleted_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <form
                                        action="{{ route('suppliers.restore', $supplier->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Pulihkan supplier?"
                                        data-confirm-message="Supplier ini akan dikembalikan dari recycle bin. Lanjutkan pemulihan?"
                                        data-confirm-variant="info"
                                        data-confirm-icon="fa-solid fa-trash-arrow-up">
                                        @csrf
                                        <button type="submit" class="icon-btn icon-btn--success" aria-label="Restore supplier">
                                            <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                        </button>
                                    </form>

                                    <form
                                        action="{{ route('suppliers.forceDelete', $supplier->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus permanen?"
                                        data-confirm-message="Supplier ini akan dihapus permanen dan tidak bisa dipulihkan lagi. Lanjutkan?"
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-triangle-exclamation">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Force delete supplier">
                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
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
                                        <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                    </div>
                                    <strong>Recycle bin masih kosong.</strong>
                                    <p>Supplier yang dihapus sementara akan muncul di sini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#suppliersRecycleTable">
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

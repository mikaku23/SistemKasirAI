@extends('template-admin.layout')

@section('title', 'Daftar Unit')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $units = $units ?? [];
    $unitsStats = $unitsStats ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'trashed' => 0];
@endphp

<section class="page-card glass-card unit-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">UNITS</p>
            <h2>Daftar Unit</h2>
            <p>Menampilkan seluruh data unit dengan aksi lihat, edit, hapus, dan akses ke recycle bin.</p>
        </div>

        <div class="page-card__actions">
            <label class="search-box" for="unitSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="unitSearch"
                    placeholder="Search unit..."
                    data-table-search-target="#unitsTable">
            </label>

            <label class="filter-box" for="unitStatusFilter">
                <span>Status</span>
                <select id="unitStatusFilter" data-table-filter-target="#unitsTable">
                    <option value="">Semua status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>

            <a href="{{ route('units.recycle') }}" class="btn btn--ghost">
                <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                Recycle
            </a>

            <a href="{{ route('units.create') }}" class="btn btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Tambah Unit
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">UNITS</p>
                <h3>Tabel data unit</h3>
            </div>

            <button
                class="btn btn--secondary"
                type="button"
                data-action="export-table"
                data-target="#unitsTable">
                Export
            </button>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="unitsTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Symbol</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($units as $unit)
                        <tr
                            data-unit-row
                            data-status="{{ $unit->is_active ? 'active' : 'inactive' }}"
                            data-search-text="{{ strtolower(trim(($unit->name ?? '') . ' ' . ($unit->symbol ?? '') . ' ' . ($unit->description ?? '') . ' ' . ($unit->is_active ? 'active' : 'inactive'))) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">{{ $unit->name }}</td>
                            <td><span class="mono-chip">{{ $unit->symbol }}</span></td>
                            <td class="td-description">{{ $unit->description ?: '-' }}</td>
                            <td>
                                <span class="status-pill {{ $unit->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                    {{ $unit->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $unit->updated_at ? $unit->updated_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a
                                        href="{{ route('units.show', $unit->id) }}"
                                        class="icon-btn"
                                        aria-label="Show unit">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <a
                                        href="{{ route('units.edit', $unit->id) }}"
                                        class="icon-btn"
                                        aria-label="Edit unit">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>

                                    <form
                                        action="{{ route('units.destroy', $unit->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus unit?"
                                        data-confirm-message="Unit ini akan dipindahkan ke recycle bin. Lanjutkan proses hapus?"
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-trash">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Delete unit">
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
                                    <strong>Belum ada data unit.</strong>
                                    <p>Tekan tombol <b>Tambah Unit</b> untuk membuat data pertama.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#unitsTable">
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

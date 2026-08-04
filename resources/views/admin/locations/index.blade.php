@extends('template-admin.layout')

@section('title', 'Daftar Location')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $locations = $locations ?? [];
    $locationStats = $locationStats ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'in_use' => 0, 'trashed' => 0];
@endphp

<section class="page-card glass-card location-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">LOCATIONS</p>
            <h2>Daftar Location</h2>
            <p>Menampilkan lokasi operasional yang dipakai untuk users, stok, transaksi, dan mutasi.</p>
        </div>

        <div class="page-card__actions">
            <label class="search-box" for="locationSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="locationSearch"
                    placeholder="Search location..."
                    data-table-search-target="#locationsTable">
            </label>

            <label class="filter-box" for="locationStatusFilter">
                <span>Status</span>
                <select id="locationStatusFilter" data-table-filter-target="#locationsTable">
                    <option value="">Semua status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>

            <a href="{{ route('locations.recycle') }}" class="btn btn--ghost">
                <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                Recycle
            </a>

            <a href="{{ route('locations.create') }}" class="btn btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Tambah Location
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">LOCATIONS</p>
                <h3>Tabel data location</h3>
            </div>

            <button
                class="btn btn--secondary"
                type="button"
                data-action="export-table"
                data-target="#locationsTable">
                Export
            </button>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="locationsTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Used By</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($locations as $location)
                        <tr
                            data-location-row
                            data-status="{{ $location->is_active ? 'active' : 'inactive' }}"
                            data-search-text="{{ strtolower(trim(
                                ($location->code ?? '') . ' ' .
                                ($location->name ?? '') . ' ' .
                                ($location->address ?? '') . ' ' .
                                ($location->phone ?? '') . ' ' .
                                ($location->is_active ? 'active' : 'inactive')
                            )) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td><span class="mono-chip">{{ $location->code }}</span></td>
                            <td class="td-strong">{{ $location->name }}</td>
                            <td class="td-description">{{ $location->address ?: '-' }}</td>
                            <td>{{ $location->phone ?: '-' }}</td>
                            <td>
                                <div class="mini-chips">
                                    <span class="status-pill status-pill--muted">U: {{ $location->users_count ?? 0 }}</span>
                                    <span class="status-pill status-pill--muted">P: {{ $location->products_count ?? 0 }}</span>
                                    <span class="status-pill status-pill--muted">B: {{ $location->stock_batches_count ?? 0 }}</span>
                                </div>
                            </td>
                            <td>
                                <span class="status-pill {{ $location->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                    {{ $location->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $location->updated_at ? $location->updated_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a
                                        href="{{ route('locations.show', $location->id) }}"
                                        class="icon-btn"
                                        aria-label="Show location">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <a
                                        href="{{ route('locations.edit', $location->id) }}"
                                        class="icon-btn"
                                        aria-label="Edit location">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>

                                    <form
                                        action="{{ route('locations.destroy', $location->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus location?"
                                        data-confirm-message="Location ini akan dipindahkan ke recycle bin. Lanjutkan proses hapus?"
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-trash">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Delete location">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
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
                                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    </div>
                                    <strong>Belum ada data location.</strong>
                                    <p>Tekan tombol <b>Tambah Location</b> untuk membuat data pertama.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#locationsTable">
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

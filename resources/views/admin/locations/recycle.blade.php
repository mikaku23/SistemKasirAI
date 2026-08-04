@extends('template-admin.layout')

@section('title', 'Recycle Location')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $trashedLocations = $trashedLocations ?? [];
@endphp

<section class="page-card glass-card location-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">LOCATIONS</p>
            <h2>Recycle Bin</h2>
            <p>Daftar location yang sudah dihapus sementara. Location bisa dipulihkan atau dihapus permanen.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('locations.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RECYCLE</p>
                <h3>Data location terhapus</h3>
            </div>

            <label class="search-box" for="locationRecycleSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="locationRecycleSearch"
                    placeholder="Search deleted location..."
                    data-table-search-target="#locationsRecycleTable">
            </label>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="locationsRecycleTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Deleted At</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trashedLocations as $location)
                        <tr data-search-text="{{ strtolower(trim(($location->code ?? '') . ' ' . ($location->name ?? '') . ' ' . ($location->address ?? ''))) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td><span class="mono-chip">{{ $location->code }}</span></td>
                            <td class="td-strong">{{ $location->name }}</td>
                            <td class="td-description">{{ $location->address ?: '-' }}</td>
                            <td>{{ $location->deleted_at ? $location->deleted_at->format('d M Y H:i') : '-' }}</td>
                            <td>
                                <div class="inline-actions">
                                    <form
                                        action="{{ route('locations.restore', $location->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Pulihkan location?"
                                        data-confirm-message="Location ini akan dikembalikan dari recycle bin. Lanjutkan pemulihan?"
                                        data-confirm-variant="info"
                                        data-confirm-icon="fa-solid fa-trash-arrow-up">
                                        @csrf
                                        <button type="submit" class="icon-btn icon-btn--success" aria-label="Restore location">
                                            <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                        </button>
                                    </form>

                                    <form
                                        action="{{ route('locations.forceDelete', $location->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus permanen?"
                                        data-confirm-message="Location ini akan dihapus permanen dan tidak bisa dipulihkan lagi. Lanjutkan?"
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-triangle-exclamation">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Force delete location">
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
                                    <p>Location yang dihapus sementara akan muncul di sini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

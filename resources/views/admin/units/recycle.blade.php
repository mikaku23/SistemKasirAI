@extends('template-admin.layout')

@section('title', 'Recycle Unit')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $trashedUnits = $trashedUnits ?? [];
@endphp

<section class="page-card glass-card unit-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">UNITS</p>
            <h2>Recycle Bin</h2>
            <p>Daftar data yang sudah dihapus sementara. Data bisa dipulihkan atau dihapus permanen.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('units.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RECYCLE</p>
                <h3>Data terhapus</h3>
            </div>

            <label class="search-box" for="unitRecycleSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="unitRecycleSearch"
                    placeholder="Search deleted data..."
                    data-table-search-target="#unitsRecycleTable">
            </label>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="unitsRecycleTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Symbol</th>
                        <th>Description</th>
                        <th>Deleted At</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trashedUnits as $unit)
                        <tr data-search-text="{{ strtolower(trim(($unit->name ?? '') . ' ' . ($unit->symbol ?? '') . ' ' . ($unit->description ?? ''))) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">{{ $unit->name }}</td>
                            <td><span class="mono-chip">{{ $unit->symbol }}</span></td>
                            <td class="td-description">{{ $unit->description ?: '-' }}</td>
                            <td>{{ $unit->deleted_at ? $unit->deleted_at->format('d M Y H:i') : '-' }}</td>
                            <td>
                                <div class="inline-actions">
                                    <form
                                        action="{{ route('units.restore', $unit->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Pulihkan data?"
                                        data-confirm-message="Data ini akan dikembalikan dari recycle bin. Lanjutkan pemulihan?"
                                        data-confirm-variant="info"
                                        data-confirm-icon="fa-solid fa-trash-arrow-up">
                                        @csrf
                                        <button type="submit" class="icon-btn icon-btn--success" aria-label="Restore data">
                                            <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                        </button>
                                    </form>

                                    <form
                                        action="{{ route('units.forceDelete', $unit->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus permanen?"
                                        data-confirm-message="Data ini akan dihapus permanen dan tidak bisa dipulihkan lagi. Lanjutkan?"
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-triangle-exclamation">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Force delete data">
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
                                    <p>Data yang dihapus sementara akan muncul di sini.</p>
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

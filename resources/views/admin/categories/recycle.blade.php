@extends('template-admin.layout')

@section('title', 'Recycle Kategori')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $trashedCategories = $trashedCategories ?? [];
@endphp

<section class="page-card glass-card category-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">CATEGORIES</p>
            <h2>Recycle Bin</h2>
            <p>Daftar kategori yang sudah dihapus sementara. Data bisa dipulihkan atau dihapus permanen.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('categories.index') }}" class="btn btn--secondary"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Kembali</a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RECYCLE</p>
                <h3>Data kategori terhapus</h3>
            </div>

            <label class="search-box" for="categoryRecycleSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="categoryRecycleSearch" placeholder="Search deleted category..." data-table-search-target="#categoriesRecycleTable">
            </label>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="categoriesRecycleTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Deleted At</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trashedCategories as $category)
                        <tr data-search-text="{{ strtolower(trim(($category->name ?? '') . ' ' . ($category->sku ?? '') . ' ' . ($category->slug ?? '') . ' ' . ($category->description ?? ''))) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">{{ $category->name }}</td>
                            <td><span class="mono-chip">{{ $category->sku }}</span></td>
                            <td><span class="mono-chip">{{ $category->slug }}</span></td>
                            <td class="td-description">{{ $category->description ?: '-' }}</td>
                            <td>{{ $category->deleted_at ? $category->deleted_at->format('d M Y H:i') : '-' }}</td>
                            <td>
                                <div class="inline-actions">
                                    <form action="{{ route('categories.restore', $category->id) }}" method="POST" class="inline-form" data-confirm-form data-confirm-title="Pulihkan kategori?" data-confirm-message="Kategori ini akan dikembalikan dari recycle bin. Lanjutkan pemulihan?" data-confirm-variant="info" data-confirm-icon="fa-solid fa-trash-arrow-up">
                                        @csrf
                                        <button type="submit" class="icon-btn icon-btn--success" aria-label="Restore kategori"><i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i></button>
                                    </form>

                                    <form action="{{ route('categories.forceDelete', $category->id) }}" method="POST" class="inline-form" data-confirm-form data-confirm-title="Hapus permanen?" data-confirm-message="Kategori ini akan dihapus permanen dan tidak bisa dipulihkan lagi. Lanjutkan?" data-confirm-variant="danger" data-confirm-icon="fa-solid fa-triangle-exclamation">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Force delete kategori"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-state__icon"><i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i></div>
                                    <strong>Recycle bin masih kosong.</strong>
                                    <p>Kategori yang dihapus sementara akan muncul di sini.</p>
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

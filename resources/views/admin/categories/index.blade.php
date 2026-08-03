@extends('template-admin.layout')

@section('title', 'Daftar Kategori')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $categories = $categories ?? [];
    $categoriesStats = $categoriesStats ?? ['total' => 0, 'active' => 0, 'inactive' => 0, 'trashed' => 0];
@endphp

<section class="page-card glass-card category-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">CATEGORIES</p>
            <h2>Daftar Kategori</h2>
            <p>Menampilkan seluruh data kategori dengan SKU dasar untuk pembentukan SKU produk.</p>
        </div>

        <div class="page-card__actions">
            <label class="search-box" for="categorySearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="categorySearch" placeholder="Search category..." data-table-search-target="#categoriesTable">
            </label>

            <label class="filter-box" for="categoryStatusFilter">
                <span>Status</span>
                <select id="categoryStatusFilter" data-table-filter-target="#categoriesTable">
                    <option value="">Semua status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>

            <a href="{{ route('categories.recycle') }}" class="btn btn--ghost">
                <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                Recycle
            </a>

            <a href="{{ route('categories.create') }}" class="btn btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Tambah Kategori
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">CATEGORIES</p>
                <h3>Tabel data kategori</h3>
            </div>

            <button class="btn btn--secondary" type="button" data-action="export-table" data-target="#categoriesTable">Export</button>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="categoriesTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr data-category-row data-status="{{ $category->is_active ? 'active' : 'inactive' }}" data-search-text="{{ strtolower(trim(($category->name ?? '') . ' ' . ($category->sku ?? '') . ' ' . ($category->slug ?? '') . ' ' . ($category->description ?? '') . ' ' . ($category->is_active ? 'active' : 'inactive'))) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">{{ $category->name }}</td>
                            <td><span class="mono-chip">{{ $category->sku }}</span></td>
                            <td><span class="mono-chip">{{ $category->slug }}</span></td>
                            <td class="td-description">{{ $category->description ?: '-' }}</td>
                            <td><span class="status-pill {{ $category->is_active ? 'status-pill--success' : 'status-pill--muted' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>{{ $category->updated_at ? $category->updated_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a href="{{ route('categories.show', $category->id) }}" class="icon-btn" aria-label="Show kategori"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                                    <a href="{{ route('categories.edit', $category->id) }}" class="icon-btn" aria-label="Edit kategori"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></a>
                                    <form action="{{ route('categories.destroy', $category->id) }}" method="POST" class="inline-form" data-confirm-form data-confirm-title="Hapus kategori?" data-confirm-message="Kategori ini akan dipindahkan ke recycle bin. Lanjutkan proses hapus?" data-confirm-variant="danger" data-confirm-icon="fa-solid fa-trash">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Delete kategori"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-state__icon"><i class="fa-solid fa-circle-info" aria-hidden="true"></i></div>
                                    <strong>Belum ada data kategori.</strong>
                                    <p>Tekan tombol <b>Tambah Kategori</b> untuk membuat data pertama.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#categoriesTable">
            <button type="button" class="btn btn--secondary table-pagination__btn" data-page-action="prev"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i> Back</button>
            <div class="table-pagination__info" data-page-info>Showing 0-0 of 0</div>
            <button type="button" class="btn btn--secondary table-pagination__btn" data-page-action="next">Next <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
        </div>
    </div>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

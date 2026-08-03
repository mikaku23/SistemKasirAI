@extends('template-admin.layout')

@section('title', 'Daftar Produk')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $products = $products ?? [];
    $productStats = $productStats ?? [
        'total' => 0,
        'active' => 0,
        'featured' => 0,
        'tracked_expiry' => 0,
        'expiring_soon' => 0,
        'expired' => 0,
        'trashed' => 0,
    ];
@endphp

<section class="page-card glass-card product-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">PRODUCTS</p>
            <h2>Daftar Produk</h2>
            <p>Menampilkan seluruh data produk dengan relasi kategori, unit, supplier, lokasi, gambar, dan status expiry.</p>
        </div>

        <div class="page-card__actions">
            <label class="search-box" for="productSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="productSearch"
                    placeholder="Search product..."
                    data-table-search-target="#productsTable">
            </label>

            <label class="filter-box" for="productStatusFilter">
                <span>Status</span>
                <select id="productStatusFilter" data-table-filter-target="#productsTable">
                    <option value="">Semua status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>

            <a href="{{ route('products.recycle') }}" class="btn btn--ghost">
                <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                Recycle
            </a>

            <a href="{{ route('products.create') }}" class="btn btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Tambah Produk
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">PRODUCTS</p>
                <h3>Tabel data produk</h3>
            </div>

            <button
                class="btn btn--secondary"
                type="button"
                data-action="export-table"
                data-target="#productsTable">
                Export
            </button>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="productsTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Name</th>
                       
                        <th>Expiry</th>
                        <th>Sale Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr
                            data-product-row
                            data-status="{{ $product->is_active ? 'active' : 'inactive' }}"
                            data-search-text="{{ strtolower(trim(
                                ($product->name ?? '') . ' ' .
                                (optional($product->category)->name ?? '') . ' ' .
                                (optional($product->unit)->name ?? '') . ' ' .
                                (optional($product->supplier)->name ?? '') . ' ' .
                                (optional($product->location)->name ?? '') . ' ' .
                                ($product->sku ?? '') . ' ' .
                                ($product->barcode ?? '') . ' ' .
                                ($product->expiry_status_label ?? '') . ' ' .
                                ($product->resolved_expiry_at ?? '') . ' ' .
                                (is_array($product->search_keywords) ? implode(' ', $product->search_keywords) : '') . ' ' .
                                ($product->is_active ? 'active' : 'inactive')
                            )) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @if ($product->image)
                                    <img
                                        src="{{ Storage::disk('public')->url($product->image) }}"
                                        alt="{{ $product->name }}"
                                        style="width: 44px; height: 44px; object-fit: cover; border-radius: 12px;">
                                @else
                                    <span class="mono-chip">No image</span>
                                @endif
                            </td>
                            <td class="td-strong">{{ optional($product->category)->name ?? '-' }}</td>
                            <td>{{ optional($product->unit)->symbol ?? '-' }}</td>
                            <td class="td-strong">{{ $product->name }}</td>
                          
                            <td>
                                <div>
                                    <span class="status-pill {{ $product->expiry_status_class }}">{{ $product->expiry_status_label }}</span>
                                    <small style="display:block; margin-top:6px;">
                                        {{ $product->expiry_summary }}
                                        @if ($product->resolved_expiry_at)
                                            · {{ \Illuminate\Support\Carbon::parse($product->resolved_expiry_at)->format('d M Y') }}
                                        @endif
                                    </small>
                                </div>
                            </td>
                            <td>{{ $product->sale_price !== null ? 'Rp ' . number_format((float) $product->sale_price, 0, ',', '.') : '-' }}</td>
                            <td>{{ $product->stock_on_hand !== null ? number_format((int) $product->stock_on_hand, 0, ',', '.') : '-' }}</td>
                            <td>
                                <span class="status-pill {{ $product->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                    {{ $product->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $product->updated_at ? $product->updated_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <a
                                        href="{{ route('products.show', $product->id) }}"
                                        class="icon-btn"
                                        aria-label="Show product">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <a
                                        href="{{ route('products.edit', $product->id) }}"
                                        class="icon-btn"
                                        aria-label="Edit product">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </a>

                                    <form
                                        action="{{ route('products.destroy', $product->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus produk?"
                                        data-confirm-message="Produk ini akan dipindahkan ke recycle bin. Lanjutkan proses hapus?"
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-trash">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Delete product">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="13">
                                <div class="empty-state">
                                    <div class="empty-state__icon">
                                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    </div>
                                    <strong>Belum ada data produk.</strong>
                                    <p>Tekan tombol <b>Tambah Produk</b> untuk membuat data pertama.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination" data-table-pagination-target="#productsTable">
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

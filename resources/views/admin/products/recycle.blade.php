@extends('template-admin.layout')

@section('title', 'Recycle Produk')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $trashedProducts = $trashedProducts ?? [];
@endphp

<section class="page-card glass-card product-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">PRODUCTS</p>
            <h2>Recycle Bin</h2>
            <p>Daftar produk yang sudah dihapus sementara. Data bisa dipulihkan atau dihapus permanen.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('products.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RECYCLE</p>
                <h3>Data produk terhapus</h3>
            </div>

            <label class="search-box" for="productRecycleSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="productRecycleSearch"
                    placeholder="Search deleted product..."
                    data-table-search-target="#productsRecycleTable">
            </label>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="productsRecycleTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Image</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Name</th>
                        <th>SKU</th>
                        <th>Barcode</th>
                        <th>Expiry</th>
                        <th>Deleted At</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trashedProducts as $product)
                        <tr data-search-text="{{ strtolower(trim(
                            ($product->name ?? '') . ' ' .
                            (optional($product->category)->name ?? '') . ' ' .
                            (optional($product->unit)->name ?? '') . ' ' .
                            (optional($product->supplier)->name ?? '') . ' ' .
                            (optional($product->location)->name ?? '') . ' ' .
                            ($product->sku ?? '') . ' ' .
                            ($product->barcode ?? '') . ' ' .
                            ($product->expiry_status_label ?? '') . ' ' .
                            ($product->resolved_expiry_at ?? '')
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
                            <td><span class="mono-chip">{{ $product->sku ?: '-' }}</span></td>
                            <td>{{ $product->barcode ?: '-' }}</td>
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
                            <td>{{ $product->deleted_at ? $product->deleted_at->format('d M Y H:i') : '-' }}</td>
                            <td>
                                <div class="inline-actions">
                                    <form
                                        action="{{ route('products.restore', $product->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Pulihkan produk?"
                                        data-confirm-message="Produk ini akan dikembalikan dari recycle bin. Lanjutkan pemulihan?"
                                        data-confirm-variant="info"
                                        data-confirm-icon="fa-solid fa-trash-arrow-up">
                                        @csrf
                                        <button type="submit" class="icon-btn icon-btn--success" aria-label="Restore product">
                                            <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                        </button>
                                    </form>

                                    <form
                                        action="{{ route('products.forceDelete', $product->id) }}"
                                        method="POST"
                                        class="inline-form"
                                        data-confirm-form
                                        data-confirm-title="Hapus permanen?"
                                        data-confirm-message="Produk ini akan dihapus permanen dan tidak bisa dipulihkan lagi. Lanjutkan?"
                                        data-confirm-variant="danger"
                                        data-confirm-icon="fa-solid fa-triangle-exclamation">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Force delete product">
                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr data-empty-row>
                            <td colspan="10">
                                <div class="empty-state">
                                    <div class="empty-state__icon">
                                        <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                    </div>
                                    <strong>Recycle bin masih kosong.</strong>
                                    <p>Produk yang dihapus sementara akan muncul di sini.</p>
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

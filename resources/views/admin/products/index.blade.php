@extends('template-admin.layout')

@section('title', 'Daftar Produk')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">

<style>
    .adjustment-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
        background: rgba(3, 7, 18, 0.62);
        z-index: 1080;
    }

    .adjustment-modal.is-open {
        display: flex;
    }

    .adjustment-modal__panel {
        width: min(100%, 920px);
        max-height: min(92vh, 920px);
        overflow: auto;
        border-radius: 24px;
        background: #0f172a;
        color: #e5e7eb;
        box-shadow: 0 28px 90px rgba(0, 0, 0, 0.35);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .adjustment-modal__head,
    .adjustment-modal__body,
    .adjustment-modal__foot {
        padding: 18px 20px;
    }

    .adjustment-modal__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .adjustment-modal__title h3 {
        margin: 0;
        font-size: 22px;
    }

    .adjustment-modal__title p {
        margin: 4px 0 0;
        color: rgba(229, 231, 235, 0.72);
        font-size: 13px;
    }

    .adjustment-modal__close {
        border: 0;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        width: 40px;
        height: 40px;
        border-radius: 12px;
        cursor: pointer;
    }

    .adjustment-card {
        padding: 14px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .adjustment-card span {
        display: block;
        font-size: 12px;
        color: rgba(229, 231, 235, 0.7);
        margin-bottom: 6px;
    }

    .adjustment-card strong {
        display: block;
        font-size: 18px;
    }

    .adjustment-meta {
        display: grid;
        gap: 14px;
        margin-top: 14px;
    }

    .expiry-trigger {
        border: 0;
        cursor: pointer;
        text-align: left;
    }

    .expiry-trigger:focus-visible {
        outline: 2px solid rgba(99, 102, 241, 0.75);
        outline-offset: 2px;
    }

    @media (max-width: 768px) {
        .adjustment-modal {
            padding: 12px;
        }
    }
</style>

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

       <div class="stats-grid">
        <div class="stat-card glass-card">
            <span>Total</span>
            <strong>{{ $productStats['total'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Active</span>
            <strong>{{ $productStats['active'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Tracked Expiry</span>
            <strong>{{ $productStats['tracked_expiry'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Expiring Soon</span>
            <strong>{{ $productStats['expiring_soon'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Expired</span>
            <strong>{{ $productStats['expired'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Recycle</span>
            <strong>{{ $productStats['trashed'] }}</strong>
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
                                <div style="display:grid; gap:6px;">
                                    @if ((int) $product->expiry_snapshot_count > 0)
                                        <button
                                            type="button"
                                            class="status-pill expiry-trigger {{ $product->expiry_status_class }}"
                                            style="border:0; width:fit-content;"
                                            data-product-expiry-trigger
                                            data-product-name="{{ e($product->name) }}"
                                            data-product-expiry='@json($product->expiry_snapshot_items)'>
                                            {{ $product->expiry_snapshot_count }} data expiry
                                        </button>
                                        <small style="display:block;">
                                            {{ $product->expiry_summary }}
                                            @if ($product->resolved_expiry_at)
                                                · {{ \Illuminate\Support\Carbon::parse($product->resolved_expiry_at)->format('d M Y') }}
                                            @endif
                                        </small>
                                    @else
                                        <span class="status-pill {{ $product->expiry_status_class }}">{{ $product->expiry_status_label }}</span>
                                        <small style="display:block; margin-top:6px;">
                                            {{ $product->expiry_summary }}
                                            @if ($product->resolved_expiry_at)
                                                · {{ \Illuminate\Support\Carbon::parse($product->resolved_expiry_at)->format('d M Y') }}
                                            @endif
                                        </small>
                                    @endif
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

                                    <a
                                        href="{{ route('products.print-barcode', $product->id) }}"
                                        class="icon-btn"
                                        aria-label="Print barcode"
                                        title="Print barcode"
                                        target="_blank"
                                        rel="noopener">
                                        <i class="fa-solid fa-barcode" aria-hidden="true"></i>
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

<div class="adjustment-modal" id="productExpiryModal" aria-hidden="true">
    <div class="adjustment-modal__panel" role="dialog" aria-modal="true" aria-labelledby="productExpiryModalTitle">
        <div class="adjustment-modal__head">
            <div class="adjustment-modal__title">
                <h3 id="productExpiryModalTitle">Detail expiry batches</h3>
                <p id="productExpiryModalSubtitle">Klik angka expiry untuk melihat detail per batch.</p>
            </div>
            <button type="button" class="adjustment-modal__close" data-product-expiry-close aria-label="Tutup">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div class="adjustment-modal__body">
            <div id="productExpiryList" class="adjustment-meta" style="grid-template-columns: 1fr; gap: 12px;"></div>
        </div>
        <div class="adjustment-modal__foot">
            <button type="button" class="btn btn--secondary" data-product-expiry-close>Tutup</button>
        </div>
    </div>
</div>

@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('productExpiryModal');
    const subtitle = document.getElementById('productExpiryModalSubtitle');
    const list = document.getElementById('productExpiryList');

    const statusOrder = {
        grace_period: 0,
        expired: 1,
        expires_today: 2,
        expiring_soon: 3,
        active: 4,
        depleted: 5,
        no_tracking: 6,
        sync_pending: 6,
        unknown: 7,
    };

    const formatQty = (value) => {
        const number = Number(value ?? 0);
        return Number.isFinite(number) ? number.toLocaleString('id-ID') : '-';
    };

    const renderItem = (item) => {
        const card = document.createElement('div');
        card.className = 'adjustment-card';
        card.innerHTML = `
            <span>${item.batch_code || '-'}</span>
            <strong>${item.expiry_status_label || 'Unknown'}</strong>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <span class="status-pill ${item.expiry_status_class || 'status-pill--muted'}">${item.expiry_summary || '-'}</span>
            </div>
            <div class="mt-2" style="font-size: 13px; line-height: 1.6;">
                <div>Qty remaining: <strong>${formatQty(item.qty_remaining)}</strong></div>
                <div>Expired at: <strong>${item.resolved_expiry_at || '-'}</strong></div>
                <div>Ditambahkan: <strong>${item.added_at_label || item.added_at || '-'}</strong></div>
                <div>Oleh: <strong>${item.added_by_name || '-'}</strong></div>
                <div>Source: <strong>${item.source_label || '-'}</strong></div>
            </div>
        `;
        return card;
    };

    const openModal = (productName, items) => {
        const ordered = [...items].sort((a, b) => {
            const ar = statusOrder[a.expiry_status] ?? 99;
            const br = statusOrder[b.expiry_status] ?? 99;
            if (ar !== br) return ar - br;
            const ad = Number(a.expiry_days_left ?? 99999);
            const bd = Number(b.expiry_days_left ?? 99999);
            if (ad !== bd) return ad - bd;
            return Number(a.batch_id ?? 0) - Number(b.batch_id ?? 0);
        });

        subtitle.textContent = `${productName} · ${ordered.length} batch expiry`;
        list.innerHTML = '';
        ordered.forEach(item => list.appendChild(renderItem(item)));
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    };

    document.querySelectorAll('[data-product-expiry-trigger]').forEach((button) => {
        button.addEventListener('click', () => {
            const items = JSON.parse(button.dataset.productExpiry || '[]');
            openModal(button.dataset.productName || '-', items);
        });
    });

    document.querySelectorAll('[data-product-expiry-close]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
});
</script>

@endsection

@extends('template-admin.layout')

@section('title', 'Create Supplier Return')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
<style>
    .return-builder { display: grid; gap: 18px; }
    .return-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .return-items { display: grid; gap: 12px; }
    .return-item-row { display: grid; grid-template-columns: 2fr 2fr 1fr 1fr 1.5fr auto; gap: 10px; align-items: end; padding: 14px; border: 1px solid rgba(255,255,255,.10); border-radius: 18px; background: rgba(255,255,255,.03); }
    .return-item-row .form-field { margin: 0; }
    .return-item-row select, .return-item-row input, .return-item-row textarea { width: 100%; }
    .return-item-row textarea { min-height: 42px; }
    .return-toolbar { display:flex; gap:12px; flex-wrap:wrap; justify-content:space-between; align-items:center; }
    .return-summary { display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
    .return-hint { padding: 14px 16px; border-radius: 18px; border: 1px dashed rgba(255,255,255,.16); background: rgba(255,255,255,.03); }
    .return-hint strong { display:block; margin-bottom: 4px; }
    .return-field-note { font-size: 12px; opacity: .78; margin-top: 6px; }
    .return-auto-field[disabled] { opacity: .9; cursor: not-allowed; }
    @media (max-width: 1100px) {
        .return-item-row { grid-template-columns: 1fr 1fr; }
        .return-grid, .return-summary { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
@php
    $suppliers = $suppliers ?? [];
    $locations = $locations ?? [];
    $products = collect($products ?? []);
    $stockBatches = collect($stockBatches ?? []);
    $oldItems = old('items', []);

    $productPayload = $products->map(function ($product) {
        return [
            'id' => (int) $product->id,
            'supplier_id' => (int) ($product->supplier_id ?? 0),
            'location_id' => (int) ($product->location_id ?? 0),
            'supplier_name' => optional($product->supplier)->name,
            'location_name' => optional($product->location)->name,
            'batch_count' => (int) ($product->batch_count ?? 0),
            'qty_total' => (int) ($product->qty_total ?? 0),
            'label' => trim(($product->name ?? '-') . ($product->sku ? ' · ' . $product->sku : '') . ' · ' . (optional($product->supplier)->name ?? '-') . ' · ' . (optional($product->location)->name ?? '-') . (($product->batch_count ?? 0) ? ' · batch ' . (int) $product->batch_count : '') . (($product->qty_total ?? 0) ? ' · stok ' . (int) $product->qty_total : '')), 
        ];
    })->values();

    $batchPayload = $stockBatches->map(function ($batch) {
        return [
            'id' => (int) $batch->id,
            'product_id' => (int) $batch->product_id,
            'supplier_id' => (int) ($batch->supplier_id ?? 0),
            'location_id' => (int) ($batch->location_id ?? 0),
            'qty_remaining' => (int) $batch->qty_remaining,
            'purchase_price' => (int) $batch->purchase_price,
            'product_name' => optional($batch->product)->name,
            'product_sku' => optional($batch->product)->sku,
            'product_category' => optional(optional($batch->product)->category)->name,
            'supplier_name' => optional($batch->supplier)->name,
            'location_name' => optional($batch->location)->name,
            'label' => trim((optional($batch->product)->name ?? '-') . ' · ' . ($batch->batch_code ?? '-') . ' · ' . (optional($batch->supplier)->name ?? '-') . ' · ' . (optional($batch->location)->name ?? '-') . ' · sisa ' . (int) $batch->qty_remaining),
        ];
    })->values();
@endphp

<section class="page-card glass-card">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">SUPPLIER RETURNS</p>
            <h2>Buat Return ke Supplier</h2>
            <p>Konsep ini mengarsipkan batch aktif secara penuh. Saat return disimpan, batch akan soft delete, stock product dihitung ulang, dan semua jejak masuk ke stock movement.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('supplier-returns.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="form-alert form-alert--danger">
            <strong>Periksa kembali data yang diisi.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('supplier-returns.store') }}" method="POST" class="return-builder" data-supplier-return-form>
        @csrf

        <div class="return-grid">
            <label class="form-field">
                <span>Supplier</span>
                <select name="supplier_id" id="supplier_id" required>
                    <option value="">Pilih supplier</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (string) old('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="form-field">
                <span>Location</span>
                <select name="location_id" id="location_id" required>
                    <option value="">Pilih location</option>
                    @foreach ($locations as $location)
                        <option value="{{ $location->id }}" {{ (string) old('location_id') === (string) $location->id ? 'selected' : '' }}>
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="form-field">
                <span>Return At</span>
                <input type="datetime-local" name="return_at" value="{{ old('return_at', now()->format('Y-m-d\\TH:i')) }}" required>
            </label>

            <label class="form-field">
                <span>Reason</span>
                <textarea name="reason" rows="4" required>{{ old('reason') }}</textarea>
            </label>
        </div>

        <div class="return-hint" data-filter-hint>
            <strong>Menunggu filter</strong>
            <p>Pilih supplier dan location agar product dan batch yang cocok muncul. Qty akan terkunci mengikuti sisa stok batch aktif karena satu batch akan diarsipkan penuh.</p>
        </div>

        <div class="table-card glass-card">
            <div class="return-toolbar">
                <div>
                    <p class="eyebrow">ITEMS</p>
                    <h3>Daftar item return</h3>
                </div>

                <button type="button" class="btn btn--secondary" id="addReturnItem" disabled>
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Tambah Baris
                </button>
            </div>

            <div class="return-items" id="returnItems"></div>
        </div>

        <div class="table-card glass-card">
            <div class="return-summary">
                <div class="review-item">
                    <strong>Total Item</strong>
                    <div id="summaryItemCount">0</div>
                </div>
                <div class="review-item">
                    <strong>Total Qty</strong>
                    <div id="summaryQty">0</div>
                </div>
                <div class="review-item">
                    <strong>Total Estimasi</strong>
                    <div id="summaryAmount">Rp 0</div>
                </div>
            </div>
        </div>

        <div class="page-card__actions">
            <button type="submit" class="btn btn--primary">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                Simpan Return
            </button>
        </div>
    </form>
</section>

<template id="returnItemTemplate">
    <div class="return-item-row" data-return-item>
        <label class="form-field">
            <span>Product</span>
            <select data-field="product_id" required>
                <option value="">Pilih product</option>
            </select>
        </label>

        <label class="form-field">
            <span>Stock Batch</span>
            <select data-field="stock_batch_id" required>
                <option value="">Pilih batch</option>
            </select>
        </label>

        <label class="form-field">
            <span>Qty</span>
            <input type="number" min="1" step="1" data-field="quantity" value="1" readonly required>
            <div class="return-field-note">Auto sama dengan sisa batch aktif.</div>
        </label>

        <label class="form-field">
            <span>Unit Price</span>
            <input type="number" min="0" step="1" data-field="unit_price" placeholder="Auto">
            <div class="return-field-note">Default ambil purchase price batch.</div>
        </label>

        <label class="form-field">
            <span>Notes</span>
            <textarea rows="2" data-field="notes" placeholder="Opsional"></textarea>
        </label>

        <button type="button" class="btn btn--ghost" data-remove-item>
            <i class="fa-solid fa-trash" aria-hidden="true"></i>
        </button>
    </div>
</template>

<script>
    window.__SUPPLIER_RETURN_CONTEXT__ = {
        productCatalog: @json($productPayload),
        batchCatalog: @json($batchPayload),
        oldItems: @json($oldItems),
    };
</script>
<script src="{{ asset('assets/js/SupplierReturn.js') }}"></script>
@endsection

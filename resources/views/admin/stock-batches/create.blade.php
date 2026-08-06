@extends('template-admin.layout')

@section('title', 'Tambah Batch Stok')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $products = $products ?? [];
    $suppliers = $suppliers ?? [];
    $locations = $locations ?? [];
    $users = $users ?? [];

    $selectedProductId = old('product_id');
    $selectedSupplierId = old('supplier_id');
    $selectedLocationId = old('location_id');
    $selectedReceiverId = old('received_by', auth()->id());

    $selectedProduct = collect($products)->firstWhere('id', (int) $selectedProductId);
    $selectedSupplier = collect($suppliers)->firstWhere('id', (int) $selectedSupplierId);
    $selectedLocation = collect($locations)->firstWhere('id', (int) $selectedLocationId);
    $selectedReceiver = collect($users)->firstWhere('id', (int) $selectedReceiverId);

    $formatQty = function ($value) {
        if ($value === null || $value === '') {
            return '-';
        }

        $float = (float) $value;
        $decimals = abs($float - round($float)) < 0.00001 ? 0 : 2;

        return number_format($float, $decimals, ',', '.');
    };

    $formatMoney = function ($value) {
        if ($value === null || $value === '') {
            return '-';
        }

        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    };
@endphp

<section class="page-card glass-card product-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">STOCK BATCHES</p>
            <h2>Tambah Batch Stok</h2>
            <p>Isi data penerimaan stok pada langkah 1 dan 2. Langkah 3 hanya untuk review ulang sebelum disimpan.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('stock-batches.index') }}" class="btn btn--secondary">
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

    <form
        action="{{ route('stock-batches.store') }}"
        method="POST"
        class="wizard-form page-form"
        data-step-form
        data-draft-key="stock-batches:create">
        @csrf

        <input type="hidden" name="received_by" value="{{ old('received_by', auth()->id()) }}">

        <div class="stepper">
            <span class="step active" data-step-indicator="1">1</span>
            <span class="step" data-step-indicator="2">2</span>
            <span class="step" data-step-indicator="3">3</span>
        </div>

        <div class="wizard-body">
            <section class="wizard-step active" data-step="1">
                <div class="wizard-step__head">
                    <h4>Receipt & Identity</h4>
                    <p>Pilih produk lalu catat batch, qty, dan informasi penerimaan.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Product</span>
                        <select name="product_id" required>
                            <option value="">Pilih product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" data-location-id="{{ $product->location_id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                    @if ($product->sku)
                                        ({{ $product->sku }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Supplier</span>
                        <select name="supplier_id">
                            <option value="">-- Optional --</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Location</span>
                        <select name="location_id">
                            <option value="">-- Optional --</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Received By</span>
                        <select name="received_by">
                            <option value="">-- Auto from login --</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ old('received_by', auth()->id()) == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <div class="form-field form-field--full">
                        <span>Smart Batch Identity</span>
                        <div class="form-alert form-alert--info">
                            <strong>Batch Code</strong>
                            <p>BCH-{ID}-{received_at}-{qty_received}-{production_date}</p>
                            <strong>Lot Number</strong>
                            <p>LOT-{SKU_PREFIX}-{SUPPLIER_PREFIX}-{received_at}</p>
                            <small>Nilai final digenerate otomatis saat disimpan. Qty Remaining juga otomatis mengikuti Qty Received.</small>
                        </div>
                    </div>

                    <label class="form-field">
                        <span>Qty Received</span>
                        <input type="number" name="qty_received" value="{{ old('qty_received') }}" min="1" step="1" placeholder="20" required>
                    </label>

                    <label class="form-field">
                        <span>Purchase Price</span>
                        <input type="number" name="purchase_price" value="{{ old('purchase_price') }}" min="0" step="1" placeholder="60000">
                    </label>

                    <label class="form-field">
                        <span>Received At</span>
                        <input type="date" name="received_at" value="{{ old('received_at', now()->toDateString()) }}">
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="wizard-step__head">
                    <h4>Smart Expiry</h4>
                    <p>Pilih mode expiry, lalu isi data yang diperlukan untuk batch ini.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Expiry Mode</span>
                        <select name="expiry_mode" required>
                            <option value="none" {{ old('expiry_mode', 'fixed_date') === 'none' ? 'selected' : '' }}>No Tracking</option>
                            <option value="fixed_date" {{ old('expiry_mode', 'fixed_date') === 'fixed_date' ? 'selected' : '' }}>Fixed Date</option>
                            <option value="shelf_life" {{ old('expiry_mode') === 'shelf_life' ? 'selected' : '' }}>Shelf Life</option>
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Production Date</span>
                        <input type="date" name="production_date" value="{{ old('production_date') }}">
                    </label>

                    <label class="form-field">
                        <span>Expired At</span>
                        <input type="date" name="expired_at" value="{{ old('expired_at') }}">
                    </label>

                    <label class="form-field">
                        <span>Shelf Life Days</span>
                        <input type="number" name="shelf_life_days" value="{{ old('shelf_life_days') }}" min="1" step="1" placeholder="365">
                    </label>

                    <label class="form-field">
                        <span>Expiry Warning Days</span>
                        <input type="number" name="expiry_warning_days" value="{{ old('expiry_warning_days', 30) }}" min="0" step="1" placeholder="30">
                    </label>

                    <label class="form-field">
                        <span>Expiry Grace Days</span>
                        <input type="number" name="expiry_grace_days" value="{{ old('expiry_grace_days', 0) }}" min="0" step="1" placeholder="0">
                    </label>

                    <label class="form-field form-field--full">
                        <span>Notes</span>
                        <textarea name="notes" rows="4" placeholder="Catatan penerimaan barang...">{{ old('notes') }}</textarea>
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="3">
                <div class="wizard-step__head">
                    <h4>Review & Submit</h4>
                    <p>Langkah ini hanya untuk pengecekan ulang. Tidak ada field input tambahan.</p>
                </div>

                <div class="review-grid">
                    <div class="review-item">
                        <span>Product</span>
                        <strong data-review-field="product">{{ optional($selectedProduct)->name ?? '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Supplier</span>
                        <strong data-review-field="supplier">{{ optional($selectedSupplier)->name ?? '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Location</span>
                        <strong data-review-field="location">{{ optional($selectedLocation)->name ?? '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Received By</span>
                        <strong data-review-field="received_by">{{ optional($selectedReceiver)->name ?? '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Batch Code</span>
                        <strong data-review-field="batch_code">Auto after save</strong>
                    </div>
                    <div class="review-item">
                        <span>Lot Number</span>
                        <strong data-review-field="lot_number">Auto after save</strong>
                    </div>
                    <div class="review-item">
                        <span>Qty Received</span>
                        <strong data-review-field="qty_received">{{ old('qty_received') ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Qty Remaining</span>
                        <strong data-review-field="qty_remaining">{{ old('qty_received') ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Purchase Price</span>
                        <strong data-review-field="purchase_price">{{ old('purchase_price') !== null && old('purchase_price') !== '' ? 'Rp ' . number_format((float) old('purchase_price'), 0, ',', '.') : '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Received At</span>
                        <strong data-review-field="received_at">{{ old('received_at') ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Expiry Mode</span>
                        <strong data-review-field="expiry_mode">{{ old('expiry_mode', 'fixed_date') === 'none' ? 'No Tracking' : (old('expiry_mode', 'fixed_date') === 'shelf_life' ? 'Shelf Life' : 'Fixed Date') }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Production Date</span>
                        <strong data-review-field="production_date">{{ old('production_date') ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Expired At</span>
                        <strong data-review-field="expired_at">{{ old('expired_at') ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Shelf Life Days</span>
                        <strong data-review-field="shelf_life_days">{{ old('shelf_life_days') ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Warning Days</span>
                        <strong data-review-field="expiry_warning_days">{{ old('expiry_warning_days', 30) }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Grace Days</span>
                        <strong data-review-field="expiry_grace_days">{{ old('expiry_grace_days', 0) }}</strong>
                    </div>
                    <div class="review-item review-item--full">
                        <span>Notes</span>
                        <p data-review-field="notes">{{ old('notes') ?: '-' }}</p>
                    </div>
                </div>
            </section>
        </div>

        <div class="wizard-actions">
            <button class="btn btn--secondary" type="button" data-step-action="prev">Back</button>

            <div class="wizard-actions__right">
                <button class="btn btn--ghost" type="button" data-step-action="skip">Skip</button>
                <button class="btn btn--primary" type="button" data-step-action="next">Next</button>
                <button class="btn btn--primary" type="submit" data-step-submit hidden>
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Simpan Batch
                </button>
            </div>
        </div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
<script src="{{ asset('assets/js/location-product-filter.js') }}"></script>
@endsection

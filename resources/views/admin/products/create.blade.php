@extends('template-admin.layout')

@section('title', 'Create Produk')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $categories = $categories ?? [];
    $units = $units ?? [];
    $suppliers = $suppliers ?? [];
    $locations = $locations ?? [];
    $product = $product ?? null;

    $categoryMap = collect($categories)->pluck('name', 'id')->all();
    $unitMap = collect($units)->mapWithKeys(fn ($unit) => [$unit->id => trim($unit->name . ' (' . $unit->symbol . ')')])->all();
    $supplierMap = collect($suppliers)->pluck('name', 'id')->all();
    $locationMap = collect($locations)->pluck('name', 'id')->all();

    $selectedCategoryId = old('category_id', $product?->category_id);
    $selectedUnitId = old('unit_id', $product?->unit_id);
    $selectedSupplierId = old('supplier_id', $product?->supplier_id);
    $selectedLocationId = old('location_id', $product?->location_id);

    $selectedCategoryLabel = $selectedCategoryId ? ($categoryMap[$selectedCategoryId] ?? '-') : '-';
    $selectedUnitLabel = $selectedUnitId ? ($unitMap[$selectedUnitId] ?? '-') : '-';
    $selectedSupplierLabel = $selectedSupplierId ? ($supplierMap[$selectedSupplierId] ?? '-') : '-';
    $selectedLocationLabel = $selectedLocationId ? ($locationMap[$selectedLocationId] ?? '-') : '-';

    $selectedTracksExpiry = old('tracks_expiry', 1);
    $selectedExpiryType = old('expiry_type', $product?->expiry_type ?: 'none');
    $selectedProductionDate = old('production_date', $product?->production_date?->format('Y-m-d') ?? '');
    $selectedExpiredAt = old('expired_at', $product?->expired_at?->format('Y-m-d') ?? '');
    $selectedShelfLifeDays = old('shelf_life_days', $product?->shelf_life_days);
    $selectedWarningDays = old('expiry_warning_days', $product?->expiry_warning_days ?? 30);
    $selectedGraceDays = old('expiry_grace_days', $product?->expiry_grace_days ?? 0);

    $reviewResolvedExpiryAt = '-';
    $reviewDaysLeft = null;
    $reviewExpiryStatus = 'sync_pending';
    $reviewExpiryStatusLabel = 'Auto from batches';
    $reviewExpiryStatusClass = 'status-pill--muted';

    if ((int) $selectedTracksExpiry === 1) {
        if ($selectedExpiryType === 'fixed_date' && $selectedExpiredAt) {
            $reviewResolvedExpiryAt = $selectedExpiredAt;
            $reviewDaysLeft = now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($reviewResolvedExpiryAt)->startOfDay(), false);
            if ($reviewDaysLeft < 0) {
                $reviewExpiryStatus = abs($reviewDaysLeft) <= (int) $selectedGraceDays ? 'grace_period' : 'expired';
            } elseif ($reviewDaysLeft === 0) {
                $reviewExpiryStatus = 'expires_today';
            } elseif ($reviewDaysLeft <= (int) $selectedWarningDays) {
                $reviewExpiryStatus = 'expiring_soon';
            } else {
                $reviewExpiryStatus = 'safe';
            }
        } elseif ($selectedExpiryType === 'shelf_life' && $selectedProductionDate && $selectedShelfLifeDays !== null && $selectedShelfLifeDays !== '') {
            $reviewResolvedExpiryAt = \Illuminate\Support\Carbon::parse($selectedProductionDate)
                ->startOfDay()
                ->addDays((int) $selectedShelfLifeDays)
                ->format('Y-m-d');

            $reviewDaysLeft = now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($reviewResolvedExpiryAt)->startOfDay(), false);
            if ($reviewDaysLeft < 0) {
                $reviewExpiryStatus = abs($reviewDaysLeft) <= (int) $selectedGraceDays ? 'grace_period' : 'expired';
            } elseif ($reviewDaysLeft === 0) {
                $reviewExpiryStatus = 'expires_today';
            } elseif ($reviewDaysLeft <= (int) $selectedWarningDays) {
                $reviewExpiryStatus = 'expiring_soon';
            } else {
                $reviewExpiryStatus = 'safe';
            }
        } elseif ($selectedExpiryType === 'none') {
            $reviewExpiryStatus = 'sync_pending';
            $reviewExpiryStatusLabel = 'Auto from batches';
            $reviewExpiryStatusClass = 'status-pill--muted';
        }
    }

    $reviewExpiryStatusLabel = match ($reviewExpiryStatus) {
        'expired' => 'Expired',
        'grace_period' => 'Grace Period',
        'expires_today' => 'Expires Today',
        'expiring_soon' => 'Expiring Soon',
        'safe' => 'Safe',
        'sync_pending' => 'Auto from batches',
        default => 'Tidak dilacak',
    };

    $reviewExpiryStatusClass = match ($reviewExpiryStatus) {
        'expired' => 'status-pill--danger',
        'grace_period', 'expires_today', 'expiring_soon' => 'status-pill--warning',
        'safe' => 'status-pill--success',
        'sync_pending' => 'status-pill--muted',
        default => 'status-pill--muted',
    };

    $reviewExpirySummary = match ($reviewExpiryStatus) {
        'expired' => $reviewDaysLeft !== null ? 'Lewat ' . abs($reviewDaysLeft) . ' hari' : '-',
        'grace_period' => $reviewDaysLeft !== null ? 'Grace period, lewat ' . abs($reviewDaysLeft) . ' hari' : '-',
        'expires_today' => 'Expired hari ini',
        'expiring_soon' => $reviewDaysLeft === 1 ? 'Sisa 1 hari' : 'Sisa ' . ($reviewDaysLeft ?? 0) . ' hari',
        'safe' => $reviewDaysLeft === 1 ? 'Sisa 1 hari' : 'Sisa ' . ($reviewDaysLeft ?? 0) . ' hari',
        'sync_pending' => 'Akan disinkronkan dari batches',
        default => 'Tidak dilacak',
    };@endphp

<section class="page-card glass-card product-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">PRODUCTS</p>
            <h2>Create Produk</h2>
            <p>Isi data produk pada langkah 1 dan 2. Langkah 3 hanya review ulang sebelum simpan.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('products.index') }}" class="btn btn--secondary">
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
        action="{{ route('products.store') }}"
        method="POST"
        enctype="multipart/form-data"
        class="wizard-form page-form"
        data-step-form
        data-draft-key="products:create">
        @csrf

        <div class="stepper">
            <span class="step active" data-step-indicator="1">1</span>
            <span class="step" data-step-indicator="2">2</span>
            <span class="step" data-step-indicator="3">3</span>
        </div>

        <div class="wizard-body">
            <section class="wizard-step active" data-step="1">
                <div class="wizard-step__head">
                    <h4>Identity & Relasi</h4>
                    <p>Hubungkan produk dengan kategori, unit, supplier, dan lokasi.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Category</span>
                        <select name="category_id" required>
                            <option value="">Pilih category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Unit</span>
                        <select name="unit_id" required>
                            <option value="">Pilih unit</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->name }} ({{ $unit->symbol }})
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
                        <span>Name</span>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Beras Premium"
                            required
                            autocomplete="off"
                            data-autoslug-source="name"
                            data-autoslug-target="slug">
                    </label>

                    <label class="form-field">
                        <span>Slug</span>
                        <input
                            type="text"
                            name="slug"
                            value="{{ old('slug') }}"
                            placeholder="beras-premium"
                            required
                            autocomplete="off">
                    </label>

                    <label class="form-field">
                        <span>Barcode</span>
                        <input type="text" name="barcode" value="{{ old('barcode') }}" placeholder="Akan digenerate otomatis" readonly autocomplete="off">
                        <small>Barcode dibuat otomatis dan akan disimpan sebagai kode unik yang bisa dicetak/scanned.</small>
                    </label>

                    <label class="form-field">
                        <span>SKU</span>
                        <input type="text" name="sku" value="{{ old('sku') }}" placeholder="Akan digenerate otomatis" readonly autocomplete="off">
                        <small>SKU final dibentuk dari SKU kategori + kode produk + kode unik.</small>
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="wizard-step__head">
                    <h4>Detail, Harga, dan Expiry</h4>
                    <p>Tambahkan deskripsi, gambar, keyword, harga, stok, dan kontrol expired.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field form-field--full">
                        <span>Short Description</span>
                        <textarea name="short_description" rows="3" placeholder="Ringkasan singkat produk...">{{ old('short_description') }}</textarea>
                    </label>

                    <label class="form-field form-field--full">
                        <span>Description</span>
                        <textarea name="description" rows="5" placeholder="Deskripsi lengkap produk...">{{ old('description') }}</textarea>
                    </label>

                    <label class="form-field form-field--full">
                        <span>Image</span>
                        <input type="file" name="image" accept="image/*">
                        <small>Gambar akan disimpan ke folder <b>storage/public/product</b>.</small>
                    </label>

                    <label class="form-field form-field--full">
                        <span>Search Keywords</span>
                        <textarea name="search_keywords" rows="3" placeholder="beras, premium, 5kg">{{ old('search_keywords') }}</textarea>
                        <small>Gunakan koma atau baris baru untuk memisahkan keyword. Data akan masuk ke <b>product_keywords</b>.</small>
                    </label>

                    <label class="form-field">
                        <span>Purchase Price</span>
                        <input type="text" value="Auto from batches" disabled>
                        <small class="text-muted">Nilai modal product akan dihitung otomatis dari total semua batches.</small>
                    </label>

                    <label class="form-field">
                        <span>Sale Price</span>
                        <input type="number" name="sale_price" value="{{ old('sale_price') }}" min="0" step="1" placeholder="0">
                    </label>

                    <label class="form-field">
                        <span>Min Stock</span>
                        <input type="number" name="min_stock" value="{{ old('min_stock') }}" min="0" step="1" placeholder="0">
                    </label>

                    <label class="form-field">
                        <span>Stock On Hand (otomatis dari batch)</span>
                        <input type="number" name="stock_on_hand" value="{{ old('stock_on_hand') }}" min="0" step="1" placeholder="0">
                        <small class="text-muted">Nilai ini akan disinkronkan otomatis dari total qty remaining semua batch.</small>
                    </label>

                    <div class="form-field form-field--full">
                        <span>Expiry Sync (otomatis dari batch)</span>
                        <div class="form-alert form-alert--info" style="margin-top:10px;">
                            <strong>Data expiry product tidak diisi manual.</strong>
                            <span>Field ini hanya penanda. Nilai expiry akan tersimpan <b>null</b> saat product dibuat, lalu disinkronkan otomatis dari batches.</span>
                        </div>
                        <input type="hidden" name="tracks_expiry" value="1">
                        <input type="hidden" name="expiry_type" value="none">
                        <div class="wizard-form-grid" style="margin-top: 12px;">
                            <label class="form-field">
                                <span>Tracks Expiry</span>
                                <select name="tracks_expiry" disabled>
                                    <option value="1" selected>Tracking</option>
                                </select>
                            </label>

                            <label class="form-field">
                                <span>Expiry Type</span>
                                <select name="expiry_type" disabled>
                                    <option value="none" selected>Auto from batches</option>
                                </select>
                            </label>

                            <label class="form-field">
                                <span>Production Date</span>
                                <input type="date" name="production_date" value="" disabled>
                            </label>

                            <label class="form-field">
                                <span>Expired At</span>
                                <input type="date" name="expired_at" value="" disabled>
                            </label>

                            <label class="form-field">
                                <span>Shelf Life Days</span>
                                <input type="number" name="shelf_life_days" value="" min="1" step="1" placeholder="365" disabled>
                            </label>

                            <label class="form-field">
                                <span>Expiry Warning Days</span>
                                <input type="number" name="expiry_warning_days" value="" min="0" step="1" placeholder="30" disabled>
                            </label>

                            <label class="form-field">
                                <span>Expiry Grace Days</span>
                                <input type="number" name="expiry_grace_days" value="" min="0" step="1" placeholder="0" disabled>
                            </label>
                        </div>
                    </div>

                    <label class="form-field">
                        <span>Popularity Score</span>
                        <input type="number" name="popularity_score" value="{{ old('popularity_score') }}" min="0" step="0.01" placeholder="0">
                    </label>

                    <label class="form-field">
                        <span>Last Sold At</span>
                        <input type="datetime-local" name="last_sold_at" value="{{ old('last_sold_at') }}">
                    </label>

                    <label class="form-field">
                        <span>Featured</span>
                        <select name="is_featured" required>
                            <option value="1" {{ old('is_featured', 0) == 1 ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ old('is_featured', 0) == 0 ? 'selected' : '' }}>No</option>
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Available Online</span>
                        <select name="is_available_online" required>
                            <option value="1" {{ old('is_available_online', 0) == 1 ? 'selected' : '' }}>Yes</option>
                            <option value="0" {{ old('is_available_online', 0) == 0 ? 'selected' : '' }}>No</option>
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Status</span>
                        <select name="is_active" required>
                            <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', 1) == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>
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
                        <span>Category</span>
                        <strong>{{ $selectedCategoryLabel }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Unit</span>
                        <strong>{{ $selectedUnitLabel }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Supplier</span>
                        <strong>{{ $selectedSupplierLabel }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Location</span>
                        <strong>{{ $selectedLocationLabel }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Name</span>
                        <strong>{{ old('name', '-') }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Slug</span>
                        <strong>{{ old('slug', '-') }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Barcode</span>
                        <strong>{{ old('barcode') ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>SKU</span>
                        <strong>{{ old('sku') ?: '-' }}</strong>
                    </div>
                    <div class="review-item review-item--full">
                        <span>Short Description</span>
                        <p>{{ old('short_description') ?: '-' }}</p>
                    </div>
                    <div class="review-item review-item--full">
                        <span>Description</span>
                        <p>{{ old('description') ?: '-' }}</p>
                    </div>
                    <div class="review-item review-item--full">
                        <span>Image</span>
                        <p>File gambar akan diunggah saat data disimpan.</p>
                    </div>
                    <div class="review-item review-item--full">
                        <span>Search Keywords</span>
                        <p>{{ old('search_keywords') ?: '-' }}</p>
                    </div>
                    <div class="review-item">
                        <span>Purchase Price</span>
                        <strong>Auto from batches</strong>
                    </div>
                    <div class="review-item">
                        <span>Sale Price</span>
                        <strong>{{ old('sale_price') !== null && old('sale_price') !== '' ? old('sale_price') : '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Min Stock</span>
                        <strong>{{ old('min_stock') !== null && old('min_stock') !== '' ? old('min_stock') : '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Stock On Hand (otomatis dari batch)</span>
                        <strong>{{ old('stock_on_hand') !== null && old('stock_on_hand') !== '' ? old('stock_on_hand') : '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Tracks Expiry</span>
                        <strong>{{ (int) $selectedTracksExpiry === 1 ? 'Yes' : 'No' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Expiry Type</span>
                        <strong>{{ (int) $selectedTracksExpiry === 1 ? ($selectedExpiryType === 'shelf_life' ? 'Shelf Life' : 'Fixed Date') : 'No Tracking' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Production Date</span>
                        <strong>{{ $selectedProductionDate ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Expired At</span>
                        <strong>{{ $selectedExpiredAt ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Shelf Life Days</span>
                        <strong>{{ $selectedShelfLifeDays !== null && $selectedShelfLifeDays !== '' ? $selectedShelfLifeDays : '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Warning Days</span>
                        <strong>{{ $selectedWarningDays !== null && $selectedWarningDays !== '' ? $selectedWarningDays : '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Grace Days</span>
                        <strong>{{ $selectedGraceDays !== null && $selectedGraceDays !== '' ? $selectedGraceDays : '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Resolved Expiry</span>
                        <strong>{{ $reviewResolvedExpiryAt }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Expiry Status</span>
                        <strong class="status-pill {{ $reviewExpiryStatusClass }}">{{ $reviewExpiryStatusLabel }}</strong>
                    </div>
                    <div class="review-item review-item--full">
                        <span>Expiry Summary</span>
                        <p>{{ $reviewExpirySummary }}</p>
                    </div>
                    <div class="review-item">
                        <span>Popularity Score</span>
                        <strong>{{ old('popularity_score') !== null && old('popularity_score') !== '' ? old('popularity_score') : '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Last Sold At</span>
                        <strong>{{ old('last_sold_at') ?: '-' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Featured</span>
                        <strong>{{ old('is_featured', 0) == 1 ? 'Yes' : 'No' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Available Online</span>
                        <strong>{{ old('is_available_online', 0) == 1 ? 'Yes' : 'No' }}</strong>
                    </div>
                    <div class="review-item">
                        <span>Status</span>
                        <strong>{{ old('is_active', 1) == 1 ? 'Active' : 'Inactive' }}</strong>
                    </div>
                </div>
            </section>
        </div>

        <div class="wizard-actions">
            <button class="btn btn--secondary" type="button" data-step-action="prev">Back</button>

            <div class="wizard-actions__right">
                <button class="btn btn--primary" type="button" data-step-action="next">Next</button>
                <button class="btn btn--primary" type="submit" data-step-submit hidden>
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Simpan Produk
                </button>
            </div>
        </div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
@endsection

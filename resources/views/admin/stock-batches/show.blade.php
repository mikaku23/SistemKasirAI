@extends('template-admin.layout')

@section('title', 'Detail Batch Stok')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $stockBatch = $stockBatch ?? null;
    $metadata = is_array($stockBatch->metadata ?? null) ? $stockBatch->metadata : [];

    $formatQty = function ($value) {
        if ($value === null || $value === '') {
            return '-';
        }

        $float = (float) $value;
        $decimals = abs($float - round($float)) < 0.00001 ? 0 : 2;

        return number_format($float, $decimals, ',', '.');
    };
@endphp

<section class="page-card glass-card product-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">STOCK BATCHES</p>
            <h2>Detail Batch Stok</h2>
            <p>Seluruh data ditampilkan dalam mode baca saja.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('stock-batches.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>

            <a href="{{ route('stock-batches.edit', $stockBatch->id) }}" class="btn btn--primary">
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                Edit Batch
            </a>
        </div>
    </div>

    <div class="form-alert form-alert--info">
        <strong>Mode baca saja.</strong>
        <span>Gunakan tombol edit jika ingin mengubah data batch ini.</span>
    </div>

    <div class="stats-grid" style="margin-bottom: 1rem;">
        <div class="stat-card glass-card">
            <span>Expiry Status</span>
            <strong>{{ $stockBatch->expiry_status_label }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Summary</span>
            <strong>{{ $stockBatch->expiry_summary }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Sisa Stok</span>
            <strong>{{ $formatQty($stockBatch->qty_remaining) }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Ditambahkan Pada</span>
            <strong>{{ $stockBatch->received_at ? $stockBatch->received_at->format('d M Y') : '-' }}</strong>
        </div>
    </div>

    <div class="detail-card glass-card">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Product</span>
                <input type="text" value="{{ optional($stockBatch->product)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Category</span>
                <input type="text" value="{{ optional(optional($stockBatch->product)->category)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Unit</span>
                <input type="text" value="{{ optional(optional($stockBatch->product)->unit)->name ?? '-' }} ({{ optional(optional($stockBatch->product)->unit)->symbol ?? '-' }})" disabled>
            </label>

            <label class="form-field">
                <span>Supplier</span>
                <input type="text" value="{{ optional($stockBatch->supplier)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Location</span>
                <input type="text" value="{{ optional($stockBatch->location)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Ditambahkan Oleh</span>
                <input type="text" value="{{ optional($stockBatch->receiver)->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Sumber Data</span>
                <input type="text" value="{{ data_get($metadata, 'source_label', 'Penerimaan batch') }}" disabled>
            </label>

            <label class="form-field">
                <span>Batch Code</span>
                <input type="text" value="{{ $stockBatch->batch_code }}" disabled>
            </label>

            <label class="form-field">
                <span>Lot Number</span>
                <input type="text" value="{{ $stockBatch->lot_number ?: '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Qty Received</span>
                <input type="text" value="{{ $formatQty($stockBatch->qty_received) }}" disabled>
            </label>

            <label class="form-field">
                <span>Qty Remaining</span>
                <input type="text" value="{{ $formatQty($stockBatch->qty_remaining) }}" disabled>
            </label>

            <label class="form-field">
                <span>Purchase Price</span>
                <input type="text" value="{{ $stockBatch->purchase_price !== null ? 'Rp ' . number_format((float) $stockBatch->purchase_price, 0, ',', '.') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Production Date</span>
                <input type="text" value="{{ $stockBatch->production_date ? $stockBatch->production_date->format('d M Y') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Expired At</span>
                <input type="text" value="{{ $stockBatch->expired_at ? $stockBatch->expired_at->format('d M Y') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Expiry Mode</span>
                <input type="text" value="{{ $stockBatch->expiry_mode_label }}" disabled>
            </label>

            <label class="form-field">
                <span>Expiry Warning Days</span>
                <input type="text" value="{{ data_get($metadata, 'expiry_warning_days', 30) }}" disabled>
            </label>

            <label class="form-field">
                <span>Expiry Grace Days</span>
                <input type="text" value="{{ data_get($metadata, 'expiry_grace_days', 0) }}" disabled>
            </label>

            <label class="form-field">
                <span>Status</span>
                <input type="text" value="{{ $stockBatch->status_label }}" disabled>
            </label>

            <label class="form-field form-field--full">
                <span>Notes</span>
                <textarea rows="4" disabled>{{ $stockBatch->notes ?: '-' }}</textarea>
            </label>

            <label class="form-field">
                <span>Updated At</span>
                <input type="text" value="{{ $stockBatch->updated_at ? $stockBatch->updated_at->format('d M Y H:i') : '-' }}" disabled>
            </label>
        </div>
    </div>
</section>
@endsection

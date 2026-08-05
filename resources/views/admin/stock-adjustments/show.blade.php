@extends('template-admin.layout')

@section('title', 'Detail Pengecekan Stok')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    /** @var \App\Models\StockAdjustment $stockAdjustment */
    $payload = $payload ?? [];
@endphp

<section class="page-card glass-card">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">STOCK CHECK</p>
            <h2>Detail Pengecekan Stok</h2>
            <p>{{ $payload['review_status_label'] ?? $stockAdjustment->review_status_label }}</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('stock-adjustments.index') }}" class="btn btn--secondary">Kembali</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card glass-card">
            <span>Kode</span>
            <strong>{{ $payload['adjustment_code'] ?? $stockAdjustment->adjustment_code }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Produk</span>
            <strong>{{ $payload['product_name'] ?? optional($stockAdjustment->product)->name }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Selisih</span>
            <strong>{{ $payload['difference_label'] ?? $stockAdjustment->difference_label }}</strong>
        </div>
    </div>

    <div class="table-card glass-card" style="margin-top: 18px;">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RINGKASAN</p>
                <h3>Data pemeriksaan</h3>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <tbody>
                    <tr>
                        <th>Stok sistem</th>
                        <td>{{ number_format((int) ($payload['system_qty'] ?? $stockAdjustment->system_qty), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Stok fisik</th>
                        <td>{{ number_format((int) ($payload['physical_qty'] ?? $stockAdjustment->physical_qty), 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Selisih</th>
                        <td>{{ $payload['difference_label'] ?? $stockAdjustment->difference_label }}</td>
                    </tr>
                    <tr>
                        <th>Batch acuan</th>
                        <td>{{ $payload['batch_code'] ?? optional($stockAdjustment->stockBatch)->batch_code ?: 'Auto' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="status-pill {{ $payload['review_status_class'] ?? $stockAdjustment->review_status_class }}">{{ $payload['review_status_label'] ?? $stockAdjustment->review_status_label }}</span></td>
                    </tr>
                    <tr>
                        <th>Tindakan sistem</th>
                        <td>{{ $payload['system_action_text'] ?? $stockAdjustment->system_action_text }}</td>
                    </tr>
                    <tr>
                        <th>Pengecek</th>
                        <td>{{ $payload['checker_name'] ?? optional($stockAdjustment->user)->name ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>Waktu</th>
                        <td>{{ $payload['checked_at'] ?? optional($stockAdjustment->adjusted_at)->format('d M Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Catatan</th>
                        <td>{{ $payload['reason'] ?? $stockAdjustment->reason ?: '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="page-card__actions" style="margin-top: 18px;">
        @if($stockAdjustment->review_status === 'pending_review')
            <form action="{{ route('stock-adjustments.confirm-system', $stockAdjustment->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn--secondary">Sistem benar</button>
            </form>
            <form action="{{ route('stock-adjustments.apply-correction', $stockAdjustment->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn--danger">Kesalahan jumlah</button>
            </form>
        @endif
    </div>
</section>
@endsection

@extends('template-admin.layout')

@section('title', 'Detail Stock Movement')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $movement = $stockMovement ?? null;
    $groupSummary = $groupSummary ?? [];
    $groupedMovements = $groupedMovements ?? [];
    $movementTypes = $movementTypes ?? [];

    $formatQty = function ($value) {
        if ($value === null || $value === '') {
            return '-';
        }

        $float = (float) $value;
        $decimals = abs($float - round($float)) < 0.00001 ? 0 : 2;

        return number_format($float, $decimals, ',', '.');
    };

    $money = function ($value) {
        return 'Rp ' . number_format((int) $value, 0, ',', '.');
    };
@endphp

<section class="page-card glass-card stock-movement-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">STOCK MOVEMENTS</p>
            <h2>Detail Log Mutasi</h2>
            <p>Menampilkan satu kelompok mutasi dengan tipe yang sama pada tanggal yang sama.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ $backUrl ?? route('stock-movements.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card glass-card">
            <span>Date</span>
            <strong>{{ $groupSummary['date'] ?? '-' }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Movement</span>
            <strong>
                <span class="status-pill {{ $groupSummary['movement_type_class'] ?? 'status-pill--muted' }}">
                    {{ $groupSummary['movement_type_label'] ?? '-' }}
                </span>
            </strong>
        </div>
        <div class="stat-card glass-card">
            <span>Entries</span>
            <strong>{{ $groupSummary['entries_count'] ?? 0 }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Total Qty</span>
            <strong>{{ $formatQty($groupSummary['total_quantity'] ?? 0) }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Total Value</span>
            <strong>{{ $money($groupSummary['total_value'] ?? 0) }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Latest</span>
            <strong>{{ $groupSummary['latest_at'] ?? '-' }}</strong>
        </div>
    </div>

    <div class="detail-card glass-card" style="margin-bottom: 16px;">
        <div class="wizard-form-grid">
            <label class="form-field">
                <span>Product</span>
                <input type="text" value="{{ $movement->product->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Movement Type</span>
                <input type="text" value="{{ $movementTypes[$movement->movement_type]['label'] ?? $movement->movement_type }}" disabled>
            </label>

            <label class="form-field">
                <span>User</span>
                <input type="text" value="{{ $movement->user->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Location</span>
                <input type="text" value="{{ $movement->location->name ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Batch Code</span>
                <input type="text" value="{{ $movement->stockBatch->batch_code ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Lot Number</span>
                <input type="text" value="{{ $movement->stockBatch->lot_number ?? '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Quantity</span>
                <input type="text" value="{{ $formatQty($movement->quantity) }}" disabled>
            </label>

            <label class="form-field">
                <span>Unit Cost</span>
                <input type="text" value="{{ $money($movement->unit_cost) }}" disabled>
            </label>

            <label class="form-field">
                <span>Reference</span>
                <input type="text" value="{{ $movement->reference_type ? class_basename($movement->reference_type) . ' #' . ($movement->reference_id ?? '-') : '-' }}" disabled>
            </label>

            <label class="form-field">
                <span>Created At</span>
                <input type="text" value="{{ $movement->created_at ? $movement->created_at->format('d M Y H:i') : '-' }}" disabled>
            </label>

            <label class="form-field form-field--full">
                <span>Notes</span>
                <textarea rows="3" disabled>{{ $movement->notes ?: '-' }}</textarea>
            </label>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">GROUP DETAIL</p>
                <h3>Semua log dengan tipe dan tanggal yang sama</h3>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="stockMovementsGroupTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Created At</th>
                        <th>Product</th>
                        <th>Batch / Lot</th>
                        <th>User</th>
                        <th>Location</th>
                        <th>Qty</th>
                        <th>Unit Cost</th>
                        <th>Reference</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groupedMovements as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->created_at ? $row->created_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-strong">{{ $row->product->name ?? '-' }}</td>
                            <td>
                                <div style="display:grid; gap:4px;">
                                    <span class="mono-chip">{{ $row->stockBatch->batch_code ?? '-' }}</span>
                                    <small class="text-muted">{{ $row->stockBatch->lot_number ?? '-' }}</small>
                                </div>
                            </td>
                            <td>{{ $row->user->name ?? '-' }}</td>
                            <td>{{ $row->location->name ?? '-' }}</td>
                            <td>{{ $formatQty($row->quantity) }}</td>
                            <td>{{ $money($row->unit_cost) }}</td>
                            <td>{{ $row->reference_type ? class_basename($row->reference_type) . ' #' . ($row->reference_id ?? '-') : '-' }}</td>
                            <td class="td-description">{{ $row->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <div class="empty-state__icon">
                                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                                    </div>
                                    <strong>Data kelompok tidak ditemukan.</strong>
                                    <p>Log ini mungkin sudah berubah atau tidak lagi tersedia.</p>
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

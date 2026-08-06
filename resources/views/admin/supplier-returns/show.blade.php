@extends('template-admin.layout')

@section('title', 'Detail Supplier Return')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $returnRecord = $returnRecord ?? null;
    $summary = $summary ?? [
        'item_count' => 0,
        'qty_returned' => 0,
        'total_amount' => 0,
        'completed_at' => null,
    ];
@endphp

<section class="page-card glass-card role-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">SUPPLIER RETURNS</p>
            <h2>Detail Return</h2>
            <p>Audit lengkap item, batch, dan pergerakan stok yang terjadi pada return supplier ini.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ $backUrl ?? route('supplier-returns.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card glass-card">
            <span>Return Code</span>
            <strong>{{ $returnRecord->return_code }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Supplier</span>
            <strong>{{ optional($returnRecord->supplier)->name ?? '-' }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Qty Returned</span>
            <strong>{{ $summary['qty_returned'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Total Amount</span>
            <strong>Rp {{ number_format((int) $summary['total_amount'], 0, ',', '.') }}</strong>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">HEADER</p>
                <h3>Informasi return</h3>
            </div>
        </div>

        <div class="detail-grid" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;padding:0 4px 8px;">
            <div class="review-item">
                <strong>Status</strong>
                <div><span class="status-pill {{ $returnRecord->status_class }}">{{ $returnRecord->status_label }}</span></div>
            </div>
            <div class="review-item">
                <strong>Return At</strong>
                <div>{{ optional($returnRecord->return_at)->format('d M Y H:i') }}</div>
            </div>
            <div class="review-item">
                <strong>Location</strong>
                <div>{{ optional($returnRecord->location)->name ?? '-' }}</div>
            </div>
            <div class="review-item">
                <strong>User</strong>
                <div>{{ optional($returnRecord->user)->name ?? '-' }}</div>
            </div>
            <div class="review-item" style="grid-column:1/-1;">
                <strong>Reason</strong>
                <div>{{ $returnRecord->reason ?: '-' }}</div>
            </div>
        </div>
    </div>

    <div class="table-card glass-card" style="margin-top:18px;">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">ITEMS</p>
                <h3>Detail item return</h3>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Batch</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($returnRecord->items as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="table-meta">
                                    <strong>{{ optional($item->product)->name ?? '-' }}</strong>
                                    <small>{{ optional(optional($item->product)->category)->name ?? '-' }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="table-meta">
                                    <strong>{{ optional($item->stockBatch)->batch_code ?? '-' }}</strong>
                                    <small>{{ optional($item->stockBatch)->lot_number ?? '-' }}</small>
                                </div>
                            </td>
                            <td>{{ (int) $item->quantity }}</td>
                            <td>Rp {{ number_format((int) $item->unit_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((int) $item->subtotal, 0, ',', '.') }}</td>
                            <td>{{ $item->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">Tidak ada item.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="table-card glass-card" style="margin-top:18px;">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">MOVEMENTS</p>
                <h3>Pergerakan stok terkait</h3>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Movement Type</th>
                        <th>Product</th>
                        <th>Batch</th>
                        <th>Qty</th>
                        <th>Unit Cost</th>
                        <th>Movement At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($returnRecord->stockMovements as $movement)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td><span class="status-pill {{ app(\App\Http\Services\StockMovementService::class)->movementTypeClass($movement->movement_type) }}">{{ app(\App\Http\Services\StockMovementService::class)->movementTypeLabel($movement->movement_type) }}</span></td>
                            <td>{{ optional($movement->product)->name ?? '-' }}</td>
                            <td>{{ optional($movement->stockBatch)->batch_code ?? '-' }}</td>
                            <td>{{ (int) $movement->quantity }}</td>
                            <td>Rp {{ number_format((int) $movement->unit_cost, 0, ',', '.') }}</td>
                            <td>{{ optional($movement->movement_at)->format('d M Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">Belum ada stock movement.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

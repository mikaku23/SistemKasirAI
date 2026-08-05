@extends('template-admin.layout')

@section('title', 'Pengecekan Stok Manual')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
<style>
    .adjustment-row { cursor: pointer; }
    .adjustment-row--matched { background: rgba(25, 135, 84, 0.06); }
    .adjustment-row--system_correct { background: rgba(108, 117, 125, 0.06); }
    .adjustment-row--system_updated { background: rgba(220, 53, 69, 0.06); }
    .adjustment-row--pending_review { background: rgba(255, 193, 7, 0.08); }

    .adjustment-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        line-height: 1;
    }
    .adjustment-chip--danger { background: rgba(220, 53, 69, 0.12); color: #b02a37; }
    .adjustment-chip--warning { background: rgba(255, 193, 7, 0.14); color: #8a6d00; }
    .adjustment-chip--success { background: rgba(25, 135, 84, 0.12); color: #146c43; }
    .adjustment-chip--muted { background: rgba(108, 117, 125, 0.12); color: #6c757d; }

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
    .adjustment-modal.is-open { display: flex; }
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
    .adjustment-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }
    .adjustment-card {
        padding: 14px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .adjustment-card span { display: block; font-size: 12px; color: rgba(229, 231, 235, 0.7); margin-bottom: 6px; }
    .adjustment-card strong { display: block; font-size: 18px; }
    .adjustment-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 14px;
    }
    .adjustment-note {
        margin-top: 14px;
        padding: 14px 16px;
        border-radius: 18px;
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.18);
    }
    .adjustment-note p { margin: 0; color: rgba(229, 231, 235, 0.9); }
    .adjustment-modal__foot {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: flex-end;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .adjustment-form-row {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 14px;
    }
    @media (max-width: 768px) {
        .adjustment-grid,
        .adjustment-meta,
        .adjustment-form-row {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
@php
    $stockAdjustments = $stockAdjustments ?? [];
    $stockAdjustmentStats = $stockAdjustmentStats ?? [
        'total' => 0,
        'pending' => 0,
        'matched' => 0,
        'systemCorrect' => 0,
        'systemUpdated' => 0,
        'overage' => 0,
        'shortage' => 0,
    ];
@endphp

<section class="page-card glass-card">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">STOCK CHECK</p>
            <h2>Pengecekan Stok Manual</h2>
            <p>Setiap input disimpan sebagai log pengecekan. Jika ada selisih, sistem akan membuka popup untuk memilih: stok sistem dipertahankan atau stok disesuaikan mengikuti data fisik.</p>
        </div>

        <div class="page-card__actions">
            <label class="search-box" for="stockAdjustmentSearch">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="stockAdjustmentSearch"
                    placeholder="Cari pengecekan..."
                    data-table-search-target="#stockAdjustmentsTable">
            </label>

            <a href="{{ route('stock-adjustments.create') }}" class="btn btn--primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Pengecekan Baru
            </a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card glass-card">
            <span>Total</span>
            <strong>{{ $stockAdjustmentStats['total'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Perlu Verifikasi</span>
            <strong>{{ $stockAdjustmentStats['pending'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Input Cocok</span>
            <strong>{{ $stockAdjustmentStats['matched'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Stok Dipertahankan</span>
            <strong>{{ $stockAdjustmentStats['systemCorrect'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Stok Diperbarui</span>
            <strong>{{ $stockAdjustmentStats['systemUpdated'] }}</strong>
        </div>
        <div class="stat-card glass-card">
            <span>Selisih Positif</span>
            <strong>{{ $stockAdjustmentStats['overage'] }}</strong>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">LOG PENGECEKAN</p>
                <h3>Tabel data pengecekan</h3>
            </div>

            <a href="{{ route('stock-adjustments.create') }}" class="btn btn--secondary">
                Input baru
            </a>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact" id="stockAdjustmentsTable" data-page-size="10">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Kode</th>
                        <th>Produk</th>
                        <th>Sistem</th>
                        <th>Fisik</th>
                        <th>Selisih</th>
                        <th>Batch Acuan</th>
                        <th>Pengecek</th>
                        <th>Status</th>
                        <th>Tindakan Sistem</th>
                        <th>Waktu</th>
                        <th class="th-actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stockAdjustments as $stockAdjustment)
                        @php
                            $payload = [
                                'id' => $stockAdjustment->id,
                                'adjustment_code' => $stockAdjustment->adjustment_code,
                                'product_name' => optional($stockAdjustment->product)->name,
                                'product_sku' => optional($stockAdjustment->product)->sku,
                                'category_name' => optional(optional($stockAdjustment->product)->category)->name,
                                'unit_name' => optional(optional($stockAdjustment->product)->unit)->name,
                                'location_name' => optional($stockAdjustment->location)->name,
                                'system_qty' => (int) $stockAdjustment->system_qty,
                                'physical_qty' => (int) $stockAdjustment->physical_qty,
                                'difference_qty' => (int) $stockAdjustment->difference_qty,
                                'difference_label' => $stockAdjustment->difference_label,
                                'difference_direction_label' => $stockAdjustment->difference_direction_label,
                                'review_status' => $stockAdjustment->review_status,
                                'review_status_label' => $stockAdjustment->review_status_label,
                                'review_status_class' => $stockAdjustment->review_status_class,
                                'review_action_label' => $stockAdjustment->review_action_label,
                                'system_action_text' => $stockAdjustment->system_action_text,
                                'reason' => $stockAdjustment->reason,
                                'checker_name' => optional($stockAdjustment->user)->name,
                                'checked_at' => optional($stockAdjustment->adjusted_at)->format('d M Y H:i'),
                                'batch_code' => optional($stockAdjustment->stockBatch)->batch_code,
                                'batch_qty_remaining' => optional($stockAdjustment->stockBatch)->qty_remaining !== null ? (int) optional($stockAdjustment->stockBatch)->qty_remaining : null,
                                'batch_expired_at' => optional(optional($stockAdjustment->stockBatch)->expired_at)->format('Y-m-d'),
                                'confirm_url' => route('stock-adjustments.confirm-system', $stockAdjustment->id),
                                'apply_url' => route('stock-adjustments.apply-correction', $stockAdjustment->id),
                                'show_url' => route('stock-adjustments.show', $stockAdjustment->id),
                            ];
                        @endphp
                        <tr
                            class="adjustment-row adjustment-row--{{ $stockAdjustment->review_status }}"
                            data-adjustment='@json($payload)'>
                            <td>{{ $loop->iteration }}</td>
                            <td class="td-strong">{{ $stockAdjustment->adjustment_code }}</td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <strong>{{ optional($stockAdjustment->product)->name }}</strong>
                                    <small class="text-muted">
                                        {{ optional($stockAdjustment->product)->sku ?: '-' }}
                                        @if(optional($stockAdjustment->location)->name)
                                            · {{ optional($stockAdjustment->location)->name }}
                                        @endif
                                    </small>
                                </div>
                            </td>
                            <td>{{ number_format((int) $stockAdjustment->system_qty, 0, ',', '.') }}</td>
                            <td>{{ number_format((int) $stockAdjustment->physical_qty, 0, ',', '.') }}</td>
                            <td>
                                <span class="adjustment-chip {{ (int) $stockAdjustment->difference_qty > 0 ? 'adjustment-chip--success' : ((int) $stockAdjustment->difference_qty < 0 ? 'adjustment-chip--danger' : 'adjustment-chip--muted') }}">
                                    {{ $stockAdjustment->difference_label }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <strong>{{ optional($stockAdjustment->stockBatch)->batch_code ?: 'Auto' }}</strong>
                                    <small class="text-muted">
                                        {{ optional($stockAdjustment->stockBatch)->qty_remaining !== null ? 'Sisa ' . number_format((int) optional($stockAdjustment->stockBatch)->qty_remaining, 0, ',', '.') . ' pcs' : 'Acuan otomatis' }}
                                    </small>
                                </div>
                            </td>
                            <td>{{ optional($stockAdjustment->user)->name ?: '-' }}</td>
                            <td>
                                <span class="status-pill {{ $stockAdjustment->review_status_class }}">
                                    {{ $stockAdjustment->review_status_label }}
                                </span>
                            </td>
                            <td>{{ $stockAdjustment->system_action_text }}</td>
                            <td>{{ $stockAdjustment->adjusted_at ? $stockAdjustment->adjusted_at->format('d M Y H:i') : '-' }}</td>
                            <td class="td-actions">
                                <div class="inline-actions">
                                    <button type="button" class="icon-btn" data-open-adjustment aria-label="Lihat detail">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>

                                    <a href="{{ route('stock-adjustments.show', $stockAdjustment->id) }}" class="icon-btn" aria-label="Halaman detail">
                                        <i class="fa-solid fa-square-up-right" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted">Belum ada data pengecekan stok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<div class="adjustment-modal" id="adjustmentModal" aria-hidden="true">
    <div class="adjustment-modal__panel" role="dialog" aria-modal="true" aria-labelledby="adjustmentModalTitle">
        <div class="adjustment-modal__head">
            <div class="adjustment-modal__title">
                <p class="eyebrow">DETAIL PENGECEKAN</p>
                <h3 id="adjustmentModalTitle">-</h3>
                <p id="adjustmentModalSubtitle">-</p>
            </div>
            <button type="button" class="adjustment-modal__close" data-close-adjustment aria-label="Tutup modal">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <div class="adjustment-modal__body">
            <div class="adjustment-grid">
                <div class="adjustment-card">
                    <span>Stok sistem</span>
                    <strong id="modalSystemQty">0</strong>
                </div>
                <div class="adjustment-card">
                    <span>Stok fisik</span>
                    <strong id="modalPhysicalQty">0</strong>
                </div>
                <div class="adjustment-card">
                    <span>Selisih</span>
                    <strong id="modalDifferenceQty">0</strong>
                </div>
            </div>

            <div class="adjustment-meta">
                <div class="adjustment-card">
                    <span>Batch acuan</span>
                    <strong id="modalBatchCode">-</strong>
                    <small id="modalBatchInfo" class="text-muted"></small>
                </div>
                <div class="adjustment-card">
                    <span>Pengecek</span>
                    <strong id="modalCheckerName">-</strong>
                    <small id="modalCheckedAt" class="text-muted"></small>
                </div>
            </div>

            <div class="adjustment-note">
                <p id="modalActionText">-</p>
            </div>

            <div class="adjustment-form-row">
                <div class="form-field">
                    <span>Status saat ini</span>
                    <input type="text" id="modalStatusLabel" disabled>
                </div>
                <div class="form-field">
                    <span>Alasan / catatan</span>
                    <input type="text" id="modalReason" disabled>
                </div>
            </div>
        </div>

        <div class="adjustment-modal__foot">
            <form method="POST" id="modalConfirmForm">
                @csrf
                <button type="submit" class="btn btn--secondary">
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    Sistem benar
                </button>
            </form>

            <form method="POST" id="modalApplyForm">
                @csrf
                <button type="submit" class="btn btn--danger">
                    <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                    Kesalahan jumlah
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('adjustmentModal');
    const closeButton = document.querySelector('[data-close-adjustment]');
    const confirmForm = document.getElementById('modalConfirmForm');
    const applyForm = document.getElementById('modalApplyForm');

    const modalFields = {
        title: document.getElementById('adjustmentModalTitle'),
        subtitle: document.getElementById('adjustmentModalSubtitle'),
        systemQty: document.getElementById('modalSystemQty'),
        physicalQty: document.getElementById('modalPhysicalQty'),
        differenceQty: document.getElementById('modalDifferenceQty'),
        batchCode: document.getElementById('modalBatchCode'),
        batchInfo: document.getElementById('modalBatchInfo'),
        checkerName: document.getElementById('modalCheckerName'),
        checkedAt: document.getElementById('modalCheckedAt'),
        actionText: document.getElementById('modalActionText'),
        statusLabel: document.getElementById('modalStatusLabel'),
        reason: document.getElementById('modalReason'),
    };

    const openModal = (payload) => {
        modalFields.title.textContent = payload.adjustment_code || '-';
        modalFields.subtitle.textContent = [payload.product_name, payload.category_name, payload.unit_name].filter(Boolean).join(' · ') || '-';
        modalFields.systemQty.textContent = Number(payload.system_qty ?? 0).toLocaleString('id-ID');
        modalFields.physicalQty.textContent = Number(payload.physical_qty ?? 0).toLocaleString('id-ID');
        modalFields.differenceQty.textContent = payload.difference_label || '0';
        modalFields.batchCode.textContent = payload.batch_code || 'Acuan otomatis';
        modalFields.batchInfo.textContent = payload.batch_qty_remaining !== null
            ? `Sisa batch acuan: ${Number(payload.batch_qty_remaining).toLocaleString('id-ID')} pcs${payload.batch_expired_at ? ' · Expired ' + payload.batch_expired_at : ''}`
            : 'Acuan dipilih otomatis oleh sistem.';
        modalFields.checkerName.textContent = payload.checker_name || '-';
        modalFields.checkedAt.textContent = payload.checked_at || '-';
        modalFields.actionText.textContent = payload.system_action_text || '-';
        modalFields.statusLabel.value = payload.review_status_label || '-';
        modalFields.reason.value = payload.reason || '-';

        confirmForm.action = payload.confirm_url || '#';
        applyForm.action = payload.apply_url || '#';

        const isPending = (payload.review_status || '') === 'pending_review';
        confirmForm.style.display = isPending ? 'inline-block' : 'none';
        applyForm.style.display = isPending ? 'inline-block' : 'none';

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    };

    document.querySelectorAll('[data-open-adjustment], tr[data-adjustment]').forEach((element) => {
        element.addEventListener('click', (event) => {
            if (event.target.closest('a, button') && !event.target.closest('[data-open-adjustment]')) {
                return;
            }

            const row = element.closest('tr[data-adjustment]') || element;
            const payload = JSON.parse(row.dataset.adjustment || '{}');
            openModal(payload);
        });
    });

    closeButton?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
});
</script>
@endsection

@extends('template-admin.layout')

@section('title', 'Recycle Setting Pajak')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $trashedTaxSettings = $trashedTaxSettings ?? [];
@endphp

<section class="page-card glass-card">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">TAX SETTINGS</p>
            <h2>Recycle Bin</h2>
            <p>Daftar setting pajak yang dihapus sementara.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('tax-settings.index') }}" class="btn btn--secondary">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="table-card glass-card">
        <div class="table-card__head">
            <div>
                <p class="eyebrow">RECYCLE</p>
                <h3>Data pajak terhapus</h3>
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table data-table--compact">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Deleted At</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trashedTaxSettings as $taxSetting)
                        <tr data-search-text="{{ strtolower(trim(($taxSetting->code ?? '') . ' ' . ($taxSetting->name ?? '') . ' ' . ($taxSetting->display_value ?? ''))) }}">
                            <td>{{ $loop->iteration }}</td>
                            <td><span class="mono-chip">{{ $taxSetting->code }}</span></td>
                            <td class="td-strong">{{ $taxSetting->name }}</td>
                            <td>{{ $taxSetting->tax_type_label }}</td>
                            <td>{{ $taxSetting->display_value }}</td>
                            <td>{{ $taxSetting->deleted_at ? $taxSetting->deleted_at->format('d M Y H:i') : '-' }}</td>
                            <td>
                                <div class="inline-actions">
                                    <form action="{{ route('tax-settings.restore', $taxSetting->id) }}" method="POST" class="inline-form" data-confirm-form data-confirm-title="Pulihkan setting pajak?" data-confirm-message="Setting pajak ini akan dikembalikan dari recycle bin. Lanjutkan?" data-confirm-variant="info" data-confirm-icon="fa-solid fa-trash-arrow-up">
                                        @csrf
                                        <button type="submit" class="icon-btn icon-btn--success" aria-label="Restore tax setting">
                                            <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('tax-settings.forceDelete', $taxSetting->id) }}" method="POST" class="inline-form" data-confirm-form data-confirm-title="Hapus permanen?" data-confirm-message="Setting pajak ini akan dihapus permanen dan tidak bisa dipulihkan lagi. Lanjutkan?" data-confirm-variant="danger" data-confirm-icon="fa-solid fa-triangle-exclamation">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="icon-btn icon-btn--danger" aria-label="Force delete tax setting">
                                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-state__icon">
                                        <i class="fa-solid fa-trash-arrow-up" aria-hidden="true"></i>
                                    </div>
                                    <strong>Recycle bin masih kosong.</strong>
                                    <p>Setting pajak yang dihapus sementara akan muncul di sini.</p>
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

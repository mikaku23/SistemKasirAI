@extends('template-admin.layout')

@section('title', 'Print Barcode Produk')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
<style>
    .barcode-print-shell {
        max-width: 960px;
        margin: 0 auto;
    }

    .barcode-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-bottom: 18px;
    }

    .barcode-sheet {
        background: #fff;
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
    }

    .barcode-card {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
        align-items: center;
        text-align: center;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 20px;
        padding: 24px;
    }

    .barcode-card__brand {
        font-size: 12px;
        letter-spacing: .18em;
        text-transform: uppercase;
        color: #64748b;
        margin: 0;
    }

    .barcode-card__name {
        margin: 0;
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
    }

    .barcode-card__meta {
        margin: 0;
        color: #475569;
        font-size: 14px;
        line-height: 1.6;
    }

    .barcode-art {
        margin: 8px auto 0;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        min-height: 96px;
        padding: 12px 16px;
        border-radius: 16px;
        background: #f8fafc;
        overflow: hidden;
    }

    .barcode-art__img {
        display: block;
        max-width: 100%;
        height: auto;
    }

    .barcode-code {
        font-size: 18px;
        font-weight: 700;
        letter-spacing: .24em;
        margin: 0;
        color: #0f172a;
    }

    .barcode-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
    }

    .barcode-mini {
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 16px;
        padding: 12px 14px;
        text-align: left;
    }

    .barcode-mini span {
        display: block;
        font-size: 12px;
        color: #64748b;
        margin-bottom: 4px;
    }

    .barcode-mini strong {
        display: block;
        font-size: 14px;
        color: #0f172a;
        word-break: break-word;
    }

    @media print {
        .no-print { display: none !important; }
        body { background: #fff !important; }
        .barcode-sheet { box-shadow: none; padding: 0; }
        .barcode-print-shell { max-width: none; }
    }
</style>
@endsection

@section('content')
@php
    $barcodeImage = $barcodeImage ?? null;
@endphp

<section class="barcode-print-shell">
    <div class="barcode-actions no-print">
        <a href="{{ route('products.index') }}" class="btn btn--secondary">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Kembali
        </a>

        <button type="button" class="btn btn--primary" onclick="window.print()">
            <i class="fa-solid fa-print" aria-hidden="true"></i>
            Print
        </button>
    </div>

    <div class="barcode-sheet">
        <div class="barcode-card">
            <p class="barcode-card__brand">Product Barcode</p>
            <h1 class="barcode-card__name">{{ $product->name }}</h1>
            <p class="barcode-card__meta">
                SKU: {{ $product->sku ?? '-' }}<br>
                Kategori: {{ optional($product->category)->name ?? '-' }}<br>
                Harga jual: Rp {{ number_format((float) $product->sale_price, 0, ',', '.') }}
            </p>

            <div class="barcode-art">
                @if (!empty($barcodeImage))
                    <img class="barcode-art__img" src="{{ $barcodeImage }}" alt="Barcode {{ $product->barcode }}">
                @else
                    <p class="barcode-code">{{ $product->barcode }}</p>
                @endif
            </div>

            <p class="barcode-code">{{ $product->barcode }}</p>

            <div class="barcode-grid">
                <div class="barcode-mini">
                    <span>Unit</span>
                    <strong>{{ optional($product->unit)->name ?? '-' }}</strong>
                </div>
                <div class="barcode-mini">
                    <span>Location</span>
                    <strong>{{ optional($product->location)->name ?? '-' }}</strong>
                </div>
                <div class="barcode-mini">
                    <span>Supplier</span>
                    <strong>{{ optional($product->supplier)->name ?? '-' }}</strong>
                </div>
                <div class="barcode-mini">
                    <span>Status</span>
                    <strong>{{ $product->is_active ? 'Active' : 'Inactive' }}</strong>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('js')
<script>
    window.addEventListener('load', function () {
        window.setTimeout(function () {
            window.print();
        }, 300);
    });
</script>
@endsection

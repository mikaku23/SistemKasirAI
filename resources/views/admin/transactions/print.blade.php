<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $transaction->transaction_code }}</title>
    <style>
        @page {
            margin: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: auto;
            font-family: monospace;
            font-size: 11px;
            color: #111;
        }

        body {
            display: block;
        }

        .receipt {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 0px 16px;
        }

        .center {
            text-align: center;
        }

        .muted {
            color: #555;
        }

        .line {
            border-top: 1px dashed #333;
            margin: 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            vertical-align: top;
            padding: 2px 0;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        .small {
            font-size: 10px;
        }

        .item {
            page-break-inside: avoid;
            break-inside: avoid;
            margin-bottom: 2px;
        }

        .item + .item {
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="center bold">STRUK PEMBAYARAN</div>
        <div class="center">{{ optional($transaction->location)->name ?? 'STORE' }}</div>
        <div class="center small muted">{{ optional($transaction->location)->address ?? '' }}</div>

        <div class="line"></div>

        <table>
            <tr>
                <td>Bon</td>
                <td>{{ $transaction->transaction_code }}</td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td>{{ optional($transaction->cashier)->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>{{ $transaction->transaction_at ? $transaction->transaction_at->format('d-m-Y H:i:s') : '-' }}</td>
            </tr>
        </table>

        <div class="line"></div>

        @foreach($transaction->items as $item)
            <table class="item">
                <tr>
                    <td colspan="2" class="bold">{{ optional($item->product)->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>{{ (int) $item->quantity }} x Rp {{ number_format((int) $item->unit_price, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format((int) $item->subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="small muted">Promo Item: Rp {{ number_format((int) $item->discount_amount, 0, ',', '.') }}</td>
                </tr>
            </table>
        @endforeach

        <div class="line"></div>

        <table>
            <tr>
                <td>Subtotal</td>
                <td class="right">Rp {{ number_format((int) $transaction->subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Diskon Promo Item</td>
                <td class="right">Rp {{ number_format((int) data_get($transaction->metadata, 'item_discount_total', 0), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Diskon Transaksi</td>
                <td class="right">Rp {{ number_format((int) $transaction->discount_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Pajak</td>
                <td class="right">Rp {{ number_format((int) $transaction->tax_amount, 0, ',', '.') }}</td>
            </tr>
            <tr class="bold">
                <td>Total</td>
                <td class="right">Rp {{ number_format((int) $transaction->total_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Tunai</td>
                <td class="right">Rp {{ number_format((int) $transaction->paid_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Kembalian</td>
                <td class="right">Rp {{ number_format((int) $transaction->change_amount, 0, ',', '.') }}</td>
            </tr>
        </table>

        <div class="line"></div>

        <div class="center small muted">Terima kasih telah berbelanja</div>
    </div>
</body>
</html>

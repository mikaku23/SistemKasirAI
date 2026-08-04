@php
    use Illuminate\Support\Str;

    $transaction = $transaction ?? null;
    $items = $transaction?->items ?? collect();
    $taxSetting = $transaction?->taxSetting;
    $location = $transaction?->location;
    $cashier = $transaction?->cashier;
    $gross = (int) $transaction->subtotal;
    $discount = (int) $transaction->discount_amount;
    $tax = (int) $transaction->tax_amount;
    $total = (int) $transaction->total_amount;
    $paid = (int) $transaction->paid_amount;
    $change = (int) $transaction->change_amount;
    $title = $location?->name ?: 'SistemKasirAI';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $transaction->transaction_code ?? 'Receipt' }}</title>
    <style>
        @page { margin: 8mm; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .receipt { width: 100%; }
        .center { text-align: center; }
        .divider { border-top: 1px dashed #444; margin: 8px 0; }
        .small { font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        .items td { vertical-align: top; padding: 2px 0; }
        .items .qty, .items .price, .items .total { text-align: right; white-space: nowrap; }
        .summary td { padding: 2px 0; }
        .summary .label { width: 62%; }
        .summary .value { text-align: right; }
        .bold { font-weight: 700; }
        .header-title { font-size: 16px; font-weight: 700; letter-spacing: .3px; }
        .subtitle { font-size: 10px; line-height: 1.4; }
        .status {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #111;
            border-radius: 999px;
            font-size: 10px;
            margin-top: 4px;
        }
    </style>
</head>
<body>
<div class="receipt">
    <div class="center">
        <div class="header-title">{{ strtoupper($title) }}</div>
        <div class="subtitle">{{ $location?->phone ? '/' . $location->phone : '' }}</div>
        <div class="subtitle">{{ $location?->code ? strtoupper($location->code) : 'POS RECEIPT' }}</div>
        <div class="subtitle">{{ $location?->address ?: 'Struk pembayaran' }}</div>
        <div class="subtitle">NPWP : -</div>
        <div class="status">{{ strtoupper($transaction->status_label ?? $transaction->status) }}</div>
    </div>

    <div class="divider"></div>

    <table class="small">
        <tr>
            <td>Bon</td>
            <td class="bold">{{ $transaction->transaction_code }}</td>
        </tr>
        <tr>
            <td>Kasir</td>
            <td class="bold">{{ $cashier?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>Tanggal</td>
            <td>{{ $transaction->transaction_at ? $transaction->transaction_at->format('d-m-Y H:i:s') : '-' }}</td>
        </tr>
        <tr>
            <td>Shift</td>
            <td>{{ $transaction->shift_label ?? $transaction->shift }}</td>
        </tr>
        <tr>
            <td>Pajak</td>
            <td>{{ $taxSetting?->name ?? '-' }} ({{ $taxSetting?->display_value ?? '-' }})</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="items small">
        <tbody>
            @foreach ($items as $item)
                @php
                    $product = $item->product;
                    $qty = (int) $item->quantity;
                    $unitPrice = (int) $item->unit_price;
                    $discountAmount = (int) $item->discount_amount;
                    $subtotal = (int) $item->subtotal;
                @endphp
                <tr>
                    <td colspan="4" class="bold">{{ strtoupper(Str::limit($product?->name ?? '-', 24)) }}</td>
                </tr>
                <tr>
                    <td class="qty">{{ $qty }} x</td>
                    <td class="price">{{ number_format($unitPrice, 0, ',', '.') }}</td>
                    <td class="total" colspan="2">{{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="small">Diskon: {{ number_format($discountAmount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table class="summary small">
        <tr><td class="label">Subtotal</td><td class="value">{{ number_format($gross, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Discount</td><td class="value">{{ number_format($discount, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Tax</td><td class="value">{{ number_format($tax, 0, ',', '.') }}</td></tr>
        <tr><td class="label bold">Total</td><td class="value bold">{{ number_format($total, 0, ',', '.') }}</td></tr>
        <tr><td class="label">Uang Diterima</td><td class="value">{{ number_format($paid, 0, ',', '.') }}</td></tr>
        <tr><td class="label bold">Kembalian</td><td class="value bold">{{ number_format($change, 0, ',', '.') }}</td></tr>
    </table>

    <div class="divider"></div>

    <div class="center small">
        <div>Tgl. {{ $transaction->transaction_at ? $transaction->transaction_at->format('d-m-Y H:i:s') : '-' }}</div>
        <div>{{ $transaction->transaction_code }}</div>
        <div>{{ $transaction->payment_method_label ?? $transaction->payment_method }}</div>
    </div>
</div>
</body>
</html>

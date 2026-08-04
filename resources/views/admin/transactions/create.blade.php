@extends('template-admin.layout')

@section('title', 'Input Transaksi')

@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
@endsection

@section('content')
@php
    $locations = $locations ?? [];
    $products = $products ?? [];
    $taxSettings = $taxSettings ?? [];
    $defaultTaxSetting = $defaultTaxSetting ?? null;

    $oldItems = old('items');
    $itemRows = is_array($oldItems) && count($oldItems)
        ? $oldItems
        : [[
            'product_id' => old('product_id'),
            'quantity' => old('quantity', 1),
        ]];

    $itemRows = collect($itemRows)->filter(function ($row) {
        return is_array($row) && (int) data_get($row, 'product_id', 0) > 0;
    })->values()->all();

    if (empty($itemRows)) {
        $itemRows = [[
            'product_id' => null,
            'quantity' => 1,
        ]];
    }
@endphp

<section class="page-card glass-card transaction-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">TRANSACTIONS</p>
            <h2>Input Transaksi</h2>
            <p>Tambahkan satu atau lebih product. Diskon promo diambil otomatis dari product, tax diambil dari tax setting, dan kembalian dihitung otomatis.</p>
        </div>

        <div class="page-card__actions">
            <a href="{{ route('transactions.index') }}" class="btn btn--secondary">
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

    <form action="{{ route('transactions.store') }}" method="POST" class="wizard-form page-form" data-step-form data-draft-key="transactions:create">
        @csrf

        <input type="hidden" name="transaction_at" value="{{ old('transaction_at', now()->format('Y-m-d H:i:s')) }}">
        <input type="hidden" name="cashier_id" value="{{ auth()->id() }}">

        <input type="hidden" name="subtotal" value="0" data-summary-field="subtotal-hidden">
        <input type="hidden" name="discount_amount" value="0" data-summary-field="discount-hidden">
        <input type="hidden" name="tax_amount" value="0" data-summary-field="tax-hidden">
        <input type="hidden" name="total_amount" value="0" data-summary-field="total-hidden">
        <input type="hidden" name="change_amount" value="0" data-summary-field="change-hidden">

        <div class="stepper">
            <span class="step active" data-step-indicator="1">1</span>
            <span class="step" data-step-indicator="2">2</span>
            <span class="step" data-step-indicator="3">3</span>
        </div>

        <div class="wizard-body">
            <section class="wizard-step active" data-step="1">
                <div class="wizard-step__head">
                    <h4>Header & Item Barang</h4>
                    <p>Pilih location, tax setting, lalu isi item barang keluar.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Location</span>
                        <select name="location_id" required data-summary-input="location">
                            <option value="">Pilih location</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Tax Setting</span>
                        <select name="tax_setting_id" required data-summary-input="tax-setting">
                            <option value="">Pilih pajak</option>
                            @foreach ($taxSettings as $taxSetting)
                                <option
                                    value="{{ $taxSetting->id }}"
                                    data-tax-type="{{ $taxSetting->tax_type }}"
                                    data-tax-value="{{ $taxSetting->tax_value }}"
                                    {{ old('tax_setting_id', $defaultTaxSetting?->id) == $taxSetting->id ? 'selected' : '' }}>
                                    {{ $taxSetting->name }} ({{ $taxSetting->display_value }})
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Shift</span>
                        <select name="shift" required data-summary-input="shift">
                            <option value="morning" {{ old('shift', 'morning') === 'morning' ? 'selected' : '' }}>Morning</option>
                            <option value="afternoon" {{ old('shift') === 'afternoon' ? 'selected' : '' }}>Afternoon</option>
                            <option value="night" {{ old('shift') === 'night' ? 'selected' : '' }}>Night</option>
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Payment Method</span>
                        <select name="payment_method" required data-summary-input="payment-method">
                            <option value="cash" {{ old('payment_method', 'cash') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="qris" {{ old('payment_method') === 'qris' ? 'selected' : '' }}>QRIS</option>
                            <option value="transfer" {{ old('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                            <option value="debit" {{ old('payment_method') === 'debit' ? 'selected' : '' }}>Debit</option>
                            <option value="credit" {{ old('payment_method') === 'credit' ? 'selected' : '' }}>Credit</option>
                            <option value="mixed" {{ old('payment_method') === 'mixed' ? 'selected' : '' }}>Mixed</option>
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Transaction At</span>
                        <input type="text" value="{{ now()->format('d M Y H:i') }}" disabled>
                    </label>

                    <label class="form-field">
                        <span>Customer Name</span>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}" placeholder="Nama pelanggan" autocomplete="off">
                    </label>

                    <label class="form-field">
                        <span>Customer Phone</span>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone') }}" placeholder="08xxxxxxxxxx" autocomplete="off">
                    </label>

                    <label class="form-field form-field--full">
                        <span>Notes</span>
                        <textarea name="notes" rows="3" placeholder="Catatan transaksi...">{{ old('notes') }}</textarea>
                    </label>
                </div>

                <div class="table-card glass-card" style="margin-top: 16px;">
                    <div class="table-card__head">
                        <div>
                            <p class="eyebrow">ITEM</p>
                            <h3>Barang terjual</h3>
                        </div>

                        <button type="button" class="btn btn--secondary" data-action="add-transaction-item">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            Tambah Baris
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table data-table--compact" id="transactionItemsTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Qty</th>
                                    <th>Unit Price</th>
                                    <th>Promo / Pcs</th>
                                    <th>Stock</th>
                                    <th>Line Total</th>
                                    <th class="th-actions">Action</th>
                                </tr>
                            </thead>
                            <tbody data-transaction-items-body>
                                @foreach ($itemRows as $index => $row)
                                    <tr data-item-row>
                                        <td style="min-width: 320px;">
                                            <select name="items[{{ $index }}][product_id]" data-product-select required>
                                                <option value="">Pilih product</option>
                                                @foreach ($products as $product)
                                                    <option
                                                        value="{{ $product->id }}"
                                                        data-sale-price="{{ (int) $product->sale_price }}"
                                                        data-promo-discount="{{ (int) $product->effective_discount_amount }}"
                                                        data-stock-on-hand="{{ (int) $product->stock_on_hand }}"
                                                        data-product-name="{{ $product->name }}"
                                                        data-unit-label="{{ optional($product->unit)->symbol ?? '-' }}"
                                                        {{ (int) old('items.' . $index . '.product_id', $row['product_id'] ?? 0) === (int) $product->id ? 'selected' : '' }}>
                                                        {{ $product->name }} — Rp {{ number_format((int) $product->sale_price, 0, ',', '.') }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td style="width: 120px;">
                                            <input
                                                type="number"
                                                name="items[{{ $index }}][quantity]"
                                                data-qty-input
                                                min="1"
                                                step="1"
                                                value="{{ old('items.' . $index . '.quantity', $row['quantity'] ?? 1) }}"
                                                required>
                                            <small class="text-muted" data-stock-warning>Stok: -</small>
                                        </td>
                                        <td style="width: 150px;">
                                            <input type="text" data-unit-price-input readonly value="Rp 0">
                                        </td>
                                        <td style="width: 150px;">
                                            <input type="text" data-discount-input readonly value="Rp 0">
                                        </td>
                                        <td style="width: 120px;">
                                            <input type="text" data-stock-display readonly value="-">
                                        </td>
                                        <td style="width: 150px;">
                                            <input type="text" data-line-total-display readonly value="Rp 0">
                                        </td>
                                        <td class="td-actions" style="width: 70px;">
                                            <button type="button" class="icon-btn icon-btn--danger" data-remove-row aria-label="Remove row">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <template id="transactionItemTemplate">
                        <tr data-item-row>
                            <td style="min-width: 320px;">
                                <select name="items[__INDEX__][product_id]" data-product-select required>
                                    <option value="">Pilih product</option>
                                    @foreach ($products as $product)
                                        <option
                                            value="{{ $product->id }}"
                                            data-sale-price="{{ (int) $product->sale_price }}"
                                            data-promo-discount="{{ (int) $product->effective_discount_amount }}"
                                            data-stock-on-hand="{{ (int) $product->stock_on_hand }}"
                                            data-product-name="{{ $product->name }}"
                                            data-unit-label="{{ optional($product->unit)->symbol ?? '-' }}">
                                            {{ $product->name }} — Rp {{ number_format((int) $product->sale_price, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td style="width: 120px;">
                                <input type="number" name="items[__INDEX__][quantity]" data-qty-input min="1" step="1" value="1" required>
                                <small class="text-muted" data-stock-warning>Stok: -</small>
                            </td>
                            <td style="width: 150px;">
                                <input type="text" data-unit-price-input readonly value="Rp 0">
                            </td>
                            <td style="width: 150px;">
                                <input type="text" data-discount-input readonly value="Rp 0">
                            </td>
                            <td style="width: 120px;">
                                <input type="text" data-stock-display readonly value="-">
                            </td>
                            <td style="width: 150px;">
                                <input type="text" data-line-total-display readonly value="Rp 0">
                            </td>
                            <td class="td-actions" style="width: 70px;">
                                <button type="button" class="icon-btn icon-btn--danger" data-remove-row aria-label="Remove row">
                                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="wizard-step__head">
                    <h4>Payment</h4>
                    <p>Masukkan uang pelanggan. Total tagihan, pajak, diskon, dan kembalian dihitung otomatis.</p>
                </div>

                <div class="wizard-form-grid">
                    <label class="form-field">
                        <span>Subtotal</span>
                        <input type="text" value="Rp 0" readonly data-summary-display="subtotal">
                    </label>

                    <label class="form-field">
                        <span>Total Diskon Promo</span>
                        <input type="text" value="Rp 0" readonly data-summary-display="discount">
                    </label>

                    <label class="form-field">
                        <span>Total Pajak</span>
                        <input type="text" value="Rp 0" readonly data-summary-display="tax">
                    </label>

                    <label class="form-field">
                        <span>Total Tagihan</span>
                        <input type="text" value="Rp 0" readonly data-summary-display="total">
                    </label>

                    <label class="form-field">
                        <span>Uang Diterima Pelanggan</span>
                        <input type="number" name="paid_amount" value="{{ old('paid_amount', 0) }}" min="0" step="1" data-paid-input required>
                    </label>

                    <label class="form-field">
                        <span>Kembalian Uang Pelanggan</span>
                        <input type="text" value="Rp 0" readonly data-summary-display="change">
                    </label>
                </div>
            </section>

            <section class="wizard-step" data-step="3">
                <div class="wizard-step__head">
                    <h4>Review & Submit</h4>
                    <p>Pastikan detail transaksi sudah benar sebelum disimpan.</p>
                </div>

                <div class="review-grid">
                    <div class="review-item">
                        <span>Location</span>
                        <strong data-review-field="location">-</strong>
                    </div>
                    <div class="review-item">
                        <span>Tax Setting</span>
                        <strong data-review-field="tax-setting">-</strong>
                    </div>
                    <div class="review-item">
                        <span>Shift</span>
                        <strong data-review-field="shift">-</strong>
                    </div>
                    <div class="review-item">
                        <span>Payment Method</span>
                        <strong data-review-field="payment-method">-</strong>
                    </div>
                    <div class="review-item">
                        <span>Customer Name</span>
                        <strong data-review-field="customer-name">-</strong>
                    </div>
                    <div class="review-item">
                        <span>Customer Phone</span>
                        <strong data-review-field="customer-phone">-</strong>
                    </div>
                    <div class="review-item">
                        <span>Transaction At</span>
                        <strong data-review-field="transaction-at">-</strong>
                    </div>
                    <div class="review-item review-item--full">
                        <span>Item Barang</span>
                        <div data-review-items class="review-stack">Belum ada item dipilih.</div>
                    </div>
                    <div class="review-item">
                        <span>Subtotal</span>
                        <strong data-review-field="subtotal">Rp 0</strong>
                    </div>
                    <div class="review-item">
                        <span>Total Diskon</span>
                        <strong data-review-field="discount">Rp 0</strong>
                    </div>
                    <div class="review-item">
                        <span>Total Pajak</span>
                        <strong data-review-field="tax">Rp 0</strong>
                    </div>
                    <div class="review-item">
                        <span>Total Tagihan</span>
                        <strong data-review-field="total">Rp 0</strong>
                    </div>
                    <div class="review-item">
                        <span>Uang Diterima</span>
                        <strong data-review-field="paid">Rp 0</strong>
                    </div>
                    <div class="review-item">
                        <span>Kembalian Uang Pelanggan</span>
                        <strong data-review-field="change">Rp 0</strong>
                    </div>
                    <div class="review-item review-item--full">
                        <span>Notes</span>
                        <p data-review-field="notes">-</p>
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
                    Simpan Transaksi
                </button>
            </div>
        </div>
    </form>
</section>
@endsection

@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
<script>
(function () {
    const form = document.querySelector('[data-step-form]');
    if (!form) return;

    const currencyFormatter = new Intl.NumberFormat('id-ID');

    const money = (value) => 'Rp ' + currencyFormatter.format(Math.max(0, Math.round(Number(value || 0))));
    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const body = form.querySelector('[data-transaction-items-body]');
    const template = document.getElementById('transactionItemTemplate');
    const addRowButton = form.querySelector('[data-action="add-transaction-item"]');
    const taxSelect = form.querySelector('select[name="tax_setting_id"]');
    const paymentMethodSelect = form.querySelector('select[name="payment_method"]');
    const paidInput = form.querySelector('[data-paid-input]');
    const reviewItems = form.querySelector('[data-review-items]');

    const displays = {
        subtotal: form.querySelector('[data-summary-display="subtotal"]'),
        discount: form.querySelector('[data-summary-display="discount"]'),
        tax: form.querySelector('[data-summary-display="tax"]'),
        total: form.querySelector('[data-summary-display="total"]'),
        change: form.querySelector('[data-summary-display="change"]'),
    };

    const hidden = {
        subtotal: form.querySelector('[data-summary-field="subtotal-hidden"]'),
        discount: form.querySelector('[data-summary-field="discount-hidden"]'),
        tax: form.querySelector('[data-summary-field="tax-hidden"]'),
        total: form.querySelector('[data-summary-field="total-hidden"]'),
        change: form.querySelector('[data-summary-field="change-hidden"]'),
    };

    const review = {
        location: form.querySelector('[data-review-field="location"]'),
        taxSetting: form.querySelector('[data-review-field="tax-setting"]'),
        shift: form.querySelector('[data-review-field="shift"]'),
        paymentMethod: form.querySelector('[data-review-field="payment-method"]'),
        customerName: form.querySelector('[data-review-field="customer-name"]'),
        customerPhone: form.querySelector('[data-review-field="customer-phone"]'),
        transactionAt: form.querySelector('[data-review-field="transaction-at"]'),
        subtotal: form.querySelector('[data-review-field="subtotal"]'),
        discount: form.querySelector('[data-review-field="discount"]'),
        tax: form.querySelector('[data-review-field="tax"]'),
        total: form.querySelector('[data-review-field="total"]'),
        paid: form.querySelector('[data-review-field="paid"]'),
        change: form.querySelector('[data-review-field="change"]'),
        notes: form.querySelector('[data-review-field="notes"]'),
    };

    const taxValue = () => {
        const selected = taxSelect?.selectedOptions?.[0];
        return {
            type: selected?.dataset.taxType || 'fixed',
            value: Number(selected?.dataset.taxValue || 0),
            label: selected ? selected.textContent.trim() : '-',
        };
    };

    const getRows = () => Array.from(body.querySelectorAll('[data-item-row]'));

    const rowData = (row) => {
        const select = row.querySelector('[data-product-select]');
        const option = select?.selectedOptions?.[0];
        return {
            select,
            option,
            productId: Number(select?.value || 0),
            name: option?.dataset.productName || '',
            salePrice: Number(option?.dataset.salePrice || 0),
            promoDiscount: Number(option?.dataset.promoDiscount || 0),
            stock: Number(option?.dataset.stockOnHand || 0),
            unit: option?.dataset.unitLabel || '-',
            qty: Number(row.querySelector('[data-qty-input]')?.value || 0),
        };
    };

    const usedQtyByProduct = () => {
        const totals = {};
        getRows().forEach((row) => {
            const data = rowData(row);
            if (!data.productId) return;
            totals[data.productId] = (totals[data.productId] || 0) + Math.max(0, data.qty);
        });
        return totals;
    };

    const updateRow = (row) => {
        const data = rowData(row);
        const qtyInput = row.querySelector('[data-qty-input]');
        const unitPriceInput = row.querySelector('[data-unit-price-input]');
        const discountInput = row.querySelector('[data-discount-input]');
        const stockDisplay = row.querySelector('[data-stock-display]');
        const stockWarning = row.querySelector('[data-stock-warning]');
        const lineTotalDisplay = row.querySelector('[data-line-total-display]');
        const totals = usedQtyByProduct();

        if (data.productId) {
            const remainingForRow = Math.max(0, data.stock - ((totals[data.productId] || 0) - Math.max(0, data.qty)));
            qtyInput.max = String(remainingForRow || data.stock || '');
            if (Number(qtyInput.value || 0) > remainingForRow && remainingForRow > 0) {
                qtyInput.value = String(remainingForRow);
            }
            unitPriceInput.value = money(data.salePrice);
            discountInput.value = money(data.promoDiscount);
            stockDisplay.value = currencyFormatter.format(data.stock);
            stockWarning.textContent = `Stok tersedia: ${currencyFormatter.format(data.stock)} ${data.unit}`;
        } else {
            qtyInput.max = '';
            unitPriceInput.value = money(0);
            discountInput.value = money(0);
            stockDisplay.value = '-';
            stockWarning.textContent = 'Stok: -';
        }

        const qty = Math.max(1, Number(qtyInput.value || 1));
        const lineGross = data.salePrice * qty;
        const lineDiscount = Math.min(lineGross, data.promoDiscount * qty);
        const lineNet = Math.max(0, lineGross - lineDiscount);
        lineTotalDisplay.value = money(lineNet);

        return {
            ...data,
            qty,
            lineGross,
            lineDiscount,
            lineNet,
        };
    };

    const calc = () => {
        const rows = getRows().map(updateRow).filter((row) => row.productId);
        const totals = rows.reduce((acc, row) => {
            acc.subtotal += row.lineGross;
            acc.discount += row.lineDiscount;
            return acc;
        }, { subtotal: 0, discount: 0 });

        const net = Math.max(0, totals.subtotal - totals.discount);
        const tax = taxValue();
        const taxAmount = tax.type === 'percent'
            ? Math.round((net * tax.value) / 100)
            : Math.max(0, tax.value);
        const total = Math.max(0, net + taxAmount);

        if (paymentMethodSelect.value !== 'cash') {
            paidInput.value = String(total);
            paidInput.readOnly = true;
        } else {
            paidInput.readOnly = false;
        }

        const paid = Number(paidInput.value || 0);
        const change = Math.max(0, paid - total);

        displays.subtotal.value = money(totals.subtotal);
        displays.discount.value = money(totals.discount);
        displays.tax.value = money(taxAmount);
        displays.total.value = money(total);
        displays.change.value = money(change);

        hidden.subtotal.value = String(totals.subtotal);
        hidden.discount.value = String(totals.discount);
        hidden.tax.value = String(taxAmount);
        hidden.total.value = String(total);
        hidden.change.value = String(change);

        review.location.textContent = form.querySelector('select[name="location_id"] option:checked')?.textContent?.trim() || '-';
        review.taxSetting.textContent = tax.label;
        review.shift.textContent = form.querySelector('select[name="shift"] option:checked')?.textContent?.trim() || '-';
        review.paymentMethod.textContent = form.querySelector('select[name="payment_method"] option:checked')?.textContent?.trim() || '-';
        review.customerName.textContent = form.querySelector('input[name="customer_name"]')?.value?.trim() || '-';
        review.customerPhone.textContent = form.querySelector('input[name="customer_phone"]')?.value?.trim() || '-';
        review.transactionAt.textContent = form.querySelector('input[name="transaction_at"]')?.value || '-';
        review.subtotal.textContent = money(totals.subtotal);
        review.discount.textContent = money(totals.discount);
        review.tax.textContent = money(taxAmount);
        review.total.textContent = money(total);
        review.paid.textContent = money(paid);
        review.change.textContent = money(change);
        review.notes.textContent = form.querySelector('textarea[name="notes"]')?.value?.trim() || '-';

        if (reviewItems) {
            if (!rows.length) {
                reviewItems.innerHTML = 'Belum ada item dipilih.';
            } else {
                reviewItems.innerHTML = rows.map((row) => {
                    const select = row.querySelector('[data-product-select]');
                    const option = select?.selectedOptions?.[0];
                    const name = option?.dataset.productName || '-';
                    const qty = Number(row.querySelector('[data-qty-input]')?.value || 0);
                    const unitPrice = Number(option?.dataset.salePrice || 0);
                    const promo = Number(option?.dataset.promoDiscount || 0);
                    const gross = qty * unitPrice;
                    const discount = Math.min(gross, promo * qty);
                    const net = Math.max(0, gross - discount);

                    return `
                        <div class="form-alert form-alert--info" style="margin: 0 0 8px 0;">
                            <strong>${escapeHtml(name)}</strong>
                            <div>Qty: ${qty} | Unit: ${money(unitPrice)} | Promo/Pcs: ${money(promo)} | Subtotal: ${money(net)}</div>
                        </div>
                    `;
                }).join('');
            }
        }

        return { subtotal: totals.subtotal, discount: totals.discount, taxAmount, total, paid, change };
    };

    const reindexRows = () => {
        getRows().forEach((row, index) => {
            const productSelect = row.querySelector('[data-product-select]');
            const qtyInput = row.querySelector('[data-qty-input]');
            if (productSelect) {
                productSelect.name = `items[${index}][product_id]`;
            }
            if (qtyInput) {
                qtyInput.name = `items[${index}][quantity]`;
            }
        });
    };

    const addRow = () => {
        const index = getRows().length;
        const html = template.innerHTML.replaceAll('__INDEX__', index);
        const wrapper = document.createElement('tbody');
        wrapper.innerHTML = html.trim();
        const row = wrapper.firstElementChild;
        body.appendChild(row);
        bindRow(row);
        reindexRows();
        calc();
    };

    const removeRow = (row) => {
        const rows = getRows();
        if (rows.length <= 1) {
            row.querySelector('[data-product-select]').value = '';
            row.querySelector('[data-qty-input]').value = 1;
            calc();
            return;
        }

        row.remove();
        reindexRows();
        calc();
    };

    const bindRow = (row) => {
        row.querySelector('[data-product-select]')?.addEventListener('change', calc);
        row.querySelector('[data-qty-input]')?.addEventListener('input', calc);
        row.querySelector('[data-remove-row]')?.addEventListener('click', () => removeRow(row));
    };

    getRows().forEach(bindRow);

    addRowButton?.addEventListener('click', addRow);

    form.querySelector('select[name="location_id"]')?.addEventListener('change', calc);
    form.querySelector('select[name="shift"]')?.addEventListener('change', calc);
    form.querySelector('select[name="tax_setting_id"]')?.addEventListener('change', calc);
    form.querySelector('select[name="payment_method"]')?.addEventListener('change', calc);
    form.querySelector('input[name="customer_name"]')?.addEventListener('input', calc);
    form.querySelector('input[name="customer_phone"]')?.addEventListener('input', calc);
    form.querySelector('textarea[name="notes"]')?.addEventListener('input', calc);
    paidInput?.addEventListener('input', calc);

    calc();
})();
</script>
@endsection

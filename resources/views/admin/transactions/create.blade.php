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
@endphp

<section class="page-card glass-card transaction-page">
    <div class="page-card__head">
        <div class="page-card__title">
            <p class="eyebrow">TRANSACTIONS</p>
            <h2>Input Transaksi</h2>
            <p>Isi data barang keluar 1 product saja. Diskon promo diambil otomatis dari product, tax diambil dari tax setting, dan kembalian dihitung otomatis.</p>
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
                    <p>Pilih location, tax setting, lalu pilih 1 product yang akan dijual.</p>
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
                            <option value="ewallet" {{ old('payment_method') === 'ewallet' ? 'selected' : '' }}>E-Wallet</option>
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
                        <span class="mono-chip">Diskon promo diambil otomatis dari product</span>
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
                                </tr>
                            </thead>
                            <tbody>
                                <tr data-item-row>
                                    <td style="min-width: 320px;">
                                        <select name="product_id" data-product-select required>
                                            <option value="">Pilih product</option>
                                            @foreach ($products as $product)
                                                <option
                                                    value="{{ $product->id }}"
                                                    data-sale-price="{{ (int) $product->sale_price }}"
                                                    data-promo-discount="{{ (int) $product->effective_discount_amount }}"
                                                    data-stock-on-hand="{{ (int) $product->stock_on_hand }}"
                                                    data-product-name="{{ $product->name }}"
                                                    data-unit-label="{{ optional($product->unit)->symbol ?? '-' }}"
                                                    {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                                    {{ $product->name }} — Rp {{ number_format((int) $product->sale_price, 0, ',', '.') }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td style="width: 120px;">
                                        <input type="number" name="quantity" data-qty-input min="1" step="1" value="{{ old('quantity', 1) }}" required>
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
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="wizard-step" data-step="2">
                <div class="wizard-step__head">
                    <h4>Payment</h4>
                    <p>Masukkan uang pelanggan. Total, pajak, diskon, dan kembalian dihitung otomatis.</p>
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
                        <span>Produk</span>
                        <p data-review-field="product-summary">Belum ada product dipilih.</p>
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

    const money = (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(Number(value || 0))));
    const productSelect = form.querySelector('[data-product-select]');
    const qtyInput = form.querySelector('[data-qty-input]');
    const unitPriceInput = form.querySelector('[data-unit-price-input]');
    const discountInput = form.querySelector('[data-discount-input]');
    const stockDisplay = form.querySelector('[data-stock-display]');
    const stockWarning = form.querySelector('[data-stock-warning]');
    const taxSelect = form.querySelector('select[name="tax_setting_id"]');
    const paymentMethodSelect = form.querySelector('select[name="payment_method"]');
    const paidInput = form.querySelector('[data-paid-input]');

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
        productSummary: form.querySelector('[data-review-field="product-summary"]'),
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

    const productInfo = () => {
        const selected = productSelect?.selectedOptions?.[0];
        return {
            id: selected?.value || '',
            name: selected?.dataset.productName || '',
            salePrice: Number(selected?.dataset.salePrice || 0),
            promoDiscount: Number(selected?.dataset.promoDiscount || 0),
            stock: Number(selected?.dataset.stockOnHand || 0),
            unit: selected?.dataset.unitLabel || '-',
        };
    };

    const syncProduct = () => {
        const info = productInfo();
        const qty = Number(qtyInput.value || 0);

        unitPriceInput.value = money(info.salePrice);
        discountInput.value = money(info.promoDiscount);
        stockDisplay.value = info.id ? String(info.stock) : '-';
        stockWarning.textContent = info.id ? `Stok tersedia: ${new Intl.NumberFormat('id-ID').format(info.stock)} ${info.unit}` : 'Stok: -';

        qtyInput.max = info.stock > 0 ? String(info.stock) : '';
        if (info.stock > 0 && qty > info.stock) {
            qtyInput.value = String(info.stock);
        }
    };

    const calc = () => {
        const info = productInfo();
        const qty = Math.max(1, Number(qtyInput.value || 1));
        const unitPrice = Number(info.salePrice || 0);
        const promo = Number(info.promoDiscount || 0);

        const subtotal = qty * unitPrice;
        const discountTotal = Math.min(subtotal, qty * promo);
        const net = Math.max(0, subtotal - discountTotal);
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

        displays.subtotal.value = money(subtotal);
        displays.discount.value = money(discountTotal);
        displays.tax.value = money(taxAmount);
        displays.total.value = money(total);
        displays.change.value = money(change);

        hidden.subtotal.value = String(subtotal);
        hidden.discount.value = String(discountTotal);
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
        review.productSummary.textContent = info.id
            ? `${info.name} • Qty ${qty} • Unit ${money(unitPrice)} • Promo/Pcs ${money(promo)}`
            : 'Belum ada product dipilih.';
        review.subtotal.textContent = money(subtotal);
        review.discount.textContent = money(discountTotal);
        review.tax.textContent = money(taxAmount);
        review.total.textContent = money(total);
        review.paid.textContent = money(paid);
        review.change.textContent = money(change);
        review.notes.textContent = form.querySelector('textarea[name="notes"]')?.value?.trim() || '-';

        return { subtotal, discountTotal, taxAmount, total, paid, change };
    };

    [productSelect, qtyInput, taxSelect, paymentMethodSelect, paidInput].forEach((el) => {
        el?.addEventListener('change', () => {
            syncProduct();
            calc();
        });
        el?.addEventListener('input', () => {
            syncProduct();
            calc();
        });
    });

    form.querySelector('select[name="location_id"]')?.addEventListener('change', calc);
    form.querySelector('select[name="shift"]')?.addEventListener('change', calc);
    form.querySelector('input[name="customer_name"]')?.addEventListener('input', calc);
    form.querySelector('input[name="customer_phone"]')?.addEventListener('input', calc);
    form.querySelector('textarea[name="notes"]')?.addEventListener('input', calc);

    syncProduct();
    calc();
})();
</script>
@endsection

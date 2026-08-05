@extends('template-admin.layout')
@section('title', 'Input Transaksi')
@section('css')
<link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}">
<style>
  .scan-mode-switch{display:flex;gap:12px;flex-wrap:wrap;margin:12px 0 18px}
  .scan-mode-switch .btn.is-active{transform:translateY(-1px);box-shadow:0 10px 24px rgba(0,0,0,.12)}
  .scanner-panel{display:none;margin-top:16px;padding:16px;border:1px solid rgba(255,255,255,.12);border-radius:18px;background:rgba(255,255,255,.04)}
  .scanner-panel.is-active{display:block}
  .scanner-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(280px,.85fr);gap:16px;align-items:start}
  .scanner-camera{position:relative;border-radius:18px;overflow:hidden;min-height:320px;background:#0b1220;border:1px solid rgba(255,255,255,.08)}
  .scanner-video{width:100%;height:100%;min-height:320px;object-fit:cover;display:block}
  .scanner-overlay{position:absolute;inset:auto 14px 14px 14px;padding:12px 14px;border-radius:16px;background:rgba(8,15,28,.82);backdrop-filter:blur(10px);color:#fff}
  .scanner-title{font-weight:700;font-size:16px;margin-bottom:4px}
  .scanner-status{font-size:13px;opacity:.9;line-height:1.45}
  .scan-pill{display:inline-flex;align-items:center;gap:8px;padding:7px 12px;border-radius:999px;background:rgba(255,255,255,.08);font-size:12px;font-weight:600;margin-bottom:10px}
  .scan-controls{display:grid;gap:10px}
  .scan-controls input[type="text"]{width:100%}
  .scan-meta{display:grid;gap:8px;margin-top:10px}
  .scan-meta .review-item{padding:12px 14px;border-radius:14px}
  .scan-empty{padding:14px;border:1px dashed rgba(255,255,255,.18);border-radius:16px;background:rgba(255,255,255,.03)}
  .mode-helper{margin-top:12px;padding:14px 16px;border-radius:16px;background:rgba(61,133,255,.08);border:1px solid rgba(61,133,255,.18)}
  .mode-helper strong{display:block;margin-bottom:4px}
  @media (max-width: 980px){
    .scanner-grid{grid-template-columns:1fr}
    .scanner-camera{min-height:260px}
    .scanner-video{min-height:260px}
  }
</style>
@endsection
@section('content')
@php
$locations = $locations ?? [];
$products = $products ?? [];
$taxSettings = $taxSettings ?? [];
$defaultTaxSetting = $defaultTaxSetting ?? null;
$discountSettings = $discountSettings ?? [];
$discountSettingsPayload = $discountSettings->map(function ($setting) {
    return [
        'id' => $setting->id,
        'name' => $setting->name,
        'code' => $setting->code,
        'discount_type' => $setting->discount_type,
        'discount_value' => (int) $setting->discount_value,
        'minimum_total_amount' => (int) $setting->minimum_total_amount,
        'starts_at' => optional($setting->starts_at)->toIso8601String(),
        'ends_at' => optional($setting->ends_at)->toDateString(),
        'priority' => (int) $setting->priority,
        'is_default' => (bool) $setting->is_default,
        'is_active' => (bool) $setting->is_active,
    ];
})->values();
$productCatalog = $products->mapWithKeys(function ($product) {
    $barcode = preg_replace('/\D/', '', (string) $product->barcode);
    return [$barcode => [
        'id' => (int) $product->id,
        'barcode' => $barcode,
        'name' => $product->name,
        'sale_price' => (int) $product->sale_price,
        'promo_discount' => (int) $product->effective_discount_amount,
        'stock_on_hand' => (int) $product->stock_on_hand,
        'unit_label' => optional($product->unit)->symbol ?? optional($product->unit)->name ?? '-',
    ]];
})->all();
@endphp
<section class="page-card glass-card">
  <div class="page-card__head">
    <div class="page-card__title">
      <p class="eyebrow">TRANSACTIONS</p>
      <h2>Input Transaksi</h2>
      <p>Masuk ke mode scan barcode untuk input cepat lewat kamera laptop, atau tetap gunakan mode manual jika dibutuhkan.</p>
    </div>
    <div class="page-card__actions">
      <a href="{{ route('transactions.index') }}" class="btn btn--secondary">Kembali</a>
    </div>
  </div>

  @if($errors->any())
    <div class="form-alert form-alert--danger">
      <strong>Periksa kembali data yang diisi.</strong>
      <ul>
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="scan-mode-switch">
    <button type="button" class="btn btn--primary is-active" data-mode-switch="scan">Scan Barcode</button>
    <button type="button" class="btn btn--secondary" data-mode-switch="manual">Manual</button>
  </div>

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
          <h4>Scan / Input Item</h4>
          <p>Scan barcode barang satu per satu. Jika barang yang sama dipindai lagi, quantity otomatis bertambah.</p>
        </div>

        <div class="scanner-panel is-active" data-scanner-panel>
          <div class="scanner-grid">
            <div class="scanner-camera">
              <video class="scanner-video" autoplay playsinline muted data-scanner-video></video>
              <canvas data-scanner-canvas hidden></canvas>
              <div class="scanner-overlay">
                <div class="scanner-title">Arahkan barcode ke kamera</div>
                <div class="scanner-status" data-scan-status>Siap scan. Klik tombol mulai scan untuk mengaktifkan kamera laptop.</div>
              </div>
            </div>

            <div class="scan-controls">
              <div class="scan-pill">Mode scan barcode</div>
              <div class="scan-empty">
                <label class="form-field" style="margin:0;">
                  <span>Input barcode manual / fallback</span>
                  <input type="text" placeholder="Ketik barcode lalu Enter" data-barcode-input autocomplete="off">
                </label>
                <div class="page-card__actions" style="margin-top:10px;gap:10px;">
                  <button type="button" class="btn btn--primary" data-scan-start>Mulai Scan</button>
                  <button type="button" class="btn btn--secondary" data-scan-stop disabled>Stop</button>
                  <button type="button" class="btn btn--secondary" data-scan-add>manual add</button>
                </div>
              </div>

              <div class="scan-meta">
                <div class="review-item"><span>Barcode terakhir</span><strong data-last-barcode>-</strong></div>
                <div class="review-item"><span>Produk terakhir</span><strong data-last-product>-</strong></div>
                <div class="review-item"><span>Mode aktif</span><strong data-active-mode>Scan</strong></div>
              </div>
            </div>
          </div>
        </div>

        <div class="mode-helper">
          <strong>Alur scan</strong>
          Scan barcode → data produk langsung masuk tabel → lanjut ke tahap hitung → isi uang customer → review → save → detail transaksi → print PDF.
        </div>

        <div class="wizard-form-grid" style="margin-top:16px;">
          <label class="form-field"><span>Location</span>
            <select name="location_id" required>
              <option value="">Pilih location</option>
              @foreach($locations as $location)
                <option value="{{ $location->id }}">{{ $location->name }}</option>
              @endforeach
            </select>
          </label>

          <label class="form-field"><span>Tax Setting</span>
            <select name="tax_setting_id" required>
              @foreach($taxSettings as $taxSetting)
                <option value="{{ $taxSetting->id }}" data-tax-type="{{ $taxSetting->tax_type }}" data-tax-value="{{ $taxSetting->tax_value }}" {{ old('tax_setting_id', $defaultTaxSetting?->id) == $taxSetting->id ? 'selected' : '' }}>
                  {{ $taxSetting->name }} ({{ $taxSetting->display_value }})
                </option>
              @endforeach
            </select>
          </label>

          <label class="form-field"><span>Shift</span>
            <select name="shift" required>
              <option value="morning">Morning</option>
              <option value="afternoon">Afternoon</option>
              <option value="night">Night</option>
            </select>
          </label>

          <label class="form-field"><span>Payment Method</span>
            <select name="payment_method" required>
              <option value="cash">Cash</option>
              <option value="qris">QRIS</option>
              <option value="transfer">Transfer</option>
              <option value="debit">Debit</option>
              <option value="credit">Credit</option>
              <option value="mixed">Mixed</option>
            </select>
          </label>

          <label class="form-field"><span>Transaction At</span><input type="text" value="{{ now()->format('d M Y H:i') }}" disabled></label>
          <label class="form-field"><span>Customer Name</span><input type="text" name="customer_name" value="{{ old('customer_name') }}"></label>
          <label class="form-field"><span>Customer Phone</span><input type="text" name="customer_phone" value="{{ old('customer_phone') }}"></label>
          <label class="form-field form-field--full"><span>Notes</span><textarea name="notes" rows="3">{{ old('notes') }}</textarea></label>
        </div>

        <div class="table-card glass-card" style="margin-top:16px;">
          <div class="table-card__head">
            <div>
              <p class="eyebrow">ITEM</p>
              <h3>Barang terjual</h3>
            </div>
            <button type="button" class="btn btn--secondary" data-action="add-transaction-row">Tambah Baris</button>
          </div>

          <div class="table-responsive">
            <table class="data-table data-table--compact">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Qty</th>
                  <th>Unit Price</th>
                  <th>Promo / Pcs</th>
                  <th>Stock</th>
                  <th>Line Total</th>
                  <th class="th-actions">Actions</th>
                </tr>
              </thead>
              <tbody data-items-tbody>
                <tr data-item-row>
                  <td>
                    <select name="items[0][product_id]" data-product-select required>
                      <option value="">Pilih product</option>
                      @foreach($products as $product)
                        <option value="{{ $product->id }}" data-barcode="{{ preg_replace('/\D/', '', (string) $product->barcode) }}" data-sale-price="{{ (int)$product->sale_price }}" data-promo-discount="{{ (int)$product->effective_discount_amount }}" data-stock-on-hand="{{ (int)$product->stock_on_hand }}" data-product-name="{{ $product->name }}" data-unit-label="{{ optional($product->unit)->symbol ?? '-' }}">{{ $product->name }} — Rp {{ number_format((int)$product->sale_price,0,',','.') }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td><input type="number" name="items[0][quantity]" data-qty-input min="1" step="1" value="1" required><small class="text-muted" data-stock-warning>Stok: -</small></td>
                  <td><input type="text" data-unit-price-input readonly value="Rp 0"></td>
                  <td><input type="text" data-discount-input readonly value="Rp 0"></td>
                  <td><input type="text" data-stock-display readonly value="-"></td>
                  <td><input type="text" data-line-total-display readonly value="Rp 0"></td>
                  <td class="td-actions"><button type="button" class="icon-btn icon-btn--danger" data-action="remove-transaction-row"><i class="fa-solid fa-trash"></i></button></td>
                </tr>
              </tbody>
            </table>
          </div>

          <template id="transactionRowTemplate">
            <tr data-item-row>
              <td>
                <select data-product-select required>
                  <option value="">Pilih product</option>
                  @foreach($products as $product)
                    <option value="{{ $product->id }}" data-barcode="{{ preg_replace('/\D/', '', (string) $product->barcode) }}" data-sale-price="{{ (int)$product->sale_price }}" data-promo-discount="{{ (int)$product->effective_discount_amount }}" data-stock-on-hand="{{ (int)$product->stock_on_hand }}" data-product-name="{{ $product->name }}" data-unit-label="{{ optional($product->unit)->symbol ?? '-' }}">{{ $product->name }} — Rp {{ number_format((int)$product->sale_price,0,',','.') }}</option>
                  @endforeach
                </select>
              </td>
              <td><input type="number" data-qty-input min="1" step="1" value="1" required><small class="text-muted" data-stock-warning>Stok: -</small></td>
              <td><input type="text" data-unit-price-input readonly value="Rp 0"></td>
              <td><input type="text" data-discount-input readonly value="Rp 0"></td>
              <td><input type="text" data-stock-display readonly value="-"></td>
              <td><input type="text" data-line-total-display readonly value="Rp 0"></td>
              <td class="td-actions"><button type="button" class="icon-btn icon-btn--danger" data-action="remove-transaction-row"><i class="fa-solid fa-trash"></i></button></td>
            </tr>
          </template>
        </div>
      </section>

      <section class="wizard-step" data-step="2">
        <div class="wizard-step__head">
          <h4>Payment</h4>
          <p>Masukkan uang pelanggan. Diskon transaksi otomatis dihitung berdasarkan rule yang aktif.</p>
        </div>
        <div class="wizard-form-grid">
          <label class="form-field"><span>Subtotal</span><input type="text" value="Rp 0" readonly data-summary-display="subtotal"></label>
          <label class="form-field"><span>Total Diskon Promo Item</span><input type="text" value="Rp 0" readonly data-summary-display="item-discount"></label>
          <label class="form-field"><span>Diskon Transaksi Otomatis</span><input type="text" value="Rp 0" readonly data-summary-display="transaction-discount"><small data-discount-rule-note>Tidak ada diskon transaksi aktif.</small></label>
          <label class="form-field"><span>Subtotal Setelah Diskon</span><input type="text" value="Rp 0" readonly data-summary-display="after-discount"></label>
          <label class="form-field"><span>Total Pajak</span><input type="text" value="Rp 0" readonly data-summary-display="tax"></label>
          <label class="form-field"><span>Total Tagihan</span><input type="text" value="Rp 0" readonly data-summary-display="total"></label>
          <label class="form-field"><span>Uang Diterima Pelanggan</span><input type="number" name="paid_amount" value="{{ old('paid_amount',0) }}" min="0" step="1" data-paid-input required></label>
          <label class="form-field"><span>Kembalian Uang Pelanggan</span><input type="text" value="Rp 0" readonly data-summary-display="change"></label>
        </div>
      </section>

      <section class="wizard-step" data-step="3">
        <div class="wizard-step__head">
          <h4>Review & Submit</h4>
          <p>Pastikan detail transaksi sudah benar sebelum disimpan.</p>
        </div>
        <div class="review-grid">
          <div class="review-item"><span>Location</span><strong data-review-field="location">-</strong></div>
          <div class="review-item"><span>Tax Setting</span><strong data-review-field="tax-setting">-</strong></div>
          <div class="review-item"><span>Shift</span><strong data-review-field="shift">-</strong></div>
          <div class="review-item"><span>Payment Method</span><strong data-review-field="payment-method">-</strong></div>
          <div class="review-item"><span>Customer Name</span><strong data-review-field="customer-name">-</strong></div>
          <div class="review-item"><span>Customer Phone</span><strong data-review-field="customer-phone">-</strong></div>
          <div class="review-item"><span>Transaction At</span><strong data-review-field="transaction-at">-</strong></div>
          <div class="review-item review-item--full"><span>Produk</span><p data-review-field="product-summary">Belum ada product dipilih.</p></div>
          <div class="review-item"><span>Subtotal</span><strong data-review-field="subtotal">Rp 0</strong></div>
          <div class="review-item"><span>Total Diskon Promo Item</span><strong data-review-field="item-discount">Rp 0</strong></div>
          <div class="review-item"><span>Diskon Transaksi</span><strong data-review-field="transaction-discount">Rp 0</strong></div>
          <div class="review-item"><span>Subtotal Setelah Diskon</span><strong data-review-field="after-discount">Rp 0</strong></div>
          <div class="review-item"><span>Total Pajak</span><strong data-review-field="tax">Rp 0</strong></div>
          <div class="review-item"><span>Total Tagihan</span><strong data-review-field="total">Rp 0</strong></div>
          <div class="review-item"><span>Uang Diterima</span><strong data-review-field="paid">Rp 0</strong></div>
          <div class="review-item"><span>Kembalian Uang Pelanggan</span><strong data-review-field="change">Rp 0</strong></div>
          <div class="review-item review-item--full"><span>Diskon Otomatis</span><p data-review-field="discount-rule">Tidak ada diskon aktif.</p></div>
          <div class="review-item review-item--full"><span>Notes</span><p data-review-field="notes">-</p></div>
        </div>
      </section>
    </div>

    <div class="wizard-actions">
      <button class="btn btn--secondary" type="button" data-step-action="prev">Back</button>
      <div class="wizard-actions__right">
        <button class="btn btn--primary" type="button" data-step-action="next">Next</button>
        <button class="btn btn--primary" type="submit" data-step-submit hidden>Simpan Transaksi</button>
      </div>
    </div>
  </form>
</section>
@endsection
@section('js')
<script src="{{ asset('assets/js/layout.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
<script>
window.__DISCOUNT_SETTINGS__ = @json($discountSettingsPayload);
window.__PRODUCT_CATALOG__ = @json($productCatalog);
window.__BARCODE_LOOKUP_URL_TEMPLATE__ = @json(route('transactions.barcode-lookup', ['barcode' => '__CODE__']));
(function () {
  const form = document.querySelector('[data-step-form]');
  if (!form) return;

  const money = (value) => 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.max(0, Math.round(Number(value || 0))));
  const discountSettings = Array.isArray(window.__DISCOUNT_SETTINGS__) ? window.__DISCOUNT_SETTINGS__ : [];
  const productCatalog = window.__PRODUCT_CATALOG__ && typeof window.__PRODUCT_CATALOG__ === 'object' ? window.__PRODUCT_CATALOG__ : {};

  const tbody = form.querySelector('[data-items-tbody]');
  const template = document.getElementById('transactionRowTemplate');
  const addRowBtn = form.querySelector('[data-action="add-transaction-row"]');
  const paidInput = form.querySelector('[data-paid-input]');
  const taxSelect = form.querySelector('select[name="tax_setting_id"]');
  const paymentMethodSelect = form.querySelector('select[name="payment_method"]');
  const transactionAtInput = form.querySelector('input[name="transaction_at"]');
  const discountRuleNote = form.querySelector('[data-discount-rule-note]');
  const scanPanel = form.querySelector('[data-scanner-panel]');
  const scanVideo = form.querySelector('[data-scanner-video]');
  const scanCanvas = form.querySelector('[data-scanner-canvas]');
  const scanInput = form.querySelector('[data-barcode-input]');
  const scanStatus = form.querySelector('[data-scan-status]');
  const lastBarcode = form.querySelector('[data-last-barcode]');
  const lastProduct = form.querySelector('[data-last-product]');
  const activeModeLabel = form.querySelector('[data-active-mode]');
  const scanStart = form.querySelector('[data-scan-start]');
  const scanStop = form.querySelector('[data-scan-stop]');
  const scanManualAdd = form.querySelector('[data-scan-add]');
  const modeButtons = [...document.querySelectorAll('[data-mode-switch]')];

  const displays = {
    subtotal: form.querySelector('[data-summary-display="subtotal"]'),
    itemDiscount: form.querySelector('[data-summary-display="item-discount"]'),
    transactionDiscount: form.querySelector('[data-summary-display="transaction-discount"]'),
    afterDiscount: form.querySelector('[data-summary-display="after-discount"]'),
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
    itemDiscount: form.querySelector('[data-review-field="item-discount"]'),
    transactionDiscount: form.querySelector('[data-review-field="transaction-discount"]'),
    afterDiscount: form.querySelector('[data-review-field="after-discount"]'),
    tax: form.querySelector('[data-review-field="tax"]'),
    total: form.querySelector('[data-review-field="total"]'),
    paid: form.querySelector('[data-review-field="paid"]'),
    change: form.querySelector('[data-review-field="change"]'),
    discountRule: form.querySelector('[data-review-field="discount-rule"]'),
    notes: form.querySelector('[data-review-field="notes"]'),
  };

  const stepIndicators = [...form.querySelectorAll('[data-step-indicator]')];
  const stepSections = [...form.querySelectorAll('[data-step]')];
  const prevBtn = form.querySelector('[data-step-action="prev"]');
  const nextBtn = form.querySelector('[data-step-action="next"]');
  const submitBtn = form.querySelector('[data-step-submit]');

  let activeStep = 1;
  let activeMode = 'scan';
  let cameraStream = null;
  let detector = null;
  let scanning = false;
  let detectionFrameId = null;
  let lastScanCode = '';
  let lastScanAt = 0;
  let cameraBusy = false;
  let quaggaHandler = null;
  let quaggaReady = false;

  const normalizeBarcode = (value) => String(value || '').replace(/\D/g, '').trim();
  const parseDate = (value) => {
    if (!value) return null;
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
  };

  const taxValue = () => {
    const selected = taxSelect?.selectedOptions?.[0];
    return {
      type: selected?.dataset.taxType || 'fixed',
      value: Number(selected?.dataset.taxValue || 0),
      label: selected ? selected.textContent.trim() : '-',
    };
  };

  const productInfoFromRow = (row) => {
    const select = row.querySelector('[data-product-select]');
    const selected = select?.selectedOptions?.[0];
    return {
      id: selected?.value || '',
      barcode: normalizeBarcode(selected?.dataset.barcode || ''),
      name: selected?.dataset.productName || '',
      salePrice: Number(selected?.dataset.salePrice || 0),
      promoDiscount: Number(selected?.dataset.promoDiscount || 0),
      stock: Number(selected?.dataset.stockOnHand || 0),
      unit: selected?.dataset.unitLabel || '-',
    };
  };

  const syncRowNames = () => {
    [...tbody.querySelectorAll('[data-item-row]')].forEach((row, index) => {
      const productSelect = row.querySelector('[data-product-select]');
      const quantityInput = row.querySelector('[data-qty-input]');
      if (productSelect) productSelect.name = `items[${index}][product_id]`;
      if (quantityInput) quantityInput.name = `items[${index}][quantity]`;
    });
  };

  const updateRow = (row) => {
    const info = productInfoFromRow(row);
    const qtyInput = row.querySelector('[data-qty-input]');
    const unitPriceInput = row.querySelector('[data-unit-price-input]');
    const discountInput = row.querySelector('[data-discount-input]');
    const stockDisplay = row.querySelector('[data-stock-display]');
    const stockWarning = row.querySelector('[data-stock-warning]');
    const lineTotalDisplay = row.querySelector('[data-line-total-display]');
    const qty = Math.max(1, Number(qtyInput?.value || 1));
    const unitPrice = Number(info.salePrice || 0);
    const promo = Number(info.promoDiscount || 0);
    const gross = qty * unitPrice;
    const itemDiscount = Math.min(gross, qty * promo);
    const lineNet = Math.max(0, gross - itemDiscount);

    if (unitPriceInput) unitPriceInput.value = money(unitPrice);
    if (discountInput) discountInput.value = money(promo);
    if (stockDisplay) stockDisplay.value = info.id ? String(info.stock) : '-';
    if (stockWarning) stockWarning.textContent = info.id ? `Stok tersedia: ${new Intl.NumberFormat('id-ID').format(info.stock)} ${info.unit}` : 'Stok: -';
    if (lineTotalDisplay) lineTotalDisplay.value = money(lineNet);
    if (info.stock > 0 && qty > info.stock) qtyInput.value = String(info.stock);

    return { gross, itemDiscount, lineNet, productName: info.name, qty, unitPrice, promo, barcode: info.barcode, id: info.id };
  };

  const resolveDiscountSetting = (baseAmount) => {
    const txDate = parseDate(transactionAtInput?.value) || new Date();
    const candidates = discountSettings.filter((setting) => {
      if (!setting.is_active) return false;
      const startsAt = parseDate(setting.starts_at);
      const endsAt = parseDate(setting.ends_at ? `${setting.ends_at}T23:59:59` : null);
      if (startsAt && startsAt > txDate) return false;
      if (endsAt && endsAt < txDate) return false;
      return baseAmount >= Number(setting.minimum_total_amount || 0);
    });

    candidates.sort((a, b) => (
      (Number(b.is_default) - Number(a.is_default)) ||
      (Number(b.priority || 0) - Number(a.priority || 0)) ||
      (Number(b.minimum_total_amount || 0) - Number(a.minimum_total_amount || 0)) ||
      (Number(b.discount_value || 0) - Number(a.discount_value || 0)) ||
      (Number(b.id || 0) - Number(a.id || 0))
    ));

    return candidates[0] || null;
  };

  const calculateDiscountAmount = (setting, baseAmount) => {
    if (!setting || baseAmount <= 0) return 0;
    if (setting.discount_type === 'percent') return Math.max(0, Math.round((baseAmount * Number(setting.discount_value || 0)) / 100));
    return Math.min(baseAmount, Math.max(0, Number(setting.discount_value || 0)));
  };

  const updateStepIndicators = () => {
    stepIndicators.forEach((indicator) => {
      const step = Number(indicator.dataset.stepIndicator || 0);
      indicator.classList.toggle('active', step === activeStep);
      indicator.classList.toggle('completed', step < activeStep);
    });

    stepSections.forEach((section) => {
      section.classList.toggle('active', Number(section.dataset.step || 0) === activeStep);
    });

    if (prevBtn) prevBtn.disabled = activeStep === 1;
    if (nextBtn) nextBtn.hidden = activeStep === 3;
    if (submitBtn) submitBtn.hidden = activeStep !== 3;
  };

  const goToStep = (step) => {
    activeStep = Math.min(3, Math.max(1, step));
    updateStepIndicators();
    if (activeStep === 1 && activeMode === 'scan' && !scanning) {
      // keep the panel visible, user can start camera anytime
    }
  };

  const summaryText = () => {
    const rows = [...tbody.querySelectorAll('[data-item-row]')];
    const lines = [];
    rows.forEach((row) => {
      const info = updateRow(row);
      if (info.productName && info.id) {
        lines.push(`${info.productName} • Qty ${info.qty} • Unit ${money(info.unitPrice)} • Promo/Pcs ${money(info.promo)}`);
      }
    });
    return lines;
  };

  const calc = () => {
    const rows = [...tbody.querySelectorAll('[data-item-row]')];
    let grossSubtotal = 0;
    let itemDiscountTotal = 0;
    const productSummaryLines = [];

    rows.forEach((row) => {
      const info = updateRow(row);
      grossSubtotal += info.gross;
      itemDiscountTotal += info.itemDiscount;
      if (info.productName && info.id) {
        productSummaryLines.push(`${info.productName} • Qty ${info.qty} • Unit ${money(info.unitPrice)} • Promo/Pcs ${money(info.promo)}`);
      }
    });

    const subtotalAfterItemDiscount = Math.max(0, grossSubtotal - itemDiscountTotal);
    const txSetting = resolveDiscountSetting(subtotalAfterItemDiscount);
    const txDiscount = calculateDiscountAmount(txSetting, subtotalAfterItemDiscount);
    const afterDiscount = Math.max(0, subtotalAfterItemDiscount - txDiscount);
    const tax = taxValue();
    const taxAmount = tax.type === 'percent' ? Math.round((afterDiscount * tax.value) / 100) : Math.max(0, tax.value);
    const total = Math.max(0, afterDiscount + taxAmount);

    if (paymentMethodSelect.value !== 'cash') {
      paidInput.value = String(total);
      paidInput.readOnly = true;
    } else {
      paidInput.readOnly = false;
    }

    const paid = Number(paidInput.value || 0);
    const change = Math.max(0, paid - total);

    displays.subtotal.value = money(grossSubtotal);
    displays.itemDiscount.value = money(itemDiscountTotal);
    displays.transactionDiscount.value = money(txDiscount);
    displays.afterDiscount.value = money(afterDiscount);
    displays.tax.value = money(taxAmount);
    displays.total.value = money(total);
    displays.change.value = money(change);

    hidden.subtotal.value = String(grossSubtotal);
    hidden.discount.value = String(txDiscount);
    hidden.tax.value = String(taxAmount);
    hidden.total.value = String(total);
    hidden.change.value = String(change);

    review.location.textContent = form.querySelector('select[name="location_id"] option:checked')?.textContent?.trim() || '-';
    review.taxSetting.textContent = tax.label;
    review.shift.textContent = form.querySelector('select[name="shift"] option:checked')?.textContent?.trim() || '-';
    review.paymentMethod.textContent = form.querySelector('select[name="payment_method"] option:checked')?.textContent?.trim() || '-';
    review.customerName.textContent = form.querySelector('input[name="customer_name"]')?.value?.trim() || '-';
    review.customerPhone.textContent = form.querySelector('input[name="customer_phone"]')?.value?.trim() || '-';
    review.transactionAt.textContent = transactionAtInput?.value || '-';
    review.productSummary.textContent = productSummaryLines.length ? productSummaryLines.join(' | ') : 'Belum ada product dipilih.';
    review.subtotal.textContent = money(grossSubtotal);
    review.itemDiscount.textContent = money(itemDiscountTotal);
    review.transactionDiscount.textContent = money(txDiscount);
    review.afterDiscount.textContent = money(afterDiscount);
    review.tax.textContent = money(taxAmount);
    review.total.textContent = money(total);
    review.paid.textContent = money(paid);
    review.change.textContent = money(change);
    review.discountRule.textContent = txSetting ? `${txSetting.code} — ${txSetting.name} (${txSetting.discount_type === 'percent' ? `${txSetting.discount_value}%` : money(txSetting.discount_value)}) untuk minimal ${money(txSetting.minimum_total_amount)}` : 'Tidak ada diskon transaksi aktif.';
    review.notes.textContent = form.querySelector('textarea[name="notes"]')?.value?.trim() || '-';
    if (discountRuleNote) discountRuleNote.textContent = txSetting ? `Diskon aktif: ${txSetting.code} (${txSetting.name})` : 'Tidak ada diskon transaksi aktif.';

    return { grossSubtotal, itemDiscountTotal, subtotalAfterItemDiscount, txDiscount, afterDiscount, taxAmount, total, change, productSummaryLines };
  };

  const findRowsByProductId = (productId) => [...tbody.querySelectorAll('[data-item-row]')].filter((row) => row.querySelector('[data-product-select]')?.value === String(productId));

  const bindRow = (row) => {
    const productSelect = row.querySelector('[data-product-select]');
    const qtyInput = row.querySelector('[data-qty-input]');
    const removeBtn = row.querySelector('[data-action="remove-transaction-row"]');

    productSelect?.addEventListener('change', () => { updateRow(row); calc(); });
    qtyInput?.addEventListener('input', () => { updateRow(row); calc(); });

    removeBtn?.addEventListener('click', () => {
      const rows = tbody.querySelectorAll('[data-item-row]');
      if (rows.length <= 1) {
        const ps = row.querySelector('[data-product-select]');
        const qi = row.querySelector('[data-qty-input]');
        if (ps) ps.value = '';
        if (qi) qi.value = 1;
        calc();
        return;
      }
      row.remove();
      syncRowNames();
      calc();
    });
  };

  const createRow = () => {
    const clone = template.content.firstElementChild.cloneNode(true);
    clone.querySelectorAll('input,select').forEach((el) => {
      if (el.type === 'number') el.value = 1;
      if (el.tagName === 'SELECT') el.value = '';
    });
    tbody.appendChild(clone);
    syncRowNames();
    bindRow(clone);
    return clone;
  };

  const setRowProduct = (row, product, increment = 1) => {
    const select = row.querySelector('[data-product-select]');
    const qtyInput = row.querySelector('[data-qty-input]');
    if (select) select.value = String(product.id);
    if (qtyInput) qtyInput.value = String(Math.max(1, increment));
    updateRow(row);
    calc();
  };

  const addProductToTable = (product, increment = 1) => {
    const foundRow = findRowsByProductId(product.id)[0];
    if (foundRow) {
      const qtyInput = foundRow.querySelector('[data-qty-input]');
      const currentQty = Number(qtyInput?.value || 1);
      if (qtyInput) qtyInput.value = String(Math.max(1, currentQty + increment));
      updateRow(foundRow);
      calc();
      return foundRow;
    }

    const row = createRow();
    setRowProduct(row, product, increment);
    return row;
  };

  const showScanMessage = (message) => {
    if (scanStatus) scanStatus.textContent = message;
  };

  const syncModeUI = () => {
    if (activeModeLabel) activeModeLabel.textContent = activeMode === 'scan' ? 'Scan' : 'Manual';
    if (scanPanel) scanPanel.classList.toggle('is-active', activeMode === 'scan');
    modeButtons.forEach((button) => {
      button.classList.toggle('is-active', button.dataset.modeSwitch === activeMode);
      button.classList.toggle('btn--primary', button.dataset.modeSwitch === activeMode);
      button.classList.toggle('btn--secondary', button.dataset.modeSwitch !== activeMode);
    });
  };

  const setMode = (mode) => {
    activeMode = mode === 'manual' ? 'manual' : 'scan';
    syncModeUI();
    if (activeMode === 'scan') {
      showScanMessage(scanning ? 'Kamera aktif. Arahkan barcode ke kamera.' : 'Mode scan aktif. Klik mulai scan untuk mengaktifkan kamera laptop.');
    } else {
      showScanMessage('Mode manual aktif. Anda tetap bisa ketik barcode lalu tekan Enter atau tambah baris manual.');
      stopCamera();
    }
  };

  const getProductByBarcode = async (barcode) => {
    const normalized = normalizeBarcode(barcode);
    if (!normalized) return null;

    if (productCatalog[normalized]) {
      return productCatalog[normalized];
    }

    const url = String(window.__BARCODE_LOOKUP_URL_TEMPLATE__ || '').replace('__CODE__', encodeURIComponent(normalized));
    if (!url) return null;

    try {
      const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!response.ok) return null;
      const json = await response.json();
      return json?.data || null;
    } catch (error) {
      return null;
    }
  };

  const processBarcode = async (barcode, source = 'scanner') => {
    const normalized = normalizeBarcode(barcode);
    if (!normalized) return false;

    const now = Date.now();
    if (normalized === lastScanCode && (now - lastScanAt) < 900) {
      return true;
    }
    lastScanCode = normalized;
    lastScanAt = now;

    if (lastBarcode) lastBarcode.textContent = normalized;
    showScanMessage(source === 'scanner' ? `Membaca barcode ${normalized}...` : `Mencari barcode ${normalized}...`);

    const product = await getProductByBarcode(normalized);
    if (!product) {
      showScanMessage(`Barcode ${normalized} tidak ditemukan di master product.`);
      if (lastProduct) lastProduct.textContent = '-';
      return false;
    }

    addProductToTable(product, 1);
    if (lastProduct) lastProduct.textContent = product.name;
    showScanMessage(`Produk ${product.name} ditambahkan ke tabel.`);
    goToStep(1);
    return true;
  };

  const stopCamera = () => {
    scanning = false;
    quaggaReady = false;
    if (scanStop) scanStop.disabled = true;
    if (scanStart) scanStart.disabled = false;
    if (detectionFrameId) {
      cancelAnimationFrame(detectionFrameId);
      detectionFrameId = null;
    }

    if (typeof window.Quagga !== 'undefined' && quaggaHandler) {
      try {
        window.Quagga.offDetected(quaggaHandler);
      } catch (error) {
        // noop
      }
      quaggaHandler = null;
      try {
        window.Quagga.stop();
      } catch (error) {
        // noop
      }
    }

    if (cameraStream) {
      cameraStream.getTracks().forEach((track) => track.stop());
      cameraStream = null;
    }
  };

  const scanLoop = async () => {
    if (!scanning || !scanVideo || !detector) return;
    try {
      const barcodes = await detector.detect(scanVideo);
      if (Array.isArray(barcodes) && barcodes.length > 0) {
        const detected = barcodes[0];
        const rawValue = detected.rawValue || detected.value || '';
        if (rawValue) {
          await processBarcode(rawValue, 'scanner');
        }
      }
    } catch (error) {
      // keep scanning
    }
    if (scanning) {
      detectionFrameId = requestAnimationFrame(scanLoop);
    }
  };

  const startQuaggaScanner = async () => {
    if (typeof window.Quagga === 'undefined') {
      showScanMessage('Scanner fallback belum termuat. Gunakan input barcode manual.');
      return false;
    }

    const scannerTarget = scanPanel?.querySelector('.scanner-camera') || scanVideo?.parentElement || scanPanel;

    return new Promise((resolve) => {
      window.Quagga.init({
        inputStream: {
          name: 'Live',
          type: 'LiveStream',
          target: scannerTarget,
          constraints: {
            facingMode: 'environment',
          },
        },
        locator: {
          patchSize: 'medium',
          halfSample: true,
        },
        numOfWorkers: Math.max(1, Math.min(4, navigator.hardwareConcurrency || 2)),
        frequency: 10,
        decoder: {
          readers: [
            'ean_reader',
            'ean_8_reader',
            'code_128_reader',
            'code_39_reader',
          ],
        },
        locate: true,
      }, (error) => {
        if (error) {
          showScanMessage('Gagal memulai fallback scanner. Gunakan input barcode manual.');
          resolve(false);
          return;
        }

        quaggaReady = true;
        quaggaHandler = (result) => {
          const code = result?.codeResult?.code || '';
          if (code) {
            processBarcode(code, 'scanner');
          }
        };

        window.Quagga.onDetected(quaggaHandler);
        window.Quagga.start();
        resolve(true);
      });
    });
  };

  const startCamera = async () => {
    if (!('mediaDevices' in navigator) || !navigator.mediaDevices.getUserMedia) {
      showScanMessage('Browser tidak mendukung kamera. Gunakan input barcode manual.');
      return;
    }

    try {
      if ('BarcodeDetector' in window) {
        detector = detector || new BarcodeDetector({ formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'qr_code'] });
        cameraStream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: { ideal: 'environment' } },
          audio: false,
        });

        if (scanVideo) {
          scanVideo.srcObject = cameraStream;
          await scanVideo.play();
        }

        scanning = true;
        if (scanStart) scanStart.disabled = true;
        if (scanStop) scanStop.disabled = false;
        showScanMessage('Kamera aktif. Arahkan barcode ke kamera dan tunggu data masuk ke tabel.');
        detectionFrameId = requestAnimationFrame(scanLoop);
        return;
      }

      showScanMessage('BarcodeDetector tidak didukung browser ini. Menyalakan scanner fallback...');
      const started = await startQuaggaScanner();
      if (!started) {
        stopCamera();
        return;
      }

      scanning = true;
      if (scanStart) scanStart.disabled = true;
      if (scanStop) scanStop.disabled = false;
      showScanMessage('Scanner fallback aktif. Arahkan barcode ke kamera laptop.');
    } catch (error) {
      stopCamera();
      showScanMessage('Gagal membuka kamera. Pastikan izin kamera diaktifkan dan coba lagi.');
    }
  };

  const manualBarcodeSubmit = async () => {
    const value = scanInput?.value || '';
    const ok = await processBarcode(value, 'manual');
    if (ok && scanInput) scanInput.value = '';
  };

  const syncBarcodeInputEnter = () => {
    if (!scanInput) return;
    scanInput.addEventListener('keydown', async (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        await manualBarcodeSubmit();
      }
    });
  };

  [...tbody.querySelectorAll('[data-item-row]')].forEach(bindRow);
  syncRowNames();

  addRowBtn?.addEventListener('click', () => {
    createRow();
    calc();
  });

  [...form.querySelectorAll('[data-product-select], [data-qty-input], select[name="location_id"], select[name="shift"], input[name="customer_name"], input[name="customer_phone"], textarea[name="notes"], [data-paid-input]')].forEach((el) => {
    el?.addEventListener('change', calc);
    el?.addEventListener('input', calc);
  });

  taxSelect?.addEventListener('change', calc);
  paymentMethodSelect?.addEventListener('change', calc);
  transactionAtInput?.addEventListener('change', calc);
  transactionAtInput?.addEventListener('input', calc);

  prevBtn?.addEventListener('click', () => goToStep(activeStep - 1));
  nextBtn?.addEventListener('click', () => {
    if (activeStep === 1) {
      const hasProduct = [...tbody.querySelectorAll('[data-product-select]')].some((el) => String(el.value || '').trim() !== '');
      if (!hasProduct) {
        showScanMessage('Minimal 1 produk harus dipilih atau discan.');
        goToStep(1);
        return;
      }
    }
    if (activeStep === 2) {
      calc();
    }
    goToStep(activeStep + 1);
  });

  form.addEventListener('submit', () => stopCamera());

  modeButtons.forEach((button) => {
    button.addEventListener('click', () => setMode(button.dataset.modeSwitch || 'scan'));
  });

  scanStart?.addEventListener('click', startCamera);
  scanStop?.addEventListener('click', stopCamera);
  scanManualAdd?.addEventListener('click', manualBarcodeSubmit);
  syncBarcodeInputEnter();

  setMode('scan');
  goToStep(1);
  calc();
})();
</script>
@endsection

<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title')</title>
  <meta name="description" content="Dark-first admin UI template with glass cards, sidebar toggle, light mode, skeleton loading, tables, wizard forms, and confirmation modals." />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
  @yield('css')
</head>
<body class="loading">
<div class="app-shell">
    @include('template-admin.sidebar')

    <div class="workspace">
      @include('template-admin.header')

      <main class="views">
       @yield('content')
       
      </main>
    </div>
  </div>

  <!-- Skeleton screen removed -->

  <!-- Wizard modal -->
  <div class="modal-backdrop" data-modal="wizard" hidden>
    <div class="modal glass-card modal--large" role="dialog" aria-modal="true" aria-labelledby="wizardTitle">
      <div class="modal__head">
        <div>
          <p class="eyebrow">Create flow</p>
          <h3 id="wizardTitle">Create Product</h3>
        </div>
        <button class="icon-btn" type="button" data-action="close-modal" aria-label="Close wizard">
          <i class="icon fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
      </div>

      <div class="stepper stepper--modal">
        <span class="step active" data-step-indicator="1">1</span>
        <span class="step" data-step-indicator="2">2</span>
        <span class="step" data-step-indicator="3">3</span>
      </div>

      <div class="wizard-body">
        <div class="wizard-step active" data-step="1">
          <div class="wizard-step__head">
            <h4>Basic Info</h4>
            <p>Identity, barcode, and category.</p>
          </div>
          <div class="wizard-form-grid">
            <label class="form-field">
              <span>Name</span>
              <input type="text" placeholder="Product name" />
            </label>
            <label class="form-field">
              <span>Barcode</span>
              <input type="text" placeholder="000123456789" />
            </label>
            <label class="form-field">
              <span>Category</span>
              <select>
                <option>Choose category</option>
                <option>Beverages</option>
                <option>Snacks</option>
              </select>
            </label>
            <label class="form-field">
              <span>Slug</span>
              <input type="text" placeholder="auto-generated" />
            </label>
          </div>
        </div>

        <div class="wizard-step" data-step="2">
          <div class="wizard-step__head">
            <h4>Pricing & Stock</h4>
            <p>Unit, supplier, prices, and minimum stock.</p>
          </div>
          <div class="wizard-form-grid">
            <label class="form-field">
              <span>Unit</span>
              <select>
                <option>pcs</option>
                <option>dus</option>
                <option>kg</option>
              </select>
            </label>
            <label class="form-field">
              <span>Supplier</span>
              <select>
                <option>Choose supplier</option>
                <option>PT Makmur</option>
                <option>CV Sumber</option>
              </select>
            </label>
            <label class="form-field">
              <span>Purchase Price</span>
              <input type="number" placeholder="0" />
            </label>
            <label class="form-field">
              <span>Sale Price</span>
              <input type="number" placeholder="0" />
            </label>
            <label class="form-field">
              <span>Min Stock</span>
              <input type="number" placeholder="0" />
            </label>
            <label class="form-field">
              <span>Track Expiry</span>
              <select>
                <option>Yes</option>
                <option>No</option>
              </select>
            </label>
          </div>
        </div>

        <div class="wizard-step" data-step="3">
          <div class="wizard-step__head">
            <h4>Media & Publish</h4>
            <p>Image, description, and publishing controls.</p>
          </div>
          <div class="wizard-form-grid">
            <label class="form-field form-field--full">
              <span>Description</span>
              <textarea rows="4" placeholder="Write a short product description..."></textarea>
            </label>
            <label class="form-field">
              <span>Image URL</span>
              <input type="text" placeholder="https://..." />
            </label>
            <label class="form-field">
              <span>Search Keywords</span>
              <input type="text" placeholder="coffee, sachet, instant" />
            </label>
          </div>
          <div class="toggle-line">
            <label>Featured product</label>
            <button class="switch switch--active" type="button" aria-pressed="true"><span></span></button>
          </div>
        </div>
      </div>

      <div class="wizard-actions">
        <button class="btn btn--secondary" type="button" data-action="wizard-back">Back</button>
        <div class="wizard-actions__right">
          <button class="btn btn--ghost" type="button" data-action="wizard-skip">Skip</button>
          <button class="btn btn--primary" type="button" data-action="wizard-next">Next</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Confirm modal -->
  <div class="modal-backdrop" data-modal="confirm" hidden>
    <div class="modal glass-card modal--small" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
      <div class="modal__head">
        <div class="confirm-icon" id="confirmIcon">
          <i class="icon fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        </div>
        <button class="icon-btn" type="button" data-action="close-modal" aria-label="Close dialog">
          <i class="icon fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
      </div>
      <h3 id="confirmTitle">Confirm action</h3>
      <p id="confirmMessage">Are you sure you want to continue?</p>
      <div class="modal__actions">
        <button class="btn btn--secondary" type="button" data-action="cancel-confirm">Cancel</button>
        <button class="btn btn--danger" type="button" data-action="confirm-ok">Confirm</button>
      </div>
    </div>
  </div>

  <div class="toast-stack" id="toastStack" aria-live="polite" aria-atomic="true"></div>
  <div class="theme-transition-veil" id="themeVeil" aria-hidden="true"></div>

  <script src="{{ asset('assets/js/script.js') }}"></script>
  @yield('js')
</body>
</html>

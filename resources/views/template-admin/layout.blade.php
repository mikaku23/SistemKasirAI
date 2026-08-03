<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title')</title>
  <meta name="description" content="Dark-first admin UI template with glass cards, sidebar toggle, tables, forms, and reusable page layouts." />
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


  <script>
    @php
      $flashToasts = array_values(array_filter([
        session('success') ? ['variant' => 'success', 'title' => 'Berhasil', 'message' => session('success')] : null,
        session('error') ? ['variant' => 'danger', 'title' => 'Gagal', 'message' => session('error')] : null,
        session('warning') ? ['variant' => 'warn', 'title' => 'Peringatan', 'message' => session('warning')] : null,
        session('info') ? ['variant' => 'info', 'title' => 'Info', 'message' => session('info')] : null,
        $errors->any() ? ['variant' => 'danger', 'title' => 'Validasi gagal', 'message' => 'Periksa kembali data yang diisi.'] : null,
      ]));
    @endphp

    window.__FLASH_TOASTS = @json($flashToasts);
  </script>

  <script src="{{ asset('assets/js/script.js') }}"></script>
  @yield('js')
</body>
</html>

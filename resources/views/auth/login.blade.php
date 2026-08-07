
@extends('template-auth.layout')

@section('title', 'Login Admin')

@section('content')
<main class="auth-page">
    <div class="auth-shell">
        <section class="auth-hero glass-card">
            <div class="auth-hero__content">
                <div class="auth-brand">
                    <div class="brand-mark">
                        <i class="fa-solid fa-shield-halved icon--brand" aria-hidden="true"></i>
                    </div>

                    @if(\Illuminate\Support\Facades\Auth::check())
                    @php
                    $role = \Illuminate\Support\Facades\Auth::user()->role->slug ?? null;
                    @endphp

                    @if($role === 'admin')
                    <script>
                        window.location = "{{ route('dashboardadmin') }}";
                    </script>
                    @elseif($role === 'cashier')
                    <script>
                        window.location = "{{ route('dashboardcashier') }}";
                    </script>
                    @endif
                    @endif
                    <div>
                        <p class="eyebrow">Secure Access</p>
                        <strong>Glass Admin</strong>
                    </div>
                </div>

                <div class="auth-badge">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    Role-based authentication
                </div>

                <h1>Masuk ke dashboard admin dengan aman.</h1>
                <p>
                    Gunakan username, email, NIM, atau NIP yang terdaftar. Login akan diarahkan otomatis ke dashboard admin jika role kamu memiliki slug <strong>admin</strong>.
                </p>

                <div class="auth-points">
                    <div class="auth-point">
                        <i class="fa-solid fa-user-shield icon" aria-hidden="true"></i>
                        <div>
                            <strong>Role validation</strong>
                            <span>Hanya akun dengan role admin yang dapat masuk ke panel administrator.</span>
                        </div>
                    </div>
                    <div class="auth-point">
                        <i class="fa-solid fa-location-dot icon" aria-hidden="true"></i>
                        <div>
                            <strong>Data terpusat</strong>
                            <span>Login memakai data user yang sudah tersimpan di database dan relasi role.</span>
                        </div>
                    </div>
                    <div class="auth-point">
                        <i class="fa-solid fa-bolt icon" aria-hidden="true"></i>
                        <div>
                            <strong>Fast redirect</strong>
                            <span>Setelah autentikasi berhasil, sistem langsung mengarah ke dashboard admin.</span>
                        </div>
                    </div>
                </div>

                <div class="auth-stats">
                    <div class="auth-stat">
                        <span>Credential field</span>
                        <strong>1 input</strong>
                    </div>
                    <div class="auth-stat">
                        <span>Password safety</span>
                        <strong>Hashed</strong>
                    </div>
                    <div class="auth-stat">
                        <span>Session mode</span>
                        <strong>Secure</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="auth-card glass-card">
            <div class="auth-card__inner">
                <div class="auth-card__head">
                    <p class="eyebrow">Admin Login</p>
                    <h2>Welcome back</h2>
                    <p>Masukkan kredensial untuk melanjutkan ke dashboard admin.</p>
                </div>

                @if (session('success'))
                <div class="form-alert form-alert--info">
                    <strong>{{ session('success') }}</strong>
                </div>
                @endif

                @if ($errors->any())
                <div class="form-alert form-alert--danger">
                    <strong>Login gagal.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('login') }}" method="POST" class="auth-form" autocomplete="off">
                    @csrf

                    <label class="auth-field">
                        <label for="login">Username / Email </label>
                        <input
                            id="login"
                            type="text"
                            name="login"
                            class="auth-input"
                            value="{{ old('login') }}"
                            placeholder="contoh: admin atau admin@example.com"
                            required
                            autofocus>
                    </label>

                    <label class="auth-field">
                        <label for="password">Password</label>
                        <div class="auth-input-wrap">
                            <input
                                id="password"
                                type="password"
                                name="password"
                                class="auth-input"
                                placeholder="Masukkan password"
                                required
                                autocomplete="current-password"
                                data-password-input>
                            <button type="button" class="auth-password-toggle" aria-label="Toggle password visibility" data-password-toggle>
                                <i class="fa-solid fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </label>

                    <div class="auth-actions">
                        <label class="auth-check">
                            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                            <span>Ingat saya</span>
                        </label>

                        <span class="muted">Akses khusus admin</span>
                    </div>

                    <button type="submit" class="btn btn--primary auth-submit">
                        <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                        Masuk
                    </button>
                </form>

                <div class="auth-note">
                    <strong>Catatan:</strong> akun login admin dibuat otomatis oleh seeder. Setelah seeder dijalankan, gunakan kredensial yang telah disiapkan di file seeder.
                </div>
            </div>
        </section>
    </div>
</main>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.querySelector('[data-password-toggle]');
        const input = document.querySelector('[data-password-input]');
        const icon = btn?.querySelector('i');

        if (!btn || !input || !icon) return;

        btn.addEventListener('click', () => {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
            btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });
    });
</script>
@endsection
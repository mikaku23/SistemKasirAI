<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:3'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'login.required' => 'Username, email, NIM, atau NIP wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $login = trim((string) $validated['login']);

        $user = User::with('role')
            ->where(function ($query) use ($login) {
                $query->where('username', $login)
                    ->orWhere('email', $login);
                   
            })
            ->first();

        if (! $user || ! Hash::check((string) $validated['password'], (string) $user->password)) {
            $this->auditSystem('warning', 'auth', 'Login gagal', [
                'action' => 'login_failed',
                'metadata' => [
                    'login' => $login,
                ],
            ]);

            return back()
                ->withErrors(['login' => 'Login gagal. Periksa kembali identitas dan password.'])
                ->withInput($request->only('login', 'remember'));
        }

        if (strtolower((string) $user->role?->slug) !== 'admin') {
            $this->auditSystem('warning', 'auth', 'Akun tidak memiliki akses admin', [
                'action' => 'login_blocked',
                'metadata' => [
                    'user_id' => $user->id,
                    'role' => $user->role?->slug,
                ],
            ]);

            return back()
                ->withErrors(['login' => 'Akun ini tidak memiliki akses admin.'])
                ->withInput($request->only('login', 'remember'));
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $this->auditSystem('info', 'auth', 'Login berhasil', [
            'action' => 'login_success',
            'user_id' => $user->id,
            'metadata' => [
                'remember' => $request->boolean('remember'),
            ],
        ]);

        return redirect()
            ->intended(route('dashboardadmin'))
            ->with('success', 'Login berhasil.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $user = $request->user();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->auditSystem('info', 'auth', 'Logout berhasil', [
            'action' => 'logout',
            'user_id' => $user?->id,
        ]);

        return redirect()
            ->route('login.form')
            ->with('success', 'Berhasil logout.');
    }
}

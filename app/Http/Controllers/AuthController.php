<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\VisitorSessionLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        protected VisitorSessionLogger $visitorLogger
    ) {
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:3'],
            'remember' => ['nullable', 'boolean'],
        ], [
            'login.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 3 karakter.',
        ]);

        $login = trim((string) $validated['login']);

        $user = User::with('role')
            ->where(function ($query) use ($login) {
                $query->where('username', $login);
            })
            ->first();

        if (! $user || ! Hash::check((string) $validated['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'login' => 'Login gagal. Periksa kembali identitas dan password.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'login' => 'Akun Anda sedang nonaktif.',
            ]);
        }

        if (! $user->role || ! $user->role->is_active) {
            throw ValidationException::withMessages([
                'login' => 'Role akun tidak valid atau sedang nonaktif.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        $this->visitorLogger->sync($request, $user, 'login');

        $routeName = match (strtolower((string) $user->role->name)) {
            'admin' => 'dashboardadmin',
            'cashier' => 'dashboardcashier',
            default => 'dashboardadmin',
        };

        if (Route::has($routeName)) {
            return redirect()->route($routeName)->with('success', 'Login berhasil.');
        }

        return redirect()
            ->to($routeName === 'dashboardcashier' ? url('/dashboard-cashier') : url('/dashboard-admin'))
            ->with('success', 'Login berhasil.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $this->visitorLogger->sync($request, $user, 'logout');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login.form')
            ->with('success', 'Berhasil logout.');
    }
}

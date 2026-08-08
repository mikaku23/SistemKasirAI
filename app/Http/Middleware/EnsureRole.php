<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login.form');
        }

        $user->loadMissing('role');

        $role = $user->role;

        if (! $role) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login.form')->withErrors([
                'identity' => 'Akun tidak memiliki role yang valid.',
            ]);
        }

        if (! $role->is_active) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login.form')->withErrors([
                'identity' => 'Role Anda sedang nonaktif.',
            ]);
        }

        $normalizedUserRole = $this->normalizeRole((string) ($role->slug ?: $role->name));
        $allowedRoles = collect($roles)
            ->flatMap(function (string $roleSet): array {
                return preg_split('/[|,]/', $roleSet, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            })
            ->map(fn (string $roleValue): string => $this->normalizeRole($roleValue))
            ->filter()
            ->values()
            ->all();

        if (! empty($allowedRoles) && ! in_array($normalizedUserRole, $allowedRoles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }

    protected function normalizeRole(string $role): string
    {
        return strtolower(trim(str_replace(['-', '_'], ' ', $role)));
    }
}

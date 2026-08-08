<?php

namespace App\Http\Controllers;

use App\Http\Sistem\AI\Core\AiCoreService;
use App\Models\User;
use App\Support\AuditTrail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    use AuditTrail;

    protected function aiCore(): AiCoreService
    {
        return app(AiCoreService::class);
    }

    protected function aiGuard(string $module, string $action, array $payload = [], ?User $user = null): array
    {
        return $this->aiCore()->guardCrud($module, $action, $payload, $user);
    }

    protected function aiDenyRedirect(array $guard): RedirectResponse
    {
        return back()
            ->withErrors([
                'ai' => $guard['reason'] ?? 'Akses ditolak oleh AI Core.',
            ])
            ->withInput();
    }

    protected function aiDenyJson(array $guard, int $status = 403): JsonResponse
    {
        return response()->json([
            'message' => $guard['reason'] ?? 'Akses ditolak oleh AI Core.',
        ], $status);
    }
}

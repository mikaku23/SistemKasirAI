<?php

namespace App\Http\Controllers;

use App\Http\Sistem\AI\Core\AiCoreService;
use App\Support\AuditTrail;

abstract class Controller
{
    use AuditTrail;

    protected function aiCore(): AiCoreService
    {
        return app(AiCoreService::class);
    }

    protected function aiGuard(string $module, string $action, array $payload = [], ?\App\Models\User $user = null): array
    {
        return $this->aiCore()->guardCrud($module, $action, $payload, $user);
    }
}

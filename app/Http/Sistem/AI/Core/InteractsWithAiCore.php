<?php

namespace App\Http\Sistem\AI\Core;

use App\Models\User;

trait InteractsWithAiCore
{
    protected function aiCoreService(): AiCoreService
    {
        return app(AiCoreService::class);
    }

    protected function aiCrudGuard(string $module, string $action, array $payload = [], ?User $user = null): array
    {
        return $this->aiCoreService()->guardCrud($module, $action, $payload, $user);
    }
}

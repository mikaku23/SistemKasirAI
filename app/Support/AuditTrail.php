<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\SystemLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

trait AuditTrail
{
    protected function auditActivity(string $action, array $context = []): ?ActivityLog
    {
        try {
            $request = $this->auditRequest();
            $route = $request?->route();
            $routeParameters = $this->auditNormalize($route?->parameters() ?? []);
            $requestPayload = $request ? $this->auditSanitize($request->except([
                '_token',
                'password',
                'password_confirmation',
                'current_password',
                'new_password',
                'old_password',
                'secret',
                'api_token',
                'token',
            ])) : null;

            return ActivityLog::create([
                'user_id' => $context['user_id'] ?? auth()->id(),
                'action' => $action,
                'module' => $context['module'] ?? class_basename(static::class),
                'menu' => $context['menu'] ?? null,
                'route' => $context['route'] ?? ($route?->getName() ?: $route?->uri()),
                'target_type' => $context['target_type'] ?? null,
                'target_id' => $context['target_id'] ?? null,
                'ip_address' => $context['ip_address'] ?? ($request?->ip()),
                'user_agent' => $context['user_agent'] ?? ($request?->userAgent()),
                'status' => $context['status'] ?? 'success',
                'description' => $context['description'] ?? $this->auditDescribe($action),
                'metadata' => array_filter([
                    'request' => $requestPayload,
                    'route_parameters' => $routeParameters,
                    'context' => $this->auditSanitize($context['metadata'] ?? []),
                    'method' => $request?->method(),
                    'full_url' => $request?->fullUrl(),
                    'at' => now()->toISOString(),
                ], static fn ($value) => $value !== null && $value !== []),
            ]);
        } catch (Throwable $throwable) {
            return null;
        }
    }

    protected function auditSystem(string $level, string $channel, string $message, array $context = []): ?SystemLog
    {
        try {
            $request = $this->auditRequest();
            return SystemLog::create([
                'level' => $level,
                'channel' => $channel,
                'message' => $message,
                'context' => array_filter([
                    'module' => $context['module'] ?? class_basename(static::class),
                    'action' => $context['action'] ?? null,
                    'status' => $context['status'] ?? null,
                    'route' => $context['route'] ?? ($request?->route()?->getName() ?: $request?->route()?->uri()),
                    'ip_address' => $context['ip_address'] ?? $request?->ip(),
                    'user_id' => $context['user_id'] ?? auth()->id(),
                    'metadata' => $this->auditSanitize($context['metadata'] ?? []),
                    'request' => $request ? $this->auditSanitize($request->except([
                        '_token',
                        'password',
                        'password_confirmation',
                        'current_password',
                        'new_password',
                        'old_password',
                        'secret',
                        'api_token',
                        'token',
                    ])) : null,
                    'at' => now()->toISOString(),
                ], static fn ($value) => $value !== null && $value !== []),
            ]);
        } catch (Throwable $throwable) {
            return null;
        }
    }

    protected function auditRequest(): ?Request
    {
        return app()->bound('request') ? request() : null;
    }


    protected function auditNormalize(mixed $value): mixed
    {
        if ($value instanceof Carbon) {
            return $value->toISOString();
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toISOString();
        }

        if (is_array($value)) {
            $result = [];

            foreach ($value as $key => $item) {
                $result[$key] = $this->auditNormalize($item);
            }

            return $result;
        }

        if (is_object($value)) {
            if (method_exists($value, 'getKey')) {
                return [
                    'class' => class_basename($value),
                    'id' => $value->getKey(),
                ];
            }

            if (method_exists($value, 'toArray')) {
                return $this->auditNormalize($value->toArray());
            }

            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            return class_basename($value);
        }

        return $value;
    }

    protected function auditDescribe(string $action): string
    {
        $action = strtolower($action);

        return match (true) {
            str_contains($action, 'index') => 'Membuka daftar data',
            str_contains($action, 'create') => 'Membuka form input data',
            str_contains($action, 'store') => 'Menyimpan data baru',
            str_contains($action, 'show') => 'Membuka detail data',
            str_contains($action, 'edit') => 'Membuka form edit data',
            str_contains($action, 'update') => 'Memperbarui data',
            str_contains($action, 'destroy') => 'Menghapus data',
            str_contains($action, 'restore') => 'Memulihkan data',
            str_contains($action, 'forcedelete') => 'Menghapus permanen data',
            str_contains($action, 'print') => 'Mencetak data',
            str_contains($action, 'lookup') => 'Mencari data',
            str_contains($action, 'logout') => 'Logout pengguna',
            str_contains($action, 'login') => 'Autentikasi pengguna',
            str_contains($action, 'confirmsystemcorrect') => 'Menandai stok sistem benar',
            str_contains($action, 'applycorrection') => 'Menerapkan koreksi stok',
            default => 'Aktivitas sistem tercatat',
        };
    }

    protected function auditSanitize(mixed $value): mixed
    {
        if ($value instanceof Carbon) {
            return $value->toISOString();
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toISOString();
        }

        if (is_array($value)) {
            $result = [];

            foreach ($value as $key => $item) {
                if (is_string($key) && in_array(strtolower($key), [
                    '_token',
                    'token',
                    'password',
                    'password_confirmation',
                    'current_password',
                    'new_password',
                    'old_password',
                    'secret',
                    'api_token',
                ], true)) {
                    continue;
                }

                $result[$key] = $this->auditSanitize($item);
            }

            return $result;
        }

        if (is_object($value)) {
            if (method_exists($value, 'getKey')) {
                return [
                    'class' => class_basename($value),
                    'id' => $value->getKey(),
                ];
            }

            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            if (method_exists($value, 'toArray')) {
                return $this->auditSanitize($value->toArray());
            }

            return class_basename($value);
        }

        if (is_string($value)) {
            return Str::limit($value, 2000, '…');
        }

        return $value;
    }
}

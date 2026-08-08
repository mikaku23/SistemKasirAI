<?php

namespace App\Http\Sistem\AI\Core;

use App\Models\AiConversation;
use App\Models\AiMessages;
use App\Models\User;
use Illuminate\Support\Arr;

class AiNotificationService
{
    public function buildToast(string $variant, string $title, string $message, array $metadata = []): array
    {
        $variant = $this->normalizeVariant($variant);

        return [
            'variant' => $variant,
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
        ];
    }

    public function flash(array $toast): void
    {
        $variant = $this->normalizeVariant((string) ($toast['variant'] ?? 'info'));
        $message = (string) ($toast['message'] ?? '');
        $title = (string) ($toast['title'] ?? ucfirst($variant));

        session()->flash(match ($variant) {
            'success' => 'success',
            'danger' => 'error',
            'warn' => 'warning',
            default => 'info',
        }, $message);

        session()->flash('ai_toast_title', $title);
    }

    public function persist(User $user, ?AiConversation $conversation, array $toast, array $metadata = []): ?AiMessages
    {
        try {
            return AiMessages::query()->create([
                'ai_channel_id' => $conversation?->ai_channel_id,
                'user_id' => $user->id,
                'visitor_id' => null,
                'ai_conversation_id' => $conversation?->id,
                'recipient_type' => $this->recipientType($user),
                'type' => $this->messageType((string) ($toast['variant'] ?? 'info')),
                'title' => (string) ($toast['title'] ?? 'AI Notification'),
                'content' => (string) ($toast['message'] ?? ''),
                'target_url' => data_get($metadata, 'target_url'),
                'target_type' => data_get($metadata, 'target_type'),
                'target_id' => data_get($metadata, 'target_id'),
                'is_read' => false,
                'read_at' => null,
                'source_type' => data_get($metadata, 'source_type', 'ai-core'),
                'source_id' => data_get($metadata, 'source_id'),
                'priority' => (int) data_get($metadata, 'priority', $this->priority((string) ($toast['variant'] ?? 'info'))),
                'metadata' => array_merge(
                    Arr::except($metadata, ['target_url', 'target_type', 'target_id', 'source_type', 'source_id']),
                    [
                        'toast' => $toast,
                    ]
                ),
            ]);
        } catch (\Throwable $throwable) {
            return null;
        }
    }

    public function messageType(string $variant): string
    {
        return match ($this->normalizeVariant($variant)) {
            'danger' => 'security',
            'warn' => 'warning',
            'success' => 'info',
            default => 'info',
        };
    }

    public function priority(string $variant): int
    {
        return match ($this->normalizeVariant($variant)) {
            'danger' => 9,
            'warn' => 7,
            'success' => 5,
            default => 3,
        };
    }

    protected function recipientType(User $user): string
    {
        $role = strtolower((string) data_get($user, 'role.name', 'user'));

        return $role === 'admin' ? 'admin' : 'user';
    }

    protected function normalizeVariant(string $variant): string
    {
        $variant = strtolower(trim($variant));

        return in_array($variant, ['success', 'danger', 'warn', 'info'], true) ? $variant : 'info';
    }
}

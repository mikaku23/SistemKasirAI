<?php

namespace App\Http\Sistem\AI\Core;

use App\Models\AiConversation;
use App\Models\AiConversationMessage;
use App\Models\User;
use Illuminate\Support\Str;

class AiConversationManager
{
    public function resolve(?User $user, string $channelSlug, ?string $conversationType = null): AiConversation
    {
        $conversationType ??= match ($channelSlug) {
            'manager-chatbot' => 'website_chatbot',
            'warehouse-search' => 'product_search',
            'customer-service' => 'customer_service',
            default => 'admin_chatbot',
        };

        $conversation = AiConversation::query()->firstOrCreate(
            [
                'ai_channel_id' => $this->findChannelId($channelSlug),
                'user_id' => $user?->id,
                'conversation_type' => $conversationType,
                'status' => 'active',
            ],
            [
                'title' => $this->defaultTitle($channelSlug, $user),
                'last_activity_at' => now(),
                'metadata' => [
                    'channel_slug' => $channelSlug,
                    'created_by' => $user?->id,
                ],
            ]
        );

        return $conversation;
    }

    public function logUserMessage(AiConversation $conversation, string $channelSlug, string $message, ?User $user = null, array $metadata = []): AiConversationMessage
    {
        return $this->storeMessage($conversation, [
            'ai_channel_id' => $conversation->ai_channel_id,
            'sender_type' => 'user',
            'sender_role' => $this->roleName($user, 'user'),
            'message_type' => 'text',
            'message' => $message,
            'intent_key' => data_get($metadata, 'intent_key'),
            'confidence_score' => data_get($metadata, 'confidence_score'),
            'status' => 'draft',
            'requires_confirmation' => (bool) data_get($metadata, 'requires_confirmation', false),
            'requires_handoff' => (bool) data_get($metadata, 'requires_handoff', false),
            'payload' => $metadata,
            'metadata' => array_merge($metadata, [
                'channel_slug' => $channelSlug,
            ]),
        ]);
    }

    public function logAiMessage(AiConversation $conversation, string $channelSlug, string $message, array $metadata = []): AiConversationMessage
    {
        return $this->storeMessage($conversation, [
            'ai_channel_id' => $conversation->ai_channel_id,
            'sender_type' => 'ai',
            'sender_role' => 'ai',
            'message_type' => data_get($metadata, 'message_type', 'text'),
            'message' => $message,
            'intent_key' => data_get($metadata, 'intent_key'),
            'confidence_score' => data_get($metadata, 'confidence_score'),
            'tool_name' => data_get($metadata, 'tool_name'),
            'tool_payload' => data_get($metadata, 'tool_payload'),
            'tool_result' => data_get($metadata, 'tool_result'),
            'status' => data_get($metadata, 'status', 'executed'),
            'requires_confirmation' => (bool) data_get($metadata, 'requires_confirmation', false),
            'requires_handoff' => (bool) data_get($metadata, 'requires_handoff', false),
            'confirmed_at' => data_get($metadata, 'confirmed_at'),
            'executed_at' => data_get($metadata, 'executed_at'),
            'payload' => data_get($metadata, 'payload'),
            'metadata' => array_merge($metadata, [
                'channel_slug' => $channelSlug,
            ]),
        ]);
    }

    protected function storeMessage(AiConversation $conversation, array $attributes): AiConversationMessage
    {
        $message = AiConversationMessage::query()->create($attributes);

        $conversation->forceFill([
            'last_message_at' => now(),
            'last_activity_at' => now(),
            'last_intent_key' => data_get($attributes, 'intent_key'),
        ])->save();

        return $message;
    }

    protected function defaultTitle(string $channelSlug, ?User $user = null): string
    {
        return trim(sprintf('%s • %s', Str::headline(str_replace('-', ' ', $channelSlug)), $user?->name ?: 'System'));
    }

    protected function roleName(?User $user, string $fallback = 'user'): string
    {
        return strtolower((string) data_get($user, 'role.name', $fallback));
    }

    protected function findChannelId(string $channelSlug): ?int
    {
        return \App\Models\AiChannel::query()->where('slug', $channelSlug)->value('id');
    }
}

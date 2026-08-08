@extends('template-admin.layout')

@section('title', 'AI Core')

@section('content')
@php
    $stats = $statistics ?? [];
    $channels = $channels ?? collect();
    $permissions = $permissions ?? collect();
    $recentMessages = $recent_messages ?? collect();
    $recentConversations = $recent_conversations ?? collect();
    $recentSearchLogs = $recent_search_logs ?? collect();
    $recentHandoffs = $recent_handoffs ?? collect();
    $result = session('ai_result');
@endphp

<section class="page-card glass-card">
    <div class="page-card__head">
        <div>
            <p class="eyebrow">AI CORE</p>
            <h1>Inti AI sistem kasir management</h1>
            <p>Core ini menjadi pusat kontrol, guardrail, routing channel, dan jembatan ke logika controller CRUD.</p>
        </div>
        <div class="page-card__actions">
            <a href="{{ route('ai-channels.index') }}" class="btn btn--secondary">
                <i class="icon fa-solid fa-diagram-project" aria-hidden="true"></i>
                Channel Matrix
            </a>
        </div>
    </div>

    <div class="stats-grid stats-grid--4">
        <article class="stat-card">
            <span>Channels</span>
            <strong>{{ $stats['channels'] ?? 0 }}</strong>
            <small>{{ $stats['active_channels'] ?? 0 }} aktif</small>
        </article>
        <article class="stat-card">
            <span>Permissions</span>
            <strong>{{ $stats['permissions'] ?? 0 }}</strong>
            <small>Blueprint intent terdaftar</small>
        </article>
        <article class="stat-card">
            <span>Conversations</span>
            <strong>{{ $stats['conversations'] ?? 0 }}</strong>
            <small>{{ $stats['messages'] ?? 0 }} pesan</small>
        </article>
        <article class="stat-card">
            <span>Knowledge</span>
            <strong>{{ $stats['knowledge_articles'] ?? 0 }}</strong>
            <small>{{ $stats['handoffs'] ?? 0 }} handoff aktif</small>
        </article>
    </div>
</section>

<section class="page-grid page-grid--2">
    <article class="glass-card page-card">
        <div class="page-card__head">
            <div>
                <p class="eyebrow">AI DISPATCH</p>
                <h2>Uji AI Core</h2>
                <p>Kirim pesan ke channel yang sesuai. Core akan memeriksa role, intent, dan permission sebelum merespons.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('ai-core.dispatch') }}" class="form-stack">
            @csrf
            <div class="form-group">
                <label for="channel_slug">Channel</label>
                <select id="channel_slug" name="channel_slug" required>
                    @foreach ($channels as $channel)
                        <option value="{{ $channel->slug }}" @selected(($allowed_channel_slug ?? 'admin-core') === $channel->slug)>
                            {{ $channel->name }} — {{ $channel->slug }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="message">Pesan AI</label>
                <textarea id="message" name="message" rows="5" placeholder="Contoh: tampilkan ringkasan stok, cari batch expired, atau bantu scan barcode." required></textarea>
            </div>

            <div class="form-group">
                <label for="module">Module opsional</label>
                <input id="module" name="module" type="text" placeholder="products / stock-batches / transactions" />
            </div>

            <div class="form-group">
                <label for="action">Action opsional</label>
                <input id="action" name="action" type="text" placeholder="index / show / store / search" />
            </div>

            <div class="form-group">
                <label for="payload">Payload JSON opsional</label>
                <textarea id="payload" name="payload_json" rows="4" placeholder='{"id": 1, "force": false}'></textarea>
            </div>

            <button class="btn btn--primary" type="submit">Dispatch ke AI Core</button>
        </form>
    </article>

    <article class="glass-card page-card">
        <div class="page-card__head">
            <div>
                <p class="eyebrow">LAST RESULT</p>
                <h2>Respons terakhir</h2>
            </div>
        </div>

        @if ($result)
            <div class="ai-result">
                <div class="ai-result__badge ai-result__badge--{{ data_get($result, 'toast.variant', 'info') }}">
                    {{ strtoupper((string) data_get($result, 'toast.variant', 'info')) }}
                </div>
                <h3>{{ data_get($result, 'toast.title', 'AI Core') }}</h3>
                <p>{{ data_get($result, 'message', '-') }}</p>

                @if (!empty(data_get($result, 'intent.intent_key')))
                    <div class="meta-pill">Intent: {{ data_get($result, 'intent.intent_key') }}</div>
                @endif

                @if (!empty(data_get($result, 'tool_result')))
                    <details class="ai-result__details">
                        <summary>Payload detail</summary>
                        <pre>{{ json_encode(data_get($result, 'tool_result'), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                @endif
            </div>
        @else
            <p class="muted">Belum ada hasil dispatch pada sesi ini.</p>
        @endif
    </article>
</section>

<section class="page-grid page-grid--2">
    <article class="glass-card page-card">
        <div class="page-card__head">
            <div>
                <p class="eyebrow">CHANNEL MATRIX</p>
                <h2>Role → Channel</h2>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Type</th>
                        <th>Roles</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($channels as $channel)
                        <tr>
                            <td>
                                <strong>{{ $channel->name }}</strong>
                                <div class="muted">{{ $channel->slug }}</div>
                            </td>
                            <td>{{ $channel->type }}</td>
                            <td>{{ implode(', ', data_get($channel->metadata, 'roles', [])) }}</td>
                            <td>
                                <span class="status-pill {{ $channel->is_active ? 'status-pill--success' : 'status-pill--danger' }}">
                                    {{ $channel->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </article>

    <article class="glass-card page-card">
        <div class="page-card__head">
            <div>
                <p class="eyebrow">GUARDRAIL</p>
                <h2>Blueprint permission</h2>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Intent</th>
                        <th>Module</th>
                        <th>Read</th>
                        <th>Write</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($permissions->take(10) as $permission)
                        <tr>
                            <td>
                                <strong>{{ $permission->intent_key }}</strong>
                                <div class="muted">{{ $permission->description }}</div>
                            </td>
                            <td>{{ $permission->module }}</td>
                            <td>{{ $permission->can_read ? 'Yes' : 'No' }}</td>
                            <td>{{ $permission->can_write ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="page-grid page-grid--2">
    <article class="glass-card page-card">
        <div class="page-card__head">
            <div>
                <p class="eyebrow">RECENT</p>
                <h2>Conversation terbaru</h2>
            </div>
        </div>

        <div class="stack-list">
            @forelse ($recentConversations as $conversation)
                <article class="stack-item">
                    <strong>{{ $conversation->title ?? $conversation->aiChannel?->name ?? 'Conversation' }}</strong>
                    <div class="muted">{{ $conversation->aiChannel?->slug ?? '-' }} • {{ $conversation->status }}</div>
                </article>
            @empty
                <p class="muted">Belum ada conversation.</p>
            @endforelse
        </div>
    </article>

    <article class="glass-card page-card">
        <div class="page-card__head">
            <div>
                <p class="eyebrow">RECENT</p>
                <h2>Search log & handoff</h2>
            </div>
        </div>

        <div class="stack-list">
            @forelse ($recentSearchLogs as $log)
                <article class="stack-item">
                    <strong>{{ $log->query_text }}</strong>
                    <div class="muted">{{ $log->resolved_intent }} • {{ $log->result_count }} hasil</div>
                </article>
            @empty
                <p class="muted">Belum ada log pencarian.</p>
            @endforelse

            @foreach ($recentHandoffs as $handoff)
                <article class="stack-item">
                    <strong>{{ $handoff->issue_type ?? 'Handoff' }}</strong>
                    <div class="muted">{{ $handoff->status }} • {{ $handoff->priority }}</div>
                </article>
            @endforeach
        </div>
    </article>
</section>
@endsection

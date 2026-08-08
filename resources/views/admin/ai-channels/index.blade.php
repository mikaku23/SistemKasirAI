@extends('template-admin.layout')

@section('title', 'AI Channels')

@section('content')
@php
    $channels = $channels ?? collect();
    $permissions = $permissions ?? collect();
@endphp

<section class="page-card glass-card">
    <div class="page-card__head">
        <div>
            <p class="eyebrow">AI CHANNELS</p>
            <h1>Matrix channel dan role</h1>
            <p>Admin memegang channel inti. Manager, gudang, dan cashier hanya melihat scope yang telah dibatasi.</p>
        </div>
        <div class="page-card__actions">
            <a href="{{ route('ai-core.index') }}" class="btn btn--secondary">
                <i class="icon fa-solid fa-robot" aria-hidden="true"></i>
                Back to Core
            </a>
        </div>
    </div>

    <div class="stats-grid stats-grid--4">
        <article class="stat-card">
            <span>Active channels</span>
            <strong>{{ $channels->where('is_active', true)->count() }}</strong>
            <small>Semua channel yang berjalan</small>
        </article>
        <article class="stat-card">
            <span>Admin scope</span>
            <strong>{{ $channels->where('type', 'admin')->count() }}</strong>
            <small>Channel inti</small>
        </article>
        <article class="stat-card">
            <span>Search scope</span>
            <strong>{{ $channels->where('type', 'search')->count() }}</strong>
            <small>Gudang search</small>
        </article>
        <article class="stat-card">
            <span>Support scope</span>
            <strong>{{ $channels->where('type', 'customer_service')->count() }}</strong>
            <small>CS untuk cashier/gudang</small>
        </article>
    </div>
</section>

<section class="page-grid page-grid--2">
    @foreach ($channels as $channel)
        <article class="glass-card page-card">
            <div class="page-card__head">
                <div>
                    <p class="eyebrow">{{ strtoupper($channel->type) }}</p>
                    <h2>{{ $channel->name }}</h2>
                    <p>{{ $channel->description }}</p>
                </div>
                <span class="status-pill {{ $channel->is_active ? 'status-pill--success' : 'status-pill--danger' }}">
                    {{ $channel->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <div class="meta-grid">
                <div class="meta-pill">Slug: {{ $channel->slug }}</div>
                <div class="meta-pill">Roles: {{ implode(', ', data_get($channel->metadata, 'roles', [])) }}</div>
                <div class="meta-pill">Tools: {{ implode(', ', $channel->allowed_tools ?? []) }}</div>
            </div>

            <details class="ai-result__details">
                <summary>System prompt</summary>
                <pre>{{ $channel->system_prompt }}</pre>
            </details>
        </article>
    @endforeach
</section>

<section class="page-card glass-card">
    <div class="page-card__head">
        <div>
            <p class="eyebrow">ACTIVE INTENTS</p>
            <h2>Blueprint permission aktif</h2>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Intent</th>
                    <th>Controller</th>
                    <th>Method</th>
                    <th>Read</th>
                    <th>Write</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($permissions->take(20) as $permission)
                    <tr>
                        <td>
                            <strong>{{ $permission->intent_key }}</strong>
                            <div class="muted">{{ $permission->description }}</div>
                        </td>
                        <td>{{ class_basename($permission->controller_class) }}</td>
                        <td>{{ $permission->controller_method }}</td>
                        <td>{{ $permission->can_read ? 'Yes' : 'No' }}</td>
                        <td>{{ $permission->can_write ? 'Yes' : 'No' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
@endsection

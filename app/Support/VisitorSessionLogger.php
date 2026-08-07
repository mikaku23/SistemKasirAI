<?php

namespace App\Support;

use App\Models\User;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VisitorSessionLogger
{
    public function sync(Request $request, ?User $user = null, string $source = 'web'): Visitor
    {
        $sessionKey = 'visitor_session_token';
        $token = (string) $request->session()->get($sessionKey);

        if ($token === '') {
            $token = (string) Str::uuid();
            $request->session()->put($sessionKey, $token);
        }

        $visitor = Visitor::withTrashed()->firstOrNew([
            'session_token' => $token,
        ]);

        if ($visitor->exists && method_exists($visitor, 'trashed') && $visitor->trashed()) {
            $visitor->restore();
        }

        $metadata = array_filter([
            'session_id' => $request->session()->getId(),
            'route_name' => $request->route()?->getName(),
            'route_uri' => $request->route()?->uri(),
            'path' => $request->path(),
            'method' => $request->method(),
            'is_authenticated' => $request->user() !== null,
            'guard' => Auth::getDefaultDriver(),
            'user_id' => $user?->id,
            'role_name' => $user?->role?->name,
            'role_slug' => $user?->role?->slug,
            'remember_me' => $request->boolean('remember'),
            'referer' => $request->headers->get('referer'),
        ], static fn ($value) => $value !== null && $value !== '');

        $visitor->fill([
            'name' => $user?->name ?: ($visitor->name ?: $request->input('name')),
            'phone' => $user?->no_hp ?: ($visitor->phone ?: $request->input('phone')),
            'email' => $user?->email ?: ($visitor->email ?: $request->input('email')),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 2000),
            'last_seen_at' => now(),
            'source' => $source,
            'metadata' => array_replace(
                is_array($visitor->metadata) ? $visitor->metadata : [],
                $metadata
            ),
        ]);

        $visitor->save();

        return $visitor;
    }
}

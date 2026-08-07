<?php

namespace App\Http\Middleware;

use App\Support\VisitorSessionLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisitorMiddleware
{
    public function __construct(
        protected VisitorSessionLogger $logger
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user()) {
            $this->logger->sync($request, $request->user(), 'authenticated');
        }

        return $response;
    }
}

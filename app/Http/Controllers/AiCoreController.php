<?php

namespace App\Http\Controllers;

use App\Http\Requests\AiCoreDispatchRequest;
use App\Http\Sistem\AI\Core\AiCoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AiCoreController extends Controller
{
    public function __construct(
        protected AiCoreService $aiCoreService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.ai-core.index', array_merge($this->aiCoreService->dashboard(Auth::user()), [
            'menu' => 'ai-core',
        ]));
    }

    public function channels(): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.ai-channels.index', array_merge($this->aiCoreService->dashboard(Auth::user()), [
            'menu' => 'ai-channels',
        ]));
    }

    public function dispatch(AiCoreDispatchRequest $request): RedirectResponse|JsonResponse
    {
        $this->auditActivity(__FUNCTION__);

        $payload = $request->validated();
        $payload = $this->mergeJsonPayload($payload, (string) $request->input('payload_json', ''));

        $result = $this->aiCoreService->handle($payload, $request->user());

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return back()
            ->with('ai_result', $result);
    }

    public function chatbot(AiCoreDispatchRequest $request): RedirectResponse|JsonResponse
    {
        $this->auditActivity(__FUNCTION__);

        $payload = $request->validated();
        $payload = $this->mergeJsonPayload($payload, (string) $request->input('payload_json', ''));
        $payload['channel_slug'] = 'manager-chatbot';

        $result = $this->aiCoreService->handle($payload, $request->user());

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return back()
            ->with('ai_result', $result);
    }

    public function search(AiCoreDispatchRequest $request): RedirectResponse|JsonResponse
    {
        $this->auditActivity(__FUNCTION__);

        $payload = $request->validated();
        $payload = $this->mergeJsonPayload($payload, (string) $request->input('payload_json', ''));
        $payload['channel_slug'] = 'warehouse-search';

        $result = $this->aiCoreService->handle($payload, $request->user());

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return back()
            ->with('ai_result', $result);
    }

    public function customerService(AiCoreDispatchRequest $request): RedirectResponse|JsonResponse
    {
        $this->auditActivity(__FUNCTION__);

        $payload = $request->validated();
        $payload = $this->mergeJsonPayload($payload, (string) $request->input('payload_json', ''));
        $payload['channel_slug'] = 'customer-service';

        $result = $this->aiCoreService->handle($payload, $request->user());

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return back()
            ->with('ai_result', $result);
    }

    public function guard(Request $request): JsonResponse
    {
        $this->auditActivity(__FUNCTION__);

        $validated = $request->validate([
            'module' => ['required', 'string', 'max:100'],
            'action' => ['required', 'string', 'max:50'],
            'payload' => ['nullable', 'array'],
            'payload_json' => ['nullable', 'string', 'max:5000'],
        ]);

        $payload = $validated['payload'] ?? [];
        $payload = $this->mergeJsonPayload($payload, (string) ($validated['payload_json'] ?? ''));

        $result = $this->aiCoreService->guardCrud(
            $validated['module'],
            $validated['action'],
            $payload,
            $request->user()
        );

        return response()->json($result);
    }

    protected function mergeJsonPayload(array $payload, string $json): array
    {
        $json = trim($json);

        if ($json === '') {
            return $payload;
        }

        $decoded = json_decode($json, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            unset($payload['payload_json']);

            return array_replace($payload, $decoded);
        }

        unset($payload['payload_json']);

        return $payload;
    }
}

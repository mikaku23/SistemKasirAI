<?php

namespace App\Http\Controllers;

use App\Http\Requests\SystemLogIndexRequest;
use App\Http\Services\SystemLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    public function __construct(protected SystemLogService $systemLogService)
    {
    }

    public function index(SystemLogIndexRequest $request): View
    {
        $guard = $this->aiGuard('system-logs', 'index', [], $request->user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }

        return view('admin.system-logs.index', array_merge(
            $this->systemLogService->indexData($request->validated()),
            [
                'menu' => 'system-logs',
            ]
        ));
    }

    public function show(Request $request): View
    {
        $guard = $this->aiGuard('system-logs', 'show', [], $request->user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }

        return view('admin.system-logs.show', array_merge(
            $this->systemLogService->showData($request->validate([
                'date' => ['nullable', 'date'],
                'channel' => ['nullable', 'string', 'max:100'],
            ])),
            [
                'menu' => 'system-logs',
            ]
        ));
    }
}

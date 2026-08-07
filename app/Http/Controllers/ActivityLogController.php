<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityLogIndexRequest;
use App\Http\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __construct(protected ActivityLogService $activityLogService)
    {
    }

    public function index(ActivityLogIndexRequest $request): View
    {
        return view('admin.activity-logs.index', array_merge(
            $this->activityLogService->indexData($request->validated()),
            [
                'menu' => 'activity-logs',
            ]
        ));
    }

    public function show(Request $request): View
    {
        return view('admin.activity-logs.show', array_merge(
            $this->activityLogService->showData($request->validate([
                'date' => ['nullable', 'date'],
            ])),
            [
                'menu' => 'activity-logs',
            ]
        ));
    }
}

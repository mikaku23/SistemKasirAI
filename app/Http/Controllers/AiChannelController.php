<?php

namespace App\Http\Controllers;

use App\Http\Sistem\AI\Core\AiCoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AiChannelController extends Controller
{
    public function __construct(
        protected AiCoreService $aiCoreService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.ai-channels.index', array_merge($this->aiCoreService->dashboard(Auth::user()), [
            'menu' => 'ai-channels',
        ]));
    }
}

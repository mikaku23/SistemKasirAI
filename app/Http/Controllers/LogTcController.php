<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogTcIndexRequest;
use App\Http\Services\LogTcService;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LogTcController extends Controller
{
    public function __construct(protected LogTcService $logTcService)
    {
    }

    public function index(LogTcIndexRequest $request): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('log-tc', 'index', [], $request->user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.log-tc.index', array_merge(
            $this->logTcService->indexData($request->validated()),
            [
                'menu' => 'log-tc',
            ]
        ));
    }

    public function show(Transaction $transaction): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('log-tc', 'show', ['id' => $transaction->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.log-tc.show', array_merge(
            $this->logTcService->showData($transaction),
            [
                'menu' => 'log-tc',
            ]
        ));
    }
}

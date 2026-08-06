<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogTcIndexRequest;
use App\Http\Services\LogTcService;
use App\Models\Transaction;
use Illuminate\View\View;

class LogTcController extends Controller
{
    public function __construct(protected LogTcService $logTcService)
    {
    }

    public function index(LogTcIndexRequest $request): View
    {
        return view('admin.log-tc.index', array_merge(
            $this->logTcService->indexData($request->validated()),
            [
                'menu' => 'log-tc',
            ]
        ));
    }

    public function show(Transaction $transaction): View
    {
        return view('admin.log-tc.show', array_merge(
            $this->logTcService->showData($transaction),
            [
                'menu' => 'log-tc',
            ]
        ));
    }
}

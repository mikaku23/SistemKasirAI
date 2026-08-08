<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockMovementIndexRequest;
use App\Http\Services\StockMovementService;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function __construct(
        protected StockMovementService $stockMovementService
    ) {
    }

    public function index(StockMovementIndexRequest $request): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-movements', 'index', [], $request->user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.stock-movements.index', array_merge(
            $this->stockMovementService->indexData($request->validated()),
            [
                'menu' => 'stock-movements',
            ]
        ));
    }

    public function show(StockMovement $stockMovement): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('stock-movements', 'show', ['id' => $stockMovement->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.stock-movements.show', array_merge(
            $this->stockMovementService->showData($stockMovement),
            [
                'menu' => 'stock-movements',
            ]
        ));
    }
}

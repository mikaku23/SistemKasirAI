<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockMovementIndexRequest;
use App\Http\Services\StockMovementService;
use App\Models\StockMovement;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function __construct(
        protected StockMovementService $stockMovementService
    ) {
    }

    public function index(StockMovementIndexRequest $request): View
    {
        return view('admin.stock-movements.index', array_merge(
            $this->stockMovementService->indexData($request->validated()),
            [
                'menu' => 'stock-movements',
            ]
        ));
    }

    public function show(StockMovement $stockMovement): View
    {
        return view('admin.stock-movements.show', array_merge(
            $this->stockMovementService->showData($stockMovement),
            [
                'menu' => 'stock-movements',
            ]
        ));
    }
}

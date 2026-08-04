<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionStoreRequest;
use App\Http\Services\TransactionService;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(protected TransactionService $transactionService)
    {
    }

    public function index(): View
    {
        return view('admin.transactions.index', array_merge($this->transactionService->indexData(), ['menu' => 'transactions']));
    }

    public function create(): View
    {
        return view('admin.transactions.create', array_merge($this->transactionService->referenceData(), ['menu' => 'transactions']));
    }

    public function store(TransactionStoreRequest $request): RedirectResponse
    {
        $transaction = $this->transactionService->store($request->validated(), auth()->user());

        return redirect()->route('transactions.show', $transaction->id)
            ->with('success', 'Transaksi berhasil disimpan.')
            ->with('success_detail', 'Kembalian uang pelanggan: Rp ' . number_format((int) $transaction->change_amount, 0, ',', '.'));
    }

    public function show(Transaction $transaction): View
    {
        return view('admin.transactions.show', [
            'menu' => 'transactions',
            'transaction' => $transaction->load(['location', 'cashier', 'taxSetting', 'discountSetting', 'items.product', 'items.stockBatch', 'stockMovements.stockBatch']),
        ]);
    }

    public function print(Transaction $transaction)
    {
        $transaction->load(['location', 'cashier', 'taxSetting', 'discountSetting', 'items.product', 'items.stockBatch', 'stockMovements.stockBatch']);

        if (class_exists(PdfFacade::class)) {
            return PdfFacade::loadView('admin.transactions.print', ['transaction' => $transaction])
                ->setPaper([0, 0, 226.77, 900], 'portrait')
                ->download(($transaction->transaction_code ?: 'receipt') . '.pdf');
        }

        return view('admin.transactions.print', ['transaction' => $transaction]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionStoreRequest;
use App\Http\Services\TransactionService;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(protected TransactionService $transactionService)
    {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.transactions.index', array_merge($this->transactionService->indexData(), ['menu' => 'transactions']));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.transactions.create', array_merge($this->transactionService->referenceData(), ['menu' => 'transactions']));
    }

    public function store(TransactionStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $transaction = $this->transactionService->store($request->validated(), auth()->user());

        return redirect()->route('transactions.show', $transaction->id)
            ->with('success', 'Transaksi berhasil disimpan.')
            ->with('success_detail', 'Kembalian uang pelanggan: Rp ' . number_format((int) $transaction->change_amount, 0, ',', '.'));
    }

    public function show(Transaction $transaction): View
    {
        $this->auditActivity(__FUNCTION__);

        return view('admin.transactions.show', [
            'menu' => 'transactions',
            'transaction' => $transaction->load(['location', 'cashier', 'taxSetting', 'discountSetting', 'items.product', 'items.stockBatch', 'stockMovements.stockBatch']),
        ]);
    }

    public function lookupBarcode(string $barcode): JsonResponse
    {
        $this->auditActivity(__FUNCTION__);

        $product = $this->transactionService->findProductByBarcode($barcode);

        if (! $product) {
            return response()->json([
                'message' => 'Barcode tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'message' => 'Barcode ditemukan.',
            'data' => $product,
        ]);
    }

    public function print(Transaction $transaction)
    {
        $this->auditActivity(__FUNCTION__);

        $transaction->load(['location', 'cashier', 'taxSetting', 'discountSetting', 'items.product', 'items.stockBatch', 'stockMovements.stockBatch']);

        if (class_exists(PdfFacade::class)) {
            $paperHeight = $this->estimateReceiptPaperHeight($transaction);

            return PdfFacade::loadView('admin.transactions.print', ['transaction' => $transaction])
                ->setPaper([0, 0, 226.77, $paperHeight], 'portrait')
                ->download(($transaction->transaction_code ?: 'receipt') . '.pdf');
        }

        return view('admin.transactions.print', ['transaction' => $transaction]);
    }

    /**
     * Estimasi tinggi kertas receipt agar PDF tidak menyisakan area kosong yang terlalu panjang.
     * Satuan: points (1 pt = 1/72 inch).
     */
    protected function estimateReceiptPaperHeight(Transaction $transaction): int
    {
        $transaction->loadMissing(['items.product']);

        $baseHeight = 190;    // header + info transaksi + summary + footer
        $itemGap = 10;        // jarak antar item
        $lineHeight = 12;     // tinggi per baris teks receipt
        $minHeight = 320;     // jangan terlalu pendek
        $bottomPadding = 18;   // ruang bawah sedikit

        $itemsHeight = 0;

        foreach ($transaction->items as $item) {
            $productName = trim((string) optional($item->product)->name) ?: '-';
            $nameLength = mb_strlen($productName);

            // Receipt lebar sempit, jadi nama panjang perlu diasumsikan wrapping.
            // 24 karakter per baris cukup aman untuk font monospace 11px.
            $wrappedLines = max(1, (int) ceil($nameLength / 24));

            // Nama produk + qty/subtotal + promo + sedikit jarak.
            $itemsHeight += ($wrappedLines * $lineHeight) + (2 * $lineHeight) + $itemGap;
        }

        $estimated = $baseHeight + $itemsHeight + $bottomPadding;

        return (int) max($minHeight, $estimated);
    }
}

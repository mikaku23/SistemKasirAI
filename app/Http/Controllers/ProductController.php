<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Services\ProductService;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {
    }

    public function index(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('products', 'index', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.products.index', array_merge($this->productService->indexData(), [
            'menu' => 'products',
        ]));
    }

    public function recycle(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('products', 'recycle', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.products.recycle', array_merge($this->productService->indexData(), [
            'menu' => 'products',
        ]));
    }

    public function create(): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('products', 'create', [], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.products.create', array_merge($this->productService->referenceData(), [
            'menu' => 'products',
        ]));
    }

    public function store(ProductStoreRequest $request): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('products', 'store', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->productService->store($guard['payload'], $request->file('image'));

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('products', 'show', ['id' => $product->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.products.show', array_merge($this->productService->referenceData(), [
            'menu' => 'products',
            'product' => $product->load(['category', 'unit', 'supplier', 'location']),
        ]));
    }

    public function edit(Product $product): View
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('products', 'edit', ['id' => $product->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        return view('admin.products.edit', array_merge($this->productService->referenceData(), [
            'menu' => 'products',
            'product' => $product->load(['category', 'unit', 'supplier', 'location']),
        ]));
    }

    public function update(ProductUpdateRequest $request, Product $product): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('products', 'update', $request->validated(), $request->user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->productService->update($product, $guard['payload'], $request->file('image'));

        return redirect()
            ->route('products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function printBarcode(Product $product)
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('products', 'printBarcode', ['id' => $product->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            abort(403, $guard['reason'] ?? 'Akses ditolak oleh AI Core.');
        }


        $product->load(['category', 'unit', 'supplier', 'location']);

        $barcodeImage = $this->barcodeImageDataUri((string) $product->barcode);

        if (class_exists(PdfFacade::class)) {
            return PdfFacade::loadView('admin.products.print-barcode', [
                'menu' => 'products',
                'product' => $product,
                'barcodeImage' => $barcodeImage,
            ])->setPaper('a6', 'portrait')->download(
                'barcode-' . ($product->sku ?: $product->barcode ?: $product->id) . '.pdf'
            );
        }

        return view('admin.products.print-barcode', [
            'menu' => 'products',
            'product' => $product,
            'barcodeImage' => $barcodeImage,
        ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('products', 'destroy', ['id' => $product->getKey()], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->productService->trash($product);

        return back()->with('success', 'Produk dipindahkan ke recycle bin.');
    }

    public function restore(int $product): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('products', 'restore', ['id' => $product], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->productService->restore($product);

        return back()->with('success', 'Produk berhasil dipulihkan.');
    }

    public function forceDelete(int $product): RedirectResponse
    {
        $this->auditActivity(__FUNCTION__);

        $guard = $this->aiGuard('products', 'forceDelete', ['id' => $product], Auth::user());

        if (! ($guard['allowed'] ?? false)) {
            return $this->aiDenyRedirect($guard);
        }


        $this->productService->forceDelete($product);

        return back()->with('success', 'Produk berhasil dihapus permanen.');
    }


    protected function barcodeImageDataUri(?string $code): ?string
    {
        $code = preg_replace('/\D/', '', (string) $code) ?? '';
        $code = substr(str_pad($code, 13, '0', STR_PAD_LEFT), 0, 13);

        if (strlen($code) !== 13) {
            return null;
        }

        $svg = $this->buildEan13Svg($code);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    protected function buildEan13Svg(string $code): string
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        $code = substr(str_pad($code, 13, '0', STR_PAD_LEFT), 0, 13);

        if (strlen($code) !== 13) {
            return '';
        }

        $patterns = [
            'L' => [
                '0' => '0001101',
                '1' => '0011001',
                '2' => '0010011',
                '3' => '0111101',
                '4' => '0100011',
                '5' => '0110001',
                '6' => '0101111',
                '7' => '0111011',
                '8' => '0110111',
                '9' => '0001011',
            ],
            'G' => [
                '0' => '0100111',
                '1' => '0110011',
                '2' => '0011011',
                '3' => '0100001',
                '4' => '0011101',
                '5' => '0111001',
                '6' => '0000101',
                '7' => '0010001',
                '8' => '0001001',
                '9' => '0010111',
            ],
            'R' => [
                '0' => '1110010',
                '1' => '1100110',
                '2' => '1101100',
                '3' => '1000010',
                '4' => '1011100',
                '5' => '1001110',
                '6' => '1010000',
                '7' => '1000100',
                '8' => '1001000',
                '9' => '1110100',
            ],
        ];

        $parityMap = [
            '0' => 'LLLLLL',
            '1' => 'LLGLGG',
            '2' => 'LLGGLG',
            '3' => 'LLGGGL',
            '4' => 'LGLLGG',
            '5' => 'LGGLLG',
            '6' => 'LGGGLL',
            '7' => 'LGLGLG',
            '8' => 'LGLGGL',
            '9' => 'LGGLGL',
        ];

        $firstDigit = $code[0];
        $leftDigits = substr($code, 1, 6);
        $rightDigits = substr($code, 7, 6);
        $parity = $parityMap[$firstDigit] ?? 'LLLLLL';

        $bits = '101';
        for ($i = 0; $i < 6; $i++) {
            $digit = $leftDigits[$i];
            $encodingType = $parity[$i] ?? 'L';
            $bits .= $patterns[$encodingType][$digit] ?? $patterns['L'][$digit];
        }
        $bits .= '01010';
        for ($i = 0; $i < 6; $i++) {
            $digit = $rightDigits[$i];
            $bits .= $patterns['R'][$digit] ?? $patterns['R']['0'];
        }
        $bits .= '101';

        $module = 2;
        $quietZoneModules = 9;
        $barcodeWidth = (strlen($bits) + ($quietZoneModules * 2)) * $module;
        $barHeight = 78;
        $textTop = 100;
        $svgHeight = 128;
        $x = $quietZoneModules * $module;

        $rects = '';
        for ($i = 0, $len = strlen($bits); $i < $len; $i++) {
            if ($bits[$i] === '1') {
                $rects .= '<rect x="' . ($x + ($i * $module)) . '" y="0" width="' . $module . '" height="' . $barHeight . '" fill="#111827"/>';
            }
        }

        $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $barcodeWidth . '" height="' . $svgHeight . '" viewBox="0 0 ' . $barcodeWidth . ' ' . $svgHeight . '" role="img" aria-label="Barcode ' . $safeCode . '">' .
            '<rect width="100%" height="100%" fill="#ffffff"/>' .
            $rects .
            '<text x="' . ($barcodeWidth / 2) . '" y="' . $textTop . '" text-anchor="middle" font-size="12" fill="#111827" font-family="Arial, Helvetica, sans-serif" letter-spacing="2">' . $safeCode . '</text>' .
        '</svg>';
    }
}

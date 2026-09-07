<?php

namespace App\Http\Controllers;

use App\Services\PromoTileService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AdminPromoTileController extends Controller
{
    protected $promoTileService;

    public function __construct(PromoTileService $promoTileService)
    {
        $this->promoTileService = $promoTileService;
    }

    public function index()
    {
        $promoTiles = $this->promoTileService->getAll();

        return view('admin.promo_tiles.index', compact('promoTiles'));
    }

    public function create(): View
    {
        return view('admin.promo_tiles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'promo_title' => 'required|string|max:150',
            'promo_image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ], [
            'promo_image.max' => 'The promo image must not be larger than 5 MB.',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->promoTileService->create($validatedData, $adminId);

        return redirect()->route('admin.promo-tiles.index')
            ->with('success', 'Promo tile created successfully');
    }

    public function edit($id): View
    {
        $promoTile = $this->promoTileService->findForEdit($id);

        return view('admin.promo_tiles.edit', compact('promoTile'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $validatedData = $request->validate([
            'promo_title' => 'required|string|max:150',
            'promo_image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ], [
            'promo_image.max' => 'The promo image must not be larger than 5 MB.',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->promoTileService->update((int) $id, $validatedData, $adminId);

        return redirect()->route('admin.promo-tiles.edit', $id)
            ->with('success', 'Promo tile updated successfully');
    }

    public function destroy($id): RedirectResponse
    {
        $this->promoTileService->delete((int) $id);

        return redirect()->route('admin.promo-tiles.index')
            ->with('success', 'Promo tile deleted successfully');
    }

    public function products($id): View
    {
        $promoTile = $this->promoTileService->findForProductManagement($id);
        $products = $this->promoTileService->getLinkedProducts($id);
        $availableProducts = $this->promoTileService->getLinkableProducts((int) $id);

        return view('admin.promo_tiles.products', compact('promoTile', 'products', 'availableProducts'));
    }

    public function storeProduct(Request $request, $id): RedirectResponse
    {
        $request->merge([
            'product_name' => trim((string) $request->input('product_name')),
        ]);

        $validatedData = $request->validate([
            'product_name' => 'required|string|max:150',
        ]);

        $productId = $this->promoTileService->findProductIdByName($validatedData['product_name']);

        if (!$productId) {
            return back()
                ->withErrors(['product_name' => 'Please select a valid product from the suggestion list.'])
                ->withInput();
        }

        if ($this->promoTileService->existsDuplicate((int) $id, $productId)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This product is already added to this promo tile.');
        }

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->promoTileService->attachProduct((int) $id, $productId, $adminId);

        return redirect()->route('admin.promo-tiles.products', $id)
            ->with('success', 'Product added to promo tile successfully');
    }

    public function destroyProduct($id, $productId): RedirectResponse
    {
        $this->promoTileService->detachProduct((int) $id, (int) $productId);

        return redirect()->route('admin.promo-tiles.products', $id)
            ->with('success', 'Product removed from promo tile successfully');
    }
}
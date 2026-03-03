<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\FrontendCatalogService;
use Illuminate\Http\Request;

class FrontendCartController extends Controller
{
    protected $frontendCatalogService;
    protected $cartService;

    public function __construct(FrontendCatalogService $frontendCatalogService, CartService $cartService)
    {
        $this->frontendCatalogService = $frontendCatalogService;
        $this->cartService = $cartService;
    }

    public function index()
    {
        $menuCategories = $this->frontendCatalogService->getMenuCategories();

        return view('frontend.cart', compact('menuCategories'));
    }

    public function updateQuantity(Request $request, int $cartId)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $this->cartService->updateQuantity($cartId, (int) $validated['quantity']);

        return redirect()->route('frontend.cart')->with('success', 'Cart quantity updated successfully.');
    }

    public function remove(int $cartId)
    {
        $this->cartService->removeItem($cartId);

        return redirect()->route('frontend.cart')->with('success', 'Item removed from cart.');
    }
}

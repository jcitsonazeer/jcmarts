<?php

namespace App\Http\Controllers;

use App\Models\CustomerAddress;
use App\Services\CartService;
use App\Services\FrontendCatalogService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class FrontendCheckoutController extends Controller
{
    protected FrontendCatalogService $frontendCatalogService;
    protected CartService $cartService;

    public function __construct(FrontendCatalogService $frontendCatalogService, CartService $cartService)
    {
        $this->frontendCatalogService = $frontendCatalogService;
        $this->cartService = $cartService;
    }

    public function index(): RedirectResponse|View
    {
        $guardRedirect = $this->ensureCheckoutAccess();
        if ($guardRedirect) {
            return $guardRedirect;
        }

        $customerId = (int) session('customer_id');
        $orderSummary = $this->buildOrderSummary();

        $menuCategories = $this->frontendCatalogService->getMenuCategories();
        $addresses = CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->where('is_active', 1)
            ->orderByDesc('id')
            ->get();

        return view('frontend.checkout', [
            'menuCategories' => $menuCategories,
            'addresses' => $addresses,
            'subTotal' => $orderSummary['sub_total'],
            'deliveryCharge' => $orderSummary['delivery_charge'],
            'packingCharge' => $orderSummary['packing_charge'],
            'otherCharge' => $orderSummary['other_charge'],
            'total' => $orderSummary['total'],
        ]);
    }

    public function showAddAddress(): RedirectResponse|View
    {
        if (!session()->has('customer_id')) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Please login to add a delivery address.');
        }

        $menuCategories = $this->frontendCatalogService->getMenuCategories();

        return view('frontend.add_address', compact('menuCategories'));
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        if (!session()->has('customer_id')) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Please login to add a delivery address.');
        }

        $validated = $request->validate([
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:150'],
            'pincode' => ['required', 'digits_between:4,10'],
            'landmark' => ['nullable', 'string', 'max:255'],
        ]);

        CustomerAddress::query()->create([
            'customer_id' => (int) session('customer_id'),
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => $validated['address_line_2'] ?? null,
            'location' => $validated['location'],
            'pincode' => $validated['pincode'],
            'landmark' => $validated['landmark'] ?? null,
            'is_active' => 1,
            'created_by_id' => (int) session('customer_id'),
            'created_date' => Carbon::now(),
        ]);

        return redirect()
            ->route('frontend.checkout')
            ->with('success', 'Address added successfully.');
    }

    public function proceedToPayment(Request $request): RedirectResponse
    {
        $guardRedirect = $this->ensureCheckoutAccess();
        if ($guardRedirect) {
            return $guardRedirect;
        }

        $validated = $request->validate([
            'selected_address_id' => ['required', 'integer'],
        ]);

        $address = CustomerAddress::query()
            ->where('id', (int) $validated['selected_address_id'])
            ->where('customer_id', (int) session('customer_id'))
            ->where('is_active', 1)
            ->first();

        if (!$address) {
            return redirect()
                ->route('frontend.checkout')
                ->withErrors(['selected_address_id' => 'Please select a valid delivery address.']);
        }

        session(['checkout_address_id' => $address->id]);

        return redirect()->route('frontend.payment');
    }

    public function payment(): RedirectResponse|View
    {
        $guardRedirect = $this->ensureCheckoutAccess();
        if ($guardRedirect) {
            return $guardRedirect;
        }

        $selectedAddressId = (int) session('checkout_address_id', 0);

        $selectedAddress = CustomerAddress::query()
            ->where('id', $selectedAddressId)
            ->where('customer_id', (int) session('customer_id'))
            ->where('is_active', 1)
            ->first();

        if (!$selectedAddress) {
            return redirect()
                ->route('frontend.checkout')
                ->withErrors(['selected_address_id' => 'Please choose a delivery address to continue.']);
        }

        $menuCategories = $this->frontendCatalogService->getMenuCategories();
        $orderSummary = $this->buildOrderSummary();

        return view('frontend.payment', [
            'menuCategories' => $menuCategories,
            'selectedAddress' => $selectedAddress,
            'subTotal' => $orderSummary['sub_total'],
            'deliveryCharge' => $orderSummary['delivery_charge'],
            'packingCharge' => $orderSummary['packing_charge'],
            'otherCharge' => $orderSummary['other_charge'],
            'total' => $orderSummary['total'],
        ]);
    }

    private function ensureCheckoutAccess(): ?RedirectResponse
    {
        if (!session()->has('customer_id')) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Please login to continue checkout.');
        }

        if ($this->cartService->getItemCount() < 1) {
            return redirect()
                ->route('frontend.cart')
                ->with('error', 'Your cart is empty.');
        }

        return null;
    }

    private function buildOrderSummary(): array
    {
        /** @var Collection<int, mixed> $cartItems */
        $cartItems = $this->cartService->getActiveItems();

        $subTotal = (float) $cartItems->sum(function ($item) {
            return ((float) $item->unit_price) * ((int) $item->quantity);
        });

        $deliveryCharge = 0.0;
        $packingCharge = 0.0;
        $otherCharge = 0.0;
        $total = $subTotal + $deliveryCharge + $packingCharge + $otherCharge;

        return [
            'sub_total' => $subTotal,
            'delivery_charge' => $deliveryCharge,
            'packing_charge' => $packingCharge,
            'other_charge' => $otherCharge,
            'total' => $total,
        ];
    }
}

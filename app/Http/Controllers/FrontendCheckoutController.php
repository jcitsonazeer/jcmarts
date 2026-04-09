<?php

namespace App\Http\Controllers;

use App\Models\CustomerAddress;
use App\Services\CartService;
use App\Services\FrontendCatalogService;
use App\Services\OrderService;
use App\Services\Payment\RazorpayService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class FrontendCheckoutController extends Controller
{
    private const SERVICEABLE_PINCODES = [
        '629151',
        '629152',
        '629153',
        '629154',
        '629158',
        '629160',
        '629162',
        '629163',
        '629165',
        '629167',
        '629168',
        '629171',
        '629172',
        '629173',
        '629177',
        '629179',
        '629188',
        '629190',
        '629191',
        '629194',
        '629195',
        '629197',
    ];

    protected FrontendCatalogService $frontendCatalogService;
    protected CartService $cartService;
    protected RazorpayService $razorpayService;
    protected OrderService $orderService;

    public function __construct(
        FrontendCatalogService $frontendCatalogService,
        CartService $cartService,
        RazorpayService $razorpayService,
        OrderService $orderService
    ) {
        $this->frontendCatalogService = $frontendCatalogService;
        $this->cartService = $cartService;
        $this->razorpayService = $razorpayService;
        $this->orderService = $orderService;
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

        return view('frontend.add_address', [
            'menuCategories' => $menuCategories,
            'serviceablePincodes' => self::SERVICEABLE_PINCODES,
        ]);
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
            'address_line_2' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:150'],
            'pincode' => ['required', 'digits:6', Rule::in(self::SERVICEABLE_PINCODES)],
            'landmark' => ['required', 'string', 'max:255'],
        ], [
            'pincode.in' => 'Delivery not available for the entered pincode',
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

    public function createRazorpayOrder(Request $request): JsonResponse
    {
        $guardRedirect = $this->ensureCheckoutAccess();
        if ($guardRedirect) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $customerId = (int) session('customer_id');
        $addressId = (int) session('checkout_address_id');

        if ($addressId < 1) {
            return response()->json(['message' => 'Please choose a delivery address to continue.'], 422);
        }

        $this->orderService->cleanupExpiredPendingOrders();

        if (session()->has('pending_order_id')) {
            $this->orderService->releasePendingOrder((int) session('pending_order_id'), null, $customerId);
            session()->forget(['pending_order_id', 'razorpay_order_id', 'razorpay_amount']);
        }

        $orderSummary = $this->buildOrderSummary();
        $total = (float) $orderSummary['total'];
        $amountInPaise = (int) round($total * 100);

        if ($amountInPaise < 100) {
            return response()->json(['message' => 'Minimum payable amount is Rs 1.'], 422);
        }

        $cartItems = $this->cartService->getActiveItems();

        try {
            $pendingOrder = $this->orderService->createPendingOrderFromCart(
                $customerId,
                $addressId,
                $orderSummary,
                $cartItems
            );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        try {
            $order = $this->razorpayService->createOrder(
                $amountInPaise,
                'jcmart_' . $pendingOrder->id . '_' . now()->format('YmdHis'),
                (string) config('razorpay.currency', 'INR')
            );

            $this->orderService->attachRazorpayOrder(
                (int) $pendingOrder->id,
                (string) $order['id'],
                $customerId
            );
        } catch (Throwable $e) {
            $this->orderService->releasePendingOrder((int) $pendingOrder->id, null, $customerId);

            return response()->json(['message' => 'Payment could not be started. Please try again.'], 422);
        }

        session([
            'pending_order_id' => $pendingOrder->id,
            'razorpay_order_id' => $order['id'],
            'razorpay_amount' => $amountInPaise,
        ]);

        return response()->json([
            'order_id' => $order['id'],
            'amount' => $amountInPaise,
            'currency' => $order['currency'],
            'key' => config('razorpay.key'),
        ]);
    }
    public function verifyRazorpayPayment(Request $request): JsonResponse
    {
        $guardRedirect = $this->ensureCheckoutAccess();
        if ($guardRedirect) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        if ($validated['razorpay_order_id'] !== (string) session('razorpay_order_id')) {
            return response()->json(['message' => 'Order mismatch.'], 422);
        }

        try {
            $this->razorpayService->verifySignature(
                $validated['razorpay_order_id'],
                $validated['razorpay_payment_id'],
                $validated['razorpay_signature']
            );

            $cartItems = $this->cartService->getActiveItems();
            $customerId = (int) session('customer_id');

            $this->orderService->markPendingOrderAsPaid(
                $validated['razorpay_order_id'],
                $customerId,
                $validated,
                $cartItems
            );

            session()->forget(['pending_order_id', 'razorpay_order_id', 'razorpay_amount', 'checkout_address_id']);

            return response()->json([
                'message' => 'Payment successful.',
            ]);
        } catch (Throwable $e) {
            $this->orderService->releasePendingOrder(
                (int) session('pending_order_id', 0) ?: null,
                (string) session('razorpay_order_id', '') ?: null,
                (int) session('customer_id')
            );

            session()->forget(['pending_order_id', 'razorpay_order_id', 'razorpay_amount']);

            return response()->json([
                'message' => 'Payment verification failed.',
            ], 422);
        }
    }

    public function releasePendingPayment(Request $request): JsonResponse
    {
        $guardRedirect = $this->ensureCheckoutAccess();
        if ($guardRedirect) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'razorpay_order_id' => ['nullable', 'string'],
        ]);

        $customerId = (int) session('customer_id');
        $pendingOrderId = (int) session('pending_order_id', 0);
        $razorpayOrderId = (string) ($validated['razorpay_order_id'] ?? session('razorpay_order_id', ''));

        $this->orderService->releasePendingOrder(
            $pendingOrderId > 0 ? $pendingOrderId : null,
            $razorpayOrderId !== '' ? $razorpayOrderId : null,
            $customerId
        );

        session()->forget(['pending_order_id', 'razorpay_order_id', 'razorpay_amount']);

        return response()->json([
            'message' => 'Reserved stock released.',
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



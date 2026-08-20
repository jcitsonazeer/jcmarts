<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Services\ApiCartService;
use App\Services\OrderService;
use App\Services\Payment\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class CheckoutController extends Controller
{
    protected ApiCartService $apiCartService;
    protected RazorpayService $razorpayService;
    protected OrderService $orderService;

    public function __construct(
        ApiCartService $apiCartService,
        RazorpayService $razorpayService,
        OrderService $orderService
    ) {
        $this->apiCartService = $apiCartService;
        $this->razorpayService = $razorpayService;
        $this->orderService = $orderService;
    }

    /**
     * GET /api/v1/checkout
     * Return cart items, addresses, and order summary for the checkout page.
     */
    public function index(Request $request)
    {
        $customerId = $request->user()->id;
        $sessionId = $this->apiCartService->buildSessionId($customerId, null);

        $cartItems = $this->apiCartService->getActiveItems($sessionId);

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Your cart is empty',
                'data' => null,
            ], 422);
        }

        $addresses = CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->where('is_active', 1)
            ->orderByDesc('id')
            ->get();

        $subTotal = (float) $cartItems->sum(function ($item) {
            return ((float) $item->unit_price) * ((int) $item->quantity);
        });

        $deliveryCharge = 0.0;
        $packingCharge = 0.0;
        $otherCharge = 0.0;
        $total = $subTotal + $deliveryCharge + $packingCharge + $otherCharge;

        return response()->json([
            'status' => true,
            'message' => 'Checkout data fetched successfully',
            'data' => [
                'cart_items' => $cartItems,
                'addresses' => $addresses,
                'summary' => [
                    'sub_total' => $subTotal,
                    'delivery_charge' => $deliveryCharge,
                    'packing_charge' => $packingCharge,
                    'other_charge' => $otherCharge,
                    'total' => $total,
                ],
            ],
        ]);
    }

    /**
     * POST /api/v1/payment/create-order
     * Create a pending order from the cart and initiate a Razorpay order.
     *
     * Expects:
     *   - selected_address_id (int, required)
     *
     * Returns:
     *   - razorpay_order_id  — send back in verify
     *   - pending_order_id   — send back in verify / release
     *   - amount             — in paise
     *   - currency
     *   - key                — Razorpay key for the Flutter SDK
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'selected_address_id' => ['required', 'integer'],
        ]);

        $customerId = $request->user()->id;

        $address = CustomerAddress::query()
            ->where('id', (int) $validated['selected_address_id'])
            ->where('customer_id', $customerId)
            ->where('is_active', 1)
            ->first();

        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Please select a valid delivery address',
                'data' => null,
            ], 422);
        }

        $sessionId = $this->apiCartService->buildSessionId($customerId, null);
        $cartItems = $this->apiCartService->getActiveItems($sessionId);

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Your cart is empty',
                'data' => null,
            ], 422);
        }

        $subTotal = (float) $cartItems->sum(function ($item) {
            return ((float) $item->unit_price) * ((int) $item->quantity);
        });

        $orderSummary = [
            'sub_total' => $subTotal,
            'delivery_charge' => 0.0,
            'packing_charge' => 0.0,
            'other_charge' => 0.0,
            'total' => $subTotal,
        ];

        $total = (float) $orderSummary['total'];
        $amountInPaise = (int) round($total * 100);

        if ($amountInPaise < 100) {
            return response()->json([
                'status' => false,
                'message' => 'Minimum payable amount is Rs 1',
                'data' => null,
            ], 422);
        }

        // Cleanup any expired pending orders
        $this->orderService->cleanupExpiredPendingOrders();

        try {
            $pendingOrder = $this->orderService->createPendingOrderFromCart(
                $customerId,
                (int) $address->id,
                $orderSummary,
                $cartItems
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
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

            return response()->json([
                'status' => false,
                'message' => 'Payment could not be started. Please try again',
                'data' => null,
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Payment order created successfully',
            'data' => [
                'pending_order_id' => $pendingOrder->id,
                'razorpay_order_id' => $order['id'],
                'amount' => $amountInPaise,
                'currency' => $order['currency'],
                'key' => config('razorpay.key'),
            ],
        ]);
    }

    /**
     * POST /api/v1/payment/verify
     * Verify the Razorpay payment and finalize the order.
     *
     * Expects:
     *   - pending_order_id       (int, required)
     *   - razorpay_order_id      (string, required)
     *   - razorpay_payment_id    (string, required)
     *   - razorpay_signature     (string, required)
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pending_order_id' => ['required', 'integer'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $customerId = $request->user()->id;
        $sessionId = $this->apiCartService->buildSessionId($customerId, null);

        try {
            $this->razorpayService->verifySignature(
                $validated['razorpay_order_id'],
                $validated['razorpay_payment_id'],
                $validated['razorpay_signature']
            );

            $cartItems = $this->apiCartService->getActiveItems($sessionId);

            $this->orderService->markPendingOrderAsPaid(
                $validated['razorpay_order_id'],
                $customerId,
                $validated,
                $cartItems
            );

            return response()->json([
                'status' => true,
                'message' => 'Payment successful. Order placed.',
                'data' => null,
            ]);
        } catch (Throwable $e) {
            $this->orderService->releasePendingOrder(
                (int) $validated['pending_order_id'],
                $validated['razorpay_order_id'],
                $customerId
            );

            return response()->json([
                'status' => false,
                'message' => 'Payment verification failed',
                'data' => null,
            ], 422);
        }
    }

    /**
     * POST /api/v1/payment/release
     * Release a pending order (cancel stock reservation) if payment was abandoned.
     *
     * Expects:
     *   - pending_order_id    (int, required)
     *   - razorpay_order_id   (string, required)
     */
    public function releasePendingPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pending_order_id' => ['required', 'integer'],
            'razorpay_order_id' => ['required', 'string'],
        ]);

        $customerId = $request->user()->id;

        $this->orderService->releasePendingOrder(
            (int) $validated['pending_order_id'],
            $validated['razorpay_order_id'],
            $customerId
        );

        return response()->json([
            'status' => true,
            'message' => 'Reserved stock released',
            'data' => null,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ApiCartService;
use App\Services\DeliveryChargeService;
use App\Services\OrderService;
use App\Services\Payment\RazorpayService;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class CheckoutController extends Controller
{
    private const CHECKOUT_LOCK_SECONDS = 15;
    private const CHECKOUT_LOCK_WAIT_SECONDS = 5;
    private const IDEMPOTENCY_TTL_MINUTES = 5;

    protected ApiCartService $apiCartService;
    protected RazorpayService $razorpayService;
    protected OrderService $orderService;
    protected DeliveryChargeService $deliveryChargeService;

    public function __construct(
        ApiCartService $apiCartService,
        RazorpayService $razorpayService,
        OrderService $orderService,
        DeliveryChargeService $deliveryChargeService
    ) {
        $this->apiCartService = $apiCartService;
        $this->razorpayService = $razorpayService;
        $this->orderService = $orderService;
        $this->deliveryChargeService = $deliveryChargeService;
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

        $deliveryCharge = $this->deliveryChargeService->calculateDeliveryCharge($subTotal);
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
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $customerId = (int) $request->user()->id;
        $cacheKey = $this->buildIdempotencyCacheKey(
            $customerId,
            $this->resolveIdempotencyKey($request, $validated)
        );

        // Replay the previous response when the client retries the same request.
        $cachedResponse = $this->getCachedCheckoutResponse($cacheKey);
        if ($cachedResponse !== null) {
            return response()->json($cachedResponse);
        }

        $lock = Cache::lock('checkout:' . $customerId, self::CHECKOUT_LOCK_SECONDS);

        try {
            $lock->block(self::CHECKOUT_LOCK_WAIT_SECONDS);
        } catch (LockTimeoutException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Another checkout is already in progress. Please try again.',
                'data' => null,
            ], 409);
        }

        try {
            // Check again after waiting, another request may have just finished.
            $cachedResponse = $this->getCachedCheckoutResponse($cacheKey);
            if ($cachedResponse !== null) {
                return response()->json($cachedResponse);
            }

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

            if (!$this->deliveryChargeService->isDeliveryAllowed($subTotal)) {
                $setting = $this->deliveryChargeService->getActiveSettings();
                $minimum = $setting ? (float) $setting->minimum_order_value : 0;

                return response()->json([
                    'status' => false,
                    'message' => 'Minimum order value for direct delivery is Rs. ' . number_format($minimum, 0) . '. Please add more items to continue.',
                    'data' => null,
                ], 422);
            }

            $deliveryCharge = $this->deliveryChargeService->calculateDeliveryCharge($subTotal);

            $orderSummary = [
                'sub_total' => $subTotal,
                'delivery_charge' => $deliveryCharge,
                'packing_charge' => 0.0,
                'other_charge' => 0.0,
                'total' => $subTotal + $deliveryCharge,
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

            // Cleanup expired reservations and any previous open order.
            $this->orderService->cleanupExpiredPendingOrders();
            $this->orderService->releaseOpenPendingOrdersForCustomer($customerId);

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

            $responseBody = [
                'status' => true,
                'message' => 'Payment order created successfully',
                'data' => [
                    'pending_order_id' => $pendingOrder->id,
                    'razorpay_order_id' => $order['id'],
                    'amount' => $amountInPaise,
                    'currency' => $order['currency'],
                    'key' => config('razorpay.key'),
                ],
            ];

            if ($cacheKey !== null) {
                Cache::put($cacheKey, $responseBody, now()->addMinutes(self::IDEMPOTENCY_TTL_MINUTES));
            }

            return response()->json($responseBody);
        } finally {
            $lock->release();
        }
    }

    /**
     * Read the idempotency key from the body or the Idempotency-Key header.
     */
    private function resolveIdempotencyKey(Request $request, array $validated): ?string
    {
        $key = $validated['idempotency_key'] ?? $request->header('Idempotency-Key');

        if (!is_string($key)) {
            return null;
        }

        $key = trim($key);

        if ($key === '') {
            return null;
        }

        return substr($key, 0, 100);
    }

    private function buildIdempotencyCacheKey(int $customerId, ?string $idempotencyKey): ?string
    {
        if ($idempotencyKey === null) {
            return null;
        }

        return 'checkout:idempotency:' . $customerId . ':' . hash('sha256', $idempotencyKey);
    }

    /**
     * Return a cached response only while its pending order is still valid.
     */
    private function getCachedCheckoutResponse(?string $cacheKey): ?array
    {
        if ($cacheKey === null) {
            return null;
        }

        $cachedResponse = Cache::get($cacheKey);

        if (!is_array($cachedResponse)) {
            return null;
        }

        $pendingOrderId = (int) ($cachedResponse['data']['pending_order_id'] ?? 0);

        if ($pendingOrderId < 1) {
            return null;
        }

        $pendingOrder = Order::query()->find($pendingOrderId);

        if (!$pendingOrder || !$pendingOrder->is_active || !is_null($pendingOrder->reservation_released_at)) {
            return null;
        }

        return $cachedResponse;
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

        $customerId = (int) $request->user()->id;
        $sessionId = $this->apiCartService->buildSessionId($customerId, null);

        try {
            $this->razorpayService->verifySignature(
                $validated['razorpay_order_id'],
                $validated['razorpay_payment_id'],
                $validated['razorpay_signature']
            );
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

        $pendingOrder = $this->orderService->getActiveOrderByRazorpayOrderId(
            (string) $validated['razorpay_order_id'],
            $customerId
        );

        if (!$pendingOrder) {
            return response()->json([
                'status' => false,
                'message' => 'Reserved order not found or already released',
                'data' => null,
            ], 422);
        }

        try {
            $gatewayPayment = $this->razorpayService->fetchPayment(
                (string) $validated['razorpay_payment_id']
            );
        } catch (Throwable $e) {
            // Do not release stock here: Razorpay may have captured the payment.
            return response()->json([
                'status' => false,
                'message' => 'Payment could not be confirmed. Please try again.',
                'data' => null,
            ], 422);
        }

        $expectedAmount = (int) round(((float) $pendingOrder->total_amount) * 100);
        $amountMatches = (int) ($gatewayPayment['amount'] ?? 0) === $expectedAmount;
        $orderMatches = (string) ($gatewayPayment['order_id'] ?? '') === (string) $validated['razorpay_order_id'];
        $currencyMatches = strtoupper((string) ($gatewayPayment['currency'] ?? '')) === strtoupper((string) ($pendingOrder->currency ?? 'INR'));

        if (!$amountMatches || !$orderMatches || !$currencyMatches) {
            // A real payment may have been captured, so keep the reservation
            // for the webhook/reconciliation instead of releasing stock.
            return response()->json([
                'status' => false,
                'message' => 'Payment amount or currency does not match the order',
                'data' => null,
            ], 422);
        }

        try {
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

    /**
     * POST /api/v1/payments/razorpay/webhook
     * Handle Razorpay webhooks for payment.captured.
     */
    public function razorpayWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature', '');

        $secret = (string) config('razorpay.secret');
        if ($secret === '' || $signature === '') {
            return response()->json([
                'status' => false,
                'message' => 'Webhook signature missing',
            ], 400);
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid webhook signature',
            ], 400);
        }

        $data = json_decode($payload, true);
        if (!is_array($data)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid webhook payload',
            ], 400);
        }

        $event = (string) ($data['event'] ?? '');
        if ($event !== 'payment.captured') {
            return response()->json([
                'status' => true,
                'message' => 'Webhook ignored',
            ]);
        }

        $paymentEntity = $data['payload']['payment']['entity'] ?? null;
        if (!is_array($paymentEntity)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid payment entity',
            ], 400);
        }

        $razorpayOrderId = (string) ($paymentEntity['order_id'] ?? '');
        $razorpayPaymentId = (string) ($paymentEntity['id'] ?? '');
        $amount = isset($paymentEntity['amount']) ? (int) $paymentEntity['amount'] : null;
        $currency = (string) ($paymentEntity['currency'] ?? config('razorpay.currency', 'INR'));

        if ($razorpayOrderId === '' || $razorpayPaymentId === '') {
            return response()->json([
                'status' => false,
                'message' => 'Missing payment details',
            ], 400);
        }

        try {
            DB::transaction(function () use ($razorpayOrderId, $razorpayPaymentId, $amount, $currency) {
                $payment = Payment::query()
                    ->where('gateway', 'razorpay')
                    ->where('gateway_order_id', $razorpayOrderId)
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    return;
                }

                $order = Order::query()
                    ->with('items')
                    ->where('id', $payment->order_id)
                    ->lockForUpdate()
                    ->first();

                if (!$order) {
                    return;
                }

                if ($payment->status === 'paid') {
                    return;
                }

                if ($payment->status !== 'pending') {
                    return;
                }

                if (!is_null($order->reservation_released_at) || !$order->is_active) {
                    Log::warning('Webhook payment.captured ignored for a released/inactive order; reconciliation will refund if captured.', [
                        'order_id' => $order->id,
                        'razorpay_order_id' => $razorpayOrderId,
                        'razorpay_payment_id' => $razorpayPaymentId,
                    ]);
                    return;
                }

                if ($amount !== null) {
                    $expectedAmount = (int) round(((float) $order->total_amount) * 100);
                    if ($expectedAmount > 0 && $amount !== $expectedAmount) {
                        return;
                    }
                }

                $order->currency = $currency;
                $order->reservation_released_at = null;
                $order->reservation_release_reason = null;
                $order->updated_by_id = $order->customer_id;
                $order->updated_date = Carbon::now();
                $order->save();

                $payment->gateway_payment_id = $razorpayPaymentId;
                $payment->amount = (float) $order->total_amount;
                $payment->currency = $currency;
                $payment->status = 'paid';
                $payment->paid_at = Carbon::now();
                $payment->updated_by_id = $order->customer_id;
                $payment->updated_date = Carbon::now();
                $payment->save();
            });
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Webhook processing failed',
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Webhook processed',
        ]);
    }
}

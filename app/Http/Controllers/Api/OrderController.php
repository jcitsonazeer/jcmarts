<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FrontendOrderService;
use App\Services\OrderCancellationService;
use App\Services\ReturnService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class OrderController extends Controller
{
    protected FrontendOrderService $frontendOrderService;
    protected OrderCancellationService $orderCancellationService;
    protected ReturnService $returnService;

    public function __construct(
        FrontendOrderService $frontendOrderService,
        OrderCancellationService $orderCancellationService,
        ReturnService $returnService
    ) {
        $this->frontendOrderService = $frontendOrderService;
        $this->orderCancellationService = $orderCancellationService;
        $this->returnService = $returnService;
    }

    // ─── ORDERS ────────────────────────────────────────────

    /**
     * GET /api/v1/orders
     * List all orders for the authenticated customer.
     * Optional query param: ?q=<search> (order id, status, payment)
     */
    public function index(Request $request)
    {
        $customerId = $request->user()->id;
        $search = (string) $request->query('q', '');

        $orders = $this->frontendOrderService->getOrdersForCustomer($customerId, $search ?: null);

        $data = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'current_order_status' => $order->current_order_status,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
                'items_count' => $order->items_count,
                'current_payment_method' => $order->current_payment_method,
                'current_payment_status' => $order->current_payment_status,
                'created_date' => $order->created_date,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Orders fetched successfully',
            'data' => $data,
        ]);
    }

    /**
     * GET /api/v1/orders/{id}
     * Get full details for a single order (items, address, timeline, return eligibility).
     */
    public function show(Request $request, int $orderId)
    {
        $customerId = $request->user()->id;

        $order = $this->frontendOrderService->getOrderForCustomer($orderId, $customerId);

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Order fetched successfully',
            'data' => [
                'id' => $order->id,
                'sub_total' => $order->sub_total,
                'delivery_charge' => $order->delivery_charge,
                'packing_charge' => $order->packing_charge,
                'other_charge' => $order->other_charge,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,
                'current_order_status' => $order->current_order_status,
                'current_payment_method' => $order->current_payment_method,
                'current_payment_status' => $order->current_payment_status,
                'current_payment_paid_at' => $order->current_payment_paid_at,
                'can_customer_cancel' => $order->can_customer_cancel,
                'can_customer_return' => $order->can_customer_return,
                'return_allowed_until' => $order->return_allowed_until,
                'return_period_expired' => $order->return_period_expired,
                'returnable_items' => $order->returnable_items,
                'order_status_timeline' => $order->order_status_timeline,
                'items' => $order->items,
                'address' => $order->address,
                'statuses' => $order->statuses,
                'payments' => $order->payments,
                'refunds' => $order->refunds,
                'return_requests' => $order->returnRequests,
                'created_date' => $order->created_date,
            ],
        ]);
    }

    // ─── CANCELLATION ──────────────────────────────────────

    /**
     * POST /api/v1/orders/{id}/cancel
     * Customer requests cancellation of a paid order.
     * Stock is restored only after admin approval; this just submits the request.
     */
    public function cancel(Request $request, int $orderId): JsonResponse
    {
        $customerId = $request->user()->id;

        try {
            $this->orderCancellationService->cancelByCustomer(
                $orderId,
                $customerId
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Order cancellation request submitted successfully',
            'data' => null,
        ]);
    }

    // ─── RETURNS ───────────────────────────────────────────

    /**
     * GET /api/v1/returns/reasons
     * Return the list of valid return reasons.
     */
    public function returnReasons()
    {
        return response()->json([
            'status' => true,
            'message' => 'Return reasons fetched successfully',
            'data' => [
                'reasons' => $this->returnService->getReasons(),
            ],
        ]);
    }

    /**
     * POST /api/v1/orders/{id}/return
     * Submit a return request for a delivered order.
     *
     * Expects:
     *   - reason          (string, required — must match one of the reasons)
     *   - customer_note   (string, optional)
     *   - items           (object, required — key = order_item_id, value = { quantity: int })
     *
     * Example items payload:
     *   {
     *       "12": { "quantity": 1 },
     *       "15": { "quantity": 2 }
     *   }
     */
    public function requestReturn(Request $request, int $orderId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:100'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array'],
        ]);

        $customerId = $request->user()->id;

        try {
            $this->returnService->requestByCustomer(
                $orderId,
                $customerId,
                $validated['reason'],
                $validated['customer_note'] ?? null,
                $validated['items']
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Return request submitted successfully',
            'data' => null,
        ]);
    }
}

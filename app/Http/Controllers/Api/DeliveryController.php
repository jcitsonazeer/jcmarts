<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Services\DeliveryStatusService;
use App\Services\DeliveryTrackingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class DeliveryController extends Controller
{
    protected DeliveryStatusService $deliveryStatusService;
    protected DeliveryTrackingService $deliveryTrackingService;

    public function __construct(
        DeliveryStatusService $deliveryStatusService,
        DeliveryTrackingService $deliveryTrackingService
    ) {
        $this->deliveryStatusService = $deliveryStatusService;
        $this->deliveryTrackingService = $deliveryTrackingService;
    }

    /**
     * GET /api/v1/delivery/dashboard
     *
     * Summary stats for the logged-in delivery person + the current active delivery (if any).
     */
    public function dashboard(Request $request)
    {
        $deliveryPerson = $request->user();

        $activeStatuses = ['assigned', 'accepted', 'picked_up', 'out_for_delivery'];

        $activeCount = Delivery::query()
            ->where('delivery_person_id', $deliveryPerson->id)
            ->whereIn('status', $activeStatuses)
            ->count();

        $completedCount = Delivery::query()
            ->where('delivery_person_id', $deliveryPerson->id)
            ->where('status', 'delivered')
            ->count();

        $currentActive = Delivery::query()
            ->with(['order.customer', 'order.address'])
            ->where('delivery_person_id', $deliveryPerson->id)
            ->whereIn('status', $activeStatuses)
            ->orderByDesc('assigned_at')
            ->first();

        return response()->json([
            'status' => true,
            'message' => 'Delivery dashboard fetched successfully',
            'data' => [
                'delivery_person' => [
                    'id' => $deliveryPerson->id,
                    'name' => $deliveryPerson->name,
                    'vehicle_type' => $deliveryPerson->vehicle_type,
                    'vehicle_number' => $deliveryPerson->vehicle_number,
                    'availability_status' => $deliveryPerson->availability_status,
                ],
                'stats' => [
                    'active_deliveries' => $activeCount,
                    'completed_deliveries' => $completedCount,
                ],
                'current_active_delivery' => $currentActive ? $this->formatDeliverySummary($currentActive) : null,
            ],
        ]);
    }

    /**
     * GET /api/v1/delivery/assigned-orders
     *
     * All deliveries currently assigned to this delivery person.
     */
    public function assignedOrders(Request $request)
    {
        $deliveryPerson = $request->user();

        $activeStatuses = ['assigned', 'accepted', 'picked_up', 'out_for_delivery'];

        $deliveries = Delivery::query()
            ->with(['order.customer', 'order.address', 'order.items.product'])
            ->where('delivery_person_id', $deliveryPerson->id)
            ->whereIn('status', $activeStatuses)
            ->orderByDesc('assigned_at')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Assigned orders fetched successfully',
            'data' => [
                'deliveries' => $deliveries->map(function ($delivery) {
                    return $this->formatDelivery($delivery);
                }),
            ],
        ]);
    }

    /**
     * GET /api/v1/delivery/deliveries/{deliveryId}
     *
     * Full detail of a single delivery that belongs to this delivery person.
     */
    public function show(Request $request, int $deliveryId)
    {
        $deliveryPerson = $request->user();

        $delivery = Delivery::query()
            ->with([
                'order.customer',
                'order.address',
                'order.items.product',
                'statusHistories',
                'locations',
            ])
            ->where('id', $deliveryId)
            ->where('delivery_person_id', $deliveryPerson->id)
            ->first();

        if (!$delivery) {
            return response()->json([
                'status' => false,
                'message' => 'Delivery not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Delivery fetched successfully',
            'data' => $this->formatDeliveryDetail($delivery),
        ]);
    }

    /**
     * POST /api/v1/delivery/deliveries/{deliveryId}/status
     *
     * Update the delivery status by the delivery person.
     * Body: { "status": "accepted" | "picked_up" | "out_for_delivery" | "delivered" | "rejected" | "failed" | "cancelled" }
     */
    public function updateStatus(Request $request, int $deliveryId)
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $deliveryPerson = $request->user();

        $delivery = Delivery::query()
            ->where('id', $deliveryId)
            ->where('delivery_person_id', $deliveryPerson->id)
            ->first();

        if (!$delivery) {
            return response()->json([
                'status' => false,
                'message' => 'Delivery not found',
                'data' => null,
            ], 404);
        }

        try {
            $updatedDelivery = $this->deliveryStatusService->changeStatus(
                $deliveryId,
                $validated['status'],
                $deliveryPerson->id
            );
        } catch (InvalidArgumentException | RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Delivery status updated successfully',
            'data' => [
                'delivery' => $this->formatDeliverySummary($updatedDelivery),
                'allowed_next_statuses' => $this->deliveryStatusService->getAllowedNextStatuses($updatedDelivery->status),
            ],
        ]);
    }

    /**
     * GET /api/v1/delivery/history
     *
     * Delivered delivery history for this delivery person.
     */
    public function history(Request $request)
    {
        $deliveryPerson = $request->user();

        $deliveries = Delivery::query()
            ->with(['order.customer', 'order.address', 'order.items'])
            ->where('delivery_person_id', $deliveryPerson->id)
            ->where('status', 'delivered')
            ->orderByDesc('delivered_at')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Delivery history fetched successfully',
            'data' => [
                'deliveries' => $deliveries->map(function ($delivery) {
                    return $this->formatDelivery($delivery);
                }),
            ],
        ]);
    }

    /**
     * PUT /api/v1/delivery/availability
     *
     * Update the delivery person's availability status.
     * Body: { "availability_status": "available" | "offline" }
     */
    public function updateAvailability(Request $request)
    {
        $validated = $request->validate([
            'availability_status' => ['required', 'string', 'in:available,offline'],
        ]);

        $deliveryPerson = $request->user();

        $deliveryPerson->update([
            'availability_status' => $validated['availability_status'],
            'updated_date' => Carbon::now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Availability updated successfully',
            'data' => [
                'delivery_person' => [
                    'id' => $deliveryPerson->id,
                    'availability_status' => $deliveryPerson->availability_status,
                ],
            ],
        ]);
    }

    /**
     * POST /api/v1/delivery/deliveries/{deliveryId}/location
     *
     * Save the delivery person's current GPS location for a delivery.
     * Body: { "latitude": 12.123, "longitude": 77.123 }
     */
    public function saveLocation(Request $request, int $deliveryId)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $deliveryPerson = $request->user();

        try {
            $location = $this->deliveryTrackingService->saveLocation(
                $deliveryId,
                $deliveryPerson->id,
                (float) $validated['latitude'],
                (float) $validated['longitude']
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ], 403);
        }

        return response()->json([
            'status' => true,
            'message' => 'Location saved successfully',
            'data' => [
                'location' => [
                    'id' => $location->id,
                    'delivery_id' => $location->delivery_id,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'recorded_at' => $location->recorded_at,
                ],
            ],
        ]);
    }

    /**
     * Small helper for the summary card items.
     */
    private function formatDeliverySummary($delivery): array
    {
        return [
            'id' => $delivery->id,
            'order_id' => $delivery->order_id,
            'status' => $delivery->status,
            'delivery_address' => $delivery->delivery_address,
            'assigned_at' => $delivery->assigned_at,
            'customer' => $delivery->order && $delivery->order->customer ? [
                'id' => $delivery->order->customer->id,
                'name' => $delivery->order->customer->name,
                'mobile_number' => $delivery->order->customer->mobile_number,
            ] : null,
            'address' => $delivery->order && $delivery->order->address ? [
                'id' => $delivery->order->address->id,
                'address_line_1' => $delivery->order->address->address_line_1,
                'address_line_2' => $delivery->order->address->address_line_2,
                'city' => $delivery->order->address->city,
                'state' => $delivery->order->address->state,
                'pincode' => $delivery->order->address->pincode,
            ] : null,
        ];
    }

    /**
     * Helper for list views (assigned orders / history).
     */
    private function formatDelivery($delivery): array
    {
        $order = $delivery->order;

        return [
            'id' => $delivery->id,
            'order_id' => $delivery->order_id,
            'status' => $delivery->status,
            'delivery_address' => $delivery->delivery_address,
            'assigned_at' => $delivery->assigned_at,
            'accepted_at' => $delivery->accepted_at,
            'picked_up_at' => $delivery->picked_up_at,
            'out_for_delivery_at' => $delivery->out_for_delivery_at,
            'delivered_at' => $delivery->delivered_at,
            'customer' => $order && $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'mobile_number' => $order->customer->mobile_number,
            ] : null,
            'order_total' => $order ? $order->total_amount : null,
            'item_count' => $order && $order->items ? $order->items->count() : 0,
        ];
    }

    /**
     * Helper for the full single-delivery detail.
     */
    private function formatDeliveryDetail($delivery): array
    {
        $order = $delivery->order;

        $items = [];
        if ($order && $order->items) {
            foreach ($order->items as $item) {
                $items[] = [
                    'id' => $item->id,
                    'product_name' => $item->product ? $item->product->product_name : 'Product',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ];
            }
        }

        $timeline = [];
        if ($delivery->statusHistories) {
            foreach ($delivery->statusHistories as $history) {
                $timeline[] = [
                    'old_status' => $history->old_status,
                    'new_status' => $history->new_status,
                    'changed_at' => $history->changed_at,
                ];
            }
        }

        return [
            'id' => $delivery->id,
            'order_id' => $delivery->order_id,
            'status' => $delivery->status,
            'delivery_address' => $delivery->delivery_address,
            'assigned_at' => $delivery->assigned_at,
            'accepted_at' => $delivery->accepted_at,
            'picked_up_at' => $delivery->picked_up_at,
            'out_for_delivery_at' => $delivery->out_for_delivery_at,
            'delivered_at' => $delivery->delivered_at,
            'allowed_next_statuses' => $this->deliveryStatusService->getAllowedNextStatuses($delivery->status),
            'customer' => $order && $order->customer ? [
                'id' => $order->customer->id,
                'name' => $order->customer->name,
                'mobile_number' => $order->customer->mobile_number,
            ] : null,
            'order' => $order ? [
                'id' => $order->id,
                'total_amount' => $order->total_amount,
                'delivery_charge' => $order->delivery_charge,
                'currency' => $order->currency,
            ] : null,
            'address' => $order && $order->address ? [
                'id' => $order->address->id,
                'address_line_1' => $order->address->address_line_1,
                'address_line_2' => $order->address->address_line_2,
                'city' => $order->address->city,
                'state' => $order->address->state,
                'pincode' => $order->address->pincode,
            ] : null,
            'items' => $items,
            'timeline' => $timeline,
        ];
    }
}

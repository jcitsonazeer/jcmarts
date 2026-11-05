<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DeliveryPerson;
use App\Models\DeliveryStatusHistory;
use App\Models\Order;
use App\Services\ReturnService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DeliveryStatusService
{
    protected OrderStatusService $orderStatusService;

    public function __construct(OrderStatusService $orderStatusService)
    {
        $this->orderStatusService = $orderStatusService;
    }

    public const STATUS_NOT_ASSIGNED = 'not_assigned';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    public const STATUS_FLOW = [
        self::STATUS_NOT_ASSIGNED,
        self::STATUS_ASSIGNED,
        self::STATUS_ACCEPTED,
        self::STATUS_PICKED_UP,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED,
    ];

    public const TERMINAL_STATUSES = [
        self::STATUS_DELIVERED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
        self::STATUS_FAILED,
    ];

    public const STATUS_TRANSITIONS = [
        self::STATUS_NOT_ASSIGNED => [self::STATUS_ASSIGNED],
        self::STATUS_ASSIGNED => [self::STATUS_ACCEPTED, self::STATUS_REJECTED, self::STATUS_CANCELLED],
        self::STATUS_ACCEPTED => [self::STATUS_PICKED_UP, self::STATUS_CANCELLED, self::STATUS_FAILED],
        self::STATUS_PICKED_UP => [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_FAILED],
        self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_DELIVERED, self::STATUS_FAILED],
        self::STATUS_DELIVERED => [],
        self::STATUS_REJECTED => [],
        self::STATUS_CANCELLED => [],
        self::STATUS_FAILED => [],
    ];

    // Return pickups only collect the product from the customer and bring it
    // back to the admin/warehouse hub. They never go out for delivery again,
    // so picked_up leads directly to delivered ("Delivered to Hub").
    public const PICKUP_TRANSITIONS = [
        self::STATUS_NOT_ASSIGNED => [self::STATUS_ASSIGNED],
        self::STATUS_ASSIGNED => [self::STATUS_ACCEPTED, self::STATUS_REJECTED, self::STATUS_CANCELLED],
        self::STATUS_ACCEPTED => [self::STATUS_PICKED_UP, self::STATUS_CANCELLED, self::STATUS_FAILED],
        self::STATUS_PICKED_UP => [self::STATUS_DELIVERED],
        self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_DELIVERED],
        self::STATUS_DELIVERED => [],
        self::STATUS_REJECTED => [],
        self::STATUS_CANCELLED => [],
        self::STATUS_FAILED => [],
    ];

    public const TIMESTAMP_FIELD_MAP = [
        self::STATUS_ASSIGNED => 'assigned_at',
        self::STATUS_ACCEPTED => 'accepted_at',
        self::STATUS_PICKED_UP => 'picked_up_at',
        self::STATUS_OUT_FOR_DELIVERY => 'out_for_delivery_at',
        self::STATUS_DELIVERED => 'delivered_at',
    ];

    public function getStatusOptions(): array
    {
        $options = [];
        foreach (self::STATUS_FLOW as $status) {
            $options[$status] = $this->formatStatusLabel($status);
        }
        return $options;
    }

    public function getAllStatusOptions(): array
    {
        $options = [];
        foreach (array_merge(self::STATUS_FLOW, self::TERMINAL_STATUSES) as $status) {
            $options[$status] = $this->formatStatusLabel($status);
        }
        return $options;
    }

    public function formatStatusLabel(string $status, bool $isReturnPickup = false): string
    {
        if ($status === self::STATUS_ASSIGNED) {
            return $isReturnPickup ? 'Pickup Assigned' : 'Delivery Assigned';
        }

        if ($status === self::STATUS_ACCEPTED) {
            return $isReturnPickup ? 'Pickup Accepted by Delivery Person' : 'Delivery Accepted by Delivery Person';
        }

        if ($status === self::STATUS_DELIVERED && $isReturnPickup) {
            return 'Delivered to Hub';
        }

        return ucwords(str_replace('_', ' ', $status));
    }

    public function getAllowedNextStatuses(?string $currentStatus, bool $isReturnPickup = false): array
    {
        if ($currentStatus === null) {
            return [];
        }

        $transitions = $isReturnPickup ? self::PICKUP_TRANSITIONS : self::STATUS_TRANSITIONS;

        return $transitions[$currentStatus] ?? [];
    }

    public function validateStatusTransition(?string $currentStatus, string $newStatus, bool $isReturnPickup = false): void
    {
        $allowed = $this->getAllowedNextStatuses($currentStatus, $isReturnPickup);

        if (!in_array($newStatus, $allowed, true)) {
            throw new InvalidArgumentException(
                'Cannot change delivery status from "' . ($currentStatus ?: 'none') . '" to "' . $newStatus . '".'
            );
        }
    }

    public function changeStatus(int $deliveryId, string $newStatus, int $changedById, ?string $changedByType = null): Delivery
    {
        return DB::transaction(function () use ($deliveryId, $newStatus, $changedById, $changedByType) {
            $delivery = Delivery::query()
                ->where('id', $deliveryId)
                ->lockForUpdate()
                ->first();

            if (!$delivery) {
                throw new \RuntimeException('Delivery not found.');
            }

            $oldStatus = $delivery->status;
            $isReturnPickup = (bool) $delivery->return_id;

            $this->validateStatusTransition($oldStatus, $newStatus, $isReturnPickup);

            $updateData = [
                'status' => $newStatus,
                'updated_by_id' => $changedById,
                'updated_date' => Carbon::now(),
            ];

            $timestampField = self::TIMESTAMP_FIELD_MAP[$newStatus] ?? null;
            if ($timestampField) {
                $updateData[$timestampField] = Carbon::now();
            }

            $delivery->update($updateData);

            $this->recordStatusChange($delivery->id, $oldStatus, $newStatus, $changedById, $changedByType);

            if ($newStatus === self::STATUS_DELIVERED) {
                $this->handleDeliveryCompleted($delivery, $changedById, $changedByType);
            }

            if (in_array($newStatus, self::TERMINAL_STATUSES, true) && $newStatus !== self::STATUS_DELIVERED) {
                $this->handleDeliveryTerminal($delivery, $changedById, $changedByType);
            }

            $delivery->unsetRelation('statusHistories');

            return $delivery;
        });
    }

    public function recordStatusChange(int $deliveryId, ?string $oldStatus, string $newStatus, ?int $changedById, ?string $changedByType = null): DeliveryStatusHistory
    {
        return DeliveryStatusHistory::create([
            'delivery_id' => $deliveryId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by_id' => $changedById,
            'changed_by_type' => $changedByType,
            'changed_at' => Carbon::now(),
            'created_by_id' => $changedById,
            'created_date' => Carbon::now(),
        ]);
    }

    private function handleDeliveryCompleted(Delivery $delivery, int $changedById, ?string $changedByType = null): void
    {
        if ($delivery->delivery_person_id) {
            $this->checkAndFreeDeliveryPerson($delivery->delivery_person_id);
        }

        if ($delivery->return_id) {
            $actorId = (int) ($delivery->delivery_person_id ?: $changedById);
            $actorType = $changedByType ?: 'delivery_person';

            app(ReturnService::class)->markProductReceivedForDelivery(
                (int) $delivery->return_id,
                $actorId,
                $actorType
            );

            return;
        }

        // When the delivery person marks the delivery as delivered, reflect it
        // on the order itself so downstream order logic (delivered_at, returns,
        // refunds eligibility, my-orders timeline) stays consistent. This is
        // driven automatically from the delivery flow, not by the admin manually
        // selecting the step on the order-process page.
        if ($delivery->order_id) {
            $order = Order::query()->find($delivery->order_id);

            if ($order) {
                try {
                    $this->orderStatusService->addSystemStatus(
                        $order,
                        OrderStatusService::STATUS_ORDER_DELIVERED,
                        $delivery->delivery_person_id ?: $changedById
                    );

                    if (empty($order->delivered_at)) {
                        Order::query()
                            ->where('id', $order->id)
                            ->update([
                                'delivered_at' => Carbon::now(),
                                'updated_by_id' => $delivery->delivery_person_id ?: $changedById,
                                'updated_date' => Carbon::now(),
                            ]);
                    }
                } catch (\Throwable $e) {
                    // Ignore if the order status cannot move to order_delivered
                    // (e.g. already delivered); the delivery record is already saved.
                }
            }
        }
    }

    private function handleDeliveryTerminal(Delivery $delivery, int $changedById, ?string $changedByType = null): void
    {
        if ($delivery->delivery_person_id) {
            $this->checkAndFreeDeliveryPerson($delivery->delivery_person_id);
        }

        if ($delivery->return_id) {
            $actorId = (int) ($delivery->delivery_person_id ?: $changedById);
            $actorType = $changedByType ?: 'delivery_person';

            app(ReturnService::class)->reopenReturnAfterPickup(
                (int) $delivery->return_id,
                $actorId,
                $actorType
            );
        }
    }

    private function checkAndFreeDeliveryPerson(int $deliveryPersonId): void
    {
        $hasActiveDelivery = Delivery::query()
            ->where('delivery_person_id', $deliveryPersonId)
            ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'out_for_delivery'])
            ->exists();

        if (!$hasActiveDelivery) {
            DeliveryPerson::query()
                ->where('id', $deliveryPersonId)
                ->update([
                    'availability_status' => 'available',
                    'updated_date' => Carbon::now(),
                ]);
        }
    }
}

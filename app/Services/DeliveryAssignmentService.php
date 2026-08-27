<?php

namespace App\Services;

use App\Models\Delivery;
use App\Models\DeliveryPerson;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeliveryAssignmentService
{
    protected DeliveryService $deliveryService;
    protected DeliveryStatusService $deliveryStatusService;
    protected OrderStatusService $orderStatusService;

    public function __construct(
        DeliveryService $deliveryService,
        DeliveryStatusService $deliveryStatusService,
        OrderStatusService $orderStatusService
    ) {
        $this->deliveryService = $deliveryService;
        $this->deliveryStatusService = $deliveryStatusService;
        $this->orderStatusService = $orderStatusService;
    }

    public function assignDeliveryPerson(int $orderId, int $deliveryPersonId, int $adminId): Delivery
    {
        return DB::transaction(function () use ($orderId, $deliveryPersonId, $adminId) {
            $order = Order::query()
                ->with(['statuses', 'payments'])
                ->where('id', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                throw new RuntimeException('Order not found.');
            }

            $this->validateOrderForAssignment($order);

            $deliveryPerson = DeliveryPerson::query()
                ->where('id', $deliveryPersonId)
                ->lockForUpdate()
                ->first();

            if (!$deliveryPerson) {
                throw new RuntimeException('Delivery person not found.');
            }

            $this->validateDeliveryPersonForAssignment($deliveryPerson);

            $delivery = Delivery::query()
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if (!$delivery) {
                $delivery = $this->deliveryService->createDeliveryForOrder($orderId, $adminId);
            }

            if ($delivery->status !== 'not_assigned') {
                throw new RuntimeException('This delivery is already assigned.');
            }

            if ($delivery->delivery_person_id && $delivery->delivery_person_id == $deliveryPersonId) {
                throw new RuntimeException('This delivery is already assigned to this delivery person.');
            }

            $existingActiveDelivery = Delivery::query()
                ->where('delivery_person_id', $deliveryPersonId)
                ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'out_for_delivery'])
                ->where('id', '!=', $delivery->id)
                ->exists();

            if ($existingActiveDelivery) {
                throw new RuntimeException('This delivery person already has an active delivery.');
            }

            $delivery->update([
                'delivery_person_id' => $deliveryPersonId,
                'status' => 'assigned',
                'assigned_at' => Carbon::now(),
                'updated_by_id' => $adminId,
                'updated_date' => Carbon::now(),
            ]);

            $this->deliveryStatusService->recordStatusChange(
                $delivery->id,
                null,
                'assigned',
                $adminId
            );

            $deliveryPerson->update([
                'availability_status' => 'busy',
                'updated_by_id' => $adminId,
                'updated_date' => Carbon::now(),
            ]);

            $order->updated_by_id = $adminId;
            $order->updated_date = Carbon::now();
            $order->save();

            $this->orderStatusService->addSystemStatus(
                $order,
                'assigned_for_delivery',
                $adminId
            );

            $delivery->unsetRelation('order');
            $delivery->unsetRelation('deliveryPerson');

            return $delivery->load(['order.customer', 'deliveryPerson']);
        });
    }

    public function unassignDeliveryPerson(int $deliveryId, int $adminId): void
    {
        DB::transaction(function () use ($deliveryId, $adminId) {
            $delivery = Delivery::query()
                ->with(['deliveryPerson'])
                ->where('id', $deliveryId)
                ->lockForUpdate()
                ->first();

            if (!$delivery) {
                throw new RuntimeException('Delivery not found.');
            }

            if ($delivery->status === 'not_assigned') {
                throw new RuntimeException('This delivery is already unassigned.');
            }

            if (in_array($delivery->status, ['picked_up', 'out_for_delivery', 'delivered'], true)) {
                throw new RuntimeException('Cannot unassign a delivery that is in progress or completed.');
            }

            $oldStatus = $delivery->status;
            $oldDeliveryPersonId = $delivery->delivery_person_id;

            $delivery->update([
                'delivery_person_id' => null,
                'status' => 'not_assigned',
                'assigned_at' => null,
                'updated_by_id' => $adminId,
                'updated_date' => Carbon::now(),
            ]);

            $this->deliveryStatusService->recordStatusChange(
                $delivery->id,
                $oldStatus,
                'not_assigned',
                $adminId
            );

            if ($oldDeliveryPersonId) {
                $this->checkAndFreeDeliveryPerson($oldDeliveryPersonId);
            }
        });
    }

    private function validateOrderForAssignment(Order $order): void
    {
        if (!$order->is_active) {
            throw new RuntimeException('Order is not active.');
        }

        $latestStatus = $this->orderStatusService->getLatestStatusForOrder($order);
        $currentStatus = $latestStatus ? $latestStatus->order_status : null;

        $allowedStatuses = ['ready_for_delivery', 'assigned_for_delivery'];

        if (!in_array($currentStatus, $allowedStatuses, true)) {
            throw new RuntimeException('Order is not ready for delivery assignment. Current status: ' . ($currentStatus ?: 'Not Started'));
        }
    }

    private function validateDeliveryPersonForAssignment(DeliveryPerson $deliveryPerson): void
    {
        if ($deliveryPerson->status !== 'active') {
            throw new RuntimeException('Cannot assign delivery to an inactive delivery person.');
        }

        if ($deliveryPerson->availability_status === 'busy') {
            throw new RuntimeException('This delivery person is currently busy with another delivery.');
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

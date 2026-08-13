<?php

namespace App\Services;

use App\Models\AdminLogin;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderStatusService
{
    public const STATUS_CANCELLATION_REQUESTED = 'cancellation_requested';
    public const STATUS_CANCELLED_BY_CUSTOMER = 'cancelled_by_customer';
    public const STATUS_DELIVERY_PERSON_ACCEPTS = 'delivery_person_accepts';
    public const STATUS_ORDER_DELIVERED = 'order_delivered';
    public const STATUS_RETURN_REQUESTED = 'return_requested';
    public const STATUS_RETURN_APPROVED = 'return_approved';
    public const STATUS_RETURN_REJECTED = 'return_rejected';
    public const STATUS_PICKUP_SCHEDULED = 'pickup_scheduled';
    public const STATUS_PRODUCT_RECEIVED = 'product_received';
    public const STATUS_INSPECTION_PASSED = 'inspection_passed';
    public const STATUS_INSPECTION_FAILED = 'inspection_failed';
    public const STATUS_REFUND_INITIATED = 'refund_initiated';
    public const STATUS_REFUND_COMPLETED = 'refund_completed';
    public const STATUS_RETURN_CLOSED = 'return_closed';

    public const STATUS_FLOW = [
        'order_accept',
        'order_under_packing',
        'ready_for_delivery',
        'assigned_for_delivery',
        self::STATUS_DELIVERY_PERSON_ACCEPTS,
        'reached_doorstep',
        self::STATUS_ORDER_DELIVERED,
    ];

    public const RETURN_STATUS_FLOW = [
        self::STATUS_RETURN_REQUESTED,
        self::STATUS_RETURN_APPROVED,
        self::STATUS_PICKUP_SCHEDULED,
        self::STATUS_PRODUCT_RECEIVED,
        self::STATUS_INSPECTION_PASSED,
        self::STATUS_REFUND_INITIATED,
        self::STATUS_REFUND_COMPLETED,
        self::STATUS_RETURN_CLOSED,
    ];

    public const RETURN_TERMINAL_STATUSES = [
        self::STATUS_RETURN_REJECTED,
        self::STATUS_INSPECTION_FAILED,
    ];

    public function getStatusFlow(): array
    {
        return self::STATUS_FLOW;
    }

    public function getStatusOptions(): array
    {
        $options = [];

        foreach (self::STATUS_FLOW as $status) {
            $options[$status] = $this->formatStatusLabel($status);
        }

        return $options;
    }

    public function formatStatusLabel(string $status): string
    {
        if ($status === self::STATUS_CANCELLATION_REQUESTED) {
            return 'Cancellation Requested';
        }

        if ($status === self::STATUS_CANCELLED_BY_CUSTOMER) {
            return 'Cancelled by Customer';
        }

        if (in_array($status, array_merge(self::RETURN_STATUS_FLOW, self::RETURN_TERMINAL_STATUSES), true)) {
            return ucwords(str_replace('_', ' ', $status));
        }

        return ucwords(str_replace('_', ' ', $status));
    }

    public function canCustomerCancel(?string $currentStatus): bool
    {
        if ($currentStatus === null) {
            return true;
        }

        if ($currentStatus === self::STATUS_CANCELLATION_REQUESTED) {
            return false;
        }

        if ($currentStatus === self::STATUS_CANCELLED_BY_CUSTOMER) {
            return false;
        }

        if (in_array($currentStatus, array_merge(self::RETURN_STATUS_FLOW, self::RETURN_TERMINAL_STATUSES), true)) {
            return false;
        }

        $currentIndex = array_search($currentStatus, self::STATUS_FLOW, true);
        $deliveryAcceptIndex = array_search(self::STATUS_DELIVERY_PERSON_ACCEPTS, self::STATUS_FLOW, true);

        return $currentIndex !== false && $currentIndex < $deliveryAcceptIndex;
    }

    public function getLatestStatusForOrder(Order $order): ?OrderStatus
    {
        if ($order->relationLoaded('statuses')) {
            return $order->statuses->sortBy([
                ['action_time', 'asc'],
                ['id', 'asc'],
            ])->last();
        }

        return $order->statuses()->orderByDesc('action_time')->orderByDesc('id')->first();
    }

    public function getNextAllowedStatuses(?string $currentStatus): array
    {
        if ($currentStatus === null) {
            return [self::STATUS_FLOW[0]];
        }

        if ($currentStatus === self::STATUS_CANCELLED_BY_CUSTOMER) {
            return [];
        }

        if ($currentStatus === self::STATUS_CANCELLATION_REQUESTED) {
            return [];
        }

        if (in_array($currentStatus, array_merge(self::RETURN_STATUS_FLOW, self::RETURN_TERMINAL_STATUSES), true)) {
            return [];
        }

        $currentIndex = array_search($currentStatus, self::STATUS_FLOW, true);
        if ($currentIndex === false) {
            return [];
        }

        if ($currentIndex === count(self::STATUS_FLOW) - 1) {
            return [];
        }

        return [self::STATUS_FLOW[$currentIndex + 1]];
    }

    public function validateNextStatus(?string $currentStatus, string $newStatus): void
    {
        if (!in_array($newStatus, self::STATUS_FLOW, true)) {
            throw new InvalidArgumentException('Invalid order status selected.');
        }

        $allowedStatuses = $this->getNextAllowedStatuses($currentStatus);

        if (!in_array($newStatus, $allowedStatuses, true)) {
            throw new InvalidArgumentException('You can only move the order to the next step in the process.');
        }
    }

    public function addStatus(Order $order, string $newStatus, ?int $actionDoneById): OrderStatus
    {
        $latestStatus = $this->getLatestStatusForOrder($order);
        $currentStatus = null;

        if ($latestStatus) {
            $currentStatus = $latestStatus->order_status;
        }

        $this->validateNextStatus($currentStatus, $newStatus);

        return DB::transaction(function () use ($order, $newStatus, $actionDoneById) {
            $status = OrderStatus::create([
                'order_id' => $order->id,
                'order_status' => $newStatus,
                'action_time' => Carbon::now(),
                'action_done_by_id' => $actionDoneById,
            ]);

            if ($newStatus === self::STATUS_ORDER_DELIVERED && empty($order->delivered_at)) {
                Order::query()
                    ->where('id', $order->id)
                    ->update([
                        'delivered_at' => $status->action_time,
                        'updated_by_id' => $actionDoneById,
                        'updated_date' => Carbon::now(),
                    ]);

                $order->delivered_at = $status->action_time;
            }

            $order->unsetRelation('statuses');

            return $status;
        });
    }

    public function addCustomerCancelledStatus(Order $order, int $customerId): OrderStatus
    {
        return DB::transaction(function () use ($order, $customerId) {
            $status = OrderStatus::create([
                'order_id' => $order->id,
                'order_status' => self::STATUS_CANCELLED_BY_CUSTOMER,
                'action_time' => Carbon::now(),
                'action_done_by_id' => $customerId,
            ]);

            $order->unsetRelation('statuses');

            return $status;
        });
    }

    public function addCancellationRequestedStatus(Order $order, int $customerId): OrderStatus
    {
        return DB::transaction(function () use ($order, $customerId) {
            $status = OrderStatus::create([
                'order_id' => $order->id,
                'order_status' => self::STATUS_CANCELLATION_REQUESTED,
                'action_time' => Carbon::now(),
                'action_done_by_id' => $customerId,
            ]);

            $order->unsetRelation('statuses');

            return $status;
        });
    }

    public function addSystemStatus(Order $order, string $status, ?int $actionDoneById): OrderStatus
    {
        return DB::transaction(function () use ($order, $status, $actionDoneById) {
            $orderStatus = OrderStatus::create([
                'order_id' => $order->id,
                'order_status' => $status,
                'action_time' => Carbon::now(),
                'action_done_by_id' => $actionDoneById,
            ]);

            $order->unsetRelation('statuses');

            return $orderStatus;
        });
    }

    public function buildTimeline(Collection $statuses, ?Order $order = null): array
    {
        $historyByStatus = $statuses->keyBy('order_status');
        $latestStatusRecord = $statuses->sortBy([
            ['action_time', 'asc'],
            ['id', 'asc'],
        ])->last();

        $latestStatus = null;
        if ($latestStatusRecord) {
            $latestStatus = $latestStatusRecord->order_status;
        }

        $latestIndex = $latestStatus !== null ? array_search($latestStatus, self::STATUS_FLOW, true) : false;
        $actorNames = $this->resolveActorNames($statuses, $order);

        $timeline = [];

        foreach (self::STATUS_FLOW as $index => $status) {
            $history = $historyByStatus->get($status);
            $actorId = null;
            $actionTime = null;

            if ($history) {
                $actorId = $history->action_done_by_id;
                $actionTime = $history->action_time;
            }

            $timeline[] = [
                'key' => $status,
                'label' => $this->formatStatusLabel($status),
                'is_completed' => $history !== null,
                'is_current' => $latestStatus === $status,
                'is_pending' => $history === null,
                'is_reachable' => $latestIndex === false
                    ? $index === 0
                    : $index <= ((int) $latestIndex + 1),
                'action_time' => $actionTime,
                'action_done_by_id' => $actorId,
                'actor_name' => $actorId !== null ? ($actorNames[$actorId] ?? ('User ' . $actorId)) : null,
            ];
        }

        $requestedHistory = $historyByStatus->get(self::STATUS_CANCELLATION_REQUESTED);

        if ($requestedHistory) {
            $actorId = $requestedHistory->action_done_by_id;

            $timeline[] = [
                'key' => self::STATUS_CANCELLATION_REQUESTED,
                'label' => $this->formatStatusLabel(self::STATUS_CANCELLATION_REQUESTED),
                'is_completed' => true,
                'is_current' => $latestStatus === self::STATUS_CANCELLATION_REQUESTED,
                'is_pending' => false,
                'is_reachable' => true,
                'action_time' => $requestedHistory->action_time,
                'action_done_by_id' => $actorId,
                'actor_name' => $actorId !== null ? ($actorNames[$actorId] ?? ('User ' . $actorId)) : null,
            ];
        }

        $cancelledHistory = $historyByStatus->get(self::STATUS_CANCELLED_BY_CUSTOMER);

        if ($cancelledHistory) {
            $actorId = $cancelledHistory->action_done_by_id;

            $timeline[] = [
                'key' => self::STATUS_CANCELLED_BY_CUSTOMER,
                'label' => $this->formatStatusLabel(self::STATUS_CANCELLED_BY_CUSTOMER),
                'is_completed' => true,
                'is_current' => $latestStatus === self::STATUS_CANCELLED_BY_CUSTOMER,
                'is_pending' => false,
                'is_reachable' => true,
                'action_time' => $cancelledHistory->action_time,
                'action_done_by_id' => $actorId,
                'actor_name' => $actorId !== null ? ($actorNames[$actorId] ?? ('User ' . $actorId)) : null,
            ];
        }

        foreach (array_merge(self::RETURN_STATUS_FLOW, self::RETURN_TERMINAL_STATUSES) as $returnStatus) {
            $history = $historyByStatus->get($returnStatus);

            if (!$history) {
                continue;
            }

            $actorId = $history->action_done_by_id;

            $timeline[] = [
                'key' => $returnStatus,
                'label' => $this->formatStatusLabel($returnStatus),
                'is_completed' => true,
                'is_current' => $latestStatus === $returnStatus,
                'is_pending' => false,
                'is_reachable' => true,
                'action_time' => $history->action_time,
                'action_done_by_id' => $actorId,
                'actor_name' => $actorId !== null ? ($actorNames[$actorId] ?? ('User ' . $actorId)) : null,
            ];
        }

        return $timeline;
    }

    private function resolveActorNames(Collection $statuses, ?Order $order = null): array
    {
        $actorIds = $statuses
            ->pluck('action_done_by_id')
            ->filter(function ($id) {
                return $id !== null;
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();

        if ($actorIds->isEmpty()) {
            return [];
        }

        $adminNames = AdminLogin::query()
            ->whereIn('id', $actorIds->all())
            ->pluck('admin_username', 'id');

        $customerNames = Customer::query()
            ->whereIn('id', $actorIds->all())
            ->pluck('name', 'id');

        $resolved = [];

        foreach ($actorIds as $actorId) {
            if ($adminNames->has($actorId)) {
                $resolved[$actorId] = (string) $adminNames->get($actorId);
                continue;
            }

            if ($order && (int) $order->customer_id === (int) $actorId && $customerNames->has($actorId)) {
                $resolved[$actorId] = (string) $customerNames->get($actorId);
                continue;
            }

            if ($customerNames->has($actorId)) {
                $resolved[$actorId] = (string) $customerNames->get($actorId);
            }
        }

        return $resolved;
    }
}

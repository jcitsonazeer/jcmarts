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
    public const STATUS_FLOW = [
        'order_accept',
        'order_under_packing',
        'ready_for_delivery',
        'assigned_for_delivery',
        'delivery_person_accepts',
        'reached_doorstep',
        'order_delivered',
    ];

    public function getStatusFlow(): array
    {
        return self::STATUS_FLOW;
    }

    public function getStatusOptions(): array
    {
        return collect(self::STATUS_FLOW)
            ->mapWithKeys(fn (string $status) => [$status => $this->formatStatusLabel($status)])
            ->all();
    }

    public function formatStatusLabel(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
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

        $currentIndex = array_search($currentStatus, self::STATUS_FLOW, true);
        if ($currentIndex === false) {
            return [self::STATUS_FLOW[0]];
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
        $currentStatus = $this->getLatestStatusForOrder($order)?->order_status;
        $this->validateNextStatus($currentStatus, $newStatus);

        return DB::transaction(function () use ($order, $newStatus, $actionDoneById) {
            $status = OrderStatus::create([
                'order_id' => $order->id,
                'order_status' => $newStatus,
                'action_time' => Carbon::now(),
                'action_done_by_id' => $actionDoneById,
            ]);

            $order->unsetRelation('statuses');

            return $status;
        });
    }

    public function buildTimeline(Collection $statuses, ?Order $order = null): array
    {
        $historyByStatus = $statuses->keyBy('order_status');
        $latestStatus = $statuses->sortBy([
            ['action_time', 'asc'],
            ['id', 'asc'],
        ])->last()?->order_status;
        $latestIndex = $latestStatus !== null ? array_search($latestStatus, self::STATUS_FLOW, true) : false;
        $actorNames = $this->resolveActorNames($statuses, $order);

        $timeline = [];

        foreach (self::STATUS_FLOW as $index => $status) {
            $history = $historyByStatus->get($status);
            $actorId = $history?->action_done_by_id;
            $timeline[] = [
                'key' => $status,
                'label' => $this->formatStatusLabel($status),
                'is_completed' => $history !== null,
                'is_current' => $latestStatus === $status,
                'is_pending' => $history === null,
                'is_reachable' => $latestIndex === false
                    ? $index === 0
                    : $index <= ((int) $latestIndex + 1),
                'action_time' => $history?->action_time,
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
            ->filter(fn ($id) => $id !== null)
            ->map(fn ($id) => (int) $id)
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

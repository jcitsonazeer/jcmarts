<?php

namespace App\Services;

use App\Models\CustomerAddress;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use RuntimeException;

class DeliveryService
{
    protected OrderStatusService $orderStatusService;

    public function __construct(OrderStatusService $orderStatusService)
    {
        $this->orderStatusService = $orderStatusService;
    }

    public function createDeliveryForOrder(int $orderId, ?int $adminId = null): Delivery
    {
        $order = Order::query()
            ->with(['statuses', 'payments', 'address'])
            ->where('id', $orderId)
            ->first();

        if (!$order) {
            throw new RuntimeException('Order not found.');
        }

        $this->validateOrderEligibleForDelivery($order);

        $existingDelivery = Delivery::where('order_id', $order->id)->first();

        if ($existingDelivery) {
            throw new RuntimeException('A delivery already exists for this order.');
        }

        $latestStatus = $this->orderStatusService->getLatestStatusForOrder($order);
        $currentOrderStatus = $latestStatus ? $latestStatus->order_status : null;

        if ($currentOrderStatus !== 'ready_for_delivery' && $currentOrderStatus !== 'assigned_for_delivery') {
            throw new RuntimeException('Order is not ready for delivery. Current status: ' . ($currentOrderStatus ?: 'Not Started'));
        }

        $deliveryAddress = $this->buildDeliveryAddress($order);

        $delivery = Delivery::create([
            'order_id' => $order->id,
            'status' => 'not_assigned',
            'delivery_address' => $deliveryAddress,
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);

        return $delivery;
    }

    public function getDeliveryById(int $deliveryId): ?Delivery
    {
        return Delivery::query()
            ->with([
                'order.customer',
                'order.address',
                'order.items.product',
                'order.items.rate.uom',
                'order.statuses',
                'order.payments',
                'deliveryPerson',
                'statusHistories',
                'locations',
                'createdBy',
                'updatedBy',
            ])
            ->where('id', $deliveryId)
            ->first();
    }

    public function getDeliveryByOrder(int $orderId): ?Delivery
    {
        return Delivery::query()
            ->with([
                'order.customer',
                'order.address',
                'deliveryPerson',
                'statusHistories',
            ])
            ->where('order_id', $orderId)
            ->first();
    }

    public function getDeliveryStatus(int $deliveryId): ?string
    {
        $delivery = Delivery::query()->where('id', $deliveryId)->first();

        return $delivery ? $delivery->status : null;
    }

    public function getDeliveryHistory(int $deliveryId): Collection
    {
        return DeliveryStatusHistory::query()
            ->where('delivery_id', $deliveryId)
            ->orderBy('changed_at')
            ->orderBy('id')
            ->get();
    }

    public function getActiveDeliveries(): LengthAwarePaginator
    {
        return Delivery::query()
            ->with(['order.customer', 'deliveryPerson'])
            ->whereIn('status', ['assigned', 'accepted', 'picked_up', 'out_for_delivery'])
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function getCompletedDeliveries(): LengthAwarePaginator
    {
        return Delivery::query()
            ->with(['order.customer', 'deliveryPerson'])
            ->where('status', 'delivered')
            ->orderByDesc('delivered_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function getUnassignedDeliveries(): LengthAwarePaginator
    {
        $orders = $this->getOrdersReadyForDelivery();

        $perPage = 20;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $total = $orders->count();
        $items = $orders->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => request()->query()]
        );
    }

    public function getAllDeliveries(?string $search = null): LengthAwarePaginator
    {
        $query = Delivery::query()
            ->with(['order.customer', 'deliveryPerson'])
            ->orderByDesc('id');

        if ($search && $search !== '') {
            $query->whereHas('order', function ($orderQuery) use ($search) {
                $orderQuery->where('id', 'like', '%' . $search . '%');
            })->orWhereHas('deliveryPerson', function ($dpQuery) use ($search) {
                $dpQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('mobile', 'like', '%' . $search . '%');
            });
        }

        return $query->paginate(20)->withQueryString();
    }

    public function getDeliveryStats(): array
    {
        $allStatuses = ['not_assigned', 'assigned', 'accepted', 'picked_up', 'out_for_delivery', 'delivered', 'rejected', 'cancelled', 'failed'];

        $counts = Delivery::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $stats = [];
        foreach ($allStatuses as $status) {
            $stats[$status] = $counts[$status] ?? 0;
        }

        $dpStats = [
            'available' => \App\Models\DeliveryPerson::query()->where('status', 'active')->where('availability_status', 'available')->count(),
            'busy' => \App\Models\DeliveryPerson::query()->where('status', 'active')->where('availability_status', 'busy')->count(),
            'offline' => \App\Models\DeliveryPerson::query()->where('status', 'active')->where('availability_status', 'offline')->count(),
            'inactive' => \App\Models\DeliveryPerson::query()->where('status', 'inactive')->count(),
        ];

        return [
            'deliveries' => $stats,
            'delivery_persons' => $dpStats,
        ];
    }

    public function getOrdersReadyForDelivery(): Collection
    {
        $orderIds = OrderStatus::query()
            ->selectRaw('order_id, MAX(id) as max_id')
            ->groupBy('order_id')
            ->havingRaw('MAX(id) = (SELECT MAX(id) FROM order_status WHERE order_id = order_status.order_id)')
            ->pluck('order_id');

        return Order::query()
            ->with(['customer', 'address', 'statuses', 'payments', 'items.product'])
            ->whereIn('id', $orderIds)
            ->whereDoesntHave('delivery')
            ->where('is_active', 1)
            ->orderByDesc('created_date')
            ->orderByDesc('id')
            ->get()
            ->filter(function (Order $order) {
                $latestStatus = $this->orderStatusService->getLatestStatusForOrder($order);
                $currentStatus = $latestStatus ? $latestStatus->order_status : null;
                return $currentStatus === 'ready_for_delivery' || $currentStatus === 'assigned_for_delivery';
            })
            ->values();
    }

    public function getAllDeliveryPersons(?string $search = null)
    {
        $query = \App\Models\DeliveryPerson::query()
            ->with(['createdBy', 'updatedBy'])
            ->withCount(['deliveries' => function ($q) {
                $q->whereIn('status', ['assigned', 'accepted', 'picked_up', 'out_for_delivery']);
            }]);

        $term = trim((string) $search);

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                    ->orWhere('mobile', 'like', '%' . $term . '%')
                    ->orWhere('email', 'like', '%' . $term . '%')
                    ->orWhere('vehicle_number', 'like', '%' . $term . '%');
            });
        }

        return $query->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();
    }

    public function findDeliveryPersonForShow(int $id)
    {
        return \App\Models\DeliveryPerson::query()
            ->with(['createdBy', 'updatedBy'])
            ->withCount(['deliveries' => function ($q) {
                $q->whereIn('status', ['assigned', 'accepted', 'picked_up', 'out_for_delivery']);
            }])
            ->withCount(['deliveries as completed_deliveries_count' => function ($q) {
                $q->where('status', 'delivered');
            }])
            ->findOrFail($id);
    }

    public function findDeliveryPersonForEdit(int $id)
    {
        return \App\Models\DeliveryPerson::query()
            ->with(['createdBy', 'updatedBy'])
            ->findOrFail($id);
    }

    public function createDeliveryPerson(array $data, int $adminId)
    {
        return \App\Models\DeliveryPerson::create([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? null,
            'password' => bcrypt($data['password']),
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'vehicle_number' => $data['vehicle_number'] ?? null,
            'status' => $data['status'] ?? 'active',
            'availability_status' => $data['availability_status'] ?? 'offline',
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function updateDeliveryPerson(int $id, array $data, int $adminId)
    {
        $deliveryPerson = \App\Models\DeliveryPerson::findOrFail($id);

        $updateData = [
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? null,
            'vehicle_type' => $data['vehicle_type'] ?? null,
            'vehicle_number' => $data['vehicle_number'] ?? null,
            'status' => $data['status'] ?? $deliveryPerson->status,
            'availability_status' => $data['availability_status'] ?? $deliveryPerson->availability_status,
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = bcrypt($data['password']);
        }

        $deliveryPerson->update($updateData);

        return $deliveryPerson;
    }

    public function getAvailableDeliveryPersons()
    {
        return \App\Models\DeliveryPerson::query()
            ->where('status', 'active')
            ->where('availability_status', 'available')
            ->orderBy('name')
            ->get();
    }

    public function getDeliveryHistoryFiltered(?string $search = null, ?string $status = null, ?int $deliveryPersonId = null, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $query = Delivery::query()
            ->with(['order.customer', 'deliveryPerson'])
            ->where('status', 'delivered');

        if ($search && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('order', function ($oq) use ($search) {
                    $oq->where('id', 'like', '%' . $search . '%');
                })->orWhereHas('deliveryPerson', function ($dpq) use ($search) {
                    $dpq->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%');
                });
            });
        }

        if ($status && $status !== '') {
            $query->where('status', $status);
        }

        if ($deliveryPersonId) {
            $query->where('delivery_person_id', $deliveryPersonId);
        }

        if ($dateFrom) {
            $query->where('delivered_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('delivered_at', '<=', $dateTo . ' 23:59:59');
        }

        return $query->orderByDesc('delivered_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    private function validateOrderEligibleForDelivery(Order $order): void
    {
        if (!$order->is_active) {
            throw new RuntimeException('Order is not active.');
        }

        $hasPaidPayment = $order->payments()
            ->whereIn('status', ['paid', 'refund_requested', 'refund_pending', 'refunded'])
            ->exists();

        if (!$hasPaidPayment) {
            throw new RuntimeException('Order does not have a successful payment.');
        }
    }

    private function buildDeliveryAddress(Order $order): ?string
    {
        if (!$order->address) {
            return null;
        }

        $address = $order->address;
        $parts = array_filter([
            $address->address_line_1 ?? null,
            $address->address_line_2 ?? null,
            $address->city ?? null,
            $address->state ?? null,
            $address->pincode ?? null,
        ]);

        return implode(', ', $parts) ?: null;
    }
}

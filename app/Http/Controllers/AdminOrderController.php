<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdminOrderService;
use App\Services\OrderCancellationService;
use RuntimeException;

class AdminOrderController extends Controller
{
    protected AdminOrderService $adminOrderService;
    protected OrderCancellationService $orderCancellationService;

    public function __construct(AdminOrderService $adminOrderService, OrderCancellationService $orderCancellationService)
    {
        $this->adminOrderService = $adminOrderService;
        $this->orderCancellationService = $orderCancellationService;
    }

    public function index()
    {
        $orders = $this->adminOrderService->getAllOrders();

        return view('admin.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = $this->adminOrderService->getOrderById((int) $id);

        if (!$order) {
            return redirect()
                ->route('admin.orders.index')
                ->with('error', 'Order not found.');
        }

        return view('admin.orders.show', compact('order'));
    }

    public function pendingReservations()
    {
        $this->adminOrderService->cleanupExpiredPendingOrders();
        $orders = $this->adminOrderService->getReleasedReservationHistory();

        return view('admin.orders.pending_reservations', compact('orders'));
    }

    public function pendingReservationsTable()
    {
        $this->adminOrderService->cleanupExpiredPendingOrders();
        $orders = $this->adminOrderService->getReleasedReservationHistory();

        return view('admin.orders.partials.pending_reservations_table', compact('orders'));
    }

    public function releasePendingReservation(Request $request, int $orderId)
    {
        $adminId = (int) $request->session()->get('admin_id');

        $this->adminOrderService->releaseExpiredPendingOrder($orderId, $adminId);

        return redirect()
            ->route('admin.orders.pending-reservations')
            ->with('success', 'Expired pending order released successfully.');
    }

    public function approveCancellation(Request $request, int $orderId)
    {
        $adminId = (int) $request->session()->get('admin_id');

        try {
            $this->orderCancellationService->approveByAdmin($orderId, $adminId);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('admin.orders.show', $orderId)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.orders.show', $orderId)
            ->with('success', 'Cancellation approved and Razorpay refund started.');
    }
}

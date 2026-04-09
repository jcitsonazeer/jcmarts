<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdminOrderService;

class AdminOrderController extends Controller
{
    protected AdminOrderService $adminOrderService;

    public function __construct(AdminOrderService $adminOrderService)
    {
        $this->adminOrderService = $adminOrderService;
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
}

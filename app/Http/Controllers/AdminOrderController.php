<?php

namespace App\Http\Controllers;

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
}

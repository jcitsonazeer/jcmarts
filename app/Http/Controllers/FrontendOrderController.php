<?php

namespace App\Http\Controllers;

use App\Services\CustomerAuthService;
use App\Services\FrontendCatalogService;
use App\Services\FrontendOrderService;
use Illuminate\Http\Request;

class FrontendOrderController extends Controller
{
    protected FrontendCatalogService $frontendCatalogService;
    protected CustomerAuthService $customerAuthService;
    protected FrontendOrderService $frontendOrderService;

    public function __construct(
        FrontendCatalogService $frontendCatalogService,
        CustomerAuthService $customerAuthService,
        FrontendOrderService $frontendOrderService
    ) {
        $this->frontendCatalogService = $frontendCatalogService;
        $this->customerAuthService = $customerAuthService;
        $this->frontendOrderService = $frontendOrderService;
    }

    public function index(Request $request)
    {
        if (!$this->customerAuthService->isCustomerLoggedIn()) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Please login to view your orders.');
        }

        $customerId = (int) session('customer_id');
        $menuCategories = $this->frontendCatalogService->getMenuCategories();
        $search = (string) $request->query('q', '');
        $orders = $this->frontendOrderService->getOrdersForCustomer($customerId, $search);

        $selectedOrderId = (int) $request->query('order_id', 0);
        if ($selectedOrderId === 0 && $orders->isNotEmpty()) {
            $selectedOrderId = (int) $orders->first()->id;
        }

        $selectedOrder = null;
        if ($selectedOrderId > 0) {
            $selectedOrder = $this->frontendOrderService->getOrderForCustomer($selectedOrderId, $customerId);
        }

        return view('frontend.orders.index', compact('menuCategories', 'orders', 'selectedOrder', 'search', 'selectedOrderId'));
    }

    public function show($orderId)
    {
        return redirect()->route('frontend.orders.index', [
            'order_id' => (int) $orderId,
        ]);
    }
}

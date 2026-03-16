<?php

namespace App\Http\Controllers;

use App\Services\CustomerAuthService;
use App\Services\FrontendCatalogService;
use App\Services\FrontendOrderService;

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

    public function index()
    {
        if (!$this->customerAuthService->isCustomerLoggedIn()) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Please login to view your orders.');
        }

        $customerId = (int) session('customer_id');
        $menuCategories = $this->frontendCatalogService->getMenuCategories();
        $orders = $this->frontendOrderService->getOrdersForCustomer($customerId);

        return view('frontend.orders.index', compact('menuCategories', 'orders'));
    }

    public function show($orderId)
    {
        if (!$this->customerAuthService->isCustomerLoggedIn()) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Please login to view your orders.');
        }

        $customerId = (int) session('customer_id');
        $menuCategories = $this->frontendCatalogService->getMenuCategories();
        $order = $this->frontendOrderService->getOrderForCustomer((int) $orderId, $customerId);

        if (!$order) {
            return redirect()
                ->route('frontend.orders.index')
                ->with('error', 'Order not found.');
        }

        return view('frontend.orders.show', compact('menuCategories', 'order'));
    }
}

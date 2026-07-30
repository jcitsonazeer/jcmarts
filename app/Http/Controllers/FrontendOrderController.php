<?php

namespace App\Http\Controllers;

use App\Services\CustomerAuthService;
use App\Services\FrontendCatalogService;
use App\Services\FrontendOrderService;
use App\Services\OrderCancellationService;
use Illuminate\Http\Request;
use RuntimeException;

class FrontendOrderController extends Controller
{
    protected FrontendCatalogService $frontendCatalogService;
    protected CustomerAuthService $customerAuthService;
    protected FrontendOrderService $frontendOrderService;
    protected OrderCancellationService $orderCancellationService;

    public function __construct(
        FrontendCatalogService $frontendCatalogService,
        CustomerAuthService $customerAuthService,
        FrontendOrderService $frontendOrderService,
        OrderCancellationService $orderCancellationService
    ) {
        $this->frontendCatalogService = $frontendCatalogService;
        $this->customerAuthService = $customerAuthService;
        $this->frontendOrderService = $frontendOrderService;
        $this->orderCancellationService = $orderCancellationService;
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

    public function cancel(int $orderId)
    {
        if (!$this->customerAuthService->isCustomerLoggedIn()) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Please login to cancel your order.');
        }

        try {
            $this->orderCancellationService->cancelByCustomer(
                $orderId,
                (int) session('customer_id')
            );
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('frontend.orders.index', ['order_id' => $orderId])
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('frontend.orders.index', ['order_id' => $orderId])
            ->with('success', 'Order cancelled successfully.');
    }
}

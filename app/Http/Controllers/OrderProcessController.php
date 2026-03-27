<?php

namespace App\Http\Controllers;

use App\Services\AdminOrderService;
use App\Services\CustomerAuthService;
use App\Services\FrontendCatalogService;
use App\Services\FrontendOrderService;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class OrderProcessController extends Controller
{
    protected AdminOrderService $adminOrderService;
    protected FrontendOrderService $frontendOrderService;
    protected FrontendCatalogService $frontendCatalogService;
    protected CustomerAuthService $customerAuthService;
    protected OrderStatusService $orderStatusService;

    public function __construct(
        AdminOrderService $adminOrderService,
        FrontendOrderService $frontendOrderService,
        FrontendCatalogService $frontendCatalogService,
        CustomerAuthService $customerAuthService,
        OrderStatusService $orderStatusService
    ) {
        $this->adminOrderService = $adminOrderService;
        $this->frontendOrderService = $frontendOrderService;
        $this->frontendCatalogService = $frontendCatalogService;
        $this->customerAuthService = $customerAuthService;
        $this->orderStatusService = $orderStatusService;
    }

    public function adminShow(int $orderId): View|RedirectResponse
    {
        $order = $this->adminOrderService->getOrderById($orderId);

        if (!$order) {
            return redirect()
                ->route('admin.orders.index')
                ->with('error', 'Order not found.');
        }

        return view('admin.orders.process', $this->buildProcessViewData($order));
    }

    public function adminUpdate(Request $request, int $orderId): RedirectResponse
    {
        $order = $this->adminOrderService->getOrderById($orderId);

        if (!$order) {
            return redirect()
                ->route('admin.orders.index')
                ->with('error', 'Order not found.');
        }

        $validated = $request->validate([
            'order_status' => ['required', 'string'],
        ]);

        try {
            $this->orderStatusService->addStatus(
                $order,
                $validated['order_status'],
                session('admin_id') ? (int) session('admin_id') : null
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('admin.orders.process.show', $orderId)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.orders.process.show', $orderId)
            ->with('success', 'Order status updated successfully.');
    }

    public function frontendShow(int $orderId): View|RedirectResponse
    {
        if (!$this->customerAuthService->isCustomerLoggedIn()) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Please login to process your order.');
        }

        $customerId = (int) session('customer_id');
        $order = $this->frontendOrderService->getOrderForCustomer($orderId, $customerId);

        if (!$order) {
            return redirect()
                ->route('frontend.orders.index')
                ->with('error', 'Order not found.');
        }

        $menuCategories = $this->frontendCatalogService->getMenuCategories();

        return view('frontend.orders.process', array_merge(
            ['menuCategories' => $menuCategories],
            $this->buildProcessViewData($order)
        ));
    }

    public function frontendUpdate(Request $request, int $orderId): RedirectResponse
    {
        if (!$this->customerAuthService->isCustomerLoggedIn()) {
            return redirect()
                ->route('frontend.login')
                ->with('error', 'Please login to process your order.');
        }

        $customerId = (int) session('customer_id');
        $order = $this->frontendOrderService->getOrderForCustomer($orderId, $customerId);

        if (!$order) {
            return redirect()
                ->route('frontend.orders.index')
                ->with('error', 'Order not found.');
        }

        $validated = $request->validate([
            'order_status' => ['required', 'string'],
        ]);

        try {
            $this->orderStatusService->addStatus(
                $order,
                $validated['order_status'],
                $customerId
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('frontend.orders.process.show', $orderId)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('frontend.orders.process.show', $orderId)
            ->with('success', 'Order status updated successfully.');
    }

    private function buildProcessViewData($order): array
    {
        $currentStatus = $order->current_order_status;

        return [
            'order' => $order,
            'currentStatus' => $currentStatus,
            'currentStatusLabel' => $currentStatus
                ? $this->orderStatusService->formatStatusLabel($currentStatus)
                : 'Not Started',
            'nextAllowedStatuses' => $this->orderStatusService->getNextAllowedStatuses($currentStatus),
            'statusOptions' => $this->orderStatusService->getStatusOptions(),
            'statusFlow' => $this->orderStatusService->getStatusFlow(),
            'timeline' => $order->order_status_timeline ?? [],
        ];
    }
}

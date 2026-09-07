<?php

namespace App\Http\Controllers;

use App\Models\DeliveryCharge;
use App\Services\DeliveryChargeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDeliveryChargeController extends Controller
{
    protected DeliveryChargeService $deliveryChargeService;

    public function __construct(DeliveryChargeService $deliveryChargeService)
    {
        $this->deliveryChargeService = $deliveryChargeService;
    }

    public function index(): View
    {
        $settings = $this->deliveryChargeService->getActiveSettings();

        return view('admin.delivery_charge.index', compact('settings'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_charge' => ['required', 'numeric', 'min:0'],
            'free_delivery_above' => ['required', 'numeric', 'min:0'],
            'minimum_order_value' => ['required', 'numeric', 'min:0'],
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $settings = $this->deliveryChargeService->getActiveSettings();

        if ($settings) {
            $settings->delivery_charge = $validated['delivery_charge'];
            $settings->free_delivery_above = $validated['free_delivery_above'];
            $settings->minimum_order_value = $validated['minimum_order_value'];
            $settings->updated_by_id = $adminId;
            $settings->updated_date = Carbon::now();
            $settings->save();
        } else {
            DeliveryCharge::create([
                'delivery_charge' => $validated['delivery_charge'],
                'free_delivery_above' => $validated['free_delivery_above'],
                'minimum_order_value' => $validated['minimum_order_value'],
                'is_active' => 1,
                'created_by_id' => $adminId,
                'created_date' => Carbon::now(),
            ]);
        }

        return redirect()
            ->route('admin.delivery-charges.index')
            ->with('success', 'Delivery charge settings saved successfully.');
    }
}
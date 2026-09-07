<?php

namespace App\Services;

use App\Models\DeliveryCharge;

class DeliveryChargeService
{
    /**
     * Get the active delivery charge settings configured by the admin.
     * If no settings exist yet, return null so the site works as before.
     */
    public function getActiveSettings(): ?DeliveryCharge
    {
        return DeliveryCharge::query()
            ->where('is_active', 1)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Check whether the cart total is high enough for a delivery order.
     * If no settings exist, delivery is always allowed (existing behaviour).
     */
    public function isDeliveryAllowed(float $subTotal): bool
    {
        $settings = $this->getActiveSettings();
        if (!$settings) {
            return true;
        }

        $minimumOrderValue = (float) $settings->minimum_order_value;

        if ($minimumOrderValue <= 0) {
            return true;
        }

        return $subTotal >= $minimumOrderValue;
    }

    /**
     * Calculate the delivery charge for the given cart sub total.
     *
     * Rules (only when admin has saved settings):
     * - Orders below the minimum order value are not accepted for delivery.
     * - Orders at or above the free delivery amount get free delivery.
     * - All other orders pay the configured flat delivery charge.
     */
    public function calculateDeliveryCharge(float $subTotal): float
    {
        $settings = $this->getActiveSettings();
        if (!$settings) {
            return 0.0;
        }

        $minimumOrderValue = (float) $settings->minimum_order_value;
        $freeDeliveryAbove = (float) $settings->free_delivery_above;
        $deliveryCharge = (float) $settings->delivery_charge;

        if ($minimumOrderValue > 0 && $subTotal < $minimumOrderValue) {
            return 0.0;
        }

        if ($freeDeliveryAbove > 0 && $subTotal >= $freeDeliveryAbove) {
            return 0.0;
        }

        return $deliveryCharge;
    }
}
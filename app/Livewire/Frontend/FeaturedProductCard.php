<?php

namespace App\Livewire\Frontend;

use App\Models\Product;
use App\Services\CartService;
use Livewire\Component;

class FeaturedProductCard extends Component
{
    public Product $product;

    public $rates = [];               // All rate options
    public $selectedRateId = null;    // Selected dropdown value
    public int $quantity = 1;
    public bool $isSoldOut = false;

    public function mount(Product $product)
    {
        $this->product = $product;

        // Convert product rates into simple array
        foreach ($product->rates as $rate) {

            $label = trim(
                ($rate->uom->secondary_uom ?? '') . ' ' .
                ($rate->uom->primary_uom ?? '')
            );

            $this->rates[] = [
                'id' => $rate->id,
                'label' => $label != '' ? $label : 'Option ' . $rate->id,
                'selling_price' => $rate->selling_price ?? 0,
                'final_price' => $rate->final_price ?? 0,
                'offer_percentage' => $rate->offer_percentage ?? 0,
                'soldout_status' => $rate->soldout_status ?? 'NO',
            ];
        }

        // Select first rate by default
        if (!empty($this->rates)) {
            $this->selectedRateId = $this->rates[0]['id'];
        }

        $this->syncSoldOut();
    }

    /*
    |--------------------------------------------------------------------------
    | Selected Rate (Simple Version)
    |--------------------------------------------------------------------------
    | This runs automatically when you use:
    | $this->selectedRate in blade
    */
    public function getSelectedRateProperty()
    {
        foreach ($this->rates as $rate) {
            if ($rate['id'] == $this->selectedRateId) {
                return $rate;
            }
        }

        return null;
    }

    public function updatedSelectedRateId()
    {
        $this->syncSoldOut();
    }

    private function syncSoldOut(): void
    {
        $rate = $this->selectedRate;
        $this->isSoldOut = $rate
            ? strtoupper((string) ($rate['soldout_status'] ?? 'NO')) === 'YES'
            : false;
    }

    /*
    |--------------------------------------------------------------------------
    | Final Price To Show
    |--------------------------------------------------------------------------
    */
    public function getShownPriceProperty()
    {
        if (!$this->selectedRate) {
            return 0;
        }

        return $this->selectedRate['final_price'] > 0
            ? $this->selectedRate['final_price']
            : $this->selectedRate['selling_price'];
    }

    public function addToCart(CartService $cartService): void
    {
        if (empty($this->selectedRateId)) {
            return;
        }

        if ($this->isSoldOut) {
            return;
        }

        $cartService->addItem(
            (int) $this->product->id,
            (int) $this->selectedRateId,
            max(1, (int) $this->quantity),
            null
        );

        $this->quantity = 1;
        $this->dispatch('cart-updated');
        $this->dispatch('cart-item-added');
    }

    public function render()
    {
        return view('livewire.frontend.featured-product-card');
    }
}

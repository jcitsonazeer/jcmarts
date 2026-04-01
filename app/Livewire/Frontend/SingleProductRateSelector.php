<?php

namespace App\Livewire\Frontend;

use App\Models\Product;
use App\Services\CartService;
use Livewire\Component;

class SingleProductRateSelector extends Component
{
    public Product $product;

    public array $rates = [];
    public $selectedRateId = null;
    public int $quantity = 1;
    public bool $isSoldOut = false;

    public function mount(Product $product)
    {
        $this->product = $product;

        foreach ($product->rates as $rate) {
            $label = trim(
                ($rate->uom->secondary_uom ?? '') . ' ' .
                ($rate->uom->primary_uom ?? '')
            );

            $sellingPrice = (float) ($rate->selling_price ?? 0);
            $finalPrice = (float) ($rate->final_price ?? 0);

            $this->rates[] = [
                'id' => $rate->id,
                'label' => $label !== '' ? $label : 'Option ' . $rate->id,
                'selling_price' => $sellingPrice,
                'display_price' => $finalPrice > 0 ? $finalPrice : $sellingPrice,
                'soldout_status' => $rate->soldout_status ?? 'NO',
                'selected_display' => (int) ($rate->selected_display ?? 0),
            ];
        }

        $this->selectedRateId = $this->resolveDefaultRateId();

        $this->syncSoldOut();
    }

    private function resolveDefaultRateId(): ?int
    {
        foreach ($this->rates as $rate) {
            if ((int) ($rate['selected_display'] ?? 0) === 1) {
                return (int) $rate['id'];
            }
        }

        return !empty($this->rates) ? (int) $this->rates[0]['id'] : null;
    }

    public function getSelectedRateProperty()
    {
        foreach ($this->rates as $rate) {
            if ((int) $rate['id'] === (int) $this->selectedRateId) {
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
        return view('livewire.frontend.single-product-rate-selector');
    }
}

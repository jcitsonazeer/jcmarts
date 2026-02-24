<?php

namespace App\Livewire\Frontend;

use App\Models\Product;
use Livewire\Component;

class SingleProductRateSelector extends Component
{
    public Product $product;

    public array $rates = [];
    public $selectedRateId = null;

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
            ];
        }

        if (!empty($this->rates)) {
            $this->selectedRateId = $this->rates[0]['id'];
        }
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

    public function render()
    {
        return view('livewire.frontend.single-product-rate-selector');
    }
}

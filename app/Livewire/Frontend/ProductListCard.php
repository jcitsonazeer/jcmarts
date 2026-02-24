<?php

namespace App\Livewire\Frontend;

use App\Models\Product;
use Livewire\Component;

class ProductListCard extends Component
{
    public Product $product;

    public $rates = [];
    public $selectedRateId = null;

    public function mount(Product $product)
    {
        $this->product = $product;

        foreach ($product->rates as $rate) {
            $label = trim(
                ($rate->uom->secondary_uom ?? '') . ' ' .
                ($rate->uom->primary_uom ?? '')
            );

            $this->rates[] = [
                'id' => $rate->id,
                'label' => $label !== '' ? $label : 'Option ' . $rate->id,
                'selling_price' => $rate->selling_price ?? 0,
                'final_price' => $rate->final_price ?? 0,
                'offer_percentage' => $rate->offer_percentage ?? 0,
            ];
        }

        if (!empty($this->rates)) {
            $this->selectedRateId = $this->rates[0]['id'];
        }
    }

    public function getSelectedRateProperty()
    {
        foreach ($this->rates as $rate) {
            if ($rate['id'] == $this->selectedRateId) {
                return $rate;
            }
        }

        return null;
    }

    public function getShownPriceProperty()
    {
        if (!$this->selectedRate) {
            return 0;
        }

        return $this->selectedRate['final_price'] > 0
            ? $this->selectedRate['final_price']
            : $this->selectedRate['selling_price'];
    }

    public function render()
    {
        return view('livewire.frontend.product-list-card');
    }
}

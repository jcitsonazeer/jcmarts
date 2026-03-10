<?php

namespace App\Livewire\Admin;

use App\Services\StockInfoService;
use Livewire\Component;

class StockInfoCreate extends Component
{
    public $rateOptions = [];
    public $rate_master_id = '';
    public $rateDetails = null;
    public int $current_stock = 0;

    public function mount()
    {
        $service = app(StockInfoService::class);
        $this->rateOptions = $service->getRateOptions()->toArray();

        $old = session()->getOldInput();
        $this->rate_master_id = $old['rate_master_id'] ?? '';

        if (!empty($this->rate_master_id)) {
            $this->loadRateDetails();
        }
    }

    public function updatedRateMasterId()
    {
        $this->loadRateDetails();
    }

    private function loadRateDetails()
    {
        $this->rateDetails = null;
        $this->current_stock = 0;

        if (empty($this->rate_master_id)) {
            return;
        }

        $service = app(StockInfoService::class);
        $rate = $service->findRateDetails((int) $this->rate_master_id);

        if (!$rate) {
            return;
        }

        $this->rateDetails = [
            'product_name' => $rate->product ? $rate->product->product_name : '-',
            'uom_label' => $rate->uom
                ? trim(($rate->uom->secondary_uom ?? '') . ' ' . ($rate->uom->primary_uom ?? ''))
                : '-',
            'cost_price' => $rate->cost_price,
            'selling_price' => $rate->selling_price,
            'offer_percentage' => $rate->offer_percentage,
            'offer_price' => $rate->offer_price,
            'final_price' => $rate->final_price,
        ];

        $this->current_stock = $rate->latestStockInfo ? (int) $rate->latestStockInfo->current_stock : 0;
    }

    public function render()
    {
        return view('livewire.admin.stock-info-create');
    }
}

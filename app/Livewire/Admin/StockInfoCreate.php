<?php

namespace App\Livewire\Admin;

use App\Services\StockInfoService;
use Livewire\Component;
use Illuminate\Validation\Rule;

class StockInfoCreate extends Component
{
    public array $rate_results = [];
    public $rate_master_id = '';
    public $rateDetails = null;
    public int $current_stock = 0;
    public string $rate_search = '';
    public bool $rate_dropdown_open = false;
    public bool $rate_ignore_search = false;
    public $stock_in_count = '';

    public function mount()
    {
        $old = session()->getOldInput();
        $this->rate_master_id = $old['rate_master_id'] ?? '';

        if (!empty($this->rate_master_id)) {
            $this->syncSelectedRate();
        }
    }

    public function updatedRateMasterId()
    {
        $this->loadRateDetails();
    }

    public function updatedRateSearch()
    {
        if ($this->rate_ignore_search) {
            $this->rate_ignore_search = false;
            return;
        }

        $this->rate_dropdown_open = true;
        $this->loadRateResults();
    }

    public function openRateDropdown()
    {
        $this->rate_dropdown_open = true;
        $this->loadRateResults();
    }

    public function closeRateDropdown()
    {
        $this->rate_dropdown_open = false;
    }

    public function selectRate(int $rateMasterId, string $label)
    {
        $this->rate_ignore_search = true;
        $this->rate_master_id = (string) $rateMasterId;
        $this->rate_search = $label;
        $this->rate_results = [];
        $this->rate_dropdown_open = false;
        $this->loadRateDetails();
    }

    public function clearRateSelection()
    {
        if ($this->rate_ignore_search) {
            return;
        }

        $this->rate_master_id = '';
        $this->rateDetails = null;
        $this->current_stock = 0;
    }

    public function resolveRateSelection()
    {
        if (!empty($this->rate_master_id)) {
            return;
        }

        $term = trim($this->rate_search);
        if ($term === '') {
            return;
        }

        $service = app(StockInfoService::class);
        $results = $service->searchRateOptions($term, 5);

        $match = $results->first(function ($row) use ($term) {
            return isset($row['label']) && strcasecmp($row['label'], $term) === 0;
        });

        if (!$match && $results->count() === 1) {
            $match = $results->first();
        }

        if ($match) {
            $this->selectRate((int) $match['id'], (string) $match['label']);
        }
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

    private function loadRateResults()
    {
        $service = app(StockInfoService::class);
        $this->rate_results = $service->searchRateOptions($this->rate_search, 10)->toArray();
    }

    private function syncSelectedRate()
    {
        $service = app(StockInfoService::class);
        $option = $service->getRateOption((int) $this->rate_master_id);

        if ($option) {
            $this->rate_search = $option['label'];
        }

        $this->loadRateDetails();
    }

    public function save()
    {
        $validated = $this->validate([
            'rate_master_id' => [
                'required',
                'integer',
                Rule::exists('rate_master', 'id')->where('stock_dependent', 'YES'),
            ],
            'stock_in_count' => 'required|integer|min:1',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $service = app(StockInfoService::class);
        $service->createStockIn(
            (int) $validated['rate_master_id'],
            (int) $validated['stock_in_count'],
            $adminId
        );

        return redirect()->route('admin.stock-infos.create')
            ->with('success', 'Stock added successfully.');
    }

    public function render()
    {
        return view('livewire.admin.stock-info-create');
    }
}

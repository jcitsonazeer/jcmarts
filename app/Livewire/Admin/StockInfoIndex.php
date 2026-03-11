<?php

namespace App\Livewire\Admin;

use App\Services\StockInfoService;
use Livewire\Component;

class StockInfoIndex extends Component
{
    public array $rate_results = [];
    public $rate_master_id = '';
    public $history = [];
    public string $rate_search = '';
    public bool $rate_dropdown_open = false;
    public bool $rate_ignore_search = false;

    public function mount()
    {
        $old = session()->getOldInput();
        $this->rate_master_id = $old['rate_master_id'] ?? '';

        if (!empty($this->rate_master_id)) {
            $this->syncSelectedRate();
            $this->loadHistory();
        }
    }

    public function updatedRateMasterId()
    {
        $this->loadHistory();
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
        $this->loadHistory();
    }

    public function clearRateSelection()
    {
        if ($this->rate_ignore_search) {
            return;
        }

        $this->rate_master_id = '';
        $this->history = [];
    }

    private function loadHistory()
    {
        $this->history = [];

        if (empty($this->rate_master_id)) {
            return;
        }

        $service = app(StockInfoService::class);
        $this->history = $service->getHistoryByRate((int) $this->rate_master_id)->toArray();
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
    }

    public function render()
    {
        return view('livewire.admin.stock-info-index');
    }
}

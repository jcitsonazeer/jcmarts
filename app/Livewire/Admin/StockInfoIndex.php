<?php

namespace App\Livewire\Admin;

use App\Services\StockInfoService;
use Livewire\Component;
use Livewire\WithPagination;

class StockInfoIndex extends Component
{
    use WithPagination;

    public array $rate_results = [];
    public $rate_master_id = '';
    public string $rate_search = '';
    public bool $rate_dropdown_open = false;
    public bool $rate_ignore_search = false;
    protected string $paginationTheme = 'bootstrap';

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
        $this->resetPage();
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
        $this->resetPage();
    }

    public function clearRateSelection()
    {
        if ($this->rate_ignore_search) {
            return;
        }

        $this->rate_master_id = '';
        $this->resetPage();
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
        $history = null;

        if (!empty($this->rate_master_id)) {
            $history = app(StockInfoService::class)->getHistoryByRate((int) $this->rate_master_id);
        }

        return view('livewire.admin.stock-info-index', compact('history'));
    }
}

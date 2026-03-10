<?php

namespace App\Livewire\Admin;

use App\Services\StockInfoService;
use Livewire\Component;

class StockInfoIndex extends Component
{
    public $rateOptions = [];
    public $rate_master_id = '';
    public $history = [];

    public function mount()
    {
        $service = app(StockInfoService::class);
        $this->rateOptions = $service->getRateOptions()->toArray();

        $old = session()->getOldInput();
        $this->rate_master_id = $old['rate_master_id'] ?? '';

        if (!empty($this->rate_master_id)) {
            $this->loadHistory();
        }
    }

    public function updatedRateMasterId()
    {
        $this->loadHistory();
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

    public function render()
    {
        return view('livewire.admin.stock-info-index');
    }
}

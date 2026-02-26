<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Services\RateMasterService;

class RateMasterCreate extends Component
{
    public $products = [];
    public $primaryUoms = [];
    public $secondaryUomMap = [];

    public $product_id = '';
    public $primary_uom = '';
    public $rate_rows = [];

    public $isRecalculating = false;

    /*
    |--------------------------------------------------------------------------
    | MOUNT
    |--------------------------------------------------------------------------
    | Runs when component loads (after redirect also)
    */
    public function mount()
    {
        $service = app(RateMasterService::class);

        $this->products = $service->getProductsForDropdown()->toArray();
        $this->primaryUoms = $service->getPrimaryUomsForCreate()->toArray();
        $this->secondaryUomMap = $service->getSecondaryUomsByPrimaryForCreate()->toArray();

        // 🔥 Restore old values after validation error
        $old = session()->getOldInput();

        $this->product_id = $old['product_id'] ?? '';
        $this->primary_uom = $old['primary_uom'] ?? '';

        if (!empty($this->primary_uom)) {
            $this->loadRows($old['rate_rows'] ?? []);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | When primary UOM changes
    |--------------------------------------------------------------------------
    */
    public function updatedPrimaryUom()
    {
        $this->loadRows();
    }

    /*
    |--------------------------------------------------------------------------
    | Load secondary rows
    |--------------------------------------------------------------------------
    */
    private function loadRows($oldRows = [])
    {
        $secondaryRows = $this->secondaryUomMap[$this->primary_uom] ?? [];

        $this->rate_rows = [];

        foreach ($secondaryRows as $row) {

            // Find old row data if exists
            $existing = collect($oldRows)
                ->firstWhere('uom_id', $row['id']) ?? [];

            $this->rate_rows[] = [
                'uom_id' => $row['id'],
                'secondary_uom' => $row['secondary_uom'],
                'cost_price' => $existing['cost_price'] ?? '',
                'selling_price' => $existing['selling_price'] ?? '',
                'offer_percentage' => $existing['offer_percentage'] ?? '',
                'offer_price' => $existing['offer_price'] ?? '',
                'final_price' => $existing['final_price'] ?? '',
                'stock_qty' => $existing['stock_qty'] ?? '',
                'is_active' => array_key_exists('is_active', $existing) ? (string) $existing['is_active'] : '1',
            ];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | When table values change
    |--------------------------------------------------------------------------
    */
    public function updatedRateRows($value, $key)
    {
        if ($this->isRecalculating) return;

        [$index, $field] = explode('.', $key);

        if (!isset($this->rate_rows[$index])) return;

        if ($field == 'offer_price') {
            $this->calculateFromOfferPrice($index);
        }

        if ($field == 'selling_price' || $field == 'offer_percentage') {
            $this->calculateFromPercentage($index);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate using percentage
    |--------------------------------------------------------------------------
    */
    private function calculateFromPercentage($index)
    {
        $this->isRecalculating = true;

        $selling = (float) ($this->rate_rows[$index]['selling_price'] ?? 0);
        $percentage = (float) ($this->rate_rows[$index]['offer_percentage'] ?? 0);

        if ($percentage < 0) $percentage = 0;
        if ($percentage > 100) $percentage = 100;

        $offer = ($selling * $percentage) / 100;
        $final = $selling - $offer;

        $this->rate_rows[$index]['offer_price'] =
            number_format($offer, 2, '.', '');

        $this->rate_rows[$index]['final_price'] =
            number_format(max($final, 0), 2, '.', '');

        $this->isRecalculating = false;
    }

    /*
    |--------------------------------------------------------------------------
    | Calculate using offer price
    |--------------------------------------------------------------------------
    */
    private function calculateFromOfferPrice($index)
    {
        $this->isRecalculating = true;

        $selling = (float) ($this->rate_rows[$index]['selling_price'] ?? 0);
        $offer = (float) ($this->rate_rows[$index]['offer_price'] ?? 0);

        if ($offer < 0) $offer = 0;
        if ($offer > $selling) $offer = $selling;

        $percentage = $selling > 0
            ? ($offer / $selling) * 100
            : 0;

        $final = $selling - $offer;

        $this->rate_rows[$index]['offer_percentage'] =
            number_format($percentage, 2, '.', '');

        $this->rate_rows[$index]['final_price'] =
            number_format(max($final, 0), 2, '.', '');

        $this->isRecalculating = false;
    }

    public function render()
    {
        return view('livewire.admin.rate-master-create');
    }
}

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
    public string $product_search = '';
    public array $product_results = [];
    public bool $product_dropdown_open = false;
    public bool $product_ignore_search = false;
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

        if (!empty($this->product_id)) {
            $this->syncSelectedProduct();
        }

        if (!empty($this->primary_uom)) {
            $this->loadRows($old['rate_rows'] ?? []);
        }
    }

    public function updatedProductSearch()
    {
        if ($this->product_ignore_search) {
            $this->product_ignore_search = false;
            return;
        }

        $this->product_dropdown_open = true;
        $this->loadProductResults();
    }

    public function openProductDropdown()
    {
        $this->product_dropdown_open = true;
        $this->loadProductResults();
    }

    public function closeProductDropdown()
    {
        $this->product_dropdown_open = false;
    }

    public function selectProduct(int $productId, string $label)
    {
        $this->product_ignore_search = true;
        $this->product_id = (string) $productId;
        $this->product_search = $label;
        $this->product_results = [];
        $this->product_dropdown_open = false;

        if (!empty($this->primary_uom)) {
            $this->loadRows($this->rate_rows);
        }
    }

    public function clearProductSelection()
    {
        if ($this->product_ignore_search) {
            return;
        }

        $this->product_id = '';

        if (!empty($this->primary_uom)) {
            $this->loadRows($this->rate_rows);
        }
    }

    public function resolveProductSelection()
    {
        if (!empty($this->product_id)) {
            return;
        }

        $term = trim($this->product_search);
        if ($term === '') {
            return;
        }

        $service = app(RateMasterService::class);
        $results = $service->searchProductOptions($term, 5);

        $match = $results->first(function ($row) use ($term) {
            return isset($row['label']) && strcasecmp($row['label'], $term) === 0;
        });

        if (!$match && $results->count() === 1) {
            $match = $results->first();
        }

        if ($match) {
            $this->selectProduct((int) $match['id'], (string) $match['label']);
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
        $existingRates = [];
        $existingUomIds = [];
        $uomIds = array_map(function ($row) {
            return (int) ($row['id'] ?? 0);
        }, $secondaryRows);

        if (!empty($this->product_id) && !empty($secondaryRows)) {
            $service = app(RateMasterService::class);
            $existingUomIds = $service->getExistingUomIdsForProduct(
                (int) $this->product_id,
                $uomIds
            );
            $existingRates = $service->getExistingRatesForProduct((int) $this->product_id, $uomIds);
        }

        $this->rate_rows = [];

        foreach ($secondaryRows as $row) {
            $uomId = (int) ($row['id'] ?? 0);
            $alreadyExists = in_array($uomId, $existingUomIds, true);
            $oldRow = collect($oldRows)
                ->firstWhere('uom_id', $row['id']) ?? [];
            $existing = $alreadyExists
                ? ($existingRates[$uomId] ?? [])
                : $oldRow;

            $this->rate_rows[] = [
                'uom_id' => $uomId,
                'secondary_uom' => $row['secondary_uom'],
                'cost_price' => $existing['cost_price'] ?? '',
                'selling_price' => $existing['selling_price'] ?? '',
                'offer_percentage' => $existing['offer_percentage'] ?? '',
                'offer_price' => $existing['offer_price'] ?? '',
                'final_price' => $existing['final_price'] ?? '',
                'soldout_status' => $existing['soldout_status'] ?? 'NO',
                'stock_dependent' => $existing['stock_dependent'] ?? 'NO',
                'is_active' => array_key_exists('is_active', $existing) ? (string) $existing['is_active'] : '1',
                'already_exists' => $alreadyExists,
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
        if (!empty($this->rate_rows[$index]['already_exists'])) return;

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

    private function loadProductResults()
    {
        $service = app(RateMasterService::class);
        $this->product_results = $service->searchProductOptions($this->product_search, 10)->toArray();
    }

    private function syncSelectedProduct()
    {
        $service = app(RateMasterService::class);
        $option = $service->getProductOption((int) $this->product_id);

        if ($option) {
            $this->product_search = $option['label'];
        }
    }

    public function render()
    {
        return view('livewire.admin.rate-master-create');
    }
}

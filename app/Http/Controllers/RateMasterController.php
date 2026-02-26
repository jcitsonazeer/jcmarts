<?php

namespace App\Http\Controllers;

use App\Services\RateMasterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RateMasterController extends Controller
{
    protected $rateMasterService;

    public function __construct(RateMasterService $rateMasterService)
    {
        $this->rateMasterService = $rateMasterService;
    }

    public function index()
    {
        $rates = $this->rateMasterService->getAll();

        return view('admin.rate_master.index', compact('rates'));
    }

    public function create()
    {
        return view('admin.rate_master.create');
    }

    public function selectedDisplayIndex()
    {
        $products = $this->rateMasterService->getProductsForSelectedDisplay();

        return view('admin.rate_master.selected_display_index', compact('products'));
    }

    public function selectedDisplayEdit($productId)
    {
        $rates = $this->rateMasterService->getRatesForSelectedDisplayByProduct($productId);

        if ($rates->isEmpty()) {
            return redirect()->route('admin.selected-display.index')
                ->with('error', 'No rates found for the selected product.');
        }

        $product = $rates->first()->product;
        if (!$product) {
            return redirect()->route('admin.selected-display.index')
                ->with('error', 'Product not found for selected rates.');
        }

        return view('admin.rate_master.selected_display_edit', compact('product', 'rates'));
    }

    public function selectedDisplayUpdate(Request $request, $productId)
    {
        $validatedData = $request->validate([
            'selected_rate_id' => 'required|integer|exists:rate_master,id',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $validRateId = $this->rateMasterService
            ->getRatesForSelectedDisplayByProduct((int) $productId)
            ->contains('id', (int) $validatedData['selected_rate_id']);

        if (!$validRateId) {
            return redirect()->back()
                ->withErrors(['selected_rate_id' => 'Selected rate does not belong to this product.'])
                ->withInput();
        }

        $this->rateMasterService->updateSelectedDisplayForProduct(
            (int) $productId,
            (int) $validatedData['selected_rate_id'],
            $adminId
        );

        return redirect()->route('admin.selected-display.edit', $productId)
            ->with('success', 'Selected display rate updated successfully.');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'primary_uom' => 'required|string|exists:uom_master,primary_uom',
            'rate_rows' => 'required|array|min:1',
            'rate_rows.*.uom_id' => 'required|integer|exists:uom_master,id',
            'rate_rows.*.cost_price' => 'nullable|numeric|min:0',
            'rate_rows.*.selling_price' => 'nullable|numeric|min:0',
            'rate_rows.*.offer_percentage' => 'nullable|numeric|min:0|max:100',
            'rate_rows.*.offer_price' => 'nullable|numeric|min:0',
            'rate_rows.*.final_price' => 'nullable|numeric|min:0',
            'rate_rows.*.stock_qty' => 'nullable|integer|min:0',
            'rate_rows.*.is_active' => 'nullable|boolean',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $allowedUomIds = $this->rateMasterService->getUomIdsByPrimaryUom($validatedData['primary_uom']);
        $rowsToSave = [];
        $errors = [];
        $seenUomIds = [];

        foreach ($validatedData['rate_rows'] as $index => $row) {
            $uomId = (int) ($row['uom_id'] ?? 0);

            if (!in_array($uomId, $allowedUomIds, true)) {
                $errors["rate_rows.$index.uom_id"] = 'Selected UOM row does not belong to the selected primary UOM.';
                continue;
            }

            if (in_array($uomId, $seenUomIds, true)) {
                $errors["rate_rows.$index.uom_id"] = 'Duplicate UOM row selected in the request.';
                continue;
            }

            $hasAnyValue =
                $this->isFilled($row['cost_price'] ?? null) ||
                $this->isFilled($row['selling_price'] ?? null) ||
                $this->isFilled($row['stock_qty'] ?? null);

            if (!$hasAnyValue) {
                continue;
            }

            $rowValidator = Validator::make($row, [
                'cost_price' => 'required|numeric|min:0|lte:selling_price',
                'selling_price' => 'required|numeric|min:0',
                'offer_percentage' => 'nullable|numeric|min:0|max:100',
                'offer_price' => 'nullable|numeric|min:0',
                'final_price' => 'nullable|numeric|min:0',
                'stock_qty' => 'nullable|integer|min:0',
            ]);

            if ($rowValidator->fails()) {
                foreach ($rowValidator->errors()->toArray() as $field => $messages) {
                    $errors["rate_rows.$index.$field"] = $messages[0];
                }
                continue;
            }

            $rowsToSave[] = [
                'source_index' => $index,
                'uom_id' => $uomId,
                'cost_price' => $row['cost_price'],
                'selling_price' => $row['selling_price'],
                'offer_percentage' => $row['offer_percentage'] ?? null,
                'offer_price' => $row['offer_price'] ?? null,
                'final_price' => $row['final_price'] ?? null,
                'stock_qty' => $row['stock_qty'],
                'is_active' => array_key_exists('is_active', $row) ? $row['is_active'] : 1,
            ];

            $seenUomIds[] = $uomId;
        }

        if (!empty($errors)) {
            return redirect()->back()->withErrors($errors)->withInput();
        }

        if (empty($rowsToSave)) {
            return redirect()->back()
                ->withErrors(['rate_rows' => 'Please fill at least one secondary UOM row.'])
                ->withInput();
        }

        $existingUomIds = $this->rateMasterService->getExistingUomIdsForProduct(
            (int) $validatedData['product_id'],
            array_values(array_unique(array_column($rowsToSave, 'uom_id')))
        );

        if (!empty($existingUomIds)) {
            foreach ($rowsToSave as $row) {
                if (in_array((int) $row['uom_id'], $existingUomIds, true)) {
                    $errors['rate_rows.' . $row['source_index'] . '.uom_id'] = 'Rate already exists for this product and UOM.';
                }
            }

            return redirect()->back()->withErrors($errors)->withInput();
        }

        $rowsToPersist = array_map(function ($row) {
            unset($row['source_index']);
            return $row;
        }, $rowsToSave);

        $this->rateMasterService->createMultiple($validatedData['product_id'], $rowsToPersist, $adminId);

        return redirect()->route('admin.rate-masters.index')
            ->with('success', 'Rate rows added successfully');
    }

    public function show($id)
    {
        $rate = $this->rateMasterService->findForShow($id);

        return view('admin.rate_master.show', compact('rate'));
    }

    public function edit($id)
    {
        $rate = $this->rateMasterService->findForEdit($id);
        $products = $this->rateMasterService->getProductsForDropdown();
        $uoms = $this->rateMasterService->getUomsForDropdown();

        return view('admin.rate_master.edit', compact('rate', 'products', 'uoms'));
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'uom_id' => [
                'required',
                'integer',
                'exists:uom_master,id',
                Rule::unique('rate_master')
                    ->where(function ($query) use ($request) {
                        return $query->where('product_id', $request->input('product_id'));
                    })
                    ->ignore($id, 'id'),
            ],
            'cost_price' => 'required|numeric|min:0|lte:selling_price',
            'selling_price' => 'required|numeric|min:0|gte:cost_price',
            'offer_percentage' => 'nullable|numeric|min:0|max:100',
            'offer_price' => 'nullable|numeric|min:0',
            'final_price' => 'nullable|numeric|min:0',
            'stock_qty' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->rateMasterService->update($id, $validatedData, $adminId);

        return redirect()->route('admin.rate-masters.edit', $id)
            ->with('success', 'Rate updated successfully');
    }

    public function destroy($id)
    {
        $this->rateMasterService->delete($id);

        return redirect()->route('admin.rate-masters.index')
            ->with('success', 'Rate deleted successfully');
    }

    private function isFilled($value)
    {
        return $value !== null && $value !== '';
    }
}

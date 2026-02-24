<?php

namespace App\Http\Controllers;

use App\Services\UomMasterService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UomMasterController extends Controller
{
    protected $uomMasterService;

    public function __construct(UomMasterService $uomMasterService)
    {
        $this->uomMasterService = $uomMasterService;
    }

    public function index()
    {
        $uoms = $this->uomMasterService->getAll();

        return view('admin.uom_master.index', compact('uoms'));
    }

    public function create()
    {
        $primaryUomOptions = $this->uomMasterService->getPrimaryUomOptions();

        return view('admin.uom_master.create', compact('primaryUomOptions'));
    }

    public function store(Request $request)
    {
        $primaryUom = $this->normalizeUomName($request->primary_uom);
        $secondaryUom = $this->normalizeUomName($request->secondary_uom);
        $request->merge([
            'primary_uom' => $primaryUom,
            'secondary_uom' => $secondaryUom,
        ]);

        $validatedData = $request->validate([
            'primary_uom' => 'required|string|max:100',
            'secondary_uom' => [
                'required',
                'string',
                'max:100',
                Rule::unique('uom_master', 'secondary_uom')->where(function ($query) use ($primaryUom) {
                    return $query->where('primary_uom', $primaryUom);
                }),
            ],
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->uomMasterService->create($validatedData, $adminId);

        return redirect()->route('admin.uom-masters.index')
            ->with('success', 'UOM created successfully');
    }

    public function show($id)
    {
        $uom = $this->uomMasterService->findForShow($id);

        return view('admin.uom_master.show', compact('uom'));
    }

    public function edit($id)
    {
        $uom = $this->uomMasterService->findForEdit($id);
        $primaryUomOptions = $this->uomMasterService->getPrimaryUomOptions();

        return view('admin.uom_master.edit', compact('uom', 'primaryUomOptions'));
    }

    public function update(Request $request, $id)
    {
        $primaryUom = $this->normalizeUomName($request->primary_uom);
        $secondaryUom = $this->normalizeUomName($request->secondary_uom);
        $request->merge([
            'primary_uom' => $primaryUom,
            'secondary_uom' => $secondaryUom,
        ]);

        $validatedData = $request->validate([
            'primary_uom' => 'required|string|max:100',
            'secondary_uom' => [
                'required',
                'string',
                'max:100',
                Rule::unique('uom_master', 'secondary_uom')
                    ->where(function ($query) use ($primaryUom) {
                        return $query->where('primary_uom', $primaryUom);
                    })
                    ->ignore($id, 'id'),
            ],
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->uomMasterService->update($id, $validatedData, $adminId);

        return redirect()->route('admin.uom-masters.edit', $id)
            ->with('success', 'UOM updated successfully');
    }

    public function destroy($id)
    {
        if ($this->uomMasterService->hasRates($id)) {
            return redirect()->route('admin.uom-masters.index')
                ->with('error', 'Cannot delete this UOM because it is used in Rate Master.');
        }

        $this->uomMasterService->delete($id);

        return redirect()->route('admin.uom-masters.index')
            ->with('success', 'UOM deleted successfully');
    }

    private function normalizeUomName($name)
    {
        $name = trim((string) $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return Str::title((string) $name);
    }
}

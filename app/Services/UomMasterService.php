<?php

namespace App\Services;

use App\Models\RateMaster;
use App\Models\UomMaster;
use Carbon\Carbon;
use Illuminate\Support\Str;

class UomMasterService
{
    public function getAll()
    {
        return UomMaster::with(['createdBy', 'updatedBy'])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getPrimaryUomOptions()
    {
        return UomMaster::select('primary_uom')
            ->whereNotNull('primary_uom')
            ->where('primary_uom', '!=', '')
            ->distinct()
            ->orderBy('primary_uom')
            ->pluck('primary_uom');
    }

    public function findForShow($id)
    {
        return UomMaster::with(['createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function findForEdit($id)
    {
        return UomMaster::with(['createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function create($data, $adminId)
    {
        $primaryUom = $this->normalizeUomName($data['primary_uom'] ?? '');
        $secondaryUom = $this->normalizeUomName($data['secondary_uom'] ?? '');

        return UomMaster::create([
            'primary_uom' => $primaryUom,
            'secondary_uom' => $secondaryUom,
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function update($id, $data, $adminId)
    {
        $uom = UomMaster::findOrFail($id);
        $primaryUom = $this->normalizeUomName($data['primary_uom'] ?? '');
        $secondaryUom = $this->normalizeUomName($data['secondary_uom'] ?? '');

        $uom->update([
            'primary_uom' => $primaryUom,
            'secondary_uom' => $secondaryUom,
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ]);

        return $uom;
    }

    public function delete($id)
    {
        $uom = UomMaster::findOrFail($id);
        $uom->delete();
    }

    public function hasRates($id)
    {
        return RateMaster::where('uom_id', $id)->exists();
    }

    private function normalizeUomName($name)
    {
        $name = trim((string) $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return Str::title((string) $name);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\OfferDetailService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OfferDetailController extends Controller
{
    protected $offerDetailService;

    public function __construct(OfferDetailService $offerDetailService)
    {
        $this->offerDetailService = $offerDetailService;
    }

    public function index()
    {
        $offers = $this->offerDetailService->getAll();

        return view('admin.offer_details.index', compact('offers'));
    }

    public function create()
    {
        return view('admin.offer_details.create');
    }

    public function store(Request $request)
    {
        $request->merge([
            'offer_name' => Str::title(trim((string) $request->offer_name)),
        ]);

        $validatedData = $request->validate([
            'offer_name' => 'required|string|max:120|unique:offer_details,offer_name',
            'is_active' => 'required|boolean',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')->with('error', 'Please login to continue.');
        }

        $this->offerDetailService->create($validatedData, $adminId);

        return redirect()->route('admin.offer-details.index')
            ->with('success', 'Offer created successfully');
    }

    public function show($id)
    {
        $offer = $this->offerDetailService->findForShow($id);

        return view('admin.offer_details.show', compact('offer'));
    }

    public function edit($id)
    {
        $offer = $this->offerDetailService->findForEdit($id);

        return view('admin.offer_details.edit', compact('offer'));
    }

    public function update(Request $request, $id)
    {
        $request->merge([
            'offer_name' => Str::title(trim((string) $request->offer_name)),
        ]);

        $validatedData = $request->validate([
            'offer_name' => 'required|string|max:120|unique:offer_details,offer_name,' . $id,
            'is_active' => 'required|boolean',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')->with('error', 'Please login to continue.');
        }

        $this->offerDetailService->update((int) $id, $validatedData, $adminId);

        return redirect()->route('admin.offer-details.edit', $id)
            ->with('success', 'Offer updated successfully');
    }

    public function destroy($id)
    {
        if ($this->offerDetailService->hasOfferProducts((int) $id)) {
            return redirect()->route('admin.offer-details.index')
                ->with('error', 'This offer is already linked in offer products.');
        }

        if ($this->offerDetailService->hasIndexBanners((int) $id)) {
            return redirect()->route('admin.offer-details.index')
                ->with('error', 'This offer is already linked in index banners.');
        }

        $this->offerDetailService->delete((int) $id);

        return redirect()->route('admin.offer-details.index')
            ->with('success', 'Offer deleted successfully');
    }
}

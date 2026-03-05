<?php

namespace App\Http\Controllers;

use App\Services\OfferProductService;
use Illuminate\Http\Request;

class OfferProductController extends Controller
{
    protected $offerProductService;

    public function __construct(OfferProductService $offerProductService)
    {
        $this->offerProductService = $offerProductService;
    }

    public function index()
    {
        $offerProducts = $this->offerProductService->getAll();

        return view('admin.offer_products.index', compact('offerProducts'));
    }

    public function create()
    {
        $offers = $this->offerProductService->getOffersForDropdown();
        $products = $this->offerProductService->getProductsForDropdown();

        return view('admin.offer_products.create', compact('offers', 'products'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'offer_id' => 'required|integer|exists:offer_details,id',
            'products_id' => 'required|integer|exists:products,id',
            'is_active' => 'required|boolean',
        ]);

        if ($this->offerProductService->existsDuplicate((int) $validatedData['offer_id'], (int) $validatedData['products_id'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This offer and product combination already exists.');
        }

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')->with('error', 'Please login to continue.');
        }

        $this->offerProductService->create($validatedData, $adminId);

        return redirect()->route('admin.offer-products.index')
            ->with('success', 'Offer product linked successfully');
    }

    public function show($id)
    {
        $offerProduct = $this->offerProductService->findForShow($id);

        return view('admin.offer_products.show', compact('offerProduct'));
    }

    public function edit($id)
    {
        $offerProduct = $this->offerProductService->findForEdit($id);
        $offers = $this->offerProductService->getOffersForDropdown();
        $products = $this->offerProductService->getProductsForDropdown();

        return view('admin.offer_products.edit', compact('offerProduct', 'offers', 'products'));
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'offer_id' => 'required|integer|exists:offer_details,id',
            'products_id' => 'required|integer|exists:products,id',
            'is_active' => 'required|boolean',
        ]);

        if ($this->offerProductService->existsDuplicate((int) $validatedData['offer_id'], (int) $validatedData['products_id'], (int) $id)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This offer and product combination already exists.');
        }

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')->with('error', 'Please login to continue.');
        }

        $this->offerProductService->update((int) $id, $validatedData, $adminId);

        return redirect()->route('admin.offer-products.edit', $id)
            ->with('success', 'Offer product updated successfully');
    }

    public function destroy($id)
    {
        $this->offerProductService->delete((int) $id);

        return redirect()->route('admin.offer-products.index')
            ->with('success', 'Offer product deleted successfully');
    }
}

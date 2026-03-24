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

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $offerProducts = $this->offerProductService->getAll($search);

        return view('admin.offer_products.index', compact('offerProducts', 'search'));
    }

    public function create()
    {
        $offers = $this->offerProductService->getOffersForDropdown();
        $products = $this->offerProductService->getProductsForDropdown();

        return view('admin.offer_products.create', compact('offers', 'products'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'product_name' => trim((string) $request->input('product_name')),
        ]);

        $validatedData = $request->validate([
            'offer_id' => 'required|integer|exists:offer_details,id',
            'product_name' => 'required|string|max:150',
            'is_active' => 'required|boolean',
        ]);

        $validatedData['products_id'] = $this->offerProductService->findProductIdByName($validatedData['product_name']);

        if (!$validatedData['products_id']) {
            return back()
                ->withErrors(['product_name' => 'Please select a valid product from the suggestion list.'])
                ->withInput();
        }

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

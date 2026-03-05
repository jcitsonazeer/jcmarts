<?php

namespace App\Http\Controllers;

use App\Services\IndexBannerService;
use Illuminate\Http\Request;

class IndexBannerController extends Controller
{
    protected $indexBannerService;

    public function __construct(IndexBannerService $indexBannerService)
    {
        $this->indexBannerService = $indexBannerService;
    }

    public function index()
    {
        $banners = $this->indexBannerService->getAll();

        return view('admin.index_banner.index', compact('banners'));
    }

    public function create()
    {
        $subCategories = $this->indexBannerService->getSubCategoriesForDropdown();
        $offers = $this->indexBannerService->getOffersForDropdown();

        return view('admin.index_banner.create', compact('subCategories', 'offers'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'banner_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'sub_category_id' => 'nullable|integer|exists:sub_category,id',
            'offer_details_id' => 'nullable|integer|exists:offer_details,id',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->indexBannerService->create($validatedData, $adminId);

        return redirect()->route('admin.index-banners.index')
            ->with('success', 'Banner created successfully');
    }

    public function show($id)
    {
        $banner = $this->indexBannerService->findForShow($id);

        return view('admin.index_banner.show', compact('banner'));
    }

    public function edit($id)
    {
        $banner = $this->indexBannerService->findForEdit($id);
        $subCategories = $this->indexBannerService->getSubCategoriesForDropdown();
        $offers = $this->indexBannerService->getOffersForDropdown();

        return view('admin.index_banner.edit', compact('banner', 'subCategories', 'offers'));
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'banner_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'sub_category_id' => 'nullable|integer|exists:sub_category,id',
            'offer_details_id' => 'nullable|integer|exists:offer_details,id',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->indexBannerService->update($id, $validatedData, $adminId);

        return redirect()->route('admin.index-banners.edit', $id)
            ->with('success', 'Banner updated successfully');
    }

    public function destroy($id)
    {
        $this->indexBannerService->delete($id);

        return redirect()->route('admin.index-banners.index')
            ->with('success', 'Banner deleted successfully');
    }
}

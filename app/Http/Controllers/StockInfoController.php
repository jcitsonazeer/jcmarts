<?php

namespace App\Http\Controllers;

use App\Services\StockInfoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockInfoController extends Controller
{
    protected $stockInfoService;

    public function __construct(StockInfoService $stockInfoService)
    {
        $this->stockInfoService = $stockInfoService;
    }

    public function create()
    {
        return view('admin.stock_info.create');
    }

    public function index()
    {
        return view('admin.stock_info.index');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'rate_master_id' => [
                'required',
                'integer',
                Rule::exists('rate_master', 'id')->where('stock_dependent', 'YES'),
            ],
            'stock_in_count' => 'required|integer|min:1',
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->stockInfoService->createStockIn(
            (int) $validatedData['rate_master_id'],
            (int) $validatedData['stock_in_count'],
            $adminId
        );

        return redirect()->route('admin.stock-infos.create')
            ->with('success', 'Stock added successfully.');
    }
}

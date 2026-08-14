<?php

namespace App\Http\Controllers;

use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $customers = $this->customerService->getAll($search);

        return view('admin.customers.index', compact('customers', 'search'));
    }

    public function create()
    {
        return view('admin.customers.create');
    }

    public function store(Request $request)
    {
        $this->cleanCustomerInput($request);

        $validatedData = $request->validate($this->rules());

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->customerService->create($validatedData, $adminId);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer created successfully');
    }

    public function show($id)
    {
        $customer = $this->customerService->findForShow($id);

        return view('admin.customers.show', compact('customer'));
    }

    public function edit($id)
    {
        $customer = $this->customerService->findForEdit($id);

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $this->cleanCustomerInput($request);

        $validatedData = $request->validate($this->rules($id));

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->customerService->update($id, $validatedData, $adminId);

        return redirect()->route('admin.customers.edit', $id)
            ->with('success', 'Customer updated successfully');
    }

    public function destroy($id)
    {
        if (!$this->customerService->canDelete($id)) {
            return redirect()->route('admin.customers.index')
                ->with('error', 'This customer has addresses, orders, or wishlist items and cannot be deleted.');
        }

        $this->customerService->delete($id);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Customer deleted successfully');
    }

    private function cleanCustomerInput(Request $request): void
    {
        $request->merge([
            'name' => Str::title(trim((string) $request->name)),
            'mobile_number' => preg_replace('/\D+/', '', (string) $request->mobile_number),
            'verified_status' => trim((string) $request->verified_status),
        ]);
    }

    private function rules($customerId = null): array
    {
        return [
            'name' => 'required|string|max:120',
            'mobile_number' => [
                'required',
                'digits_between:10,15',
                Rule::unique('customers', 'mobile_number')->ignore($customerId),
            ],
            'verified_status' => 'required|string|max:20|in:pending,verified,rejected',
            'is_active' => 'required|boolean',
        ];
    }
}

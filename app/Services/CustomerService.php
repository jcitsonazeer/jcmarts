<?php

namespace App\Services;

use App\Models\Customer;
use Carbon\Carbon;

class CustomerService
{
    public function getAll(?string $searchTerm = null)
    {
        $query = Customer::query()
            ->with(['createdBy', 'updatedBy'])
            ->withCount(['addresses', 'orders', 'wishlists']);

        $term = trim((string) $searchTerm);

        if ($term !== '') {
            $query->where(function ($customerQuery) use ($term) {
                $customerQuery->where('name', 'like', '%' . $term . '%')
                    ->orWhere('mobile_number', 'like', '%' . $term . '%')
                    ->orWhere('verified_status', 'like', '%' . $term . '%');
            });
        }

        return $query->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();
    }

    public function findForShow($id)
    {
        return Customer::with(['createdBy', 'updatedBy', 'addresses'])
            ->withCount(['addresses', 'orders', 'wishlists'])
            ->findOrFail($id);
    }

    public function findForEdit($id)
    {
        return Customer::with(['createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function create(array $data, $adminId)
    {
        return Customer::create([
            'name' => $data['name'],
            'mobile_number' => $data['mobile_number'],
            'verified_status' => $data['verified_status'],
            'is_active' => $data['is_active'],
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function update($id, array $data, $adminId)
    {
        $customer = Customer::findOrFail($id);

        $customer->update([
            'name' => $data['name'],
            'mobile_number' => $data['mobile_number'],
            'verified_status' => $data['verified_status'],
            'is_active' => $data['is_active'],
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ]);

        return $customer;
    }

    public function canDelete($id): bool
    {
        $customer = Customer::withCount(['addresses', 'orders', 'wishlists'])->findOrFail($id);

        return $customer->addresses_count === 0
            && $customer->orders_count === 0
            && $customer->wishlists_count === 0;
    }

    public function delete($id): void
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();
    }
}

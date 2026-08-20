<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    // Serviceable pincodes — same list used by the website checkout
    private const SERVICEABLE_PINCODES = [
        '629151', '629152', '629153', '629154', '629158',
        '629160', '629162', '629163', '629165', '629167',
        '629168', '629171', '629172', '629173', '629177',
        '629179', '629188', '629190', '629191', '629194',
        '629195', '629197',
    ];

    // ─── PROFILE ───────────────────────────────────────────

    /**
     * GET /api/v1/profile
     * Return the authenticated customer's profile.
     */
    public function show(Request $request)
    {
        $customer = $request->user();

        return response()->json([
            'status' => true,
            'message' => 'Profile fetched successfully',
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'mobile_number' => $customer->mobile_number,
                'verified_status' => $customer->verified_status,
                'is_active' => $customer->is_active,
            ],
        ]);
    }

    /**
     * PUT /api/v1/profile
     * Update the authenticated customer's name.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $customer = $request->user();

        $customer->update([
            'name' => trim($validated['name']),
            'updated_date' => Carbon::now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'mobile_number' => $customer->mobile_number,
            ],
        ]);
    }

    // ─── ADDRESSES ─────────────────────────────────────────

    /**
     * GET /api/v1/addresses
     * List all active addresses for the authenticated customer.
     */
    public function addressIndex(Request $request)
    {
        $customerId = $request->user()->id;

        $addresses = CustomerAddress::query()
            ->where('customer_id', $customerId)
            ->where('is_active', 1)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Addresses fetched successfully',
            'data' => $addresses,
        ]);
    }

    /**
     * POST /api/v1/addresses
     * Add a new delivery address.
     */
    public function addressStore(Request $request)
    {
        $validated = $request->validate([
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['required', 'string', 'max:255'],
            'location'        => ['required', 'string', 'max:150'],
            'pincode'         => ['required', 'digits:6', Rule::in(self::SERVICEABLE_PINCODES)],
            'landmark'        => ['required', 'string', 'max:255'],
        ], [
            'pincode.in' => 'Delivery not available for the entered pincode',
        ]);

        $customerId = $request->user()->id;
        $now = Carbon::now();

        $address = CustomerAddress::query()->create([
            'customer_id'    => $customerId,
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => $validated['address_line_2'],
            'location'       => $validated['location'],
            'pincode'        => $validated['pincode'],
            'landmark'       => $validated['landmark'],
            'is_active'      => 1,
            'created_by_id'  => $customerId,
            'created_date'   => $now,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Address added successfully',
            'data' => $address,
        ], 201);
    }

    /**
     * PUT /api/v1/addresses/{id}
     * Update an existing address (must belong to the customer).
     */
    public function addressUpdate(Request $request, int $addressId)
    {
        $validated = $request->validate([
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['required', 'string', 'max:255'],
            'location'        => ['required', 'string', 'max:150'],
            'pincode'         => ['required', 'digits:6', Rule::in(self::SERVICEABLE_PINCODES)],
            'landmark'        => ['required', 'string', 'max:255'],
        ], [
            'pincode.in' => 'Delivery not available for the entered pincode',
        ]);

        $customerId = $request->user()->id;

        $address = CustomerAddress::query()
            ->where('id', $addressId)
            ->where('customer_id', $customerId)
            ->where('is_active', 1)
            ->first();

        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found',
                'data' => null,
            ], 404);
        }

        $address->update([
            'address_line_1' => $validated['address_line_1'],
            'address_line_2' => $validated['address_line_2'],
            'location'       => $validated['location'],
            'pincode'        => $validated['pincode'],
            'landmark'       => $validated['landmark'],
            'updated_by_id'  => $customerId,
            'updated_date'   => Carbon::now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Address updated successfully',
            'data' => $address,
        ]);
    }

    /**
     * DELETE /api/v1/addresses/{id}
     * Soft-delete an address (set is_active = 0).
     */
    public function addressDestroy(Request $request, int $addressId)
    {
        $customerId = $request->user()->id;

        $address = CustomerAddress::query()
            ->where('id', $addressId)
            ->where('customer_id', $customerId)
            ->where('is_active', 1)
            ->first();

        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found',
                'data' => null,
            ], 404);
        }

        $address->update([
            'is_active' => 0,
            'updated_by_id' => $customerId,
            'updated_date' => Carbon::now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Address deleted successfully',
            'data' => null,
        ]);
    }

    /**
     * GET /api/v1/serviceable-pincodes
     * Return the list of pincodes we deliver to.
     */
    public function serviceablePincodes()
    {
        return response()->json([
            'status' => true,
            'message' => 'Serviceable pincodes fetched successfully',
            'data' => [
                'pincodes' => self::SERVICEABLE_PINCODES,
            ],
        ]);
    }
}

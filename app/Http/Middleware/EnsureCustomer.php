<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomer
{
    /**
     * Ensure the authenticated token belongs to a Customer.
     *
     * Laravel resolves the authenticated account from the token (not from any
     * role sent by the app). If the token does not belong to a customer
     * (for example a delivery person token), we reject with 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user instanceof Customer) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden. This endpoint requires a customer account.',
                'data' => null,
            ], 403);
        }

        if (!$user->is_active) {
            return response()->json([
                'status' => false,
                'message' => 'This customer account is inactive.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}
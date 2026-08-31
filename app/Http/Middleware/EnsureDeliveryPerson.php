<?php

namespace App\Http\Middleware;

use App\Models\DeliveryPerson;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDeliveryPerson
{
    /**
     * Ensure the authenticated token belongs to a DeliveryPerson.
     *
     * Laravel resolves the authenticated account from the token (not from any
     * role sent by the app). If the token does not belong to a delivery person
     * (for example a customer token), we reject with 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user instanceof DeliveryPerson) {
            return response()->json([
                'status' => false,
                'message' => 'Forbidden. This endpoint requires a delivery person account.',
                'data' => null,
            ], 403);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'status' => false,
                'message' => 'This delivery person account is inactive.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}

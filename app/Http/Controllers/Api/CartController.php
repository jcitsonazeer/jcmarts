<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ApiCartService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class CartController extends Controller
{
    public function __construct(
        protected ApiCartService $apiCartService
    ) {
    }

    /**
     * Resolve the session ID and identity from the request.
     *
     * - If the user is logged in (Sanctum token), use customer_{id}
     * - If not logged in, require X-Device-ID header and use device_{uuid}
     *
     * Cart routes are outside auth:sanctum middleware so guests can access them.
     * This method manually resolves the user from the Bearer token if present.
     */
    private function resolveSession(Request $request): array
    {
        $user = $request->user();

        // If auth:sanctum middleware resolved the user, use it directly
        if (!$user) {
            // Cart routes are outside auth:sanctum — manually check for a Bearer token
            $token = $request->bearerToken();
            if ($token) {
                $personalAccessToken = PersonalAccessToken::findToken($token);
                if ($personalAccessToken) {
                    $user = $personalAccessToken->tokenable;
                }
            }
        }

        if ($user) {
            return [
                'session_id' => $this->apiCartService->buildSessionId($user->id, null),
                'is_guest' => false,
            ];
        }

        $deviceId = $request->header('X-Device-ID');

        if (!$deviceId || trim($deviceId) === '') {
            return [
                'session_id' => null,
                'is_guest' => true,
                'error' => 'X-Device-ID header is required for guest cart operations.',
            ];
        }

        return [
            'session_id' => $this->apiCartService->buildSessionId(null, $deviceId),
            'is_guest' => true,
        ];
    }

    /**
     * GET /api/v1/cart
     * List all cart items. Works for both guests and authenticated users.
     */
    public function index(Request $request)
    {
        $resolved = $this->resolveSession($request);

        if (isset($resolved['error'])) {
            return response()->json([
                'status' => false,
                'message' => $resolved['error'],
                'data' => null,
            ], 401);
        }

        $items = $this->apiCartService->getActiveItems($resolved['session_id']);

        // Add full image URLs to each cart item's product
        $items->each(function ($item) {
            if ($item->product && $item->product->product_image) {
                $item->product->product_image = asset('storage/product/' . $item->product->product_image);
            }
        });

        $subTotal = $this->apiCartService->getSubTotal($resolved['session_id']);
        $itemCount = $this->apiCartService->getItemCount($resolved['session_id']);

        return response()->json([
            'status' => true,
            'message' => 'Cart items fetched successfully',
            'data' => [
                'items' => $items,
                'item_count' => $itemCount,
                'sub_total' => number_format($subTotal, 2, '.', ''),
            ],
        ]);
    }

    /**
     * POST /api/v1/cart
     * Add an item to the cart. Works for both guests and authenticated users.
     *
     * Required payload:
     *   - product_id (integer)
     *   - rate_master_id (integer)
     *   - quantity (integer, optional, default: 1)
     *
     * Headers:
     *   - X-Device-ID (required for guests, ignored if authenticated)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'rate_master_id' => ['required', 'integer', 'exists:rate_master,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $resolved = $this->resolveSession($request);

        if (isset($resolved['error'])) {
            return response()->json([
                'status' => false,
                'message' => $resolved['error'],
                'data' => null,
            ], 401);
        }

        $quantity = (int) ($validated['quantity'] ?? 1);

        try {
            $cartItem = $this->apiCartService->addItem(
                $resolved['session_id'],
                (int) $validated['product_id'],
                (int) $validated['rate_master_id'],
                $quantity
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid product or rate variant. Please check your selection.',
                'data' => null,
            ], 422);
        }

        // Reload with relationships for the response
        $cartItem->load([
            'product:id,product_name,product_image',
            'rate:id,uom_id,final_price,selling_price',
            'rate.uom:id,primary_uom,secondary_uom',
        ]);

        if ($cartItem->product && $cartItem->product->product_image) {
            $cartItem->product->product_image = asset('storage/product/' . $cartItem->product->product_image);
        }

        $itemCount = $this->apiCartService->getItemCount($resolved['session_id']);
        $subTotal = $this->apiCartService->getSubTotal($resolved['session_id']);

        return response()->json([
            'status' => true,
            'message' => 'Item added to cart successfully',
            'data' => [
                'cart_item' => $cartItem,
                'item_count' => $itemCount,
                'sub_total' => number_format($subTotal, 2, '.', ''),
            ],
        ], 201);
    }

    /**
     * PUT /api/v1/cart/{cartId}
     * Update the quantity of a cart item.
     */
    public function update(Request $request, int $cartId)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $resolved = $this->resolveSession($request);

        if (isset($resolved['error'])) {
            return response()->json([
                'status' => false,
                'message' => $resolved['error'],
                'data' => null,
            ], 401);
        }

        try {
            $this->apiCartService->updateQuantity(
                $resolved['session_id'],
                $cartId,
                (int) $validated['quantity']
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found.',
                'data' => null,
            ], 404);
        }

        $itemCount = $this->apiCartService->getItemCount($resolved['session_id']);
        $subTotal = $this->apiCartService->getSubTotal($resolved['session_id']);

        return response()->json([
            'status' => true,
            'message' => 'Cart quantity updated successfully',
            'data' => [
                'item_count' => $itemCount,
                'sub_total' => number_format($subTotal, 2, '.', ''),
            ],
        ]);
    }

    /**
     * DELETE /api/v1/cart/{cartId}
     * Remove an item from the cart.
     */
    public function destroy(Request $request, int $cartId)
    {
        $resolved = $this->resolveSession($request);

        if (isset($resolved['error'])) {
            return response()->json([
                'status' => false,
                'message' => $resolved['error'],
                'data' => null,
            ], 401);
        }

        try {
            $this->apiCartService->removeItem($resolved['session_id'], $cartId);
        } catch (ModelNotFoundException) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found.',
                'data' => null,
            ], 404);
        }

        $itemCount = $this->apiCartService->getItemCount($resolved['session_id']);
        $subTotal = $this->apiCartService->getSubTotal($resolved['session_id']);

        return response()->json([
            'status' => true,
            'message' => 'Item removed from cart successfully',
            'data' => [
                'item_count' => $itemCount,
                'sub_total' => number_format($subTotal, 2, '.', ''),
            ],
        ]);
    }

    /**
     * GET /api/v1/cart/count
     * Get total item count in the cart.
     */
    public function count(Request $request)
    {
        $resolved = $this->resolveSession($request);

        if (isset($resolved['error'])) {
            return response()->json([
                'status' => false,
                'message' => $resolved['error'],
                'data' => null,
            ], 401);
        }

        $itemCount = $this->apiCartService->getItemCount($resolved['session_id']);

        return response()->json([
            'status' => true,
            'message' => 'Cart count fetched successfully',
            'data' => [
                'item_count' => $itemCount,
            ],
        ]);
    }

    /**
     * POST /api/v1/cart/merge
     * Merge guest cart into the authenticated customer's cart.
     * Requires: auth:sanctum + X-Device-ID header
     *
     * After login, the Flutter app calls this to transfer the guest cart
     * into the customer's cart. Duplicate items get their quantities added.
     */
    public function merge(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Authentication required to merge cart.',
                'data' => null,
            ], 401);
        }

        $deviceId = $request->header('X-Device-ID');

        if (!$deviceId || trim($deviceId) === '') {
            // No guest cart to merge — just return the customer's current cart
            $customerSessionId = $this->apiCartService->buildSessionId($user->id, null);
            $items = $this->apiCartService->getActiveItems($customerSessionId);

            $items->each(function ($item) {
                if ($item->product && $item->product->product_image) {
                    $item->product->product_image = asset('storage/product/' . $item->product->product_image);
                }
            });

            return response()->json([
                'status' => true,
                'message' => 'No guest cart to merge. Returning current cart.',
                'data' => [
                    'items' => $items,
                    'item_count' => $this->apiCartService->getItemCount($customerSessionId),
                    'sub_total' => number_format($this->apiCartService->getSubTotal($customerSessionId), 2, '.', ''),
                ],
            ]);
        }

        $guestSessionId = $this->apiCartService->buildSessionId(null, $deviceId);
        $customerSessionId = $this->apiCartService->buildSessionId($user->id, null);

        $mergedItems = $this->apiCartService->mergeGuestCart($guestSessionId, $customerSessionId);

        // Add full image URLs
        $mergedItems->each(function ($item) {
            if ($item->product && $item->product->product_image) {
                $item->product->product_image = asset('storage/product/' . $item->product->product_image);
            }
        });

        return response()->json([
            'status' => true,
            'message' => 'Guest cart merged successfully',
            'data' => [
                'items' => $mergedItems,
                'item_count' => $this->apiCartService->getItemCount($customerSessionId),
                'sub_total' => number_format($this->apiCartService->getSubTotal($customerSessionId), 2, '.', ''),
            ],
        ]);
    }
}

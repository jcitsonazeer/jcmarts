<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ApiWishlistService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function __construct(
        protected ApiWishlistService $apiWishlistService
    ) {
    }

    /**
     * GET /api/v1/wishlist
     * List all wishlist items for the authenticated customer.
     */
    public function index(Request $request)
    {
        $customerId = $request->user()->id;

        $items = $this->apiWishlistService->getActiveItems($customerId);

        // Add full image URLs to each wishlist item's product
        $items->each(function ($item) {
            if ($item->product && $item->product->product_image) {
                $item->product->product_image = asset('storage/product/' . $item->product->product_image);
            }
        });

        $itemCount = $this->apiWishlistService->getItemCount($customerId);

        return response()->json([
            'status' => true,
            'message' => 'Wishlist items fetched successfully',
            'data' => [
                'items' => $items,
                'item_count' => $itemCount,
            ],
        ]);
    }

    /**
     * POST /api/v1/wishlist
     * Add a product to the wishlist.
     *
     * Required payload:
     *   - product_id (integer)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $customerId = $request->user()->id;

        try {
            $wishlist = $this->apiWishlistService->add($customerId, (int) $validated['product_id']);
        } catch (ModelNotFoundException) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found or is inactive.',
                'data' => null,
            ], 422);
        }

        $itemCount = $this->apiWishlistService->getItemCount($customerId);

        return response()->json([
            'status' => true,
            'message' => 'Product added to wishlist successfully',
            'data' => [
                'wishlist_item' => [
                    'id' => $wishlist->id,
                    'product_id' => $wishlist->product_id,
                ],
                'item_count' => $itemCount,
            ],
        ], 201);
    }

    /**
     * DELETE /api/v1/wishlist/{productId}
     * Remove a product from the wishlist.
     */
    public function destroy(Request $request, int $productId)
    {
        $customerId = $request->user()->id;

        $this->apiWishlistService->remove($customerId, $productId);

        $itemCount = $this->apiWishlistService->getItemCount($customerId);

        return response()->json([
            'status' => true,
            'message' => 'Product removed from wishlist successfully',
            'data' => [
                'item_count' => $itemCount,
            ],
        ]);
    }

    /**
     * POST /api/v1/wishlist/toggle
     * Toggle a product in/out of the wishlist.
     *
     * Required payload:
     *   - product_id (integer)
     */
    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $customerId = $request->user()->id;

        $isAdded = $this->apiWishlistService->toggle($customerId, (int) $validated['product_id']);

        $itemCount = $this->apiWishlistService->getItemCount($customerId);

        return response()->json([
            'status' => true,
            'message' => $isAdded
                ? 'Product added to wishlist'
                : 'Product removed from wishlist',
            'data' => [
                'is_wishlisted' => $isAdded,
                'item_count' => $itemCount,
            ],
        ]);
    }

    /**
     * GET /api/v1/wishlist/check/{productId}
     * Check if a product is in the wishlist.
     */
    public function check(Request $request, int $productId)
    {
        $customerId = $request->user()->id;

        $isInWishlist = $this->apiWishlistService->isInWishlist($customerId, $productId);

        return response()->json([
            'status' => true,
            'message' => 'Wishlist status checked successfully',
            'data' => [
                'is_wishlisted' => $isInWishlist,
            ],
        ]);
    }

    /**
     * GET /api/v1/wishlist/count
     * Get total wishlist item count.
     */
    public function count(Request $request)
    {
        $customerId = $request->user()->id;
        $itemCount = $this->apiWishlistService->getItemCount($customerId);

        return response()->json([
            'status' => true,
            'message' => 'Wishlist count fetched successfully',
            'data' => [
                'item_count' => $itemCount,
            ],
        ]);
    }
}

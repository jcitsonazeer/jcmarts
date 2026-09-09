<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PromoTileService;
use Illuminate\Http\JsonResponse;

class PromoTileController extends Controller
{
    protected $promoTileService;

    public function __construct(PromoTileService $promoTileService)
    {
        $this->promoTileService = $promoTileService;
    }

    public function index(): JsonResponse
    {
        $promoTiles = $this->promoTileService->getActivePromoTilesForApi();

        $promoTiles->transform(function ($promoTile) {
            $promoTile->promo_image = $promoTile->promo_image
                ? asset('storage/promo_tile/' . $promoTile->promo_image)
                : null;

            foreach ($promoTile->products as $product) {
                $product->product_image = $product->product_image
                    ? asset('storage/product/' . $product->product_image)
                    : null;
            }

            return $promoTile;
        });

        return response()->json([
            'status' => true,
            'message' => 'Promo tiles fetched successfully',
            'data' => $promoTiles,
        ]);
    }
}

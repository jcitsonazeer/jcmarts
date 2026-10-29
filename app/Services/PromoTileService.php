<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PromoTile;
use App\Models\PromoTileProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PromoTileService
{
    public function getAll()
    {
        return PromoTile::query()
            ->select([
                'id',
                'promo_title',
                'promo_image',
                'sort_order',
                'is_active',
                'created_by_id',
                'created_date',
                'updated_by_id',
                'updated_date',
            ])
            ->with(['createdBy', 'updatedBy', 'products:id,product_name'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function findForEdit($id)
    {
        return PromoTile::with(['createdBy', 'updatedBy'])->findOrFail($id);
    }

    public function findForProductManagement($id)
    {
        return PromoTile::findOrFail($id);
    }

    public function getProductsForDropdown()
    {
        return Product::query()
            ->where('is_active', 1)
            ->whereHas('rates', function ($query) {
                $query->where('is_active', 1);
            })
            ->orderBy('product_name')
            ->get();
    }

    public function findProductIdByName(string $productName): ?int
    {
        $productName = trim($productName);

        if ($productName === '') {
            return null;
        }

        $product = Product::query()
            ->where('is_active', 1)
            ->whereRaw('LOWER(product_name) = ?', [Str::lower($productName)])
            ->first(['id']);

        return $product ? (int) $product->id : null;
    }

    public function create(array $data, int $adminId)
    {
        $imageName = null;
        if (!empty($data['promo_image'])) {
            $imageName = $this->storePromoImage($data['promo_image']);
        }

        return PromoTile::create([
            'promo_title' => $data['promo_title'],
            'promo_image' => $imageName,
            'sort_order' => (int) $data['sort_order'],
            'is_active' => $data['is_active'],
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function update(int $id, array $data, int $adminId)
    {
        $promoTile = PromoTile::findOrFail($id);

        $imageName = $promoTile->promo_image;
        if (!empty($data['promo_image'])) {
            $imageName = $this->storePromoImage($data['promo_image']);

            if (!empty($promoTile->promo_image)) {
                Storage::disk('public')->delete('promo_tile/' . $promoTile->promo_image);
            }
        }

        $promoTile->update([
            'promo_title' => $data['promo_title'],
            'promo_image' => $imageName,
            'sort_order' => (int) $data['sort_order'],
            'is_active' => $data['is_active'],
            'updated_by_id' => $adminId,
            'updated_date' => Carbon::now(),
        ]);

        return $promoTile;
    }

    public function delete(int $id)
    {
        $promoTile = PromoTile::findOrFail($id);

        PromoTileProduct::query()
            ->where('promo_tile_id', $id)
            ->delete();

        if (!empty($promoTile->promo_image)) {
            Storage::disk('public')->delete('promo_tile/' . $promoTile->promo_image);
        }

        $promoTile->delete();
    }

    public function getActiveTiles()
    {
        return PromoTile::query()
            ->select([
                'id',
                'promo_title',
                'promo_image',
                'sort_order',
                'is_active',
            ])
            ->where('is_active', 1)
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }

    public function getActivePromoTilesForApi()
    {
        return PromoTile::query()
            ->where('is_active', 1)
            ->withCount('products')
            ->with(['products' => function ($query) {
                $query->select([
                    'products.id',
                    'products.sub_category_id',
                    'products.brand_id',
                    'products.product_name',
                    'products.product_image',
                    'products.is_active',
                ])
                    ->where('products.is_active', 1)
                    ->whereHas('rates', function ($rateQuery) {
                        $rateQuery->where('is_active', 1);
                    })
                    ->with([
                        'brand:id,brand_name',
                        'rates' => function ($rateQuery) {
                            $rateQuery->select([
                                'id',
                                'product_id',
                                'uom_id',
                                'selling_price',
                                'offer_percentage',
                                'offer_price',
                                'final_price',
                                'soldout_status',
                                'stock_dependent',
                                'is_active',
                                'selected_display',
                            ])
                                ->where('is_active', 1)
                                ->with(['uom:id,primary_uom,secondary_uom'])
                                ->orderByDesc('selected_display')
                                ->orderBy('id');
                        },
                    ]);
            }])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }

    public function getLinkedProducts($tileId)
    {
        return PromoTileProduct::query()
            ->with('product')
            ->where('promo_tile_id', $tileId)
            ->whereHas('product', function ($query) {
                $query->where('is_active', 1);
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    public function getLinkableProducts(int $tileId)
    {
        $linkedProductIds = PromoTileProduct::query()
            ->where('promo_tile_id', $tileId)
            ->pluck('product_id');

        return Product::query()
            ->where('is_active', 1)
            ->whereHas('rates', function ($query) {
                $query->where('is_active', 1);
            })
            ->whereNotIn('id', $linkedProductIds)
            ->orderBy('product_name')
            ->get();
    }

    public function existsDuplicate(int $tileId, int $productId): bool
    {
        return PromoTileProduct::query()
            ->where('promo_tile_id', $tileId)
            ->where('product_id', $productId)
            ->exists();
    }

    public function attachProduct(int $tileId, int $productId, int $adminId)
    {
        return PromoTileProduct::create([
            'promo_tile_id' => $tileId,
            'product_id' => $productId,
            'created_by_id' => $adminId,
            'created_date' => Carbon::now(),
        ]);
    }

    public function detachProduct(int $tileId, int $productId)
    {
        PromoTileProduct::query()
            ->where('promo_tile_id', $tileId)
            ->where('product_id', $productId)
            ->delete();
    }

    private function storePromoImage($image)
    {
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $originalName);
        $sanitizedName = trim((string) $sanitizedName, '_');
        $sanitizedName = $sanitizedName !== '' ? $sanitizedName : 'promo_tile';

        // Derive the extension from the detected MIME type, never from the client.
        $extension = strtolower((string) $image->extension());
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            $extension = 'jpg';
        }

        // For static images, re-encode with GD to strip embedded metadata/EXIF.
        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            $sourcePath = $image->getRealPath();
            if (!$sourcePath) {
                throw new \RuntimeException('Unable to read the uploaded image.');
            }

            $imageInformation = \getimagesize($sourcePath);
            if ($imageInformation === false) {
                throw new \RuntimeException('Unable to read image size.');
            }

            [$sourceWidth, $sourceHeight, $imageType] = $imageInformation;

            $sourceImage = null;
            if ($imageType === IMAGETYPE_JPEG && \function_exists('imagecreatefromjpeg')) {
                $sourceImage = \imagecreatefromjpeg($sourcePath);
            } elseif ($imageType === IMAGETYPE_PNG && \function_exists('imagecreatefrompng')) {
                $sourceImage = \imagecreatefrompng($sourcePath);
            }

            if (!$sourceImage) {
                throw new \RuntimeException('Only JPG, JPEG, and PNG images are allowed.');
            }

            if ($imageType === IMAGETYPE_PNG) {
                $extension = 'png';
            } else {
                $extension = 'jpg';
            }

            $fileName = $sanitizedName . '_' . time() . '_' . uniqid() . '.' . $extension;

            \ob_start();
            if ($imageType === IMAGETYPE_PNG) {
                \imagepng($sourceImage);
            } else {
                \imagejpeg($sourceImage, null, 90);
            }
            $imageContents = (string) \ob_get_clean();

            \imagedestroy($sourceImage);

            Storage::disk('public')->put('promo_tile/' . $fileName, $imageContents);

            return $fileName;
        }

        // GIF (and any other allowed type) is stored with its guessed extension.
        $fileName = $sanitizedName . '_' . time() . '_' . uniqid() . '.' . $extension;
        $image->storeAs('promo_tile', $fileName, 'public');

        return $fileName;
    }
}
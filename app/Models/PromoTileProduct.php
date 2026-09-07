<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoTileProduct extends Model
{
    protected $table = 'promo_tile_products';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'promo_tile_id',
        'product_id',
        'created_by_id',
        'created_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
    ];

    public function promo_tile()
    {
        return $this->belongsTo(PromoTile::class, 'promo_tile_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PromoTile extends Model
{
    protected $table = 'promo_tiles';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'promo_title',
        'promo_image',
        'sort_order',
        'is_active',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(AdminLogin::class, 'created_by_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(AdminLogin::class, 'updated_by_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promo_tile_products', 'promo_tile_id', 'product_id');
    }
}
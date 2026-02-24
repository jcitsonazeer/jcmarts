<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $primaryKey = 'id';

    public $timestamps = false; // Because you are using created_date / updated_date

    protected $fillable = [
        'subproduct_id',
        'product_name',
        'product_image',
        'single_image_1',
        'single_image_2',
        'single_image_3',
        'single_image_4',
        'description',
        'warranty_info',
        'is_active',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
        'is_active'    => 'boolean',
    ];

    /**
     * Product belongs to SubCategory
     */
    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'subproduct_id');
    }

    /**
     * Product created by admin
     */
    public function createdBy()
    {
        return $this->belongsTo(AdminLogin::class, 'created_by_id');
    }

    /**
     * Product last updated by admin
     */
    public function updatedBy()
    {
        return $this->belongsTo(AdminLogin::class, 'updated_by_id');
    }

    /**
     * Product has many Rate Variants
     */
    public function rates()
    {
        return $this->hasMany(RateMaster::class, 'product_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $table = 'brands';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'brand_name',
        'is_active',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(AdminLogin::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(AdminLogin::class, 'updated_by_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}

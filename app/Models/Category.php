<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'category';

    protected $primaryKey = 'id';

    public $timestamps = false; // No created_at / updated_at

    protected $fillable = [
        'category_name',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

    /**
     * Category created by admin
     */
    public function createdBy()
    {
        return $this->belongsTo(AdminLogin::class, 'created_by_id');
    }

    /**
     * Category last updated by admin
     */
    public function updatedBy()
    {
        return $this->belongsTo(AdminLogin::class, 'updated_by_id');
    }
    
public function subCategories()
{
    return $this->hasMany(SubCategory::class, 'category_id');
}

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexBanner extends Model
{
    protected $table = 'index_banner';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'banner_image',
        'created_by_id',
        'created_date',
        'updated_by_id',
        'updated_date',
    ];

    protected $casts = [
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

    public function createdBy()
    {
        return $this->belongsTo(AdminLogin::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(AdminLogin::class, 'updated_by_id');
    }
}

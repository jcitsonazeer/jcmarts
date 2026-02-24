<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLogin extends Model
{
    protected $table = 'admin_login';

    protected $primaryKey = 'id';

    public $timestamps = false; // because we are NOT using created_at & updated_at

    protected $fillable = [
        'admin_username',
        'admin_password',
        'user_type',
        'cur_status',
        'created_date',
        'active_session_id',
        'last_login_at',
    ];

    protected $hidden = [
        'admin_password',
    ];

    protected $casts = [
        'created_date'   => 'datetime',
        'last_login_at'  => 'datetime',
    ];
}

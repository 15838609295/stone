<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Members extends Model
{
    protected $table='members';

    protected $fillable = [
        'id', 'nickname', 'openid','pic', 'mobile','company_id','is_admin','status','created_at','updated_at'
    ];
}
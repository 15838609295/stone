<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $table='collection';

    protected $fillable = [
        'id', 'u_id', 'g_id','created_at'
    ];
}
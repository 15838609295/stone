<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class GoodsAttr extends Model
{
    protected $table='goods_attr';

    protected $fillable = [
        'id', 'goods_attr_name', 'updated_at','created_at'
    ];
}
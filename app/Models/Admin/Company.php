<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table='company';

    protected $fillable = [
        'id', 'company_name', 'company_number','uid', 'company_pass','updated_at','created_at'
    ];

    public function godownAttr(){
        return $this->hasOne(GoodsAttr::class, 'company_id', 'id');
    }
}
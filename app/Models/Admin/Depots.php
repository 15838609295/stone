<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Depots extends Model
{
    protected $table='depots';

    protected $fillable = [
        'id', 'depot_name', 'updated_at','created_at'
    ];
}
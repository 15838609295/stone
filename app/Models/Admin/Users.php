<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
    protected $table='users';

    protected $fillable = [
        'id', 'name', 'email','password', 'openid','updated_at','created_at'
    ];

}
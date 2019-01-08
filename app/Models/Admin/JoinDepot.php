<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class JoinDepot extends Model
{
    protected $table='joindepot';
  
  	protected $guarded = [];

    public function getGodownPicAttribute($value){
        if(!empty($value)){
            return explode(',', $value);
        }
        return '';
    }

    public function setGodownPicAttribute($value)
    {
        if(is_array($value)){
            $this->attributes['godown_pic'] = implode(',', array_filter($value));
        }
    }
}
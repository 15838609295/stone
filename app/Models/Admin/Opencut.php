<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Opencut extends Model
{
    protected $table='opencut';

  	protected $guarded = [];
  
    public function getOldGodownPicAttribute($value){
        if(!empty($value)){
            return explode(',', $value);
        }
        return '';
    }

    public function setOldGodownPicAttribute($value)
    {
        if(is_array($value)){
            $this->attributes['old_godown_pic'] = implode(',', array_filter($value));
        }
    }

    public function getNewGodownPicAttribute($value){
        if(!empty($value)){
            return explode(',', $value);
        }
        return '';
    }

    public function setNewGodownPicAttribute($value)
    {
        if(is_array($value)){
            return $this->attributes['new_godown_pic'] = implode(',', array_filter($value));
        }
    }
}
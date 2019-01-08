<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class Godown extends Model
{
    protected $table='godown';
    protected $primaryKey = 'id';

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
  
    public function sale(){
        return $this->hasMany(Sale::class,'godown_id','id');
    }

    public function goodsattr(){
        return $this->hasOne(GoodsAttr::class,'id','goods_attr_id');
    }

   
}
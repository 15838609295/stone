<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/1
 * Time: 18:42
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Company;
use App\Models\Admin\Godown;
use App\Models\Admin\GoodsAttr;
use Illuminate\Http\Request;

class ImageController extends Controller
{
    public function index(Request $request, $id){
        $data['id'] = $id;
        $rows = Godown::from('godown as g')
            ->select('g.*','ga.goods_attr_name')
            ->join('goods_attr as ga','ga.id','=','g.goods_attr_id')
            ->where('ga.company_id','=',$id);

        if(trim($request->type) != ''&& $request->type != 2){
            $rows->where('g.type','=',$request->type);
        }else{
            $request->type = 2 ;
        }

        if(trim($request->goods_attr_name) != ''){
            $rows->where('ga.goods_attr_name','=',$request->goods_attr_name);
        }

        $data['data'] = $rows->orderBy('g.id','desc') ->get();

        $data['type'] = $request->type;
        $data['goods_attr_name'] = $request->goods_attr_name;
        $data['goodsattr'] = GoodsAttr::where('company_id','=',$id)->groupBy('goods_attr_name')->get();
        $data['name'] = Company::find($id)->company_name;
        return view('admin.company.images',$data);
    }
}
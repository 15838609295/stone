<?php
	
namespace App\Http\Controllers\Admin;

use App\Models\Admin\AdminUser;
use Illuminate\Http\Request;
use App\Models\Admin\GoodsAttr;

use App\Http\Controllers\Controller;

class GoodsAttrController extends Controller{
	
	public $result = array("status"=>0,'msg'=>'请求成功','data'=>"");
	//企业列表
	public function index(Request $request){
		if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
			$sortOrder = $request->post("sortOrder");   //排序（desc，asc）
			$pageNumber = $request->post("pageNumber");  //当前页码
			$pageSize = $request->post("pageSize");   //一页显示的条数
			$start = ($pageNumber-1)*$pageSize;   //开始位置
			$search = $request->post("search",'');  //搜索条件
			
			$total = GoodsAttr::from('goods_attr as ga')
					->select('ga.*','c.company_name')
					->leftJoin('company as c','ga.company_id','=','c.id');
			$rows = GoodsAttr::from('goods_attr as ga')
					->select('ga.*','c.company_name')
					->leftJoin('company as c','ga.company_id','=','c.id');
			
	        if(trim($search)){
	        	$total->where(function ($query) use ($search) {
                    $query->where('ga.goods_attr_name', 'LIKE', '%' . $search . '%')
                    ->orwhere('c.company_name', 'LIKE', '%' . $search . '%');
                });
	        	$rows->where(function ($query) use ($search) {
                    $query->where('ga.goods_attr_name', 'LIKE', '%' . $search . '%')
                    ->orwhere('c.company_name', 'LIKE', '%' . $search . '%');
                });
	        }
	        
	        $data['total'] = $total->count();
	        $data['rows'] = $rows->skip($start)->take($pageSize)
					        ->orderBy($sortName, $sortOrder)
					        ->get();
	        
	        return response()->json($data);
        }
        return view('admin.goodsattr.index');
	}
}

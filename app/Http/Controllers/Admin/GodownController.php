<?php
	
namespace App\Http\Controllers\Admin;

use App\Models\Admin\AdminUser;
use Illuminate\Http\Request;
use App\Models\Admin\JoinDepot;

use App\Http\Controllers\Controller;

class GodownController extends Controller{
	
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
			
			$total = JoinDepot::from('joindepot as g')
					->select('g.*','ga.goods_attr_name','d.depot_name','c.company_name')
					->leftJoin('goods_attr as ga','ga.id','=','g.goods_attr_id')
					->leftJoin('depots as d','d.id','=','g.depot_id')
					->leftJoin('company as c','c.id','=','d.company_id');
					
			$rows = JoinDepot::from('joindepot as g')
					->select('g.*','ga.goods_attr_name','d.depot_name','c.company_name')
					->leftJoin('goods_attr as ga','ga.id','=','g.goods_attr_id')
					->leftJoin('depots as d','d.id','=','g.depot_id')
					->leftJoin('company as c','c.id','=','d.company_id');
			
	        if(trim($search)){
	        	$total->where(function ($query) use ($search) {
                    $query->where('c.company_name', 'LIKE', '%' . $search . '%')
                        ->orWhere('d.depot_name', 'like', '%' . $search . '%')
                        ->orWhere('ga.goods_attr_name', 'like', '%' . $search . '%');
                });
	        	$rows->where(function ($query) use ($search) {
                    $query->where('c.company_name', 'LIKE', '%' . $search . '%')
                        ->orWhere('d.depot_name', 'like', '%' . $search . '%')
                        ->orWhere('ga.goods_attr_name', 'like', '%' . $search . '%');
                });
	        }
	        
	        $data['total'] = $total->count();
	        $data['rows'] = $rows->skip($start)->take($pageSize)
					        ->orderBy($sortName, $sortOrder)
					        ->get();
	        
	        return response()->json($data);
        }
        return view('admin.godown.index');
	}
	
}

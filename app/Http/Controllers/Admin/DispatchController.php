<?php
	
namespace App\Http\Controllers\Admin;

use App\Models\Admin\AdminUser;
use Illuminate\Http\Request;
use App\Models\Admin\Dispatch;

use App\Http\Controllers\Controller;

class DispatchController extends Controller{
	
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
			
			$total = Dispatch::from('dispatch as dd')
					->select('dd.*','d.depot_name as old_depot_name','d2.depot_name as new_depot_name','c.company_name','g.godown_no')
					->leftJoin('depots as d','d.id','=','dd.old_depot_id')
					->leftJoin('depots as d2','d.id','=','dd.new_depot_id')
					->leftJoin('company as c','c.id','=','d.company_id')
					->leftJoin('godown as g','g.id','=','dd.godown_id');
					
			$rows = Dispatch::from('dispatch as dd')
					->select('dd.*','d.depot_name as old_depot_name','d2.depot_name as new_depot_name','c.company_name','g.godown_no')
					->leftJoin('depots as d','d.id','=','dd.old_depot_id')
					->leftJoin('depots as d2','d2.id','=','dd.new_depot_id')
					->leftJoin('company as c','c.id','=','d.company_id')
					->leftJoin('godown as g','g.id','=','dd.godown_id');
			
	        if(trim($search)){
	        	$total->where(function ($query) use ($search) {
                    $query->where('c.company_name', 'LIKE', '%' . $search . '%')
                        ->orWhere('d.depot_name', 'like', '%' . $search . '%');
                });
	        	$rows->where(function ($query) use ($search) {
                    $query->where('c.company_name', 'LIKE', '%' . $search . '%')
                        ->orWhere('d.depot_name', 'like', '%' . $search . '%');
                });
	        }
	        
	        $data['total'] = $total->count();
	        $data['rows'] = $rows->skip($start)->take($pageSize)
					        ->orderBy($sortName, $sortOrder)
					        ->get();
	        
	        return response()->json($data);
        }
        return view('admin.dispatch.index');
	}
	
}

<?php
	
namespace App\Http\Controllers\Admin;

use App\Models\Admin\AdminUser;
use Illuminate\Http\Request;
use App\Models\Admin\Company;
use App\Models\Admin\Members;

use App\Http\Controllers\Controller;

class MembersController extends Controller{
	
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
			
			$total = Members::from('members as m')
					->select('m.*','c.company_name')
					->leftJoin('company as c','c.id','=','m.company_id');
			$rows = Members::from('members as m')
					->select('m.*','c.company_name')
					->leftJoin('company as c','c.id','=','m.company_id');
			
	        if(trim($search)){
	        	$total->where(function ($query) use ($search) {
                    $query->where('realname', 'LIKE', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%');
                });
	        	$rows->where(function ($query) use ($search) {
                    $query->where('realname', 'LIKE', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%');
                });
	        }
	        
	        $data['total'] = $total->count();
	        $data['rows'] = $rows->skip($start)->take($pageSize)
					        ->orderBy($sortName, $sortOrder)
					        ->get();
	        
	        return response()->json($data);
        }
        return view('admin.members.index');
	}
}

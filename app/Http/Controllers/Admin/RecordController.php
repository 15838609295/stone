<?php
	
namespace App\Http\Controllers\Admin;

use App\Models\Admin\AdminUser;
use Illuminate\Http\Request;
use App\Models\Admin\Worklog;
use App\Models\Admin\PaymentLog;
use App\Models\Admin\CompanyUser;
use App\Models\Admin\Members;

use App\Http\Controllers\Controller;

class RecordController extends Controller {
	
	public $result = array("status"=>0,'msg'=>'请求成功','data'=>"");

	/**
     * 充值记录
     */
	public function rechargeList(Request $request) {
		if ($request->ajax()) {
            $sortName = $request->post("sortName");    // 排序列名
			$sortOrder = $request->post("sortOrder");   // 排序（desc，asc）
			$pageNumber = $request->post("pageNumber");  // 当前页码
			$pageSize = $request->post("pageSize");   // 一页显示的条数
			$start = ($pageNumber-1)*$pageSize;   // 开始位置
			$search = $request->post("search",'');  // 搜索条件
			
			$total = PaymentLog::from('payment_log as pl')
					->select('pl.*', 'c.company_name', 'm.realname as opername')
					->leftJoin('company as c', 'c.id', '=', 'pl.company_id')
					->leftJoin('members as m', 'm.id', '=', 'pl.user_id');
			$rows = PaymentLog::from('payment_log as pl')
					->select('pl.*', 'c.company_name', 'm.realname as opername')
					->leftJoin('company as c', 'c.id', '=', 'pl.company_id')
					->leftJoin('members as m', 'm.id', '=', 'pl.user_id');
			
	        if (trim($search)) {
	        	$total->where(function ($query) use ($search) {
                    $query->where('c.company_name', 'LIKE', '%' . $search . '%');
                });
	        	$rows->where(function ($query) use ($search) {
                    $query->where('c.company_name', 'LIKE', '%' . $search . '%');
                });
	        }
	        
	        $data['total'] = $total->count();
	        $lists = $rows->skip($start)->take($pageSize)
					        ->orderBy($sortName, $sortOrder)
					        ->get();
			if ($lists) {
				foreach ($lists as $k => &$v) {
					$cu = CompanyUser::where('company_id', $v->company_id)->first();
					if (! $cu) {
						$v->realname = '';
					} else {
						$v->realname = Members::where('id', $cu->user_id)->value('realname');
					}
				}
			}
			$data['rows'] = $lists;
	        return response()->json($data);
        }
        return view('admin.record.recharge');
	}

	/**
     * 使用记录
     */
	public function applyList(Request $request) {
		if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
			$sortOrder = $request->post("sortOrder");   //排序（desc，asc）
			$pageNumber = $request->post("pageNumber");  //当前页码
			$pageSize = $request->post("pageSize");   //一页显示的条数
			$start = ($pageNumber-1)*$pageSize;   //开始位置
			$search = $request->post("search",'');  //搜索条件
			
			$total = Worklog::from('work_log as wl')
					->select('wl.*', 'c.company_name', 'm.realname', 'c.is_admin')
					->leftJoin('company as c', 'c.id', '=', 'wl.company_id')
					->leftJoin('members as m', 'm.id', '=', 'wl.user_id');
			$rows = Worklog::from('work_log as wl')
					->select('wl.*', 'c.company_name', 'm.realname', 'c.is_admin')
					->leftJoin('company as c', 'c.id', '=', 'wl.company_id')
					->leftJoin('members as m', 'm.id', '=', 'wl.user_id');
			
	        if (trim($search)) {
	        	$total->where(function ($query) use ($search) {
                    $query->where('wl.title', 'LIKE', '%' . $search . '%')
                    ->orwhere('c.company_name', 'LIKE', '%' . $search . '%');
                });
	        	$rows->where(function ($query) use ($search) {
                    $query->where('wl.title', 'LIKE', '%' . $search . '%')
                    ->orwhere('c.company_name', 'LIKE', '%' . $search . '%');
                });
	        }
	        
	        $data['total'] = $total->count();
	        $lists = $rows->skip($start)->take($pageSize)
					        ->orderBy($sortName, $sortOrder)
					        ->get();
			if ($lists) {
				foreach ($lists as $k => &$v) {
					$v->identity = CompanyUser::IS_ADMIN[$v->is_admin];
				}
			}
			$data['rows'] = $lists;
	        return response()->json($data);
        }
        return view('admin.record.apply');
	}

}

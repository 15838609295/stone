<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/2
 * Time: 14:33
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminLog;
use App\Models\Admin\Company;
use App\Models\Admin\CompanyUser;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class OperateController extends Controller
{

    /**
     * 用户列表
     */
    public function user (Request $request) {
        if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $search = $request->post("search",'');  //搜索条件

            $sql = "select m.id,m.realname,cu.company_id,cu.user_id,c.company_name,m.mobile,cu.join_time,cu.login_time,cu.is_admin";
            $sql .= ",(select count(id) from admin_log as al where c.id=al.company_id and cu.user_id = al.user_id and type = 0 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m')) as cur_month_d";
            $sql .= ",(select count(id) from admin_log as al where c.id=al.company_id and cu.user_id = al.user_id and type = 0) as total_d";
            $sql .= ",(select count(id) from admin_log as al where c.id=al.company_id and cu.user_id = al.user_id and type = 1 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m')) as cur_month_c";
            $sql .= ",(select count(id) from admin_log as al where c.id=al.company_id and cu.user_id = al.user_id and type = 1) as total_c";
            $sql .= ",(((select count(id) from admin_log as al where c.id=al.company_id and cu.user_id = al.user_id and type = 0 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m'))+(select count(id) from admin_log as al where c.id=al.company_id and cu.user_id = al.user_id and type = 0 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m'))))/2) as total_j_d";
            $sql .= ",(((select count(id) from admin_log as al where c.id=al.company_id and cu.user_id = al.user_id and type = 1 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m'))+(select count(id) from admin_log as al where c.id=al.company_id and cu.user_id = al.user_id and type = 1 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m'))))/2) as total_j_c";
            $sql .= " from company as c inner join company_user as cu on cu.company_id=c.id";
            $sql .= " inner join members as m on cu.user_id=m.id";

            if ($search) {
                $sql .= " and (c.company_name like '%". $search ."%'";
                $sql .= " or m.mobile like '%". $search ."%' or m.realname like'%". $search ."%')";
            }

            $data['total'] = count(DB::select($sql));

            $sql .= " order by ". $sortName ." ". $sortOrder;
            if ($pageSize != 'All') {
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $sql .= " limit ". $start .','. $pageSize;
            }

            $rows = DB::select($sql);
			$news = array();
          	
          	foreach ($rows as $k => $v) {
            	$news[$k]['id'] = $v->id;
            	$news[$k]['realname'] = $v->realname;
            	$news[$k]['company_id'] = $v->company_id;
            	$news[$k]['user_id'] = $v->user_id;
            	$news[$k]['company_name'] = $v->company_name;
            	$news[$k]['mobile'] = $v->mobile;
            	$news[$k]['join_time'] = $v->join_time;
            	$news[$k]['login_time'] = $v->login_time;
            	$news[$k]['is_admin'] = $v->is_admin;
            	$news[$k]['cur_month_d'] = $v->cur_month_d;
            	$news[$k]['total_d'] = $v->total_d;
            	$news[$k]['cur_month_c'] = $v->cur_month_c;
            	$news[$k]['total_c'] = $v->total_c;
            	$news[$k]['total_j_d'] = sprintf("%.0f", $v->total_j_d);
            	$news[$k]['total_j_c'] = sprintf("%.0f", $v->total_j_c);
            }
          	$data['rows'] = $news;
            return response()->json($data);
        }
        return view('admin.operate.user');
    }

    public function userinfo(Request $request){
        if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $search = $request->post("search", '');  //搜索条件

            if($request->company_id == '' || $request->user_id == ''){
                return response()->json(['total' => 0,'rows' => '']);
            }

            $data['total'] = AdminLog::from('admin_log as al')
                ->select('al.*','m.realname','c.company_name')
                ->leftJoin('members as m','m.id','=','al.user_id')
                ->leftJoin('company as c','c.id','=','al.company_id')
                ->where('user_id','=',$request->user_id)
                ->where('company_id','=',$request->company_id)
                ->count();
            $query = AdminLog::from('admin_log as al')
                ->select('al.*','m.realname','c.company_name')
                ->leftJoin('members as m','m.id','=','al.user_id')
                ->leftJoin('company as c','c.id','=','al.company_id')
                ->where('user_id','=',$request->user_id)
                ->where('company_id','=',$request->company_id);

            if($pageSize != 'All'){
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $query->skip($start)->take($pageSize);
            }
            $data['rows'] = $query->orderBy($sortName, $sortOrder)->get();

            return response()->json($data);

        }
        return view('admin.operate.userinfo',['user_id' => $request->user_id, 'company_id' => $request->company_id]);
    }

    public function company(Request $request){
        if($request->ajax()){

            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $search = $request->post("search", '');  //搜索条件

            if($sortName == 'id'){
                $sortName = 'c.id';
            }

            $sql = "select c.id,c.company_name,c.created_at,cu.login_time,m.realname";
            $sql .= ",(select count(al.id) from admin_log as al where c.id=al.company_id and type = 0 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m')) as cur_month_d";
            $sql .= ",(select count(al.id) from admin_log as al where c.id=al.company_id and type = 0 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m'))=1) as last_month_d";
            $sql .= ",(select count(al.id) from admin_log as al where c.id=al.company_id and type = 0) as total_d";
            $sql .= ",(select count(al.id) from admin_log as al where c.id=al.company_id and type = 1 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m')) as cur_month_c";
            $sql .= ",(select count(al.id) from admin_log as al where c.id=al.company_id and type = 1 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m'))=1) as last_month_c";
            $sql .= ",(select count(al.id) from admin_log as al where c.id=al.company_id and type = 1) as total_c";
            $sql .= ",(((select count(al.id) from admin_log as al where cu.user_id = al.user_id and type = 0 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m'))+(select count(al.id) from admin_log as al where cu.user_id = al.user_id and type = 0 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m'))=1))/2) as total_j_d";
            $sql .= ",(((select count(al.id) from admin_log as al where cu.user_id = al.user_id and type = 1 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m'))+(select count(al.id) from admin_log as al where cu.user_id = al.user_id and type = 1 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m'))=1))/2) as total_j_c";
            $sql .= ",(select count(id) from company_user where company_id = c.id)  as account_number";
            $sql .= " from company as c inner join company_user as cu on cu.company_id=c.id";
            $sql .= " inner join members as m on m.id=cu.user_id where cu.is_admin=1";

            if($search){
                $sql .= " and (c.company_name like '%". $search ."%' or m.realname like '%". $search ."%')";
            }

            $data['total'] = count(DB::select($sql));

            $sql .= " order by ". $sortName ." ". $sortOrder;
            if($pageSize != 'All'){
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $sql .= " limit ". $start .','. $pageSize;
            }

            $rows = DB::select($sql);
			$news = array();
          	
          	foreach($rows as $k => $v){
            	$news[$k]['id'] = $v->id;
            	$news[$k]['company_name'] = $v->company_name;
            	$news[$k]['created_at'] = $v->created_at;
            	$news[$k]['login_time'] = $v->login_time;
            	$news[$k]['realname'] = $v->realname;
            	$news[$k]['cur_month_d'] = $v->cur_month_d;
            	$news[$k]['last_month_d'] = $v->last_month_d;
            	$news[$k]['total_d'] = $v->total_d;
            	$news[$k]['cur_month_c'] = $v->cur_month_c;
            	$news[$k]['last_month_c'] = $v->last_month_c;
            	$news[$k]['total_c'] = $v->total_c;
            	$news[$k]['total_j_d'] = sprintf("%.0f", $v->total_j_d);
            	$news[$k]['total_j_c'] = sprintf("%.0f", $v->total_j_d);
            	$news[$k]['account_number'] = $v->account_number;
            }
          	$data['rows'] = $news;
          
            return response()->json($data);

        }
        return view('admin.operate.company');
    }

    public function companyinfo(Request $request){
        if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $search = $request->post("search", '');  //搜索条件

            if($request->company_id == ''){
                return response()->json(['total' => 0,'rows' => '']);
            }

            $data['total'] = AdminLog::from('admin_log as al')
                ->select('al.*','m.realname','c.company_name')
                ->leftJoin('members as m','m.id','=','al.user_id')
                ->leftJoin('company as c','c.id','=','al.company_id')
                ->where('company_id','=',$request->company_id)
                ->count();
            $query = AdminLog::from('admin_log as al')
                ->select('al.*','m.realname','c.company_name')
                ->leftJoin('members as m','m.id','=','al.user_id')
                ->leftJoin('company as c','c.id','=','al.company_id')
                ->where('company_id','=',$request->company_id);

            if($pageSize != 'All'){
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $query->skip($start)->take($pageSize);
            }
            $data['rows'] = $query->orderBy($sortName, $sortOrder)->get();

            return response()->json($data);

        }
        return view('admin.operate.companyinfo',['company_id' => $request->company_id]);
    }
}
<?php
	
namespace App\Http\Controllers\Admin;

use App\Models\Admin\AdminLog;
use App\Models\Admin\CompanyUser;
use App\Models\Admin\Depots;
use App\Models\Admin\Dispatch;
use App\Models\Admin\JoinDepot;
use App\Models\Admin\Opencut;
use App\Models\Admin\Sale;
use App\Models\Admin\Worklog;
use Illuminate\Http\Request;
use App\Models\Admin\Company;
use App\Models\Admin\Godown;
use App\Models\Admin\GoodsAttr;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;

class CompanyController extends Controller{
	
	public $result = array("status"=>0,'msg'=>'请求成功','data'=>"");
	//企业列表
	public function index(Request $request){
		if ($request->ajax()) {
          
            $sortName = $request->post("sortName");    //排序列名
			$sortOrder = $request->post("sortOrder");   //排序（desc，asc）
			$pageNumber = $request->post("pageNumber");  //当前页码
			$pageSize = $request->post("pageSize");   //一页显示的条数
			$search = $request->post("search",'');  //搜索条件

            $sql = "select c.*,m.realname,m.mobile,(select count(id) from company_user where company_id = c.id)  as account_number";
			$sql .= " from company as c left join company_user as cu on cu.company_id=c.id";
			$sql .= " left join members as m on m.id=cu.user_id where cu.is_admin=1 ";

            if(trim($search)){

                $sql .= " and (c.company_name like '%". $search ."%' or c.company_number like '%". $search ."%'";
                $sql .= " or m.mobile like '%". $search ."%' or m.realname like '%". $search ."%')";

            }

            $data['total'] = count(DB::select($sql));

            $sql .= " order by ". $sortName ." ". $sortOrder;
            if($pageSize != 'All'){
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $sql .= " limit ". $start .','. $pageSize;
            }

            $data['rows'] = DB::select($sql);

	        return response()->json($data);
        }
        return view('admin.company.index');
	}
	
    // 企业用户列表
    public function users(Request $request,$id){
        if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $search = $request->post("search",'');  //搜索条件

            $sql = "select m.realname,cu.is_admin,m.mobile,cu.join_time,cu.login_time";
            $sql .= ",(select count(id) from admin_log as al where cu.user_id = al.user_id and type = 0 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m')) as cur_month_d";
            $sql .= ",(select count(id) from admin_log as al where cu.user_id = al.user_id and type = 0 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m'))=1) as last_month_d";
            $sql .= ",((select count(id) from admin_log as al where cu.user_id = al.user_id and type = 0 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m'))+(select count(id) from admin_log as al where cu.user_id = al.user_id and type = 0 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m')))) as total_d";
            $sql .= ",(select count(id) from admin_log as al where cu.user_id = al.user_id and type = 1 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m')) as cur_month_c";
            $sql .= ",(select count(id) from admin_log as al where cu.user_id = al.user_id and type = 1 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m'))=1) as last_month_c";
            $sql .= ",((select count(id) from admin_log as al where cu.user_id = al.user_id and type = 1 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m'))+(select count(id) from admin_log as al where cu.user_id = al.user_id and type = 1 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m'))=1)) as total_c";
            $sql .= ",(((select count(id) from admin_log as al where cu.user_id = al.user_id and type = 0 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m'))+(select count(id) from admin_log as al where cu.user_id = al.user_id and type = 0 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m'))=1))/2) as total_j_d";
            $sql .= ",(((select count(id) from admin_log as al where cu.user_id = al.user_id and type = 1 and DATE_FORMAT(created_at,'%Y%m') = DATE_FORMAT(CURDATE(),'%Y%m'))+(select count(id) from admin_log as al where cu.user_id = al.user_id and type = 1 and PERIOD_DIFF(date_format(now(),'%Y%m'),date_format(created_at,'%Y%m'))=1))/2) as total_j_c";
            $sql .= " from company_user as cu";
            $sql .= " left join members as m on m.id=cu.user_id";
            $sql .= " where cu.company_id=". $id ." and cu.status=1";

            if(trim($search)){

                $sql .= " and (c.company_name like '%". $search ."%' or c.company_number like '%". $search ."%'";
                $sql .= " or m.mobile like '%". $search ."%' or m.realname like '%". $search ."%')";

            }

            $data['total'] = count(DB::select($sql));

            $sql .= " order by ". $sortName ." ". $sortOrder;
            if($pageSize != 'All'){
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $sql .= " limit ". $start .','. $pageSize;
            }

            $rows = DB::select($sql);
          
            foreach ($rows as $k => $v){
                $v->total_j_d = sprintf('%.0f', $v->total_j_d);
                $v->total_j_c = sprintf('%.0f', $v->total_j_c);
            }
          
			$data['rows'] = $rows;
            return response()->json($data);

        }


        return view('admin.company.users',array('id'=>$id,'name' => Company::find($id)->company_name));
    }

    public function stocks(Request $request,$id){
        if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $search = $request->post("search", '');  //搜索条件

            $sql = "select ga.goods_attr_name";
            $sql .= ",(select sum(gs.godown_weight) from goods_attr as gas inner join godown as gs on gas.id = gs.goods_attr_id where ga.id=gas.id and gs.type=0) as type_h";
            $sql .= ",(select sum(gs.godown_weight) from goods_attr as gas inner join godown as gs on gas.id = gs.goods_attr_id where ga.id=gas.id and gs.type=1) as type_d";
            $sql .= ",(IFNULL((select sum(gs.godown_weight) from goods_attr as gas inner join godown as gs on gas.id = gs.goods_attr_id where ga.id=gas.id and gs.type=0),0.0)*45+IFNULL((select sum(gs.godown_weight) from goods_attr as gas inner join godown as gs on gas.id = gs.goods_attr_id where ga.id=gas.id and gs.type=1),0.0)) as total";
            $sql .= " from goods_attr as ga where ga.company_id=". $id;

            $data['total'] = count(DB::select($sql));

            $sql .= " order by ". $sortName ." ". $sortOrder;
            if($pageSize != 'All'){
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $sql .= " limit ". $start .','. $pageSize;
            }

            $res = DB::select($sql);
            $new = array();

            if($res){
                foreach ($res as $k => $v){
                    $new[$k]['goods_attr_name'] = $v->goods_attr_name;
                    $new[$k]['type_h'] = $v->type_h;
                    $new[$k]['type_d'] = $v->type_d;
                    $new[$k]['total'] = $v->total;
                }
            }

            $newarr = array(
                'goods_attr_name' => '总库存',
                'type_h' => 0,
                'type_d' => 0,
                'total' => 0
            );

            foreach ($new as $k => $v){
                $new[$k]['type_h']= sprintf("%.4f", $v['type_h']);
                $new[$k]['type_d']= sprintf("%.4f", $v['type_d']);
                $new[$k]['total']= sprintf("%.4f", $v['total']);
                $newarr['type_h'] += sprintf("%.4f", $v['type_h']);
                $newarr['type_d'] += sprintf("%.4f", $v['type_d']);
                $newarr['total'] += sprintf("%.4f", $v['total']);
            }
                    
            $newarr['type_h'] = sprintf("%.4f", $newarr['type_h']);
            $newarr['type_d'] = sprintf("%.4f", $newarr['type_d']);
            $newarr['total'] = sprintf("%.4f", $newarr['total']);
          
            array_unshift($new,$newarr);

            $data['total'] = count($new);
            $data['rows'] = $new;
            return response()->json($data);

        }
        return view('admin.company.stocks',array('id'=>$id,'name' => Company::find($id)->company_name));
    }

    public function depots(Request $request,$id){
        if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $start = ($pageNumber - 1) * $pageSize;   //开始位置
            $search = $request->post("search", '');  //搜索条件

            $sql = "select d.depot_name";
            $sql .= ",(select sum(gs.godown_weight) from depots as ds inner join godown as gs on ds.id = gs.depot_id where d.id=ds.id and gs.type=0) as type_h";
            $sql .= ",(select sum(gs.godown_weight) from depots as ds inner join godown as gs on ds.id = gs.depot_id where d.id=ds.id and gs.type=1) as type_d";
            $sql .= ",(IFNULL((select sum(gs.godown_weight) from depots as ds inner join godown as gs on ds.id = gs.depot_id where d.id=ds.id and gs.type=0),0.0)*45+IFNULL((select sum(gs.godown_weight) from depots as ds inner join godown as gs on ds.id = gs.depot_id where d.id=ds.id and gs.type=1),0.0)) as total";
            $sql .= " from depots as d where d.company_id=". $id;

            $data['total'] = count(DB::select($sql));

            $sql .= " order by ". $sortName ." ". $sortOrder ." limit ". $start .','. $pageSize;
            $res = DB::select($sql);
            $new = array();

            if($res){
                foreach ($res as $k => $v){
                    $new[$k]['depot_name'] = $v->depot_name;
                    $new[$k]['type_h'] = $v->type_h;
                    $new[$k]['type_d'] = $v->type_d;
                    $new[$k]['total'] = $v->total;
                }
            }

            $newarr = array(
                'depot_name' => '总库存',
                'type_h' => 0,
                'type_d' => 0,
                'total' => 0
            );

            foreach ($new as $k => $v){
                $new[$k]['type_h']= sprintf("%.4f", $v['type_h']);
                $new[$k]['type_d']= sprintf("%.4f", $v['type_d']);
                $new[$k]['total']= sprintf("%.4f", $v['total']);
                $newarr['type_h'] += sprintf("%.4f", $v['type_h']);
                $newarr['type_d'] += sprintf("%.4f", $v['type_d']);
                $newarr['total'] += sprintf("%.4f", $v['total']);
            }

            $newarr['type_h'] = sprintf("%.4f", $newarr['type_h']);
            $newarr['type_d'] = sprintf("%.4f", $newarr['type_d']);
            $newarr['total'] = sprintf("%.4f", $newarr['total']);

            array_unshift($new,$newarr);

            $data['total'] = count($new);
            $data['rows'] = $new;
            return response()->json($data);
        }
        return view('admin.company.depots',array('id'=>$id,'name' => Company::find($id)->company_name));
    }
      
    //增加公司标签 
    public function ajax(Request $request,$id){

        $type = $request->type;
        if ($type == 1){  //获取详情
            $res = Company::find($id);
            if (!$res){
                $this->result['status'] = 1;
                return response()->json($this->result);
            }
            $res = json_decode(json_encode($res),true);
            $this->result['data'] = $res;
            return response()->json($this->result);
        }

//	    if(!$request->volid_time || !$request->name || !$request->sort){
//            $this->result['status'] = 1;
//            return response()->json($this->result);
//        }
        if($request->name){
            $data['company_name'] = $request->name;
        }else{
            $data['company_name'] = '';
        }
        if($request->volid_time){
            $data['volid_time'] = $request->volid_time;
        }else{
            $data['volid_time'] = '';
        }
        if($request->sort){
            $data['sort'] = $request->sort;
            $lod_data = Company::where('sort',$data['sort'])->get();
            if ($lod_data){
                $lod_data = json_decode(json_encode($lod_data),true);
                foreach ($lod_data as $v){
                    $where['sort'] = 0;
                    Company::where('id',$v['id'])->update($where);
                }
            }
        }else{
            $data['sort'] = 0;
        }

        if ($request->chk_value){
            $data['label'] = json_encode($request->chk_value);
        }else{
            $data['label'] = '';
        }
        //修改
	    $bool = Company::where('id','=',$id)->update($data);
        if(!$bool){
            $this->result['status'] = 1;
            return response()->json($this->result);
        }
        return response()->json($this->result);
    }
  
  	public function deleteCompany($id){
    	//开启事务
        DB::beginTransaction();
        try {

            $goods_attr = GoodsAttr::from('goods_attr as ga')
                ->select('g.id')
                ->leftJoin('godown as g','ga.id','=','g.goods_attr_id')
                ->where('company_id','=',$id)
                ->get();

            if($goods_attr){
                $goods_attr = $goods_attr->toArray();
                foreach($goods_attr as $v){
                    Godown::where('id','=',$v['id'])->delete();
                    JoinDepot::where('id','=',$v['id'])->delete();
                    Dispatch::where('godown_id','=',$v['id'])->delete();
                    Opencut::where('godown_id','=',$v['id'])->delete();
                    Sale::where('godown_id','=',$v['id'])->delete();
                }
            }

            GoodsAttr::where('company_id','=',$id)->delete();
            Depots::where('company_id','=',$id)->delete();
            CompanyUser::where('company_id','=',$id)->delete();
            Company::where('id','=',$id)->delete();
            AdminLog::where('company_id','=',$id)->delete();
            Worklog::where('company_id','=',$id)->delete();

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return redirect('/admin/company/index')->withErrors("更新失败");
        }
      	return redirect('/admin/company/index')->withSuccess("更新成功");
    }

    //上传企业logo
    public function uploadLogo(Request $request){
        $id = $request->input('id','');
        $logo_name = 'log'.time();
        $logo_type = $_FILES['logo']['type'];
        $logo_tmp = $_FILES['logo']['tmp_name'];
        $cover_name =  'cove'.time();
        $cover_type = $_FILES['cover']['type'];
        $cover_tmp = $_FILES['cover']['tmp_name'];
        if ($_FILES['logo']['size'] < 0 && $_FILES['cover']['size'] < 0){
            return redirect('/admin/company/index')->withErrors("更新失败");
        }
        //处理图片
        $filepath = 'uploads/picture';
        $date = date('Ymd',time());
        $new_path = $filepath.'/'.$date;
        if (!is_dir($new_path)){
            mkdir($new_path);
        }
        if ($_FILES['logo'] && $_FILES['logo']['size'] > 0){
            move_uploaded_file($logo_tmp,$new_path.'/'.$logo_name.'.png');
            $where['logo'] = $new_path.'/'.$logo_name.'.png';
        }else{
            return redirect('/admin/company/index')->withErrors("更新失败");
        }
        if ($_FILES['cover'] && $_FILES['cover']['size'] > 0){
            move_uploaded_file($cover_tmp,$new_path.'/'.$cover_name.'.png');
        $where['cover'] = $new_path.'/'.$cover_name.'.png';
        }else{
            return redirect('/admin/company/index')->withErrors("更新失败");
        }
        $res = Company::where('id','=',$id)->update($where);
        if (!$res){
            return redirect('/admin/company/index')->withErrors("更新失败");
        }
        return redirect('/admin/company/index')->withSuccess("更新成功");
    }
}

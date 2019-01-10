<?php

namespace App\Http\Controllers\Api;

use App\Library\UploadFile;
use App\Models\Admin\Company;
use App\Models\Admin\CompanyUser;
use App\Models\Admin\Configs;
use App\Models\Admin\Dispatch;
use App\Models\Admin\Godown;
use App\Models\Admin\GoodsAttr;
use App\Models\Admin\JoinDepot;
use App\Models\Admin\Members;
use App\Models\Admin\Monthly;
use App\Models\Admin\News;
use App\Models\Admin\AdminLog;
use App\Models\Admin\Opencut;
use App\Models\Admin\Sale;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;

class CommonController
{
    public $result = array("status"=>0,'msg'=>'请求成功','data'=>"");
    
	//初始化各项配置
	public function __construct() {}
	
	/**
     * 小程序上传图片接口（记录图片上传成功数量）
     *
     * @param mem_id
     * @param comp_id
     * @param file
     */
	public function uploadImsage(Request $request) {
		$file = $request->file("file");
        if (!$file) {
            $this->result["status"] = 1;
            $this->result["msg"] = "上传失败,请重试";
            return $this->result;
        }

        $res = (new UploadFile([
            "upload_dir" => "./uploads/picture/",
            "type" => ["image/jpg","image/png","image/jpeg","image/bmp","image/gif"]]
        ))->upload($file);
      
        if ($res["status"] == 0) {
            $arr = ['url' => $res["data"]];
            $this->result["data"] = $arr;

            // 存储图集：用户ID，公司ID
            $data = $request->post();
            if (isset($data['member_id']) && trim($data['member_id'] != '') && 
                isset($data['company_id']) && trim($data['company_id'] != '')) {
                $cu = CompanyUser::where('user_id','=',$data['member_id'])->where('company_id','=',$data['company_id'])->first();
                $this->adminLog($data['company_id'],2,'上传图片',$data['member_id'],CompanyUser::IS_ADMIN[$cu->is_admin]);
            }
        }
        return $this->result;
	}

    // 获取套餐信息
    public function getMonthly(Request $request){
        $arr = Monthly::where('status','=',1)->get();
        if($arr){
            $arr = $arr->toArray();
        }
        $this->result['data'] = $arr;
        return response()->json($this->result);
    }

    //创建企业
    public function createCompany(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['openid']) || trim($data['openid']) == ''){
            return $this->verify_parameter('openid'); //返回必传参数为空
        }
        if(!isset($data['company_name']) || trim($data['company_name']) == ''){
            return $this->verify_parameter('company_name'); //返回必传参数为空
        }
        if(!isset($data['realname']) || trim($data['realname']) == ''){
            return $this->verify_parameter('realname'); //返回必传参数为空
        }
        if(!isset($data['mobile']) || trim($data['mobile']) == ''){
            return $this->verify_parameter('mobile'); //返回必传参数为空
        }
        if(!isset($data['company_pass']) || trim($data['company_pass']) == ''){
            return $this->verify_parameter('company_pass'); //返回必传参数为空
        }

        //判断公司名称是否重复
        $comp = Company::where('company_name','=',$data['company_name'])->get();
        if(count($comp)>0){
            return $this->verify_parameter('该企业名已被注册',0); die;
        }

        /*$compUser = CompanyUser::where('user_id','=',$data['mem_id'])->where('is_admin','=',1)->where('status','=',1)->first();
        if($compUser){
            return $this->verify_parameter('你正在申请企业，请勿重复操作！！',0);
        }*/

        $con = Configs::first();

        $data_ins['company_name'] = $data['company_name'];
        $data_ins['company_number'] = $this->getCompanyNumber();
        $data_ins['company_pass'] = $data['company_pass'];
        $data_ins['company_status'] = 1;
        $data_ins['volid_time'] = Carbon::parse('+'.$con->test_time.' days')->toDateTimeString();
        $data_ins["created_at"] = Carbon::now()->toDateTimeString();
        $data_ins["updated_at"] = Carbon::now()->toDateTimeString();

        //写进数据库
        $cid = Company::insertGetId($data_ins);
        if($cid){

            //开启事务
            DB::beginTransaction();
            try {

                $data_upd['realname'] = $data['realname'];
                $data_upd['mobile'] = $data['mobile'];
                Members::where('openid','=',$data['openid'])->update($data_upd);

                $data_upd1['is_admin'] = 1;
                $data_upd1['company_id'] = $cid;
                $data_upd1['status'] = 1;
                $data_upd1['user_id'] = $data['mem_id'];
                $data_upd1["join_time"] = Carbon::now()->toDateTimeString();
                $data_upd1["created_at"] = Carbon::now()->toDateTimeString();
                $data_upd1["updated_at"] = Carbon::now()->toDateTimeString();

                CompanyUser::insert($data_upd1);

                DB::commit();
            } catch(\Illuminate\Database\QueryException $ex) {
                DB::rollback(); //回滚事务
                return $this->verify_parameter('创建企业失败！！',0);
            }

            return response()->json($this->result);

        }

        return $this->verify_parameter('创建企业失败',0);

    }

    //加入企业
    public function joinCompany(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['openid']) || trim($data['openid']) == ''){
            return $this->verify_parameter('openid'); //返回必传参数为空
        }
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if(!isset($data['realname']) || trim($data['realname']) == ''){
            return $this->verify_parameter('realname'); //返回必传参数为空
        }
        if(!isset($data['mobile']) || trim($data['mobile']) == ''){
            return $this->verify_parameter('mobile'); //返回必传参数为空
        }
        if(!isset($data['company_pass']) || trim($data['company_pass']) == ''){
            return $this->verify_parameter('company_pass'); //返回必传参数为空
        }

        $comp = Company::where('id','=',$data['company_id'])->where('company_pass','=',$data['company_pass'])->get();
        if(count($comp)<=0){
            return $this->verify_parameter('企业邀请码有误！！',0);
        }

        $compUser = CompanyUser::where('user_id','=',$data['mem_id'])->where('company_id','=',$data['company_id'])->first();
        if($compUser){
            return $this->verify_parameter('提交成功，请等待管理员审核',0);
        }

        //开启事务
        DB::beginTransaction();
        try {

            $data_upd['realname'] = $data['realname'];
            $data_upd['mobile'] = $data['mobile'];

            Members::where('openid','=',$data['openid'])->update($data_upd);

            $data_upd1['company_id'] = $data['company_id'];
            $data_upd1['is_admin'] = 0;
            $data_upd1['status'] = 0;
            $data_upd1['user_id'] = $data['mem_id'];
            $data_upd1["join_time"] = Carbon::now()->toDateTimeString();
            $data_upd1["created_at"] = Carbon::now()->toDateTimeString();
            $data_upd1["updated_at"] = Carbon::now()->toDateTimeString();

            CompanyUser::insert($data_upd1);

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('加入企业失败！！',0);
        }

        return response()->json($this->result);
    }
  
    //搜索获取公司信息
    public function sosoCompany(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['soso']) || trim($data['soso']) == ''){
            return $this->verify_parameter('soso'); //返回必传参数为空
        }
        $soso = trim($data['soso']);

        $res = Company::from('company as c')
            ->select('c.id','c.company_name','c.company_number','m.realname','m.mobile','c.company_pass')
            ->leftJoin('company_user as cu','cu.company_id','=','c.id')
            ->leftJoin('members as m','m.id','=','cu.user_id')
            ->where('cu.is_admin','=',1)
            ->where(function ($query) use ($soso) {
                $query->where('c.company_name', '=', $soso)
                    ->orwhere('c.company_number', '=', $soso);
            })
            ->where('company_status','=',1)
            ->first();

        if(!$res){
            return $this->verify_parameter('查不到数据',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }
  
    // 获取公司接口
    public function getCompany(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        $res = Company::from('company as c')
            ->select('c.id','c.company_name','c.company_number','m.realname','m.mobile','c.company_pass')
            ->leftJoin('company_user as cu','cu.company_id','=','c.id')
            ->leftJoin('members as m','m.id','=','cu.user_id')
            ->where('c.id','=',$data['company_id'])
            ->where('cu.is_admin','=',1)
            ->first();

        if(!$res){
            return $this->verify_parameter('查不到数据',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }
  
      //获取公司资讯
    public function getNews(Request $request){
        $data = $request->post();

        $res = News::from('news as n')
            ->select('n.title','n.type','n.content','n.created_at')
            ->get();

        if(!$res){
            return $this->verify_parameter('查不到数据',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }
  
    // 更新登陆时间
  	public function updateLoginTime(Request $request){
    	$data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
      	if(!isset($data['member_id']) || trim($data['member_id']) == ''){
            return $this->verify_parameter('member_id'); //返回必传参数为空
        }
      
      	// 更新登陆时间
        $bool = CompanyUser::where('user_id','=',$data['member_id'])->where('company_id','=',$data['company_id'])->update(['login_time'=>Carbon::now()->toDateTimeString()]);
		if(!$bool){
        	 return $this->verify_parameter('更新操作失败', 0);
        }	
      
      	$cu = CompanyUser::where('user_id','=',$data['member_id'])->where('company_id','=',$data['company_id'])->first();
        //记录登陆操作
        $this->adminLog($data['company_id'],0,'登录',$data['member_id'],CompanyUser::IS_ADMIN[$cu->is_admin]);
        return response()->json($this->result);
    }

    // 模糊获取公司产品信息
    public function getCompanyGoDown(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['soso']) || trim($data['soso']) == ''){
            return $this->verify_parameter('soso'); //返回必传参数为空
        }

        $comp1 = Company::where('company_name','like','%'.$data['soso'].'%')->orwhere('company_number','like','%'.$data['soso'].'%')->get();
        $comp2 = Company::from('company as c')->select('c.*')
                ->join('goods_attr as ga','c.id','=','ga.company_id')
                ->where('ga.goods_attr_name','like','%'.$data['soso'].'%')
                ->get();
      
        $ids = array();
        if($comp1){
            $comp1 = $comp1->toArray();
            foreach ($comp1 as $v){
                if(!in_array($v['id'], $ids)){
                    $ids[] = $v['id'];
                }
            }
        }
      
        if($comp2){
            $comp2 = $comp2->toArray();
            foreach ($comp2 as $v){
                if(!in_array($v['id'], $ids)){
                    $ids[] = $v['id'];
                }
            }
        }
      
        $comp = Company::from('company as c')
            ->select('c.*','ga.goods_attr_name','m.mobile')
            ->join('goods_attr as ga','c.id','=','ga.company_id')
            ->join('company_user as cu','cu.company_id','=','c.id')
            ->join('members as m','m.id','=','cu.user_id')
            ->where('cu.is_admin','=',1)
            ->whereIn('c.id', $ids)
            ->get();
      
        if($comp){
            $comp = $comp->toArray();
            $new = array();

            foreach ($comp as $k => $v){
                if(isset($new[$v['id']])){
                    $new[$v['id']]['names'][] = $v['goods_attr_name'];
                }else{
                  	$arr = array();
                    $arr['id'] = $v['id'];
                    $arr['company_name'] = $v['company_name'];
                    $arr['company_number'] = $v['company_number'];
                    $arr['company_pass'] = $v['company_pass'];
                    $arr['company_status'] = $v['company_status'];
                    $arr['volid_time'] = $v['volid_time'];
                    $arr['mobile'] = $v['mobile'];
                    $arr['created_at'] = $v['created_at'];
                    $arr['updated_at'] = $v['updated_at'];
                    $arr['names'][] = $v['goods_attr_name'];
                    $new[$v['id']] = $arr;
                }
            }
				
            $comp = array_values($new);
          
          	foreach($comp as $k => $v){
            	$comp[$k]['names'] = array_values(array_filter($v['names']));
            }
        }

        $this->result['data'] = $comp;

        return response()->json($this->result);
    }

  	// 查询单个公司的产品列表 
    public function getCompanyGoDownList(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        //判断是否有可选参数
        if(!isset($data['page']) || trim($data['page']) == ''){
            $data['page'] = 1;
        }
        $start = ((int)$data['page']-1)*10;  //截取部分数据


        $godownIds = array();
        if(isset($data['soso']) &&  trim($data['soso']) != ''){

            $arr1 = Dispatch::where('remarks', 'like', '%'.$data['soso'].'%')->get(['godown_id']);
            $arr2 = JoinDepot::from('joindepot as j')
                ->select('g.id as godown_id')
                ->join('godown as g', 'g.godown_no', '=', 'j.godown_no')
                ->where('j.remarks', 'like', '%'.$data['soso'].'%')
                ->get();
            $arr3 = Opencut::where('remarks', 'like', '%'.$data['soso'].'%')->get(['godown_id']);
            $arr4 = Sale::where('remarks', 'like', '%'.$data['soso'].'%')->get(['godown_id']);

            if($arr1){
                foreach ($arr1 as $v){
                    if(!in_array($v->godown_id, $godownIds)){
                        $godownIds[] = $v->godown_id;
                    }
                }
            }
            if($arr2){
                foreach ($arr2 as $v){
                    if(!in_array($v->godown_id, $godownIds)){
                        $godownIds[] = $v->godown_id;
                    }
                }
            }
            if($arr3){
                foreach ($arr3 as $v){
                    if(!in_array($v->godown_id, $godownIds)){
                        $godownIds[] = $v->godown_id;
                    }
                }
            }
            if($arr4){
                foreach ($arr4 as $v){
                    if(!in_array($v->godown_id, $godownIds)){
                        $godownIds[] = $v->godown_id;
                    }
                }
            }

        }


        $godown= Godown::from('godown as g')
            ->select('g.id','g.type','ga.goods_attr_name','d.depot_name','g.godown_no','g.godown_weight','g.godown_length','g.godown_width','g.godown_height','g.godown_pic','g.godown_number','g.no_start','g.no_end')
            ->leftJoin('goods_attr as ga','g.goods_attr_id','=','ga.id')
            ->leftJoin('depots as d','d.id','=','g.depot_id')
            ->where('d.company_id','=',$data['company_id']);

        if(isset($data['goods_attr_id']) && trim($data['goods_attr_id']) != ''){
            $godown->where('g.goods_attr_id','=',$data['goods_attr_id']);
        }

        if(isset($data['type']) && trim($data['type']) != ''){
            $godown->where('g.type','=',$data['type']);
        }

        if(isset($data['depot_id']) && trim($data['depot_id']) != ''){
            $godown->where('g.depot_id','=',$data['depot_id']);
        }

        if(isset($data['soso']) && trim($data['soso']) != ''){
            $godown->where(function ($query) use ($data, $godownIds){
                $query->whereIn('g.id', $godownIds)
                    ->orwhere('g.godown_no','like','%'.$data['soso'].'%');
            });
        }

        $res = $godown->orderBy('g.id','desc')->skip($start)->take(10)->get();

        if(!$res){
            return $this->verify_parameter('查不到数据',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    //品种查询
    public function getGoodsAttr(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        $res = GoodsAttr::select('id','goods_attr_name')->where('company_id','=',$data['company_id'])->get();
        if(!$res){
            return $this->verify_parameter('查不到数据',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);

    }
  
	//返回失败的原因
	private function verify_parameter($str,$type=1){
		$this->result['status'] = 1;
		if($type==1){
    		$this->result['msg'] = "必传参数".$str."为空";
		}else{
		  	$this->result['msg'] = $str;
		}
    	return response()->json($this->result);
	}
  
   //生成一个随机公司ID
    private function getCompanyNumber(){
        $str = '0123456789';
        $str = str_shuffle($str);
        $company_number = substr($str,0,8);
        $count = Company::where("company_number",'=',$company_number)->count();
        if($count>0){
            $this->getCompanyNumber();
        }else{
            return $company_number;
        }
    }

    //记录操作日志的函数
    private function goWorkLog($company_id,$title,$content,$godown_id=0) {
        $log = array();
        $log['title'] = $title;
        $log['godown_id'] = $godown_id;
        $log['company_id'] = $company_id;
        $log['content'] = $content;
        $log['created_at'] = Carbon::now()->toDateTimeString();
        $log['updated_at'] = Carbon::now()->toDateTimeString();
        Worklog::insertGetId($log);
    }
    
    //操作记录方法
    private function adminLog($company_id,$type,$content,$user_id,$identity) {
        $adminArr = array();
        $adminArr['company_id'] = $company_id;
        $adminArr['type'] = $type;
        $adminArr['content'] = $content;
        $adminArr['user_id'] = $user_id;
        $adminArr['identity'] = $identity;
        $adminArr['created_at'] = Carbon::now()->toDateTimeString();
        $adminArr['updated_at'] = Carbon::now()->toDateTimeString();
        AdminLog::insert($adminArr);
    }

}
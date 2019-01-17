<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminLog;
use App\Models\Admin\CompanyUser;
use App\Models\Admin\Depots;
use App\Models\Admin\Dispatch;
use App\Models\Admin\Godown;
use App\Models\Admin\GoodsAttr;
use App\Models\Admin\JoinDepot;
use App\Models\Admin\Members;
use App\Models\Admin\News;
use App\Models\Admin\Opencut;
use App\Models\Admin\Sale;
use App\Models\Admin\Worklog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Admin\Company;
use DB;

header("Access-Control-Allow-Origin: *");
class ApiController  extends Controller{

    public $result = array("status"=>0,'msg'=>'请求成功','data'=>"");
    private $user_infos = '';

    //初始化各项配置
    public function __construct(Request $request){
        //判断该用户是否被删除
        $this->middleware('checkApi');
        $this->user_infos = $request->user_infos;
    }

    //获取企业日志
    public function getCompanyLog(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        //判断是否有可选参数
        if(!isset($data['page']) || trim($data['page']) == ''){
            $data['page'] = 1;
        }

        $start = ((int)$data['page']-1)*20;  //截取部分数据

        $res = Worklog::from('work_log as wl')
            ->select('wl.title','wl.content','wl.created_at')
            ->where('company_id','=',$data['company_id'])
            ->skip($start)->take(20)
            ->orderBy('wl.id','desc')
            ->get();

        if(!$res){
            return $this->verify_parameter('查不到数据',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    //品种增加
    public function createGoodsAttr(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if(!isset($data['goods_attr_name']) || trim($data['goods_attr_name']) == ''){
            return $this->verify_parameter('goods_attr_name'); //返回必传参数为空
        }

        $data_ins['goods_attr_name'] = $data['goods_attr_name'];
        $data_ins['company_id'] = $data['company_id'];

        $data_ins['created_at'] = Carbon::now()->toDateTimeString();
        $data_ins['updated_at'] = Carbon::now()->toDateTimeString();

        $bool = GoodsAttr::insertGetId($data_ins); //插入数据库
        if(!$bool){
            return $this->verify_parameter('增加品种失败！！',0);
        }

        //记录操作日志
        $this->goWorkLog($data['company_id'],'产品设置',$data['member_name'].'新增产品类别<text class="orange">'.$data['goods_attr_name'].'</text>');

        return response()->json($this->result);
    }

    //品种删除
    public function deleteGoodsAttr(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['goods_attr_id']) || trim($data['goods_attr_id']) == ''){
            return $this->verify_parameter('goods_attr_id'); //返回必传参数为空
        }

        $tag = GoodsAttr::find((int)$data['goods_attr_id']);
        $company_id = $tag->company_id;
        $goods_attr_name = $tag->goods_attr_name;

        $count = Godown::where('goods_attr_id','=',$data['goods_attr_id'])->where('godown_status','=',0)->count();
        if($count>0){
            return $this->verify_parameter('该品种还有产品，不能删除！！',0);
        }

        //开启事务
        DB::beginTransaction();
        try {
            $tag->delete();

            JoinDepot::where('goods_attr_id','=',$data['goods_attr_id'])->delete();

            $ids = Godown::where('goods_attr_id','=',$data['goods_attr_id'])->get();
            if($ids){

                $ids = $ids->toArray();
                $newids = array();
                foreach($ids as $v){
                    $newids[] = $v['id'];
                }

                Godown::whereIn('id',$newids)->delete();
                Opencut::whereIn('godown_id',$newids)->delete();
                Dispatch::whereIn('godown_id',$newids)->delete();
                Sale::whereIn('godown_id',$newids)->delete();
            }

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('删除品种失败！！',0);
        }

        //记录操作日志
        $this->goWorkLog($company_id,'产品设置',$data['member_name'].'删除产品类别<text class="orange">'.$goods_attr_name.'</text>');

        return response()->json($this->result);
    }

    //品种修改
    public function updateGoodsAttr(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['goods_attr_id']) || trim($data['goods_attr_id']) == ''){
            return $this->verify_parameter('goods_attr_id'); //返回必传参数为空
        }
        if(!isset($data['goods_attr_name']) || trim($data['goods_attr_name']) == ''){
            return $this->verify_parameter('goods_attr_name'); //返回必传参数为空
        }
        $goodsattr = GoodsAttr::where("id","=",$data['goods_attr_id'])->first();

        $data_upd['goods_attr_name'] = $data['goods_attr_name'];
        $bool = GoodsAttr::where("id","=",$data['goods_attr_id'])->update($data_upd);

        if(!$bool){
            return $this->verify_parameter('修改品种失败！！',0);
        }

        //记录操作日志
        $this->goWorkLog($goodsattr->company_id,'品种修改',$data['member_name'].'修改产品类别<text class="orange">'.$goodsattr->goods_attr_name.'为'.$data['goods_attr_name'].'</text>');

        return response()->json($this->result);
    }

    //仓库增加
    public function createDepots(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if(!isset($data['depot_name']) || trim($data['depot_name']) == ''){
            return $this->verify_parameter('depot_name'); //返回必传参数为空
        }

        $data_ins['depot_name'] = $data['depot_name'];
        $data_ins['company_id'] = $data['company_id'];

        $data_ins['created_at'] = Carbon::now();
        $data_ins['updated_at'] = Carbon::now();

        $bool = Depots::insertGetId($data_ins); //插入数据库
        if(!$bool){
            return $this->verify_parameter('增加仓库失败！！',0);
        }

        //记录操作日志
        $this->goWorkLog($data['company_id'],'仓库设置',$data['member_name'].'新增仓库<text class="orange">'.$data['depot_name'].'</text>');

        return response()->json($this->result);
    }

    //仓库删除
    public function deleteDepots(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['depot_id']) || trim($data['depot_id']) == ''){
            return $this->verify_parameter('depot_id'); //返回必传参数为空
        }

        $tag = Depots::find((int)$data['depot_id']);
        $company_id = $tag->company_id;
        $depot_name = $tag->depot_name;

        $count = Godown::where('depot_id','=',$data['depot_id'])->where('godown_status','=',0)->count();
        if($count>0){
            return $this->verify_parameter('该仓库还有产品，不能删除！！',0);
        }

        if($tag->delete()){

            //记录操作日志
            $this->goWorkLog($company_id,'仓库设置',$data['member_name'].'删除仓库<text class="orange">'.$depot_name.'</text>');

            return response()->json($this->result);
        }else{
            return $this->verify_parameter('删除仓库失败！！',0);
        }
    }

    //仓库修改
    public function updateDepots(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['depot_id']) || trim($data['depot_id']) == ''){
            return $this->verify_parameter('depot_id'); //返回必传参数为空
        }
        if(!isset($data['depot_name']) || trim($data['depot_name']) == ''){
            return $this->verify_parameter('depot_name'); //返回必传参数为空
        }
        $depots = Depots::where("id","=",$data['depot_id'])->first();
        $data_upd['depot_name'] = $data['depot_name'];
        $bool = Depots::where("id","=",$data['depot_id'])->update($data_upd);

        if(!$bool){
            return $this->verify_parameter('修改仓库失败！！',0);
        }

        //记录操作日志
        $this->goWorkLog($depots->company_id,'仓库设置',$data['member_name'].'修改<text class="orange">'.$depots->depot_name.'</text>为<text class="orange">'.$data['depot_name'].'</text>');

        return response()->json($this->result);

    }

    //仓库查询
    public function getDepots(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        $res = Depots::select('id','depot_name')->where('company_id','=',$data['company_id'])->get();
        if(!$res){
            return $this->verify_parameter('查不到数据',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);

    }

    //员工申请列表查询
    public function getApplyMembers(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        $res = CompanyUser::from('company_user as cu')
            ->select('m.id','m.realname')
            ->leftJoin('members as m','m.id','=','cu.user_id')
            ->where('cu.company_id','=',$data['company_id'])
            ->where('cu.is_admin','=',0)
            ->where('cu.status','=',0)
            ->get();

        if(!$res){
            return $this->verify_parameter('查不到数据',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    //员工申请列表更新
    public function updateApplyMembers(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['members_id']) || trim($data['members_id']) == ''){
            return $this->verify_parameter('members_id'); //返回必传参数为空
        }
        if(!isset($data['status']) || trim($data['status']) == ''){
            return $this->verify_parameter('status'); //返回必传参数为空
        }
        if($data['status'] != 1 && $data['status'] != 2){
            return $this->verify_parameter('status参数值有误！！！',0);
        }

        $members = CompanyUser::from('company_user as cu')
            ->select('m.*','cu.company_id')
            ->leftJoin('members as m','m.id','=','cu.user_id')
            ->where('cu.user_id','=',$data['members_id'])
            ->where('cu.company_id','=',$data['comp_id'])
            ->first();

        if($data['status']==1){

            $data_upd['status'] = $data['status'];
            $bool = CompanyUser::where("user_id","=",$data['members_id'])->where('company_id','=',$data['comp_id'])->update($data_upd);
        }else{

            $data_upd['status'] = $data['status'];
            $bool = CompanyUser::where("user_id","=",$data['members_id'])->where('company_id','=',$data['comp_id'])->delete();
        }

        if(!$bool){
            return $this->verify_parameter('操作申请用户失败！！',0);
        }

        //记录操作日志
        if($data['status']==1){
            $this->goWorkLog($members->company_id,'员工管理','管理员新增员工<text class="orange">'.$members->realname.'</text>');
            $this->adminLog($members->company_id,1,'新增员工'.$members->realname,$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);
        }

        return response()->json($this->result);
    }

    //员工列表
    public function getMembers(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        $res = CompanyUser::from('company_user as cu')
            ->select('m.id','m.realname','cu.is_admin')
            ->leftJoin('members as m','m.id','=','cu.user_id')
            ->where('cu.company_id','=',$data['company_id'])
            ->where('cu.is_admin','!=',1)
            ->where('cu.status','=',1)
            ->get();

        if(!$res){
            return $this->verify_parameter('查不到数据',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    //员工删除
    public function deleteMembers(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['members_id']) || trim($data['members_id']) == ''){
            return $this->verify_parameter('members_id'); //返回必传参数为空
        }

        $members = CompanyUser::from('company_user as cu')
            ->select('m.*','cu.company_id')
            ->leftJoin('members as m','m.id','=','cu.user_id')
            ->where('cu.company_id','=',$data['comp_id'])
            ->where('cu.user_id','=',$data['members_id'])
            ->first();

        $bool = CompanyUser::where('company_id','=',$data['comp_id'])->where('user_id','=',$data['members_id'])->delete();

        if(!$bool){
            return $this->verify_parameter('操作失败！！！',0);
        }

        //记录操作日志
        $this->goWorkLog($members->company_id,'员工管理','员工<text class="orange">'.$members->realname.'</text>退出企业');
        $this->adminLog($members->company_id,1,'删除员工'.$members->realname,$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);

        return response()->json($this->result);

    }

    //任命主管
    public function appointMembers(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['members_id']) || trim($data['members_id']) == ''){
            return $this->verify_parameter('members_id'); //返回必传参数为空
        }
        if(!isset($data['is_admin']) || trim($data['is_admin']) == ''){
            return $this->verify_parameter('is_admin'); //返回必传参数为空
        }
        if($data['is_admin'] !=0 && $data['is_admin'] != 2 ){
            return $this->verify_parameter('is_admin传值有误！',0); //返回必传参数为空
        }

        $bool = CompanyUser::where('user_id','=',$data['members_id'])
            ->where('company_id','=',$data['comp_id'])
            ->update(['is_admin' => $data['is_admin']]);

        if(!$bool){
            return $this->verify_parameter('操作失败！！！',0);
        }

        $members =CompanyUser::from('company_user as cu')
            ->select('m.*','cu.company_id')
            ->leftJoin('members as m','m.id','=','cu.user_id')
            ->where('cu.user_id','=',$data['members_id'])
            ->where('cu.company_id','=',$data['comp_id'])
            ->first();

        if($data['is_admin'] == 2){
            $this->goWorkLog($members->company_id,'员工管理','<text class="orange">'.$members->realname.'</text>被管理员任命为主管');
        }
        if($data['is_admin'] == 0){
            $this->goWorkLog($members->company_id,'员工管理','<text class="orange">'.$members->realname.'</text>被管理员任命为员工');
        }

        return response()->json($this->result);
    }

    //查询产品信息
    public function getGoDown(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if(!isset($data['godown_no']) || trim($data['godown_no']) == ''){
            return $this->verify_parameter('godown_no'); //返回必传参数为空
        }

        $res = Godown::from('godown as g')
            ->select('g.id','g.type','ga.goods_attr_name','d.depot_name','g.godown_no','g.godown_weight','g.godown_length','g.godown_width','g.godown_height','g.godown_pic','g.godown_number','g.no_start','g.no_end')
            ->leftJoin('goods_attr as ga','g.goods_attr_id','=','ga.id')
            ->leftJoin('depots as d','d.id','=','g.depot_id')
            ->where('g.godown_no','=',$data['godown_no'])
            ->where('g.godown_status','=',0)
            ->where('ga.company_id','=',$data['company_id'])
            ->get();

        if(!$res){
            return $this->verify_parameter('查不到数据',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    /**
     * 修改产品图片
     *
     * @param $godown_id
     * @param $godown_pic 数组
     * @param $member_name
     * @param $del_godown_pic 数组
     * @param $member_id 用户ID
     */
    public function updateGoDownImg(Request $request) {
        $data = $request->post();

        // 判断传值是否正确
        if (!isset($data['godown_id']) || trim($data['godown_id']) == '') {
            return $this->verify_parameter('godown_id');
        }
        if (!isset($data['godown_pic']) || $data['godown_pic'] == '') {
            return $this->verify_parameter('godown_pic');
        }
        if (!isset($data['member_name']) || trim($data['member_name']) == '') {
            return $this->verify_parameter('member_name');
        }
        if (!isset($data['member_id']) || trim($data['member_id']) == '') {
            return $this->verify_parameter('member_id');
        }

        $bool = Godown::where('id','=',$data['godown_id'])->update(['godown_pic' => implode(',', $data['godown_pic'])]);
        if (!$bool) {
            return $this->verify_parameter('操作失败！请联系管理员',0);
        }

        $godown = Godown::from('godown as g')
            ->select('g.*','ga.company_id')
            ->leftJoin('goods_attr as ga','ga.id','=','g.goods_attr_id')
            ->where('g.id','=',$data['godown_id'])
            ->first();

        // 记录操作日志(删除图片)
        if (isset($data['del_godown_pic']) && count($data['del_godown_pic']) > 0) {
            $cu = CompanyUser::where('user_id','=',$data['member_id'])->where('company_id','=',$godown['company_id'])->value('is_admin');
            for ($i=0; $i < count($data['del_godown_pic']); $i++) {
                $this->adminLog($godown['company_id'], 2, '删除图片', $data['member_id'], CompanyUser::IS_ADMIN[$cu->is_admin]);
            }
        }

        // 记录工作日志
        $content = $data['member_name'].'修改了产品编号为<text class="orange">'.$godown->godown_no.'</text>的产品图片';
        $this->goWorkLog($godown->company_id,'产品修改',$content,$godown->id);
        return response()->json($this->result);
    }

    //产品入库
    public function joinGoDown(Request $request)
    {
        $data = $request->post();

        //判断传值是否正确
        if (!isset($data['member_name']) || trim($data['member_name']) == '') {
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if (!isset($data['type']) || trim($data['type']) == '') {
            return $this->verify_parameter('type'); //返回必传参数为空
        }
        if (!isset($data['goods_attr_id']) || trim($data['goods_attr_id']) == '') {
            return $this->verify_parameter('goods_attr_id'); //返回必传参数为空
        }
        if (!isset($data['depot_id']) || trim($data['depot_id']) == '') {
            return $this->verify_parameter('depot_id'); //返回必传参数为空
        }
        if (!isset($data['godown_no']) || trim($data['godown_no']) == '') {
            return $this->verify_parameter('godown_no'); //返回必传参数为空
        }
        if (!isset($data['godown_weight']) || trim($data['godown_weight']) == '') {
            return $this->verify_parameter('godown_weight'); //返回必传参数为空
        }
        if (!isset($data['godown_length']) || trim($data['godown_length']) == '') {
            return $this->verify_parameter('godown_length'); //返回必传参数为空
        }
        if (!isset($data['godown_width']) || trim($data['godown_width']) == '') {
            return $this->verify_parameter('godown_width'); //返回必传参数为空
        }

        $data_ins = array();
        if ($data['type'] == 0) {
            if (!isset($data['godown_height']) || trim($data['godown_height']) == '') {
                return $this->verify_parameter('godown_height'); //返回必传参数为空
            }
            $data_ins['godown_height'] = $data['godown_height'];

            $weight = $data['godown_weight'] . 'm³';

        } else {

            if (!isset($data['godown_number']) || trim($data['godown_number']) == '') {
                return $this->verify_parameter('godown_number'); //返回必传参数为空
            }
            /*if (!isset($data['no_start']) || trim($data['no_start']) == '') {
                return $this->verify_parameter('no_start'); //返回必传参数为空
            }
            if (!isset($data['no_end']) || trim($data['no_end']) == '') {
                return $this->verify_parameter('no_end'); //返回必传参数为空
            }
            if (intval($data['no_start']) > intval($data['no_end'])) {
                return $this->verify_parameter('传过来的产品编号有误！！！', 0);
            }
            if (($data['no_end'] - $data['no_start'] + 1) != $data['godown_number']) {
                return $this->verify_parameter('产品序号和件数是不匹配！！', 0);
            }

            $no_number = '';
            for ($i = intval($data['no_start']); $i <= intval($data['no_end']); $i++) {
                $no_number .= $i . ',';
            }
            $no_number = substr($no_number, 0, (strlen($no_number) - 1));*/

            $data_ins['godown_number'] = $data['godown_number'];
            /*$data_ins['no_start'] = $data['no_start'];
            $data_ins['no_end'] = $data['no_end'];
            $data_ins['no_number'] = $no_number;*/

            $weight = $data['godown_weight'] . 'm²';
        }

        //判断是否有可选参数
        if (!isset($data['godown_pic']) ||  $data['godown_pic'] == '') {
            $data['godown_pic'] = '';
        }
        if (isset($data['remarks']) && $data['remarks'] != '') {
            $data_ins['remarks'] = $data['remarks'];
        }

        $ga = GoodsAttr::where('id', '=', $data['goods_attr_id'])->first();

        //判断是否已有产品
        $count = JoinDepot::from('joindepot as j')
            ->leftJoin('goods_attr as ga', 'ga.id', '=', 'j.goods_attr_id')
            ->where('j.godown_no', '=', $data['godown_no'])
            ->where('ga.company_id', '=', $ga->company_id)
            ->count();

        if ($count > 0) {
            return $this->verify_parameter('该产品编号已经被注册了！！', 0);
        }

        $data_ins['type'] = $data['type'];
        $data_ins['goods_attr_id'] = $data['goods_attr_id'];
        $data_ins['depot_id'] = $data['depot_id'];
        $data_ins['godown_no'] = $data['godown_no'];
        $data_ins['godown_weight'] = $data['godown_weight'];
        $data_ins['godown_length'] = $data['godown_length'];
        $data_ins['godown_width'] = $data['godown_width'];
        $data_ins['godown_pic'] = $data['godown_pic'];

        $data_ins['created_at'] = Carbon::now()->toDateTimeString();
        $data_ins['updated_at'] = Carbon::now()->toDateTimeString();

        $godown_id = Godown::create($data_ins); //插入数据库
        $godown_id = $godown_id->id;
        JoinDepot::create($data_ins); //插入数据库
        if (!$godown_id) {
            return $this->verify_parameter('入库失败！！', 0);
        }

        //记录操作日志
        $godown = Godown::from('godown as g')
            ->select('g.godown_no', 'ga.goods_attr_name', 'd.depot_name', 'd.company_id')
            ->leftJoin('goods_attr as ga', 'ga.id', '=', 'g.goods_attr_id')
            ->leftJoin('depots as d', 'd.id', '=', 'depot_id')
            ->where('g.id', '=', $godown_id)
            ->first();

        if ($data['type'] == 0) {
            $type = '荒料';
        } else {
            $type = '大板';
        }

        $beizhu = (isset($data['remarks']) && $data['remarks'] != '') ? $data['remarks'] : '空';

        $content = '<text class="orange">' . $godown->goods_attr_name . '</text>' . $type . '，编号' . $godown->godown_no . '，数量<text class="orange">' . $weight . '</text>，存放位置为<text class="orange">' . $godown->depot_name . '</text>，备注：'.$beizhu;
        $this->goWorkLog($godown->company_id, '产品入库', $content, $godown_id);
        $this->adminLog($godown->company_id,1,'录入入库单',$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);

        return response()->json($this->result);
    }

    //入库单删除
    public function deleteGoDown(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['godown_id']) || trim($data['godown_id']) == ''){
            return $this->verify_parameter('godown_id'); //返回必传参数为空
        }
        //判断产品是否已经使用
        $joindepot = JoinDepot::find((int)$data['godown_id']);
        if($joindepot->type==0){
            $type = '荒料';
        }else{
            $type = '大板';
        }
        $godown_no = $joindepot->godown_no;
        $depots = Depots::find((int)$joindepot->depot_id);

        if($joindepot->godown_status == 1){
            $sale_count = Sale::where('godown_id','=',$data['godown_id'])->count();
            if($sale_count != 0){
                return $this->verify_parameter('产品已经使用，不能删除',0);
            }

        }

        //开启事务
        DB::beginTransaction();
        try {
            $joindepot->delete();
            $godowns = Godown::where('godown_no','=',$godown_no)->first();
            $godown_id = $godowns->id;
            Godown::where('godown_no','=',$godown_no)->delete();

            Opencut::where('godown_id','=',$godown_id)->delete();
            Dispatch::where('godown_id','=',$godown_id)->delete();
            Sale::where('godown_id','=',$godown_id)->delete();

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('入库单失败！！',0);
        }

        //记录操作日志
        $content = $data['member_name'].'删除了产品编号为<text class="orange">'.$godown_no.'</text>的'.$type.'入库单';
        $this->goWorkLog($depots->company_id,'入库删除',$content);
        $this->adminLog($depots->company_id,1,'删除入库单',$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);

        return response()->json($this->result);
    }

    //入库单修改
    public function updateGoDown(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['godown_id']) || trim($data['godown_id']) == ''){
            return $this->verify_parameter('godown_id'); //返回必传参数为空
        }

        //判断产品是否已经使用
        $godown = JoinDepot::find((int)$data['godown_id']);
        if($godown->godown_status == 1){
            return $this->verify_parameter('产品已经使用，不能删除',0);
        }

        //判断传值是否正确
        if(!isset($data['goods_attr_id']) || trim($data['goods_attr_id']) == ''){
            return $this->verify_parameter('goods_attr_id'); //返回必传参数为空
        }
        if(!isset($data['depot_id']) || trim($data['depot_id']) == ''){
            return $this->verify_parameter('depot_id'); //返回必传参数为空
        }
        if(!isset($data['godown_weight']) || trim($data['godown_weight']) == ''){
            return $this->verify_parameter('godown_weight'); //返回必传参数为空
        }
        if(!isset($data['godown_length']) || trim($data['godown_length']) == ''){
            return $this->verify_parameter('godown_length'); //返回必传参数为空
        }
        if(!isset($data['godown_width']) || trim($data['godown_width']) == ''){
            return $this->verify_parameter('godown_width'); //返回必传参数为空
        }

        $data_ins = array();
        if($godown->type==0){
            if(!isset($data['godown_height']) || trim($data['godown_height']) == ''){
                return $this->verify_parameter('godown_height'); //返回必传参数为空
            }
            $data_ins['godown_height'] = $data['godown_height'];

            $weight = $data['godown_weight'].'m³';

        }else{

            if(!isset($data['godown_number']) || trim($data['godown_number']) == ''){
                return $this->verify_parameter('godown_number'); //返回必传参数为空
            }
            /*if(!isset($data['no_start']) || trim($data['no_start']) == ''){
                return $this->verify_parameter('no_start'); //返回必传参数为空
            }
            if(!isset($data['no_end']) || trim($data['no_end']) == ''){
                return $this->verify_parameter('no_end'); //返回必传参数为空
            }
            if(intval($data['no_start']) > intval($data['no_end'])){
                return $this->verify_parameter('传过来的产品编号有误！！！',0);
            }
            if(($data['no_end']-$data['no_start']+1) != $data['godown_number']){
                return $this->verify_parameter('产品序号和件数是不匹配！！',0);
            }

            $no_number = '';
            for($i=intval($data['no_start']);$i<=intval($data['no_end']);$i++){
                $no_number .= $i.',';
            }
            $no_number = substr($no_number,0,(strlen($no_number)-1));*/

            $data_ins['godown_number'] = $data['godown_number'];
            /*$data_ins['no_start'] = $data['no_start'];
            $data_ins['no_end'] = $data['no_end'];
            $data_ins['no_number'] = $no_number;*/

            $weight = $data['godown_weight'].'m²';
        }

        //判断是否有可选参数
        if(\request()->has('godown_pic')){
          	if(is_array(\request()->get('godown_pic'))){
        		$data_ins['godown_pic'] = implode(',', array_filter($data['godown_pic']));
            }else{
            	$data_ins['godown_pic'] = \request()->get('godown_pic');
            }
        }
        if($request->has('remarks')){
            $data_ins['remarks'] = $data['remarks'];
        }
        $data_ins['goods_attr_id'] = $data['goods_attr_id'];
        $data_ins['depot_id'] = $data['depot_id'];
        $data_ins['godown_weight'] = $data['godown_weight'];
        $data_ins['godown_length'] = $data['godown_length'];
        $data_ins['godown_width'] = $data['godown_width'];

        //开启事务
        DB::beginTransaction();
        try {
            //入库表更新
            JoinDepot::where("id","=",$data['godown_id'])->update($data_ins);

            //库存表更新
            Godown::where("godown_no","=",$godown->godown_no)->update($data_ins);

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('修改数据失败！！',0);
        }

        //记录操作日志
        $godown = Godown::from('godown as g')
            ->select('g.id','g.godown_no','ga.goods_attr_name','d.depot_name','d.company_id')
            ->leftJoin('goods_attr as ga','ga.id','=','g.goods_attr_id')
            ->leftJoin('depots as d','d.id','=','depot_id')
            ->where('g.id','=',$data['godown_id'])
            ->first();

        $beizhu = (isset($data['remarks']) && $data['remarks'] != '') ? $data['remarks'] : '空';

        $content = $data['member_name'].'修改了产品编号为'.$godown->godown_no.'的入库单,<text class="orange">'.$godown->goods_attr_name.'</text>,数量<text class="orange">'.$weight.'</text>，存放于<text class="orange">'.$godown->depot_name.'</text>，备注：'.$beizhu;
        $this->goWorkLog($godown->company_id,'入库修改',$content,$godown->id);
        $this->adminLog($godown->company_id,1,'编辑入库单',$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);

        return response()->json($this->result);
    }

    //入库单列表
    public function getGodownList(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        //判断是否有可选参数
        if(!isset($data['page']) || trim($data['page']) == ''){
            $data['page'] = 1;
        }
        $start = ((int)$data['page']-1)*20;  //截取部分数据

        $joindepot = JoinDepot::from('joindepot as g')
            ->select('g.id','g.type','g.goods_attr_id','g.depot_id','g.godown_pic','d.depot_name','g.created_at','ga.goods_attr_name','g.godown_no','g.godown_weight','g.godown_length','g.godown_width','g.godown_height','g.godown_number','g.no_start','g.no_end','g.remarks')
            ->leftJoin('depots as d','g.depot_id','=','d.id')
            ->leftJoin('goods_attr as ga','ga.id','=','g.goods_attr_id')
            ->where('d.company_id','=',$data['company_id']);

        //判断是否有筛选条件
        if(isset($data['time_start']) && trim($data['time_start']) != ''){
            if(isset($data['time_end']) && trim($data['time_end']) != ''){
                $data['time_end'] = $this->setlasttime($data['time_end']);
                $joindepot->whereBetween('g.created_at',[$data['time_start'],$data['time_end']]);
            }else{
                $joindepot->where('g.created_at','>=',$data['time_start']);
            }
        }
        if(isset($data['time_end']) && trim($data['time_end']) != ''){
            $data['time_end'] = $this->setlasttime($data['time_end']);
            $joindepot->where('g.created_at','<=',$data['time_end']);
        }

        if(isset($data['goods_attr_id']) && trim($data['goods_attr_id']) != ''){
            $joindepot->where('g.goods_attr_id','=',$data['goods_attr_id']);
        }

        if(isset($data['type']) && trim($data['type']) != ''){
            $joindepot->where('g.type','=',$data['type']);
        }

        if(isset($data['depot_id']) && trim($data['depot_id']) != ''){
            $joindepot->where('g.depot_id','=',$data['depot_id']);
        }

        if(isset($data['soso']) && trim($data['soso']) != ''){
            $joindepot->where(function ($query) use ($data){
                $query->where('g.godown_no','like','%'.$data['soso'].'%')
                    ->orwhere('g.remarks','like','%'.$data['soso'].'%');
            });
        }

		$this->result['total'] = $joindepot->count();
        $res = $joindepot->orderBy('g.id','desc')
            ->skip($start)->take(20)
            ->get();

        if(!$res){
            return $this->verify_parameter('查询不到数据！！',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    //产品开切
    public function createOpencut(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['godown_id']) || trim($data['godown_id']) == ''){
            return $this->verify_parameter('godown_id'); //返回必传参数为空
        }
        if(!isset($data['new_weight']) || trim($data['new_weight']) == ''){
            return $this->verify_parameter('new_weight'); //返回必传参数为空
        }
        if(!isset($data['new_length']) || trim($data['new_length']) == ''){
            return $this->verify_parameter('new_length'); //返回必传参数为空
        }
        if(!isset($data['new_width']) || trim($data['new_width']) == ''){
            return $this->verify_parameter('new_width'); //返回必传参数为空
        }
        /*if(!isset($data['new_no_start']) || trim($data['new_no_start']) == ''){
            return $this->verify_parameter('new_no_start'); //返回必传参数为空
        }
        if(!isset($data['new_no_end']) || trim($data['new_no_end']) == ''){
            return $this->verify_parameter('new_no_end'); //返回必传参数为空
        }*/
        if(!isset($data['new_number']) || trim($data['new_number']) == ''){
            return $this->verify_parameter('new_number'); //返回必传参数为空
        }
        /*if(intval($data['new_no_start']) > intval($data['new_no_end'])){
            return $this->verify_parameter('传过来的产品编号有误！！！',0);
        }
        if(($data['new_no_end']-$data['new_no_start']+1) != $data['new_number']){
            return $this->verify_parameter('产品序号和件数是匹配！！',0);
        }*/

        //判断是否有可选参数
        if(!isset($data['new_height']) || trim($data['new_height']) == ''){
            $data['new_height'] = 0;
        }
        if(!isset($data['new_godown_pic']) || $data['new_godown_pic'] == ''){
            $data['new_godown_pic'] = '';
        }
        if (isset($data['remarks']) && $data['remarks'] != '') {
            $data_ins['remarks'] = $data['remarks'];
        }

        $godown = Godown::where('id','=',$data['godown_id'])->first();
        if($godown->type==1){
            return $this->verify_parameter('产品不能开切！！',0);
        }

        //写进数据库
        $data_ins['godown_id'] = $data['godown_id'];
        $data_ins['old_weight'] = $godown->godown_weight;
        $data_ins['new_weight'] = $data['new_weight'];
        $data_ins['old_length'] = $godown->godown_length;
        $data_ins['new_length'] = $data['new_length'];
        $data_ins['old_width'] = $godown->godown_width;
        $data_ins['new_width'] = $data['new_width'];
        $data_ins['old_height'] = $godown->godown_height;
        $data_ins['new_height'] = $data['new_height'];
        $data_ins['old_godown_pic'] = $godown->godown_pic;
        $data_ins['new_godown_pic'] = $data['new_godown_pic'];
        /*$data_ins['new_no_start'] = $data['new_no_start'];
        $data_ins['new_no_end'] = $data['new_no_end'];*/
        $data_ins['new_number'] = $data['new_number'];
        $data_ins['created_at'] = Carbon::now()->toDateTimeString();
        $data_ins['updated_at'] = Carbon::now()->toDateTimeString();

        //开启事务
        DB::beginTransaction();
        try {
            $oid = Opencut::create($data_ins);
            $oid = $oid->id;

            $data_upd['type'] = 1;
            $data_upd['godown_number'] = $data['new_number'];
            $data_upd['godown_weight'] = $data['new_weight'];
            $data_upd['godown_length'] = $data['new_length'];
            $data_upd['godown_width'] = $data['new_width'];
            $data_upd['godown_height'] = $data['new_height'];

            if(!isset($data['new_godown_pic']) && $data['new_godown_pic'] == ''){
                $data['new_godown_pic'] = '';
            }
            $data_upd['godown_pic'] = is_array($data['new_godown_pic']) ? implode(',', array_filter($data['new_godown_pic'])) : $data['new_godown_pic'];

            /*$no_number = '';
            for($i=intval($data['new_no_start']);$i<=intval($data['new_no_end']);$i++){
                $no_number .= $i.',';
            }
            $no_number = substr($no_number,0,(strlen($no_number)-1));

            $data_upd['no_start'] = $data['new_no_start'];
            $data_upd['no_end'] = $data['new_no_end'];
            $data_upd['no_number'] = $no_number;*/
            $boo = Godown::where('id','=',$data['godown_id'])->update($data_upd);

            //入库表更新
            $arr_joindepot['godown_status'] = 1;
            JoinDepot::where('godown_no','=',$godown->godown_no)->update($arr_joindepot);

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('开切失败！！',0);
        }

        //操作日志记录
        $godown = Godown::from('godown as g')
            ->select('g.id','g.godown_no','ga.goods_attr_name','d.depot_name','d.company_id')
            ->leftJoin('goods_attr as ga','ga.id','=','g.goods_attr_id')
            ->leftJoin('depots as d','d.id','=','depot_id')
            ->where('g.id','=',$data['godown_id'])
            ->first();

        $beizhu = (isset($data['remarks']) && $data['remarks'] != '') ? $data['remarks'] : '空';

        $content = '<text class="orange">'.$godown->goods_attr_name.'</text>荒料，编号'.$godown->godown_no;
        $content .= '，数量<text class="orange">'.$data_ins['old_weight'].'m³</text>，产出成品<text class="orange">';
        $content .=	$data['new_weight'].'m²</text>，存放位置为<text class="orange">'.$godown->depot_name.'</text>，备注：'.$beizhu;
        $this->goWorkLog($godown->company_id,'荒料开切',$content,$godown->id);
        $this->adminLog($godown->company_id,1,'录入开切单',$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);

        return response()->json($this->result);
    }

    //产品开切删除
    public function deleteOpencut(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['opencut_id']) || trim($data['opencut_id']) == ''){
            return $this->verify_parameter('opencut_id'); //返回必传参数为空
        }
        $opencut = Opencut::find((int)$data['opencut_id']);
        if($opencut->status==1){
            return $this->verify_parameter('该订单已不能修改',0); //返回必传参数为空
        }

        //判断产品是否已经使用
        $godown = Godown::find((int)$opencut->godown_id);
        $godown_no = $godown->godown_no;
        $depots = Depots::find((int)$godown->depot_id);
        if($opencut->status == 1){
            return $this->verify_parameter('产品已经使用，不能删除',0); //返回必传参数为空
        }

        //开启事务
        DB::beginTransaction();
        try {

            // 入库表更新
            if(!Opencut::where('godown_id', $opencut->godown_id)->where('id', '<>', $opencut->id)->exists()){
                $arrDepot_upd['godown_status'] = 0;
                JoinDepot::where('godown_no', '=', $godown->godown_no)->update($arrDepot_upd);
            }

            //产品表更新
            $data_upd['godown_weight'] = $opencut->old_weight;
            $data_upd['godown_length'] = $opencut->old_length;
            $data_upd['godown_width'] = $opencut->old_width;
            $data_upd['godown_height'] = $opencut->old_height;
            $data_upd['godown_pic'] = is_array($opencut->old_godown_pic) ? implode(',', $opencut->old_godown_pic) : $opencut->old_godown_pic;
            $data_ins['no_start'] = 0;
            $data_ins['no_end'] = 0;
            $data_upd['no_number'] = '';
            $data_upd['godown_number'] = 0;
            $data_upd['type'] = 0;
            $boo = Godown::where('id','=',$opencut->godown_id)->update($data_upd);

            //开切表删除
            $opencut->delete();

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('开切删除失败！！',0);
        }

        //记录操作日志
        $this->goWorkLog($depots->company_id,'开切删除',$data['member_name'].'删除了产品编号为<text class="orange">'.$godown_no.'</text>的荒料开切单');
        $this->adminLog($depots->company_id,1,'删除开切单',$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);

        return response()->json($this->result);
    }

    //产品开切修改
    public function updateOpencut(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['opencut_id']) || trim($data['opencut_id']) == ''){
            return $this->verify_parameter('opencut_id'); //返回必传参数为空
        }
        $opencut = Opencut::find((int)$data['opencut_id']);
        if($opencut->status==1){
            return $this->verify_parameter('请先删除关联单据',0); //返回必传参数为空
        }

        //判断产品是否已经使用
        $godown = Godown::find((int)$opencut->godown_id);
        $godown_no = $godown->godown_no;
        $godown_id = $opencut->godown_id;
        $old_weight = $godown->godown_weight;
        $depots = Depots::find((int)$godown->depot_id);
        if($godown->godown_status == 1){
            return $this->verify_parameter('产品已经使用，不能删除',0);
        }

        if(!isset($data['new_weight']) || trim($data['new_weight']) == ''){
            return $this->verify_parameter('new_weight'); //返回必传参数为空
        }
        if(!isset($data['new_length']) || trim($data['new_length']) == ''){
            return $this->verify_parameter('new_length'); //返回必传参数为空
        }
        if(!isset($data['new_width']) || trim($data['new_width']) == ''){
            return $this->verify_parameter('new_width'); //返回必传参数为空
        }
        /*if(!isset($data['new_no_start']) || trim($data['new_no_start']) == ''){
            return $this->verify_parameter('new_no_start'); //返回必传参数为空
        }
        if(!isset($data['new_no_end']) || trim($data['new_no_end']) == ''){
            return $this->verify_parameter('new_no_end'); //返回必传参数为空
        }*/
        if(!isset($data['new_number']) || trim($data['new_number']) == ''){
            return $this->verify_parameter('new_number'); //返回必传参数为空
        }
        /*if(intval($data['new_no_start']) > intval($data['new_no_end'])){
            return $this->verify_parameter('传过来的产品编号有误！！！',0);
        }
        if(($data['new_no_end']-$data['new_no_start']+1) != $data['new_number']){
            return $this->verify_parameter('产品序号和件数是匹配！！',0);
        }*/

        //判断是否有可选参数
        if(!isset($data['new_height']) || trim($data['new_height']) == ''){
            $data['new_height'] = 0;
        }
        //判断是否有可选参数
        if(!isset($data['new_godown_pic']) || $data['new_godown_pic'] == ''){
            $data['new_godown_pic'] = '';
        }
        if (isset($data['remarks']) && $data['remarks'] != '') {
            $data_ins['remarks'] = $data['remarks'];
        }

        //写进数据库
        $data_ins['new_weight'] = $data['new_weight'];
        $data_ins['new_length'] = $data['new_length'];
        $data_ins['new_width'] = $data['new_width'];
        $data_ins['new_height'] = $data['new_height'];
        $data_ins['new_godown_pic'] = is_array($data['new_godown_pic']) ? implode(',', array_filter($data['new_godown_pic'])) : $data['new_godown_pic'];
        /*$data_ins['new_no_start'] = $data['new_no_start'];
        $data_ins['new_no_end'] = $data['new_no_end'];*/
        $data_ins['new_number'] = $data['new_number'];

        //开启事务
        DB::beginTransaction();
        try {
            Opencut::where('id','=',$data['opencut_id'])->update($data_ins);

            $data_upd['type'] = 1;
            $data_upd['godown_number'] = $data['new_number'];
            $data_upd['godown_weight'] = $data['new_weight'];
            $data_upd['godown_length'] = $data['new_length'];
            $data_upd['godown_width'] = $data['new_width'];
            $data_upd['godown_height'] = $data['new_height'];

            if(isset($data['new_godown_pic']) && $data['new_godown_pic'] != ''){
                $data_upd['godown_pic'] = is_array($data['new_godown_pic']) ? implode(',', array_filter($data['new_godown_pic'])) : $data['new_godown_pic'];
            }

            /*$no_number = '';
            for($i=intval($data['new_no_start']);$i<=intval($data['new_no_end']);$i++){
                $no_number .= $i.',';
            }
            $no_number = substr($no_number,0,(strlen($no_number)-1));

            $data_upd['no_start'] = $data['new_no_start'];
            $data_upd['no_end'] = $data['new_no_end'];
            $data_upd['no_number'] = $no_number;*/
            $boo = Godown::where('id','=',$godown->id)->update($data_upd);

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('开切失败！！',0);
        }

        //操作日志记录
        $godown = Godown::from('godown as g')
            ->select('g.godown_no','ga.goods_attr_name','d.depot_name','d.company_id')
            ->leftJoin('goods_attr as ga','ga.id','=','g.goods_attr_id')
            ->leftJoin('depots as d','d.id','=','depot_id')
            ->where('g.id','=',$godown_id)
            ->first();

        $beizhu = (isset($data['remarks']) && $data['remarks'] != '') ? $data['remarks'] : '空';

        $content = $data['member_name'].'修改了编号为'.$godown->godown_no.'开切单，<text class="orange">'.$godown->goods_attr_name.'</text>荒料，编号';
        $content .= $godown->godown_no.'，数量<text class="orange">'.$old_weight.'m³</text>，产出成品<text class="orange">'.$data['new_weight'].'m²</text>，存放位置为<text class="orange">'.$godown->depot_name.'</text>，备注：'.$beizhu;
        $this->goWorkLog($godown->company_id,'开切修改',$content,$godown_id);
        $this->adminLog($godown->company_id,1,'编辑开切单',$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);

        return response()->json($this->result);
    }

    //开切列表
    public function getOpencutList(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        //判断是否有可选参数
        if(!isset($data['page']) || trim($data['page']) == ''){
            $data['page'] = 1;
        }
        $start = ((int)$data['page']-1)*20;  //截取部分数据

        $opencut = Opencut::from('opencut as o')
            ->select('g.godown_no','o.*','ga.goods_attr_name')
            ->join('godown as g','o.godown_id','=','g.id')
            ->join('depots as d','g.depot_id','=','d.id')
            ->join('goods_attr as ga','ga.id','=','g.goods_attr_id')
            ->where('d.company_id','=',$data['company_id']);

        //判断是否有筛选条件
        if(isset($data['time_start']) && trim($data['time_start']) != ''){
            if(isset($data['time_end']) && trim($data['time_end']) != ''){
                $data['time_end'] = $this->setlasttime($data['time_end']);
                $opencut->whereBetween('o.created_at',[$data['time_start'],$data['time_end']]);
            }else{
                $opencut->where('o.created_at','>=',$data['time_start']);
            }
        }
        if(isset($data['time_end']) && trim($data['time_end']) != ''){
            $data['time_end'] = $this->setlasttime($data['time_end']);
            $opencut->where('o.created_at','<=',$data['time_end']);
        }

        if(isset($data['goods_attr_id']) && trim($data['goods_attr_id']) != ''){
            $opencut->where('g.goods_attr_id','=',$data['goods_attr_id']);
        }

        if(isset($data['type']) && trim($data['type']) != ''){
            $opencut->where('g.type','=',$data['type']);
        }

        if(isset($data['depot_id']) && trim($data['depot_id']) != ''){
            $opencut->where('g.depot_id','=',$data['depot_id']);
        }

        if(isset($data['soso']) && trim($data['soso']) != ''){
            $opencut->where(function ($query) use ($data){
                $query->where('g.godown_no','like','%'.$data['soso'].'%')
                    ->orWhere('o.remarks','like','%'.$data['soso'].'%');
            });
        }

        $this->result['total'] = $opencut->count();
        $res = $opencut->orderBy('o.id','desc')
            ->skip($start)->take(20)
            ->get();

        if(!$res){
            return $this->verify_parameter('查询不到数据！！',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    //产品调库
    public function createDispatch(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['godown_id']) || trim($data['godown_id']) == ''){
            return $this->verify_parameter('godown_id'); //返回必传参数为空
        }
        if(!isset($data['new_depot_id']) || trim($data['new_depot_id']) == ''){
            return $this->verify_parameter('new_depot_id'); //返回必传参数为空
        }

        $godown = Godown::where('id','=',$data['godown_id'])->first();
        $old_depot_id = $godown->depot_id;

        //开启事务
        DB::beginTransaction();
        try {
            //入库表更新
            $data_upd['depot_id'] = $data['new_depot_id'];
            Godown::where('id','=',$data['godown_id'])->update($data_upd);

            //调库表更新
            $upd['status'] = 1;
            Dispatch::where('godown_id','=',$data['godown_id'])->update($upd);

            //调库表记录
            $data_ins['godown_id'] = $data['godown_id'];
            $data_ins['old_depot_id'] = $old_depot_id;
            $data_ins['new_depot_id'] = $data['new_depot_id'];
            $data_ins['created_at'] = Carbon::now();
            $data_ins['updated_at'] = Carbon::now();

            if (isset($data['remarks']) && $data['remarks'] != '') {
                $data_ins['remarks'] = $data['remarks'];
            }

            $did = Dispatch::insertGetId($data_ins);

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('调库失败！！',0);
        }

        //记录操作日志
        $godown = Godown::from('godown as g')
            ->select('g.id','g.type','g.godown_weight','g.godown_no','ga.goods_attr_name','d.depot_name','d.company_id')
            ->leftJoin('goods_attr as ga','ga.id','=','g.goods_attr_id')
            ->leftJoin('depots as d','d.id','=','depot_id')
            ->where('g.id','=',$data['godown_id'])
            ->first();

        $dispatch = Dispatch::from('dispatch as dis')
            ->select('d.depot_name as old_depot','dd.depot_name as new_depot')
            ->leftJoin('depots as d','d.id','=','dis.old_depot_id')
            ->leftJoin('depots as dd','dd.id','=','dis.new_depot_id')
            ->where('dis.id','=',$did)
            ->first();

        if($godown->type==0){
            $type = '荒料';
            $weight = $godown->godown_weight.'m³';
        }else{
            $type = '大板';
            $weight = $godown->godown_weight.'m²';
        }

        $beizhu = (isset($data['remarks']) && $data['remarks'] != '') ? $data['remarks'] : '空';

        $content = '<text class="orange">'.$godown->goods_attr_name.'</text>'.$type.'，编号'.$godown->godown_no.'，数量<text class="orange">'.$weight.'</text>,存放位置由'.$dispatch->old_depot.'变更为<text class="orange">'.$dispatch->new_depot.'</text>，备注：'.$beizhu;
        $this->goWorkLog($godown->company_id,'产品移库',$content,$godown->id);
        $this->adminLog($godown->company_id,1,'录入调度单',$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);

        return response()->json($this->result);
    }

    //产品调库删除
    public function deleteDispatch(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['dispatch_id']) || trim($data['dispatch_id']) == ''){
            return $this->verify_parameter('dispatch_id'); //返回必传参数为空
        }
        $dispatch = Dispatch::find((int)$data['dispatch_id']);
        if($dispatch->status==1){
            return $this->verify_parameter('产品已经重新调库，不能删除',0);
        }

        //判断产品是否已经使用
        $godown = Godown::find((int)$dispatch->godown_id);
        $godown_no = $godown->godown_no;
        $depots = Depots::find((int)$godown->depot_id);
        if($godown->godown_status == 1){
            return $this->verify_parameter('产品已经使用，不能删除',0); //返回必传参数为空
        }

        //开启事务
        DB::beginTransaction();
        try {
            //入库表更新
            $data_upd['depot_id'] = $dispatch->old_depot_id;
            Godown::where('id','=',$dispatch->godown_id)->update($data_upd);

            //调库表删除
            $dispatch->delete();

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('调度删除失败！！',0);
        }

        //记录操作日志
        $this->goWorkLog($depots->company_id,'调度删除',$data['member_name'].'删除了产品编号为<text class="orange">'.$godown_no.'</text>的调度单',$godown->id);
        $this->adminLog($depots->company_id,1,'删除调度单',$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);

        return response()->json($this->result);
    }

    //产品调库修改
    public function updateDispatch(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['dispatch_id']) || trim($data['dispatch_id']) == ''){
            return $this->verify_parameter('dispatch_id'); //返回必传参数为空
        }
        if(!isset($data['new_depot_id']) || trim($data['new_depot_id']) == ''){
            return $this->verify_parameter('new_depot_id'); //返回必传参数为空
        }

        $dispatch = Dispatch::find((int)$data['dispatch_id']);
        $godown_id = $dispatch->godown_id;
        if($dispatch->status==1){
            return $this->verify_parameter('产品已经重新调库，不能修改',0);
        }

        //判断产品是否已经使用
        $godown = Godown::find((int)$dispatch->godown_id);
        if($godown->godown_status == 1){
            return $this->verify_parameter('产品已经使用，不能修改',0); //返回必传参数为空
        }

        //开启事务
        DB::beginTransaction();
        try {
            //入库表更新
            if(isset($data['new_godown_pic']) && $data['new_godown_pic'] != ''){
                $data_upd['godown_pic'] = $data['new_godown_pic'];
            }
            $data_upd['depot_id'] = $data['new_depot_id'];
            Godown::where('id','=',$dispatch->godown_id)->update($data_upd);

            //调库表更新
            if (isset($data['remarks']) && $data['remarks'] != '') {
                $dis_upd['remarks'] = $data['remarks'];
            }
            $dis_upd['new_depot_id'] =  $data['new_depot_id'];
            Dispatch::where('id','=',$data['dispatch_id'])->update($dis_upd);

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('调库修改失败！！',0);
        }

        //记录操作日志
        $godown = Godown::from('godown as g')
            ->select('g.id','g.type','g.godown_weight','g.godown_no','ga.goods_attr_name','d.depot_name','d.company_id')
            ->leftJoin('goods_attr as ga','ga.id','=','g.goods_attr_id')
            ->leftJoin('depots as d','d.id','=','depot_id')
            ->where('g.id','=',$godown_id)
            ->first();

        $dispatch = Dispatch::from('dispatch as dis')
            ->select('d.depot_name as old_depot','dd.depot_name as new_depot')
            ->leftJoin('depots as d','d.id','=','dis.old_depot_id')
            ->leftJoin('depots as dd','dd.id','=','dis.new_depot_id')
            ->where('dis.id','=',$data['dispatch_id'])
            ->first();

        if($godown->type==0){
            $type = '荒料';
            $weight = $godown->godown_weight.'m³';
        }else{
            $type = '大板';
            $weight = $godown->godown_weight.'m²';
        }

        $beizhu = (isset($data['remarks']) && $data['remarks'] != '') ? $data['remarks'] : '空';

        $content = $data['member_name'].'修改了产品编号为'.$godown->godown_no.'的调度单，<text class="orange">'.$godown->goods_attr_name.'</text>'.$type.'，编号'.$godown->godown_no.'，数量<text class="orange">'.$weight.'</text>,存放位置由'.$dispatch->old_depot.'变更为<text class="orange">'.$dispatch->new_depot.'</text>，备注：'.$beizhu;
        $this->goWorkLog($godown->company_id,'移库修改',$content,$godown_id);
        $this->adminLog($godown->company_id,1,'编辑调度单',$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);

        return response()->json($this->result);

    }

    //产品调库列表
    public function getDispatchList(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        //判断是否有可选参数
        if(!isset($data['page']) || trim($data['page']) == ''){
            $data['page'] = 1;
        }
        $start = ((int)$data['page']-1)*20;  //截取部分数据

        $dispatch = Dispatch::from('dispatch as dis')
            ->select('g.godown_no','d.depot_name as old_depot_name','dd.depot_name as new_depot_name','dis.*')
            ->join('depots as d','dis.old_depot_id','=','d.id')
            ->join('depots as dd','dis.new_depot_id','=','dd.id')
            ->join('godown as g','dis.godown_id','=','g.id')
            ->where('d.company_id','=',$data['company_id']);

        //判断是否有筛选条件
        if(isset($data['time_start']) && trim($data['time_start']) != ''){
            if(isset($data['time_end']) && trim($data['time_end']) != ''){
                $data['time_end'] = $this->setlasttime($data['time_end']);
                $dispatch->whereBetween('dis.created_at',[$data['time_start'],$data['time_end']]);
            }else{
                $dispatch->where('dis.created_at','>=',$data['time_start']);
            }
        }
        if(isset($data['time_end']) && trim($data['time_end']) != ''){
            $data['time_end'] = $this->setlasttime($data['time_end']);
            $dispatch->where('dis.created_at','<=',$data['time_end']);
        }

        if(isset($data['goods_attr_id']) && trim($data['goods_attr_id']) != ''){
            $dispatch->where('g.goods_attr_id','=',$data['goods_attr_id']);
        }

        if(isset($data['type']) && trim($data['type']) != ''){
            $dispatch->where('g.type','=',$data['type']);
        }

        if(isset($data['depot_id']) && trim($data['depot_id']) != ''){
            $dispatch->where('g.depot_id','=',$data['depot_id']);
        }

        if(isset($data['soso']) && trim($data['soso']) != ''){
            $dispatch->where(function ($query) use ($data){
                $query->where('g.godown_no','like','%'.$data['soso'].'%')
                    ->orwhere('dis.remarks','like','%'.$data['soso'].'%');
            });
        }

        $this->result['total'] = $dispatch->count();
        $res = $dispatch->orderBy('dis.id','desc')
            ->skip($start)->take(20)
            ->get();

        if(!$res){
            return $this->verify_parameter('查询不到数据！！',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);

    }

    //产品销售
    public function createSale(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['godown_id']) || trim($data['godown_id']) == ''){
            return $this->verify_parameter('godown_id'); //返回必传参数为空
        }
        if(!isset($data['sale_price']) || trim($data['sale_price']) == ''){
            return $this->verify_parameter('sale_price'); //返回必传参数为空
        }
        if(!isset($data['sale_weight']) || trim($data['sale_weight']) == ''){
            return $this->verify_parameter('sale_weight'); //返回必传参数为空
        }
        if(!isset($data['sale_total_price']) || trim($data['sale_total_price']) == ''){
            return $this->verify_parameter('sale_total_price'); //返回必传参数为空
        }
        if(!isset($data['remarks']) || trim($data['remarks']) == ''){
            $data['remarks'] = '';
        }
        if( $data['sale_weight'] * $data['sale_price'] != $data['sale_total_price']){
            return $this->verify_parameter('计算总价有误！！！',0);
        }

        $godown = Godown::where('id','=',$data['godown_id'])->first();
        if($godown->type==1){
            if(!isset($data['sale_number']) || trim($data['sale_number']) == ''){
                return $this->verify_parameter('sale_number'); //返回必传参数为空
            }
            if((int)$data['sale_number'] > $godown->godown_number){
                return $this->verify_parameter('剩余数量不足！！！', 0);
            }

            /*if(!isset($data['sale_no_start']) || trim($data['sale_no_start']) == ''){
                return $this->verify_parameter('sale_no_start'); //返回必传参数为空
            }
            if(!isset($data['sale_no_end']) || trim($data['sale_no_end']) == ''){
                return $this->verify_parameter('sale_no_end'); //返回必传参数为空
            }
            if(intval($data['sale_no_start']) > intval($data['sale_no_end'])){
                return $this->verify_parameter('传过来的产品编号有误！！！',0);
            }
            if(($data['sale_no_end']-$data['sale_no_start']+1) != $data['sale_number']){
                return $this->verify_parameter('产品序号和件数是不匹配的！！',0);
            }*/

            $weight = $godown->godown_weight.'m²';
            $sale_weight = $data['sale_weight'].'m²';
            $type = '大板';
            $unit = 'm²';

            //处理产品序号
            /*$arr = array();
            $no_number = '';
            for($i=intval($data['sale_no_start']);$i<=intval($data['sale_no_end']);$i++){
                $no_number .= $i.',';
            }
            $no_number = substr($no_number,0,(strlen($no_number)-1));
            $arr1 = explode(',',$no_number);
            $arr2 = explode(',',$godown->no_number);
            foreach($arr1 as $v){
                if(!in_array($v,$arr2)){
                    return $this->verify_parameter('有的产品序号不存在',0);
                }
            }
            foreach($arr2 as $v){
                if(!in_array($v,$arr1)){
                    array_push($arr,$v);
                }
            }
            $no_number_new = implode(',',$arr);*/
            $data['sale_no_start'] = 0;
            $data['sale_no_end'] = 0;

        }else{
            $data['sale_number'] = 0;
            $data['sale_no_start'] = 0;
            $data['sale_no_end'] = 0;

            $weight = $godown->godown_weight.'m³';
            $sale_weight = $data['sale_weight'].'m³';
            $type = '荒料';
            $unit = 'm³';
            $no_number_new = '';
        }

        $data_ins['godown_id'] = $data['godown_id'];
        $data_ins['curtype'] = $godown->type;
        $data_ins['old_weight'] = $godown->godown_weight;
        $data_ins['sale_weight'] = $data['sale_weight'];
        $data_ins['old_no_start'] = $godown->no_start;
        $data_ins['sale_no_start'] = $data['sale_no_start'];
        $data_ins['old_no_end'] = $godown->no_end;
        $data_ins['sale_no_end'] = $data['sale_no_end'];
        $data_ins['old_number'] = $godown->godown_number;
        $data_ins['sale_number'] = $data['sale_number'];
        $data_ins['sale_price'] = $data['sale_price'];
        $data_ins['sale_total_price'] = $data['sale_total_price'];
        $data_ins['remarks'] = $data['remarks'];
        $data_ins['created_at'] = Carbon::now()->toDateTimeString();
        $data_ins['updated_at'] = Carbon::now()->toDateTimeString();

        $sale_sale_number = ($godown->godown_number)-$data['sale_number'];
        $sale_new_weight = ($godown->godown_weight)-$data['sale_weight'];
        if($sale_new_weight < 0){
            return $this->verify_parameter('传过来的体积或重量有误！！！',0);
        }

        //开启事务
        DB::beginTransaction();
        try {
            //销售表更新
            /*$total_upd['status'] = 1;
            Sale::where('godown_id','=',$godown->id)->update($total_upd);*/

            Sale::insertGetId($data_ins);

            //入库表更新
            $arr_joindepot['godown_status'] = 1;
            JoinDepot::where('godown_no','=',$godown->godown_no)->update($arr_joindepot);

            //库存表更新
            $data_upd['godown_number'] = $sale_sale_number;  //($godown->godown_number)-$data['sale_number'];
            $data_upd['godown_weight'] = $sale_new_weight;   //($godown->godown_weight)-$data['sale_weight'];
            //$data_upd['no_number'] = $no_number_new;
            if($data_upd['godown_number']==0&&$data_upd['godown_weight']==0){
                $data_upd['godown_status'] = 1;
            }
            Godown::where('id','=',$data['godown_id'])->update($data_upd);

            //开切表更新
            $opencut_upd['status'] = 1;
            Opencut::where('godown_id','=',$data['godown_id'])->update($opencut_upd);

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('销售失败！！',0);
        }

        //操作日志记录
        $godown = Godown::from('godown as g')
            ->select('g.id','g.godown_no','ga.goods_attr_name','d.depot_name','d.company_id')
            ->leftJoin('goods_attr as ga','ga.id','=','g.goods_attr_id')
            ->leftJoin('depots as d','d.id','=','depot_id')
            ->where('g.id','=',$data['godown_id'])
            ->first();

        $beizhu = ($data['remarks']=='')?'空':$data['remarks'];

        $content = '<text class="orange">'.$godown->goods_attr_name.'</text>'.$type.'，编号'.$godown->godown_no.'，库存<text class="orange">'.$weight.'</text>，售出'.$sale_weight; //.'（板材编号'.$data['sale_no_start'].'-'.$data['sale_no_end'].'）'
        $content .= ',单价<text class="orange">'.$data['sale_price'].'元/'.$unit.'</text>，合计<text class="orange">'.$data['sale_total_price'].'元</text>，备注：'.$beizhu;
        $this->goWorkLog($godown->company_id,'产品销售',$content,$godown->id);
        $this->adminLog($godown->company_id,1,'录入销售单',$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);

        return response()->json($this->result);
    }

    //销售订单删除
    public function deleteSale(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['member_name']) || trim($data['member_name']) == ''){
            return $this->verify_parameter('member_name'); //返回必传参数为空
        }
        if(!isset($data['sale_id']) || trim($data['sale_id']) == ''){
            return $this->verify_parameter('sale_id'); //返回必传参数为空
        }
        $sale = Sale::find((int)$data['sale_id']);
        if($sale->status==1){
            return $this->verify_parameter('该订单已不能修改',0);
        }

        //判断产品是否已经使用
        $godown = Godown::find((int)$sale->godown_id);
        $godown_no = $godown->godown_no;
        $depots = Depots::find((int)$godown->depot_id);

        /*$no_number = '';
        for($i=intval($sale->sale_no_start);$i<=intval($sale->sale_no_end);$i++){
            $no_number .= $i.',';
        }
        $no_number = substr($no_number,0,(strlen($no_number)-1));*/

        //开启事务
        DB::beginTransaction();
        try {
            //产品表更新
            $data_upd['godown_weight'] = $sale->old_weight;
            $data_upd['godown_number'] = $godown->godown_number + $sale->sale_number;
            //$data_upd['no_number'] = $godown->no_number.','.$no_number;
            $data_upd['godown_status'] = 0;
            Godown::where('id','=',$sale->godown_id)->update($data_upd);

            //销售表删除
            $sale->delete();

            // 该编号产品没有销售单时可以修改开切单
            if(!Sale::where('godown_id', $sale->godown_id)->exists()){

                Opencut::where('godown_id',  $sale->godown_id)->update(['status' => 0]);

                if($godown->type == 0 || !Opencut::where('godown_id',  $sale->godown_id)->exists()){
                    $arrDepot_upd['godown_status'] = 0;
                    JoinDepot::where('godown_no', '=', $godown->godown_no)->update($arrDepot_upd);
                }

            }

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('销售删除失败！！',0);
        }

        //记录操作日志
        $this->goWorkLog($depots->company_id,'销售删除',$data['member_name'].'删除了产品编号为<text class="orange">'.$godown_no.'</text>的大板销售单',$godown->id);
        $this->adminLog($depots->company_id,1,'删除销售单',$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);

        return response()->json($this->result);
    }

    //销售订单的修改
    public function updateSale(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['sale_id']) || trim($data['sale_id']) == ''){
            return $this->verify_parameter('sale_id'); //返回必传参数为空
        }
        $sale = Sale::find((int)$data['sale_id']);

        //判断产品是否已经使用
        $godown = Godown::find((int)$sale->godown_id);
        $godown_no = $godown->godown_no;
        $godown_id = $sale->godown_id;
        $depots = Depots::find((int)$godown->depot_id);

        //判断传值是否正确
        if(!isset($data['sale_price']) || trim($data['sale_price']) == ''){
            return $this->verify_parameter('sale_price'); //返回必传参数为空
        }
        if(!isset($data['sale_weight']) || trim($data['sale_weight']) == ''){
            return $this->verify_parameter('sale_weight'); //返回必传参数为空
        }
        if(!isset($data['sale_total_price']) || trim($data['sale_total_price']) == ''){
            return $this->verify_parameter('sale_total_price'); //返回必传参数为空
        }
        if(!isset($data['remarks']) || trim($data['remarks']) == ''){
            $data['remarks'] = '';
        }

        if( $data['sale_weight'] * $data['sale_price'] != $data['sale_total_price']){
            return $this->verify_parameter('计算总价有误！！！',0);
        }

        if($godown->type==1){
            if(!isset($data['sale_number']) || trim($data['sale_number']) == ''){
                return $this->verify_parameter('sale_number'); //返回必传参数为空
            }
            /*if(!isset($data['sale_no_start']) || trim($data['sale_no_start']) == ''){
                return $this->verify_parameter('sale_no_start'); //返回必传参数为空
            }
            if(!isset($data['sale_no_end']) || trim($data['sale_no_end']) == ''){
                return $this->verify_parameter('sale_no_end'); //返回必传参数为空
            }
            if(intval($data['sale_no_start']) > intval($data['sale_no_end'])){
                return $this->verify_parameter('传过来的产品编号有误！！！',0);
            }
            if(($data['sale_no_end']-$data['sale_no_start']+1) != $data['sale_number']){
                return $this->verify_parameter('产品序号和件数是不匹配！！',0);
            }*/

            // 判断修改后的件数是否超过当前的件数
            $total_num = (int)$godown->godown_number+(int)$sale->sale_number;
            if((int)$data['sale_number'] > $total_num){
                return $this->verify_parameter('错误不允许修改！！',0);
            }

            $data['sale_no_start'] = 0;
            $data['sale_no_end'] = 0;

            $type = '大板';
            $weight = $godown->godown_weight.'m²';
        }else{
            $total_num = 0;
            $data['sale_number'] = 0;
            $data['sale_no_start'] = 0;
            $data['sale_no_end'] = 0;
            $type = '荒料';
            $weight = $godown->godown_weight.'m³';
        }

        $total_weight = $godown->godown_weight + $sale->sale_weight;
        if($total_weight-$data['sale_weight'] < 0){
            return $this->verify_parameter('错误不允许修改！！',0);
        }


        $status = 0;
        /*if($sale->status==1){

            // 只有备注修改的时候可以修改销售订单
            if($sale->sale_price == $data['sale_price'] && $sale->sale_weight == $data['sale_weight']){
                if($sale->sale_total_price == $data['sale_total_price']){
                    if($sale->curtype == 1){
                        if($sale->sale_number == $data['sale_number']){
                            //  && $sale->sale_no_start == $data['sale_no_start']
                            $status = 1;
                            /*if($sale->sale_no_end == $data['sale_no_end']){

                            }else{
                                return $this->verify_parameter('该订单已不能修改',0);
                            }
                        }else{
                            return $this->verify_parameter('该订单已不能修改',0);
                        }
                    }else{
                        $status = 1;
                    }
                }else{
                    return $this->verify_parameter('该订单已不能修改',0);
                }
            }else{
                return $this->verify_parameter('该订单已不能修改',0); //返回必传参数为空
            }

        }*/

        //处理产品序号
        /*$arr = array();
        $no_number = '';
        $no_number2 = '';
        for($i=intval($data['sale_no_start']);$i<=intval($data['sale_no_end']);$i++){
            $no_number .= $i.',';
        }
        for($i=intval($sale->old_no_start);$i<=intval($sale->old_no_end);$i++){
            $no_number2 .= $i.',';
        }
        $no_number = substr($no_number,0,(strlen($no_number)-1));
        $no_number2 = substr($no_number2,0,(strlen($no_number2)-1));

        $arr1 = explode(',',$no_number);
        $arr2 = explode(',',$no_number2);

        foreach($arr1 as $v){
            if(!in_array($v,$arr2)){
                return $this->verify_parameter('有的产品序号不存在',0);
            }
        }
        foreach($arr2 as $v){
            if(!in_array($v,$arr1)){
                array_push($arr,$v);
            }
        }
        $no_number_new = implode(',',$arr);*/

        $data_ins['sale_weight'] = $data['sale_weight'];
        $data_ins['sale_no_start'] = $data['sale_no_start'];
        $data_ins['sale_no_end'] = $data['sale_no_end'];
        $data_ins['sale_number'] = $data['sale_number'];
        $data_ins['sale_price'] = $data['sale_price'];
        $data_ins['sale_total_price'] = $data['sale_total_price'];
        $data_ins['remarks'] = $data['remarks'];

        //开启事务
        DB::beginTransaction();
        try {
            //销售表更新
            /*$total_upd['status'] = 1;
            Sale::where('godown_id','=',$godown->id)->update($total_upd);*/

            $data_ins['status'] = $status;
            Sale::where('id','=',$data['sale_id'])->update($data_ins);

            //入库表更新
            if(isset($data['new_godown_pic']) && $data['new_godown_pic'] != ''){
                $data_upd['godown_pic'] = $data['new_godown_pic'];
            }
            $data_upd['godown_number'] = $total_num-$data['sale_number'];
            $data_upd['godown_weight'] = $total_weight-$data['sale_weight'];
            //$data_upd['no_number'] = $no_number_new;
            if($data_upd['godown_number']==0&&$data_upd['godown_weight']==0){
                $data_upd['godown_status'] = 1;
            }else{
                $data_upd['godown_status'] = 0;
            }
            Godown::where('id','=',$godown->id)->update($data_upd);

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('销售修改！！',0);
        }

        //操作日志记录

        $godown = Godown::from('godown as g')
            ->select('g.id','g.godown_no','ga.goods_attr_name','d.depot_name','d.company_id')
            ->leftJoin('goods_attr as ga','ga.id','=','g.goods_attr_id')
            ->leftJoin('depots as d','d.id','=','depot_id')
            ->where('g.id','=',$godown_id)
            ->first();

        $beizhu = ($data['remarks']=='')?'空':$data['remarks'];

        $content = $data['member_name'].'修改了产品编号为'.$godown->godown_no.'的销售单，<text class="orange">'.$godown->goods_attr_name.$type.'</text>，编号'.$godown->godown_no.'，库存<text class="orange">'.$weight.'</text>，售出'.$data['sale_weight']; //'（板材编号'.$data['sale_no_start'].'-'.$data['sale_no_end'].'）'
        $content .= ',单价<text class="orange">'.$data['sale_price'].'元/m²</text>，合计<text class="orange">'.$data['sale_total_price'].'元</text>，备注：'.$beizhu;
        $this->goWorkLog($godown->company_id,'销售修改',$content,$godown->id);
        $this->adminLog($godown->company_id,1,'编辑销售单',$data['mem_id'],CompanyUser::IS_ADMIN[$request->user_infos->is_admin]);

        return response()->json($this->result);
    }

    //销售订单列表
    public function getSaleList(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        //判断是否有可选参数
        if(!isset($data['page']) || trim($data['page']) == ''){
            $data['page'] = 1;
        }
        $start = ((int)$data['page']-1)*20;  //截取部分数据

        $sale = Sale::from('sale as s')
            ->select('g.type','g.godown_no','g.godown_weight','s.*')
            ->join('godown as g','g.id','=','s.godown_id')
            ->join('depots as d','d.id','=','g.depot_id')
            ->where('d.company_id','=',$data['company_id']);

        //判断是否有筛选条件
        if(isset($data['time_start']) && trim($data['time_start']) != ''){
            if(isset($data['time_end']) && trim($data['time_end']) != ''){
                $data['time_end'] = $this->setlasttime($data['time_end']);
                $sale->whereBetween('s.created_at',[$data['time_start'],$data['time_end']]);
            }else{
                $sale->where('s.created_at','>=',$data['time_start']);
            }
        }
        if(isset($data['time_end']) && trim($data['time_end']) != ''){
            $data['time_end'] = $this->setlasttime($data['time_end']);
            $sale->where('s.created_at','<=',$data['time_end']);
        }

        if(isset($data['goods_attr_id']) && trim($data['goods_attr_id']) != ''){
            $sale->where('g.goods_attr_id','=',$data['goods_attr_id']);
        }

        if(isset($data['type']) && trim($data['type']) != ''){
            $sale->where('g.type','=',$data['type']);
        }

        if(isset($data['depot_id']) && trim($data['depot_id']) != ''){
            $sale->where('g.depot_id','=',$data['depot_id']);
        }

        if(isset($data['soso']) && trim($data['soso']) != ''){
            $sale->where(function ($query) use ($data){
                $query->where('g.godown_no','like','%'.$data['soso'].'%')
                    ->orWhere('s.remarks','like','%'.$data['soso'].'%');
            });
        }

		$this->result['total'] = $sale->count();
        $res = $sale->orderBy('s.id','desc')
            ->skip($start)->take(20)
            ->get();

        if(!$res){
            return $this->verify_parameter('查询不到数据！！',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    //===========================================================================================

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

    //记录操作日志的函数
    private function goWorkLog($company_id,$title,$content,$godown_id=0){
        $log = array();
        $log['title'] = $title;
        $log['godown_id'] = $godown_id;
        $log['company_id'] = $company_id;
        $log['content'] = $content;
        $log['created_at'] = Carbon::now()->toDateTimeString();
        $log['updated_at'] = Carbon::now()->toDateTimeString();

        Worklog::insertGetId($log); //插入数据库
    }

    //时间处理函数
    private function setlasttime($lasttime){
        $cur = substr($lasttime,0,4);
        $cur1 = substr($lasttime,5,1);
        $cur2 = substr($lasttime,6,1);
        if($cur1==0){
            if($cur2<9){
                return $cur.'-0'.($cur2+1).'-01 00:00:00';
            }else{
                return $cur.'-'.($cur2+1).'-01 00:00:00';
            }
        }else{
            if($cur2<2){
                return $cur.'-1'.($cur2+1).'-01 00:00:00';
            }else{
                return (intval($cur)+1).'-01-01 00:00:00';
            }
        }
    }

    //操作记录方法
    private function adminLog($company_id,$type,$content,$user_id,$identity){
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
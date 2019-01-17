<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/31
 * Time: 0:30
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin\AdminLog;
use App\Models\Admin\Company;
use App\Models\Admin\CompanyUser;
use App\Models\Admin\Configs;
use App\Models\Admin\Depots;
use App\Models\Admin\Dispatch;
use App\Models\Admin\Godown;
use App\Models\Admin\GoodsAttr;
use App\Models\Admin\JoinDepot;
use App\Models\Admin\Monthly;
use App\Models\Admin\Opencut;
use App\Models\Admin\Order;
use App\Models\Admin\Sale;
use App\Models\Admin\Worklog;
use App\Models\Admin\TransferCompany;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Admin\Members;
use Carbon\Carbon;
use DB;

class WechatController   extends Controller
{
    /**
     * 参数定义
     */
    public $result = ['status' => 0, 'msg' => '请求成功', 'data' => ''];
    private $wechat_appid = '';
    private $wechat_secret = '';
    private $mch_id = '';
    private $mch_key = '';
    private $notify_url = '';

    /**
     * 初始化各项配置
     */
    public function __construct(Request $request){
        $this->middleware('checkApi');
      
        $con = Configs::first();
        $this->wechat_appid = $con->wechat_appid;
        $this->wechat_secret = $con->wechat_secret;
        $this->mch_id = $con->mch_id;
        $this->mch_key = $con->mch_key;
        $this->notify_url = $con->notify_url;
    }

    /**
     * 获取小程序码
     */
    public function getWXACodeUnlimit (Request $request) {
        $scene = json_encode($request->post());
        $data = $this->getQrcode($this->wechat_appid, $this->wechat_secret, $scene);
        $resData = json_decode($data, true);
        if (isset($resData['errcode']) && $resData['errcode']) {
            return $this->verify_parameter($resData['errmsg'], 0);
        }
        return response($data, 200)->header('Content-Type', 'image/jpg');
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo (Request $request) {
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['code']) || trim($data['code']) == ''){
            return $this->verify_parameter('code'); //返回必传参数为空
        }

        $url = "https://api.weixin.qq.com/sns/jscode2session?";
        $url .= "appid=".$this->wechat_appid;
        $url .= "&secret=".$this->wechat_secret;
        $url .= "&js_code=".$data['code'];
        $url .= "&grant_type=authorization_code";

        $arr = array();
        $data_ins = array();
        $data_upd = array();

        $res = file_get_contents($url);  //请求微信小程序获取用户接口
        $arr = json_decode($res,true);
        if (isset($arr['errcode']) && !empty($arr['errcode'])) {
            return $this->verify_parameter('请求微信接口报错！！！请联系管理员...',0);
        }

        $members = Members::where('openid','=',$arr['openid'])->first();
        if(!$members){
            $data_ins['openid'] =  $arr['openid'];
            $data_ins['session_key'] = $arr['session_key'];
            $data_ins['nickname'] = '';
            $data_ins['realname'] = '';
            $data_ins['pic'] = '';
            $data_ins['mobile'] = '';
            $data_ins["created_at"] = Carbon::now()->toDateTimeString();
            $data_ins["updated_at"] = Carbon::now()->toDateTimeString();

            $data_ins['id'] = Members::insertGetId($data_ins); //插入数据库

            $companys = array();
        }else{
            $data_upd['session_key'] =  $arr['session_key'];
            Members::where('openid','=',$arr['openid'])->update($data_upd);

            $data_ins = Members::from('members as m')->where('openid','=',$arr['openid'])->first();

            $companys = CompanyUser::from('company_user as cu')
                ->select('cu.*','c.company_name','c.volid_time')
                ->leftJoin('company as c','c.id','=','cu.company_id')
                ->where('user_id','=',$data_ins['id'])
                ->get();
            if($companys){
                $companys = $companys->toArray();
            }
        }

        //拼接返回的信息
        $return = array();
        $return['id'] = $data_ins['id'];
        $return['members_id'] = $data_ins['id'];
        $return['openid'] = $data_ins['openid'];
        $return['nickname'] = $data_ins['nickname'];
        $return['realname'] = $data_ins['realname'];
        $return['pic'] = $data_ins['pic'];
        $return['mobile'] = $data_ins['mobile'];
        $return['companys'] = $companys;

        //判断是否有企业
        if(isset($data_ins["company_id"])){
            $comp = Company::where('id','=',$data_ins["company_id"])->first();
            $return['company_status'] = $comp->company_status;
        }

        $this->result['data'] = $return;
        return response()->json($this->result);
    }

    //根据openid获取用户信息
    public function getUsers(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['openid']) || trim($data['openid']) == ''){
            return $this->verify_parameter('openid'); //返回必传参数为空
        }

        $res = Members::from('members as m')
            ->where('m.openid','=',$data['openid'])
            ->first();

        if(!$res){
            return $this->verify_parameter('查不到数据',0);
        }

        $res = $res->toArray();
        $res['companys'] = CompanyUser::from('company_user as cu')
            ->select('cu.*','c.company_name','c.volid_time')
            ->leftJoin('company as c','c.id','=','cu.company_id')
            ->where('cu.user_id','=',$res['id'])
            ->get();

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    //注销企业
    public function logoutCompany(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        //开启事务
        DB::beginTransaction();
        try {

            $goods_attr = GoodsAttr::from('goods_attr as ga')
                ->select('g.id')
                ->leftJoin('godown as g','ga.id','=','g.goods_attr_id')
                ->where('company_id','=',$data['company_id'])
                ->get()->toArray();

            foreach($goods_attr as $v){
                Godown::where('id','=',$v['id'])->delete();
                JoinDepot::where('id','=',$v['id'])->delete();
                Dispatch::where('godown_id','=',$v['id'])->delete();
                Opencut::where('godown_id','=',$v['id'])->delete();
                Sale::where('godown_id','=',$v['id'])->delete();
            }

            GoodsAttr::where('company_id','=',$data['company_id'])->delete();
            Depots::where('company_id','=',$data['company_id'])->delete();
            CompanyUser::where('company_id','=',$data['company_id'])->delete();
            Company::where('id','=',$data['company_id'])->delete();
            AdminLog::where('company_id','=',$data['company_id'])->delete();
            Worklog::where('company_id','=',$data['company_id'])->delete();

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('注销企业失败！！',0);
        }

        return response()->json($this->result);
    }

    //修改企业邀请码
    public function updateCompanyPass(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if(!isset($data['company_pass']) || trim($data['company_pass']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        $data_upd['company_pass'] = $data['company_pass'];
        $bool = Company::where('id','=',$data['company_id'])->update($data_upd);
        if(!$bool){
            return $this->verify_parameter('修改企业邀请码失败！请联系管理员',0); //返回必传参数为空
        }
        return response()->json($this->result);
    }

    /**
     * 数据库列表
     *
     * @param rk_sort - 入库时间（1升，0降）
     * @param st_sort - 库存数量（1升，0降）
     * @param xs_sort - 已销售额（1升，0降）
     */
    public function databaseList(Request $request) {

        // 获取参数
        $data = $request->post();

        // 判断传值是否正确
        if (!isset($data['company_id']) || trim($data['company_id']) == '') {
            return $this->verify_parameter('company_id');
        }

        // 判断是否有可选参数
        if (!isset($data['page']) || trim($data['page']) == '') {
            $data['page'] = 1;
        }

        // 截取部分数据
        $start = ((int)$data['page']-1)*10;

        $godownIds = array();
        if (isset($data['soso']) &&  trim($data['soso']) != '') {
            $arr1 = Dispatch::where('remarks', 'like', '%'.$data['soso'].'%')->get(['godown_id']);
            $arr2 = JoinDepot::from('joindepot as j')
                ->select('g.id as godown_id')
                ->join('godown as g', 'g.godown_no', '=', 'j.godown_no')
                ->where('j.remarks', 'like', '%'.$data['soso'].'%')
                ->get();
            $arr3 = Opencut::where('remarks', 'like', '%'.$data['soso'].'%')->get(['godown_id']);
            $arr4 = Sale::where('remarks', 'like', '%'.$data['soso'].'%')->get(['godown_id']);

            if ($arr1) {
                foreach ($arr1 as $v){
                    if(!in_array($v->godown_id, $godownIds)){
                        $godownIds[] = $v->godown_id;
                    }
                }
            }
            if ($arr2) {
                foreach ($arr2 as $v){
                    if(!in_array($v->godown_id, $godownIds)){
                        $godownIds[] = $v->godown_id;
                    }
                }
            }
            if ($arr3) {
                foreach ($arr3 as $v){
                    if(!in_array($v->godown_id, $godownIds)){
                        $godownIds[] = $v->godown_id;
                    }
                }
            }
            if ($arr4) {
                foreach ($arr4 as $v){
                    if(!in_array($v->godown_id, $godownIds)){
                        $godownIds[] = $v->godown_id;
                    }
                }
            }
        }

        $godown= Godown::from('godown as g')
            ->select('g.id','g.type','ga.goods_attr_name','d.depot_name','g.godown_no','g.godown_weight','g.godown_length','g.godown_width','g.godown_height','g.godown_pic','g.godown_number','g.no_start','g.no_end','g.created_at as addtime','g.remarks as godown_remarks', DB::raw("SUM(s.sale_total_price) as sale_total_price"))
            ->leftJoin('goods_attr as ga','g.goods_attr_id','=','ga.id')
            ->leftJoin('sale as s','g.id','=','s.godown_id')
            ->leftJoin('depots as d','d.id','=','g.depot_id')
            ->where('d.company_id', $data['company_id']);

        if (isset($data['goods_attr_id']) && trim($data['goods_attr_id']) != '') {
            $godown->where('g.goods_attr_id', $data['goods_attr_id']);
        }
        if (isset($data['type']) && trim($data['type']) != '') {
            $godown->where('g.type', $data['type']);
        }
        if (isset($data['depot_id']) && trim($data['depot_id']) != '') {
            $godown->where('g.depot_id', $data['depot_id']);
        }

        if (isset($data['soso']) && trim($data['soso']) != '') {
            $godown->where(function ($query) use ($data, $godownIds) {
                $query->whereIn('g.id', $godownIds)
                      ->orwhere('g.godown_no', 'like', '%'.$data['soso'].'%');
            });
        }
		$total = $godown->count();

        // 排序: rk_sort-入库时间
        if (isset($data['rk_sort']) && trim($data['rk_sort']) != '') {
            if (1 == $data['rk_sort']) {
                $godown->orderBy('g.created_at','asc');
            } else {
                $godown->orderBy('g.created_at','desc');
            }
        }
        // 排序：st_sort-库存数量
        if (isset($data['st_sort']) && trim($data['st_sort']) != '') {
            if (1 == $data['st_sort']) {
                $godown->orderBy('g.godown_number','asc');
            } else {
                $godown->orderBy('g.godown_number','desc');
            }
        }
        // 排序：xs_sort-已销售额
        // if (isset($data['xs_sort']) && trim($data['xs_sort']) != '') {
        //     if (1 == $data['xs_sort']) {
        //         $godown->orderBy('s.sale_total_price','asc');
        //     } else {
        //         $godown->orderBy('s.sale_total_price','desc');
        //     }
        // }

        // 获取数据库列表
        $res = $godown->skip($start)->take(10)->get();
        if (! $res) {
            return $this->verify_parameter('查不到数据', 0);
        }

        // 备注：销售单(sale_remarks)和调度单(dispatch_remarks)存在多个，入库单(godown_remarks)和开切单(opencut_remarks)一个
        foreach ($res as $k => &$v) {
            // 开切单备注
            $v->opencut_remarks = Opencut::where('godown_id', $v->id)->value('remarks');

            // 销售单备注
            $v->sale_remarks = $this->getSaleRemarks($v->id);

            // 调度单备注
            $v->dispatch_remarks = $this->getDispatchRemarks($v->id);
        }

		$this->result['total'] = $total;
        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    //产品操作记录查询
    public function getGodownLog(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['godown_id']) || trim($data['godown_id']) == ''){
            return $this->verify_parameter('godown_id'); //返回必传参数为空
        }

        $res = Worklog::from('work_log as wl')
            ->select('wl.id','wl.title','wl.content','wl.created_at')
            ->where('godown_id','=',$data['godown_id'])
            ->orderBy('wl.id','desc')
            ->get();

        if(!$res){
            return $this->verify_parameter('查不到数据',0);
        }

        $this->result['data'] = $res;
        return response()->json($this->result);

    }

    //按品种查询库存
    public function getStock(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if(!isset($data['type']) || trim($data['type']) == ''){
            return $this->verify_parameter('type'); //返回必传参数为空
        }
        if(trim($data['type'])!=0 && trim($data['type'])!=1){
            return $this->verify_parameter('type传值有误！！！',0);
        }

        $res = GoodsAttr::from('goods_attr as ga')
            ->select('ga.id','ga.goods_attr_name',DB::raw('sum(g.godown_weight) as total_weight'))
            ->leftJoin('godown as g','g.goods_attr_id','=','ga.id')
            ->where('ga.company_id','=',$data['company_id'])
            ->where('g.type','=',$data['type'])
            ->groupBy('ga.id')
            ->get()->toArray();

        $goods_attr = GoodsAttr::where('company_id','=',$data['company_id'])->get();
        $arr001 = array();
        $arr002 = array();

        foreach($goods_attr as $v){
            $arr001[] = $v['id'];
        }
        foreach($res as $v){
            $arr002[] = $v['id'];
        }

        foreach($arr001 as $v){
            if(!in_array($v,$arr002)){
                foreach($goods_attr as $val){
                    if($v==$val['id']){
                        $res[] = array(
                            'id' => $val['id'],
                            'goods_attr_name' => $val['goods_attr_name'],
                            'total_weight' => 0
                        );
                    }
                }
            }
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    //按仓库查询库存
    public function getDepotStock(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if(!isset($data['type']) || trim($data['type']) == ''){
            return $this->verify_parameter('type'); //返回必传参数为空
        }
        if(trim($data['type'])!=0 && trim($data['type'])!=1){
            return $this->verify_parameter('type传值有误！！！',0);
        }

        $res = Depots::from('depots as d')
            ->select('d.id','d.depot_name',DB::raw('sum(g.godown_weight) as total_weight'))
            ->leftJoin('godown as g','g.depot_id','=','d.id')
            ->where('d.company_id','=',$data['company_id'])
            ->where('g.type','=',$data['type'])
            ->groupBy('d.id')
            ->get()->toArray();

        $depots = Depots::where('company_id','=',$data['company_id'])->get();
        $arr001 = array();
        $arr002 = array();

        foreach($depots as $v){
            $arr001[] = $v['id'];
        }
        foreach($res as $v){
            $arr002[] = $v['id'];
        }

        foreach($arr001 as $v){
            if(!in_array($v,$arr002)){
                foreach($depots as $val){
                    if($v==$val['id']){
                        $res[] = array(
                            'id' => $val['id'],
                            'depot_name' => $val['depot_name'],
                            'total_weight' => 0
                        );
                    }
                }
            }
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    //根据品种查询各仓库库存
    public function getGoodsAttrStock(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['goods_attr_id']) || trim($data['goods_attr_id']) == ''){
            return $this->verify_parameter('goods_attr_id'); //返回必传参数为空
        }
        if(!isset($data['type']) || trim($data['type']) == ''){
            return $this->verify_parameter('type'); //返回必传参数为空
        }
        if(trim($data['type'])!=0 && trim($data['type'])!=1){
            return $this->verify_parameter('type传值有误！！！',0);
        }

        $res = Depots::from('depots as d')
            ->select('d.id','d.depot_name',DB::raw('sum(g.godown_weight) as total_weight'))
            ->leftJoin('godown as g','g.depot_id','=','d.id')
            ->where('g.goods_attr_id','=',$data['goods_attr_id'])
            ->where('g.type','=',$data['type'])
            ->groupBy('d.id')
            ->get()->toArray();

        $goods_attr = GoodsAttr::where('id','=',$data['goods_attr_id'])->first();
        $depots = Depots::where('company_id','=',$goods_attr->company_id)->get();

        $arr001 = array();
        $arr002 = array();

        foreach($depots as $v){
            $arr001[] = $v['id'];
        }
        foreach($res as $v){
            $arr002[] = $v['id'];
        }

        foreach($arr001 as $v){
            if(!in_array($v,$arr002)){
                foreach($depots as $val){
                    if($v==$val['id']){
                        $res[] = array(
                            'id' => $val['id'],
                            'depot_name' => $val['depot_name'],
                            'total_weight' => 0
                        );
                    }
                }
            }
        }

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    //按品种统计销量
    public function getGoodsAttrSale(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if(!isset($data['data_time']) || trim($data['data_time']) == ''){
            return $this->verify_parameter('data_time'); //返回必传参数为空
        }

        if(trim($data['data_time'])!='月' && trim($data['data_time'])!='季' && trim($data['data_time'])!='年'){
            return $this->verify_parameter('data_time传值有误！！！',0);
        }

        $arr = $this->getDatetime(trim($data['data_time']));

        $cur = GoodsAttr::from('goods_attr as ga')
            ->select('ga.id','ga.goods_attr_name as name',DB::raw('sum(s.sale_total_price) as proportion'))
            ->leftJoin('godown as g','g.goods_attr_id','=','ga.id')
            ->leftJoin('sale as s','s.godown_id','=','g.id')
            ->where('ga.company_id','=',trim($data['company_id']))
            ->whereBetween('s.created_at',[$arr['curstart'],$arr['curend']])
            ->groupBy('ga.id')
            ->get()->toArray();

        $last = GoodsAttr::from('goods_attr as ga')
            ->select('ga.id','ga.goods_attr_name as name',DB::raw('sum(s.sale_total_price) as proportion'))
            ->leftJoin('godown as g','g.goods_attr_id','=','ga.id')
            ->leftJoin('sale as s','s.godown_id','=','g.id')
            ->where('ga.company_id','=',trim($data['company_id']))
            ->whereBetween('s.created_at',[$arr['laststart'],$arr['lastend']])
            ->groupBy('ga.id')
            ->get()->toArray();

        if(!$cur){
            if($last){
                foreach($last as $k=>$v){
                    $cur[] = array(
                        'id' => $v['id'],
                        'name' => $v['name'],
                        'proportion' => 0
                    );
                }
            }
        }

        if(!$last){
            if($cur){
                foreach($cur as $k=>$v){
                    $last[] = array(
                        'id' => $v['id'],
                        'name' => $v['name'],
                        'proportion' => 0
                    );
                }
            }
        }
        $goods_attr = GoodsAttr::where('company_id','=',$data['company_id'])->get();
        if($last&&$cur){
            $cur_001 = array();
            $cur_002 = array();
            $last_001 = array();
            $last_002 = array();

            foreach($goods_attr as $v){
                $cur_001[] = $v['id'];
            }
            foreach($cur as $v){
                $cur_002[] = $v['id'];
            }
            foreach($goods_attr as $v){
                $last_001[] = $v['id'];
            }
            foreach($last as $v){
                $last_002[] = $v['id'];
            }

            foreach($cur_001 as $v){
                if(!in_array($v,$cur_002)){
                    foreach($goods_attr as $val){
                        if($v==$val['id']){
                            $cur[] = array(
                                'id' => $val['id'],
                                'name' => $val['goods_attr_name'],
                                'proportion' => 0
                            );
                        }
                    }
                }
            }

            foreach($last_001 as $v){
                if(!in_array($v,$last_002)){
                    foreach($goods_attr as $val){
                        if($v==$val['id']){
                            $last[] = array(
                                'id' => $val['id'],
                                'name' => $val['goods_attr_name'],
                                'proportion' => 0
                            );
                        }
                    }
                }
            }

        }else{
            foreach($goods_attr as $val){
                $last[] = array(
                    'id' => $val['id'],
                    'name' => $val['goods_attr_name'],
                    'proportion' => 0
                );
                $cur[] = array(
                    'id' => $val['id'],
                    'name' => $val['goods_attr_name'],
                    'proportion' => 0
                );
            }
        }

        $return['cur'] = $cur;
        $return['last'] = $last;

        $this->result['data'] = $return;
        return response()->json($this->result);

    }

    //按品种统计本月销售额
    public function getCurSaleMoney(Request $request){
        $data = $request->post();

        $curdata = Carbon::now()->toDateTimeString();
        //判断传值是否正确
        if(!isset($data['goods_attr_id']) || trim($data['goods_attr_id']) == ''){
            return $this->verify_parameter('goods_attr_id'); //返回必传参数为空
        }
        if(!isset($data['data_time']) || trim($data['data_time']) == ''){
            return $this->verify_parameter('data_time'); //返回必传参数为空
        }

        if(trim($data['data_time'])!='月' && trim($data['data_time'])!='季' && trim($data['data_time'])!='年'){
            return $this->verify_parameter('data_time传值有误！！！',0);
        }
        $arr111 = $this->getDatetime(trim($data['data_time']));

        $res = Sale::from('sale as s')
            ->select('s.*','g.type')
            ->leftJoin('godown as g','g.id','=','s.godown_id')
            ->where('g.goods_attr_id','=',$data['goods_attr_id'])
            ->whereBetween('s.created_at',[$arr111['curstart'],$arr111['curend']])
            ->get();

        $return['goods_attr_name'] = GoodsAttr::where('id','=',$data['goods_attr_id'])->first()->goods_attr_name;
        $return['material'] = 0;
        $return['product'] = 0;
        $return['material_num'] = 0;
        $return['product_num'] = 0;

        if($res){
            $res = $res->toArray();
            foreach($res as $k=>$v){
                if($v['curtype']==0){
                    $return['material'] += $v['sale_total_price'];
                    $return['material_num'] += $v['sale_weight'];
                }
                if($v['curtype']==1){
                    $return['product'] += $v['sale_total_price'];
                    $return['product_num'] += $v['sale_weight'];
                }
            }
        }

        $this->result['data'] = $return;
        return response()->json($this->result);
    }

    //按品种统计上月销售额
    public function getLastSaleMoney(Request $request){
        $data = $request->post();

        $curdata = Carbon::now()->parse('-1 months')->toDateTimeString();
        //判断传值是否正确
        if(!isset($data['goods_attr_id']) || trim($data['goods_attr_id']) == ''){
            return $this->verify_parameter('goods_attr_id'); //返回必传参数为空
        }
        if(!isset($data['data_time']) || trim($data['data_time']) == ''){
            return $this->verify_parameter('data_time'); //返回必传参数为空
        }

        if(trim($data['data_time'])!='月' && trim($data['data_time'])!='季' && trim($data['data_time'])!='年'){
            return $this->verify_parameter('data_time传值有误！！！',0);
        }
        $arr111 = $this->getDatetime(trim($data['data_time']));

        $res = Sale::from('sale as s')
            ->select('s.*','g.type')
            ->leftJoin('godown as g','g.id','=','s.godown_id')
            ->where('g.goods_attr_id','=',$data['goods_attr_id'])
            ->whereBetween('s.created_at',[$arr111['laststart'],$arr111['lastend']])
            ->get();

        $return['goods_attr_name'] = GoodsAttr::where('id','=',$data['goods_attr_id'])->first()->goods_attr_name;
        $return['material'] = 0;
        $return['product'] = 0;
        $return['material_num'] = 0;
        $return['product_num'] = 0;

        if($res){
            $res = $res->toArray();
            foreach($res as $k=>$v){
                if($v['curtype']==0){
                    $return['material'] += $v['sale_total_price'];
                    $return['material_num'] += $v['sale_weight'];
                }
                if($v['curtype']==1){
                    $return['product'] += $v['sale_total_price'];
                    $return['product_num'] += $v['sale_weight'];
                }
            }
        }

        $this->result['data'] = $return;
        return response()->json($this->result);
    }

    //去支付
    public function goPay(Request $request){
        $data = $request->post();

        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        if(!isset($data['user_id']) || trim($data['user_id']) == ''){
            return $this->verify_parameter('user_id'); //返回必传参数为空
        }

        if(!isset($data['monthly_id']) || trim($data['monthly_id']) == ''){
            return $this->verify_parameter('monthly_id'); //返回必传参数为空
        }

        $user = Members::where('id','=',$data['user_id'])->first();
        $monthly = Monthly::where('id','=',$data['monthly_id'])->first();

        $order_sn = $this->getOrderSn();
        $bool = $this->createOrder($data['user_id'],$data['company_id'],$data['monthly_id'],$monthly->cur_price,$order_sn);
        if(!$bool){
            return $this->verify_parameter('产生订单失败！！！',0);
        }

        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

        $arr['appid'] = $this->wechat_appid;
        $arr['mch_id'] = $this->mch_id;
        $arr['openid'] = $user->openid;
        $arr['nonce_str'] = $this->createNoncestr();
        $arr['body'] = '充值会员';
        $arr['out_trade_no'] = $order_sn;
        $arr['total_fee'] = 1;//$monthly->cur_price * 100;
        $arr['spbill_create_ip'] = $_SERVER['SERVER_ADDR'];
        $arr['notify_url'] = $this->notify_url.'/api/notify';
        $arr['trade_type'] = 'JSAPI';

        $arr['sign'] = $this->getSign($arr);
      
        $xmlData = $this->arrayToXml($arr);
        
        $res = $this->xmlToArray($this->postXmlCurl($xmlData,$url,60));
		
        if($res['return_code'] != 'SUCCESS' || $res['result_code'] != 'SUCCESS'){
            return $this->verify_parameter('生成支付信息失败！！！',0);
        }

        $return['appId'] = $res['appid'];
        $return['timeStamp'] = time();
        $return['nonceStr'] = $res['nonce_str'];
        $return['package'] = 'prepay_id='.$res['prepay_id'];
        $return['signType'] = 'MD5';
        $return['paySign'] = $this->getSign($return);

        $this->result['data'] = $return;
        return response()->json($this->result);
    }

    //充值异步通知
    public function notify(Request $request){
        $xml = file_get_contents("php://input");
        $data = $this->xmlToArray($xml);
      
/*
$fp = fopen('./file1.txt', 'a+b');
fwrite($fp, "\r\n".print_r($data,true));
fclose($fp);*/
      
        if($data['result_code']=='SUCCESS'){
            if($data['return_code']=='SUCCESS'){
              
                $order = Order::where('order_sn','=',$data['out_trade_no'])->first();
              
              	if($order->pay_status == 1){
                	echo 'success';die;
                }
              
                $data_upd['pay_status'] = 1;
                $data_upd['pay_time'] = $data['time_end'];

                $bool= Order::where('order_sn','=',$data['out_trade_no'])->update($data_upd);

                $monthly = Monthly::where('id','=',$order->monthly_id)->first();
				
                $company = Company::where('id','=',$order->company_id)->first();
                
                if(!empty($company->valid_time) && $company->valid_time > Carbon::now()->toDateTimeString()){
                    $upd['volid_time'] = Carbon::parse($company->valid_time)->modify('+'.$monthly->month.' days')->toDateTimeString();
                }else{
                    $upd['volid_time'] = Carbon::parse('+'.$monthly->month.' days')->toDateTimeString();
                }

                Company::where('id','=',$order->company_id)->update($upd);
              
                if($bool){
                    echo 'success';die;
                }
            }
        }
    }

    // 转让企业接口
    public function turnCompany(Request $request){
        $data = $request->post();

        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if(!isset($data['member_id']) || trim($data['member_id']) == ''){
            return $this->verify_parameter('member_id'); //返回必传参数为空
        }

        $tc = TransferCompany::where('company_id','=',$data['company_id'])->where('new_admin','=',$data['member_id'])->where('is_del','=',0)->count();
        if($tc){
            return $this->verify_parameter('企业已经申请转让', 0);
        }
      
        $cu = CompanyUser::from('company_user as cu')
          		->select('cu.*','m.realname')
          		->leftJoin('members as m','m.id','=','cu.user_id')
          		->where('cu.company_id','=',$data['company_id'])
          		->where('cu.is_admin','=',1)
          		->first();

        $data_ins['company_id'] = $data['company_id'];
        $data_ins['old_admin'] = $cu->user_id;
        $data_ins['new_admin'] = $data['member_id'];
        $data_ins['is_del'] = 0;
        $data_ins['status'] = 0;
        $data_ins['created_at'] = Carbon::now()->toDateTimeString();
        $data_ins['updated_at'] = Carbon::now()->toDateTimeString();

        $tcId = TransferCompany::insertGetId($data_ins);
        if(!$tcId){
            return $this->verify_parameter('转让企业失败！！', 0);
        }
        $this->result['data'] = array('id' => $tcId);
     
      	// 记录日志
      	$member = Members::find($data['member_id']);
        $this->goWorkLog($data['company_id'], '企业转让', '管理员<text class="orange">'.$cu->realname.'</text>向<text class="orange">'.$member->realname.'</text>出让企业管理权');
      
        return response()->json($this->result);
    }

    // 接受企业接口
    public function receiveCompany(Request $request){
        $data = $request->post();

        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }

        if(!isset($data['member_id']) || trim($data['member_id']) == ''){
            return $this->verify_parameter('member_id'); //返回必传参数为空
        }

        if(!isset($data['type']) || trim($data['type']) == ''){
            return $this->verify_parameter('type'); //返回必传参数为空
        }

        if($data['type'] != 1 && $data['type'] != 2){
            return $this->verify_parameter('type参数有误!!!', 0);
        }

        $tc = TransferCompany::where('company_id','=',$data['company_id'])->where('new_admin','=',$data['member_id'])->where('is_del','=',0)->first();

        if(!$tc){
            return  $this->verify_parameter('数据有误！！！', 0); //返回必传参数为空
        }

        //开启事务
        DB::beginTransaction();
        try {
          	$member = Members::find($data['member_id']);
            if($data['type'] == 1){
                CompanyUser::where('company_id','=',$data['company_id'])->where('user_id','=',$data['member_id'])->update(['is_admin' => 1]);
                CompanyUser::where('company_id','=',$data['company_id'])->where('user_id','=',$tc->old_admin)->update(['is_admin' => 0]);
        		$this->goWorkLog($data['company_id'], '企业转让', '<text class="orange">'.$member->realname.'</text>已接受企业管理权，成为本企业的新管理员');
            }else{
              	$this->goWorkLog($data['company_id'], '企业转让', '<text class="orange">'.$member->realname.'</text>没有响应企业管理权的移交');
            }

            TransferCompany::where('company_id','=',$data['company_id'])->where('new_admin','=',$data['member_id'])->update(['is_del' => 1,'status' => $data['type']]);

            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务
            return $this->verify_parameter('操作失败！！',0);
        }

        return response()->json($this->result);
    }

    // 转让企业列表
    public function turnCompanyList(Request $request){
        $data = $request->post();

        if(!isset($data['member_id']) || trim($data['member_id']) == ''){
            return $this->verify_parameter('member_id'); //返回必传参数为空
        }

        $tc = TransferCompany::from('transfer_company as tc')
            ->select('tc.*','c.company_name','nm.realname as new_admin_name','om.realname as old_admin_name')
            ->leftJoin('company as c','c.id','=','tc.company_id')
            ->leftJoin('members as nm','nm.id','=','tc.new_admin')
            ->leftJoin('members as om','om.id','=','tc.old_admin')
            ->where(function ($query) use ($data){
                $query->where('old_admin','=',$data['member_id'])
                    ->orwhere('new_admin','=',$data['member_id']);
            })
            ->where('is_del','=',0)
            ->get();

        if(!$tc){
            return $this->verify_parameter('查不到数据！！'); //返回必传参数为空
        }

        $tc = $tc->toArray();
        foreach ($tc as $k => $v){
            if($v['old_admin'] == $data['member_id']){
                $tc[$k]['type'] = 0;
            }else{
                $tc[$k]['type'] = 1;
            }
        }

        $this->result['data'] = $tc;
        return response()->json($this->result);

    }

    // 查询企业转让状态
    public function getTransferCompanyStatus(Request $request){
        $data = $request->post();

        if(!isset($data['tc_id']) || trim($data['tc_id']) == ''){
            return $this->verify_parameter('tc_id'); //返回必传参数为空
        }

        $tc = TransferCompany::find($data['tc_id']);

        if(!$tc){
            return $this->verify_parameter('数据有误!!', 0); //返回必传参数为空
        }

        $this->result['data'] = array('status' => $tc->status);
        return response()->json($this->result);
    }

    //==================================================================================
    // 产生订单
    private function createOrder($user_id,$company_id,$monthly_id,$moeny,$order_sn,$order_name='充值会员'){
        $data['order_sn'] = $order_sn;
        $data['order_name'] = $order_name;
        $data['user_id'] = $user_id;
        $data['company_id'] = $company_id;
        $data['monthly_id'] = $monthly_id;
        $data['money'] = $moeny;
        $data['pay_status'] = 0;
        $data['pay_time'] = null;
        $data["created_at"] = Carbon::now()->toDateTimeString();
        $data["updated_at"] = Carbon::now()->toDateTimeString();

        $bool = Order::insert($data);
        return $bool;
    }

    //生成一个随机订单号
    private function getOrderSn(){
        $order_sn = date("ymdHis").rand(1000,9999);
        $count = Order::where("order_sn",'=',$order_sn)->count();
        if($count>0){
            $this->getOrderSn();
        }else{
            return $order_sn;
        }
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

    //判断当前时间并生成对应的月份
    private function getDatetime($data_time){
        $arr = array();
        $curdata = Carbon::now()->toDateTimeString();

        if($data_time=='月'){
            $lastdata = Carbon::now()->parse('-1 months')->toDateTimeString();

            $arr['curstart'] = substr($curdata,0,7).'-01 00:00:00';
            $arr['curend'] = $curdata;
            $arr['laststart'] =  substr($lastdata,0,7).'-01 00:00:00';
            $arr['lastend'] = substr($curdata,0,7).'-01 00:00:00';
        }else if($data_time=='年'){
            $lastdata = Carbon::now()->parse('-1 year')->toDateTimeString();

            $arr['curstart'] = substr($curdata,0,4).'-01-01 00:00:00';
            $arr['curend'] = $curdata;
            $arr['laststart'] = substr($lastdata,0,4).'-01-01 00:00:00';
            $arr['lastend'] = substr($curdata,0,4).'-01-01 00:00:00';
        }else{
            $curji = substr($curdata,5,2);
            if($curji=='01'||$curji=='02'||$curji=='03'){
                $lastdata = Carbon::now()->parse('-1 year')->toDateTimeString();

                $arr['curstart'] = substr($curdata,0,4).'-01-01 00:00:00';
                $arr['curend'] = $curdata;
                $arr['laststart'] = substr($lastdata,0,4).'-09-01 00:00:00';
                $arr['lastend'] = substr($curdata,0,4).'-01-01 00:00:00';
            }else if($curji=='04'||$curji=='05'||$curji=='06'){
                $arr['curstart'] = substr($curdata,0,4).'-04-01 00:00:00';
                $arr['curend'] = $curdata;
                $arr['laststart'] = substr($curdata,0,4).'-01-01 00:00:00';
                $arr['lastend'] = substr($curdata,0,4).'-04-01 00:00:00';
            }else if($curji=='07'||$curji=='08'||$curji=='09'){
                $arr['curstart'] = substr($curdata,0,4).'-07-01 00:00:00';
                $arr['curend'] = $curdata;
                $arr['laststart'] = substr($curdata,0,4).'-04-01 00:00:00';
                $arr['lastend'] = substr($curdata,0,4).'-07-01 00:00:00';
            }else{
                $arr['curstart'] = substr($curdata,0,4).'-10-01 00:00:00';
                $arr['curend'] = $curdata;
                $arr['laststart'] = substr($curdata,0,4).'-07-01 00:00:00';
                $arr['lastend'] = substr($curdata,0,4).'-10-01 00:00:00';
            }
        }
        return $arr;
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

    //产生随机字符串
    private function createNoncestr(){
        $str = "abcdefghijklmnopqrstuvwxyz0123456789";
        return substr(str_shuffle($str),0,30);
    }

    //生成签名
    private function getSign($Obj){
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=".$this->mch_key;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }

    ///格式化参数，签名过程需要使用
    private function formatBizQueryParaMap($paraMap, $urlencode){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar='';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    //数组转换成xml
    private function arrayToXml($arr){
        ksort($arr);
        $xml = '<?xml version="1.0" encoding="UTF-8"?><xml>';
        foreach ($arr as $key => $val){
            if(is_array($val)){
                $xml .= "<" . $key . ">" . arrayToXml($val) . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    //xml转换成数组
    private function xmlToArray($xml){
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring), true);
        return $val;
    }

    //post传输xml格式curl函数
    private static function postXmlCurl($xml, $url, $second = 30){
        $header[] = "Content-type: text/xml";

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);//严格校验2
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        $data = curl_exec($ch);

        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        }else{
            $error = curl_errno($ch);
            curl_close($ch);
            return "curl出错，错误码:$error";
        }
    }

    /**
     * 获取access_token
     *
     * @param $appid
     * @param $secret
     */
    private function getAccessToken ($appid, $secret) {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
        return $this->curlGet($url);
    }

    /**
     * 开启curl get请求
     */
    private function curlGet ($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return $data;
    }

    /**
     * 获得二维码
     */
    private function getQrcode($appid, $secret, $scene) {
        // 格式自选，不同格式貌似加载速度略有不同，想加载更快可选择jpg
        header('content-type:image/jpg');
        $data = array();
        // $data['page'] = 'pages/workbench/workbench'; // 路径
        $data['scene'] = '123456'; // 场景参数
        $data['width'] = 430;
        $data = json_encode($data);
        $access = json_decode($this->getAccessToken($appid, $secret), true);
        $access_token= $access['access_token'];
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;
        return $this->getHttpArray($url, $data);
    }

    /**
     * 开启curl post请求
     *
     * @param $url
     * @param $post_data
     */
    private function getHttpArray ($url, $post_data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //没有这个会自动输出，不用print_r();也会在后面多个1
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    /**
     * 获取销售单备注
     *
     * @param $godown_id
     */
    private function getSaleRemarks ($godown_id) {
        $sale_remarks = [];
        $sales = Sale::where('godown_id', $godown_id)->orderBy('created_at', 'desc')->get();
        if ($sales) {
            foreach ($sales as $k => $v) {
                $sale_remarks[] = $v->remarks;
            }
        }
        return $sale_remarks;
    }

    /**
     * 获取调度单备注
     *
     * @param $godown_id
     */
    private function getDispatchRemarks ($godown_id) {
        $dispatch_remarks = [];
        $dispatchs = Dispatch::where('godown_id', $godown_id)->orderBy('created_at', 'desc')->get();
        if ($dispatchs) {
            foreach ($dispatchs as $k => $v) {
                $dispatch_remarks[] = $v->remarks;
            }
        }
        return $dispatch_remarks;
    }

}
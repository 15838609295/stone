<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/31
 * Time: 0:30
 */

namespace App\Http\Controllers\Api;

use App\Models\Admin\AdminLog;
use App\Models\Admin\PaymentLog;
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
use App\Models\Admin\TransferCompany;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Admin\Members;
use Carbon\Carbon;
use DB;
use Log;

class PayController{
  
    public $result = array("status"=>0,'msg'=>'请求成功','data'=>"");
    private $wechat_appid = '';
    private $wechat_secret = '';
    private $mch_id = '';
    private $mch_key = '';
    private $notify_url = '';

	//初始化各项配置
    public function __construct(Request $request) {
        $con = Configs::first();
        $this->wechat_appid = $con->wechat_appid;
        $this->wechat_secret = $con->wechat_secret;
        $this->mch_id = $con->mch_id;
        $this->mch_key = $con->mch_key;
        $this->notify_url = $con->notify_url;
    }
  
    /**
     * 微信支付
     *
     * @param company_id
     * @param user_id
     * @param monthly_id
     */
    public function goPay(Request $request) {
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
        $arr['total_fee'] = $monthly->cur_price * 100;
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

    /**
     * 微信支付异步通知
     */
    public function notify(Request $request) {

        // 获取异步通知返回数据，xml格式
        $xml = file_get_contents("php://input");
        $data = $this->xmlToArray($xml);
        Log::info('异步通知返回结果：', $data);

        if ('SUCCESS' == $data['result_code'] && 'SUCCESS' == $data['return_code']) {
            // 验证订单状态，判断是否已支付
            $order = Order::where('order_sn', $data['out_trade_no'])->first();
            if ($order->pay_status == 0) {
                // 更新订单状态和支付时间
                $data_upd['pay_status'] = 1;
                $data_upd['pay_time'] = $data['time_end'];
                $bool = Order::where('order_sn', $data['out_trade_no'])->update($data_upd);
                if (! $bool) {
                    Log::error('错误信息：更新订单（'.$data['out_trade_no'].'）信息失败！');
                    echo 'failed'; die;
                }

                // 更新企业有效时间
                $monthly = Monthly::where('id', $order->monthly_id)->first();
                $company = Company::where('id', $order->company_id)->first();
                if (!empty($company->volid_time) && $company->volid_time > Carbon::now()->toDateTimeString()) {
                    $volid_time = Carbon::parse($company->volid_time)->modify('+'.$monthly->month.' days')->toDateTimeString();
                } else {
                    $volid_time = Carbon::parse('+'.$monthly->month.' days')->toDateTimeString();
                }
                $bool = Company::where('id', $order->company_id)->update(['volid_time' => $volid_time]);
                if (! $bool) {
                    Log::error('错误信息：更新企业有效时间失败！');
                    echo 'failed';die;
                }

                // 添加充值记录
                $cu = CompanyUser::where('user_id', $order->user_id)->where('company_id', $order->company_id)->first();
                // 充值金额 = 实际支付金额
                $money = $order->money;
                $this->paymentLog($order->order_sn, '微信支付', $order->user_id, CompanyUser::IS_ADMIN[$cu->is_admin], $order->company_id, $order->monthly_id, $money, $monthly->month);
                Log::info('充值成功，订单号='.$order->order_sn);
            }
            Log::info('充值成功，订单已支付，订单号='.$order->order_sn);
            echo 'success'; die;
        }
        echo 'failed'; die;
    }

    // 产生订单
    private function createOrder($user_id,$company_id,$monthly_id,$moeny,$order_sn,$order_name='充值会员') {
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

    /**
     * 充值记录
     */
    private function paymentLog($order_sn, $ptype, $user_id, $identity, $company_id, $monthly_id, $money, $month) {
        $model = array();
        $model['order_sn'] = $order_sn;
        $model['ptype'] = $ptype;
        $model['user_id'] = $user_id;
        $model['identity'] = $identity;
        $model['company_id'] = $company_id;
        $model['monthly_id'] = $monthly_id;
        $model['money'] = $money;
        $model['month'] = $month;
        $model['created_at'] = Carbon::now()->toDateTimeString();
        $model['updated_at'] = Carbon::now()->toDateTimeString();
        PaymentLog::insert($model);
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
  
}

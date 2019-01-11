<?php
/**
 * Created by PhpStorm.
 * User: manyufun
 * Date: 2018/11/22
 * Time: 10:13
 */

namespace App\Http\Controllers\Api;

use App\Models\Admin\AdminLog;
use App\Models\Admin\Company;
use App\Models\Admin\Members;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Admin\Worklog;

class UserController
{
    public $result = array("status"=>0,'msg'=>'请求成功','data'=>"");

    // 获取用户在公司的信息
    public function getCompanyUserInfo(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if(!isset($data['user_id']) || trim($data['user_id']) == ''){
            return $this->verify_parameter('user_id'); //返回必传参数为空
        }

        $res = Company::from('company as c')
            ->select('m.realname','m.mobile','c.company_name','cu.is_admin')
            ->join('company_user as cu', 'cu.company_id', '=', 'c.id')
            ->join('members as m', 'cu.user_id', '=', 'm.id')
            ->where('c.id', '=', $data['company_id'])
            ->where('m.id', '=', $data['user_id'])
            ->first();

        if(!$res){
            return $this->verify_parameter('查不到数据！', 0); //返回必传参数为空
        }

        $this->result['data'] = $res;
        return response()->json($this->result);

    }

    // 获取用户在公司的记录
    public function getCompanyUserLoginLog(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if(!isset($data['user_id']) || trim($data['user_id']) == ''){
            return $this->verify_parameter('user_id'); //返回必传参数为空
        }

        $res = Company::from('company as c')
            ->select('m.realname','c.company_name','cu.is_admin','cu.join_time')
            ->join('company_user as cu', 'cu.company_id', '=', 'c.id')
            ->join('members as m', 'cu.user_id', '=', 'm.id')
            ->where('c.id', '=', $data['company_id'])
            ->where('m.id', '=', $data['user_id'])
            ->first();

        if(!$res){
            return $this->verify_parameter('查不到数据！', 0); //返回必传参数为空
        }

        $adminLog = AdminLog::where('company_id', '=', $data['company_id'])->where('user_id', '=', $data['user_id'])->where('type', '=', 0);
        if(isset($data['start_time']) && $data['start_time'] != ''){
            if(isset($data['end_time']) && $data['end_time'] != ''){
                $adminLog->whereBetween('created_at', [$data['start_time'], $data['end_time']]);
            }else{
                $adminLog->where('created_at', '>=', $data['start_time']);
            }
        }
        if(isset($data['end_time']) && $data['end_time'] != ''){
            $adminLog->where('created_at', '<', $data['end_time']);
        }

        $use_count = $adminLog->count();

        $res->use_count = $use_count;

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    // 获取用户在公司的操作记录
    public function getCompanyUserActionLog(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if(!isset($data['user_id']) || trim($data['user_id']) == ''){
            return $this->verify_parameter('user_id'); //返回必传参数为空
        }

        $adminLog = AdminLog::where('company_id', '=', $data['company_id'])->where('user_id', '=', $data['user_id'])->where('type', '=', 1);
        $adminCount =  AdminLog::where('company_id', '=', $data['company_id'])->where('user_id', '=', $data['user_id'])->where('type', '=', 1);

        if(isset($data['start_time']) && $data['start_time'] != ''){
            if(isset($data['end_time']) && $data['end_time'] != ''){
                $adminLog->whereBetween('created_at', [$data['start_time'], $data['end_time']]);
                $adminCount->whereBetween('created_at', [$data['start_time'], $data['end_time']]);
            }else{
                $adminLog->where('created_at', '>=', $data['start_time']);
                $adminCount->where('created_at', '>=', $data['start_time']);
            }
        }
        if(isset($data['end_time']) && $data['end_time'] != ''){
            $adminLog->where('created_at', '<', $data['end_time']);
            $adminCount->where('created_at', '<', $data['end_time']);
        }

        // 总操作数
        $total = $adminCount->count();

        // 新增员工操作数，删除员工操作数
        $numb = $adminCount->where(function($query) {
                    $query->where('content', 'like', '新增员工%')
                          ->orWhere('content', 'like', '删除员工%')
                })->count();

        // 剔除增减员工操作数
        $this->result['total'] = $total - $numb;

        $start = 0;
        $pageSize = 10;
        if(isset($data['page_size']) && $data['page_size'] != ''){
            $pageSize = $data['page_size'];
        }
        if(isset($data['page']) && $data['page'] != ''){
            $start = ((int)$data['page']-1) * $pageSize;
        }

        $this->result['data'] = $adminLog->orderBy('id', 'desc')->skip($start)->take($pageSize)->get();
        return response()->json($this->result);
    }

    /**
     * 查询公司用户图库记录
     *
     * POST /api/getCompanyUserGalleyLog
     *
     * @param company_id
     * @param user_id
     * @param start_time
     * @param end_time
     */
    public function getCompanyUserGalleyLog(Request $request) {
        $data = $request->post();

        // 判断传值是否正确
        if (!isset($data['company_id']) || trim($data['company_id']) == '') {
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if (!isset($data['user_id']) || trim($data['user_id']) == '') {
            return $this->verify_parameter('user_id'); //返回必传参数为空
        }

        $adminLog = AdminLog::where('company_id', '=', $data['company_id'])->where('user_id', '=', $data['user_id'])->where('type', '=', 2);
        $adminCount =  AdminLog::where('company_id', '=', $data['company_id'])->where('user_id', '=', $data['user_id'])->where('type', '=', 2);

        if(isset($data['start_time']) && $data['start_time'] != ''){
            if(isset($data['end_time']) && $data['end_time'] != ''){
                $adminLog->whereBetween('created_at', [$data['start_time'], $data['end_time']]);
                $adminCount->whereBetween('created_at', [$data['start_time'], $data['end_time']]);
            }else{
                $adminLog->where('created_at', '>=', $data['start_time']);
                $adminCount->where('created_at', '>=', $data['start_time']);
            }
        }
        if(isset($data['end_time']) && $data['end_time'] != ''){
            $adminLog->where('created_at', '<', $data['end_time']);
            $adminCount->where('created_at', '<', $data['end_time']);
        }

        // 删除图片数量
        $total = $adminCount->count();
        $numb = $adminCount->where('content', '=', '删除图片')->count();

        // 总操作数
        $this->result['total'] = $total - $numb;

        $start = 0;
        $pageSize = 10;
        if(isset($data['page_size']) && $data['page_size'] != ''){
            $pageSize = $data['page_size'];
        }
        if(isset($data['page']) && $data['page'] != ''){
            $start = ((int)$data['page']-1) * $pageSize;
        }
        $this->result['data'] = $adminLog->orderBy('id', 'desc')->skip($start)->take($pageSize)->get();

        // 记录操作日志
        $ip_addr = $_SERVER['REMOTE_ADDR'];
        $cu = CompanyUser::where('user_id','=',$data['user_id'])->where('company_id','=',$data['company_id'])->first();
        $content = '图库访问，访问IP地址'.$ip_addr.'，访问时间'.Carbon::today();
        $this->adminLog($data['company_id'],3,$content,$data['user_id'],CompanyUser::IS_ADMIN[$cu->is_admin]);

        return response()->json($this->result);
    }

    // 修改用户信息
    public function updateUser(Request $request){
        $data = $request->post();

        //判断传值是否正确
        if(!isset($data['user_id']) || trim($data['user_id']) == ''){
            return $this->verify_parameter('user_id'); //返回必传参数为空
        }
        if(!isset($data['company_id']) || trim($data['company_id']) == ''){
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
		
      	$user = Members::where('id', '=', $data['user_id'])->first();
      	if(!$user){
        	 return $this->verify_parameter('用户信息不正确', 0);
        }
      
        $upd = [];
        if(isset($data['realname']) && $data['realname'] != ''){
            $upd['realname'] = $data['realname'];
        }
        if(isset($data['mobile']) && $data['mobile'] != ''){
            $upd['mobile'] = $data['mobile'];

            $g = "/^1[34578]\d{9}$/";
            if(!preg_match($g, $data['mobile'])){
                return $this->verify_parameter('请输入合法的手机号', 0);
            }
        }

        $bool = Members::where('id', '=', $data['user_id'])->update($upd);
        if(!$bool){
            return $this->verify_parameter('用户信息修改失败！', 0);
        }

       if(count($upd) > 0){
          $title = '用户信息修改';
          $name = isset($upd['realname']) ? $upd['realname'] : $user->realname;
          $phone = isset($upd['mobile']) ? $upd['mobile'] : $user->mobile;
          $content = '<text class="orange">'.$user->realname.'</text>(旧名)修改了个人资料，新显示姓名为<text class="orange">'.$name.'</text>(新名)，新的联系电话为<text class="orange">'.$phone.'</text>';
      	  $this->goWorkLog($data['company_id'], $title, $content);
       }
          
        return response()->json($this->result);
    }

    /**
     * 查询企业登录记录
     *
     * POST /api/getCompanyLoginLog
     *
     * @param company_id
     * @param user_id
     * @param start_time
     * @param end_time
     */
    public function getCompanyLoginLog(Request $request) {
        $data = $request->post();

        // 判断传值是否正确
        if (!isset($data['company_id']) || trim($data['company_id']) == '') {
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if (!isset($data['user_id']) || trim($data['user_id']) == '') {
            return $this->verify_parameter('user_id'); //返回必传参数为空
        }

        $res = Company::from('company as c')
            ->select('m.realname','c.company_name','cu.is_admin','cu.join_time')
            ->join('company_user as cu', 'cu.company_id', '=', 'c.id')
            ->join('members as m', 'cu.user_id', '=', 'm.id')
            ->where('c.id', '=', $data['company_id'])
            ->where('m.id', '=', $data['user_id'])
            ->first();

        if (!$res) {
            return $this->verify_parameter('查不到数据！', 0); //返回必传参数为空
        }

        $adminLog = AdminLog::where('company_id', '=', $data['company_id'])->where('type', '=', 0);
        if(isset($data['start_time']) && $data['start_time'] != ''){
            if(isset($data['end_time']) && $data['end_time'] != ''){
                $adminLog->whereBetween('created_at', [$data['start_time'], $data['end_time']]);
            }else{
                $adminLog->where('created_at', '>=', $data['start_time']);
            }
        }
        if(isset($data['end_time']) && $data['end_time'] != ''){
            $adminLog->where('created_at', '<', $data['end_time']);
        }

        // 总登陆数
        $res->use_count = $adminLog->count();

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    /**
     * 查询企业操作记录
     *
     * POST /api/getCompanyActionLog
     *
     * @param company_id
     * @param user_id
     * @param start_time
     * @param end_time
     */
    public function getCompanyActionLog(Request $request) {
        $data = $request->post();

        //判断传值是否正确
        if (!isset($data['company_id']) || trim($data['company_id']) == '') {
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if (!isset($data['user_id']) || trim($data['user_id']) == '') {
            return $this->verify_parameter('user_id'); //返回必传参数为空
        }

        $res = Company::from('company as c')
            ->select('m.realname','c.company_name','cu.is_admin','cu.join_time')
            ->join('company_user as cu', 'cu.company_id', '=', 'c.id')
            ->join('members as m', 'cu.user_id', '=', 'm.id')
            ->where('c.id', '=', $data['company_id'])
            ->where('m.id', '=', $data['user_id'])
            ->first();

        if (!$res) {
            return $this->verify_parameter('查不到数据！', 0); //返回必传参数为空
        }

        $adminCount = AdminLog::where('company_id', '=', $data['company_id'])->where('type', '=', 1);
        if (isset($data['start_time']) && $data['start_time'] != '') {
            if (isset($data['end_time']) && $data['end_time'] != '') {
                $adminCount->whereBetween('created_at', [$data['start_time'], $data['end_time']]);
            } else {
                $adminCount->where('created_at', '>=', $data['start_time']);
            }
        }
        if (isset($data['end_time']) && $data['end_time'] != '') {
            $adminCount->where('created_at', '<', $data['end_time']);
        }

        // 总操作数
        $total = $adminCount->count();

        // 新增员工操作数，删除员工操作数
        $numb = $adminCount->where(function($query) {
                    $query->where('content', 'like', '新增员工%')
                          ->orWhere('content', 'like', '删除员工%')
                })->count();

        // 总操作数
        $res->use_count = $total - $numb;

        $this->result['data'] = $res;
        return response()->json($this->result);
    }

    /**
     * 查询企业图库记录
     *
     * POST /api/getCompanyGalleyLog
     *
     * @param company_id
     * @param user_id
     * @param start_time
     * @param end_time
     */
    public function getCompanyGalleyLog(Request $request) {
        $data = $request->post();

        // 判断传值是否正确
        if (!isset($data['company_id']) || trim($data['company_id']) == '') {
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if (!isset($data['user_id']) || trim($data['user_id']) == '') {
            return $this->verify_parameter('user_id'); //返回必传参数为空
        }

        $res = Company::from('company as c')
            ->select('m.realname','c.company_name','cu.is_admin','cu.join_time')
            ->join('company_user as cu', 'cu.company_id', '=', 'c.id')
            ->join('members as m', 'cu.user_id', '=', 'm.id')
            ->where('c.id', '=', $data['company_id'])
            ->where('m.id', '=', $data['user_id'])
            ->first();

        if (!$res) {
            return $this->verify_parameter('查不到数据！', 0); //返回必传参数为空
        }

        $adminCount =  AdminLog::where('company_id', '=', $data['company_id'])->where('type', '=', 2);
        if (isset($data['start_time']) && $data['start_time'] != '') {
            if (isset($data['end_time']) && $data['end_time'] != '') {
                $adminCount->whereBetween('created_at', [$data['start_time'], $data['end_time']]);
            } else {
                $adminCount->where('created_at', '>=', $data['start_time']);
            }
        }
        if (isset($data['end_time']) && $data['end_time'] != '') {
            $adminCount->where('created_at', '<', $data['end_time']);
        }

        // 删除图片数量
        $total = $adminCount->count();
        $numb = $adminCount->where('content', '=', '删除图片')->count();

        // 总图片张数
        $res->use_count = $total - $numb;
        $this->result['data'] = $res;

        // 记录操作日志
        $ip_addr = $_SERVER['REMOTE_ADDR'];
        $cu = CompanyUser::where('user_id','=',$data['user_id'])->where('company_id','=',$data['company_id'])->first();
        $content = '图库访问，访问IP地址'.$ip_addr.'，访问时间'.Carbon::today();
        $this->adminLog($data['company_id'],3,$content,$data['user_id'],CompanyUser::IS_ADMIN[$cu->is_admin]);

        return response()->json($this->result);
    }

    /**
     * 查询企业访问记录
     *
     * POST /api/getCompanyVisitLog
     *
     * @param company_id
     * @param user_id
     * @param start_time
     * @param end_time
     */
    public function getCompanyVisitLog(Request $request) {
        $data = $request->post();

        // 判断传值是否正确
        if (!isset($data['company_id']) || trim($data['company_id']) == '') {
            return $this->verify_parameter('company_id'); //返回必传参数为空
        }
        if (!isset($data['user_id']) || trim($data['user_id']) == '') {
            return $this->verify_parameter('user_id'); //返回必传参数为空
        }

        $res = Company::from('company as c')
            ->select('m.realname','c.company_name','cu.is_admin','cu.join_time')
            ->join('company_user as cu', 'cu.company_id', '=', 'c.id')
            ->join('members as m', 'cu.user_id', '=', 'm.id')
            ->where('c.id', '=', $data['company_id'])
            ->where('m.id', '=', $data['user_id'])
            ->first();

        if (!$res) {
            return $this->verify_parameter('查不到数据！', 0); //返回必传参数为空
        }

        $adminCount =  AdminLog::where('company_id', '=', $data['company_id'])->where('type', '=', 3);
        if (isset($data['start_time']) && $data['start_time'] != '') {
            if (isset($data['end_time']) && $data['end_time'] != '') {
                $adminCount->whereBetween('created_at', [$data['start_time'], $data['end_time']]);
            } else {
                $adminCount->where('created_at', '>=', $data['start_time']);
            }
        }
        if (isset($data['end_time']) && $data['end_time'] != '') {
            $adminCount->where('created_at', '<', $data['end_time']);
        }

        // 总访问量
        $total = $adminCount->count();
        $res->use_count = $total;

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
  
    //记录操作日志的函数
    private function goWorkLog($company_id,$title,$content,$godown_id=0){
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
        $adminArr = AdminLog::where('content', '=', $content)->first();
        if (! $adminArr) {
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

}
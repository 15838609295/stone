<?php

namespace App\Http\Middleware;

use App\Models\Admin\Company;
use App\Models\Admin\CompanyUser;
use App\Models\Admin\Members;
use Closure;
use Carbon\Carbon;

class CheckApi
{
    public $result = array("status"=>0,'msg'=>'请求成功','data'=>"");
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        //判断该用户是否被删除
        if(!$request->post('code') && !$request->post('openid') ){
            if(!$request->post('mem_id') || trim($request->post('mem_id')) == ''){
                return $this->verify_parameter('mem_id'); //返回必传参数为空
            }
            if(!$request->post('comp_id') || trim($request->post('comp_id')) == ''){
                return $this->verify_parameter('comp_id'); //返回必传参数为空
            }

            $boo = Members::where('id','=',$request->post('mem_id'))->first();
            if(!$boo){
                $this->result['status'] = 888;
                $this->result['msg'] = '该用户不存在';
                return response()->json($this->result);
            }

            $booInfo = CompanyUser::where('company_id','=',$request->post('comp_id'))->where('user_id','=',$request->post('mem_id'))->first();

            if(!$booInfo){
                $this->result['status'] = 999;
                $this->result['msg'] = '该用户暂未加入该企业';
                return response()->json($this->result);
            }

            $request->user_infos = $booInfo;


           /* $compinfo = Company::find($request->post('comp_id'));
            if($compinfo->volid_time == null || $compinfo->volid_time == '' || $compinfo->volid_time < Carbon::now()->toDateTimeString()){
                $this->result['status'] = 777;
                $this->result['msg'] = '公司已过期';

                return response()->json($this->result);
            }*/

        }

        return $next($request);
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
}

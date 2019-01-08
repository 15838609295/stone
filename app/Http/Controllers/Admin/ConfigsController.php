<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Configs;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;

class ConfigsController extends Controller
{
    public $result = array("status"=>0,'msg'=>'请求成功','data'=>"");

    //配置列表
    public function index(Request $request)
    {
    	$data = Configs::first();
        return view('admin.configs.index',$data);
    }
    
    //更新系统配置
    public function update(Request $request){
    	$id = $request->post('id');
    	$data['title'] = $request->post('title');
        $data['test_time'] = $request->post('test_time');
    	$data['wechat_appid'] = $request->post('wechat_appid');
    	$data['wechat_secret'] = $request->post('wechat_secret');
        $data['mch_id'] = $request->post('mch_id');
        $data['mch_key'] = $request->post('mch_key');
        $data['notify_url'] = $request->post('notify_url');
    	
    	$res = Configs::where("id","=",$id)->update($data);
    	if($res){
        	return redirect('/admin/configs/index')->withSuccess('更新成功！');
        }else{
        	return redirect('/admin/configs/index')->withDanger('更新失败！');
        }
    }
    
}

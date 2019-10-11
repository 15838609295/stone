<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin';
    protected $username;


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest:admin', ['except' => 'logout']);
    }
    /**
     * 重写登录视图页面
     * @author 晚黎
     * @date   2016-09-05T23:06:16+0800
     * @return [type]                   [description]
     */
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }
    /**
     * 自定义认证驱动
     * @author 晚黎
     * @date   2016-09-05T23:53:07+0800
     * @return [type]                   [description]
     */
    protected function guard()
    {
        return auth()->guard('admin');
    }

    /**
     * Log the user out of the application.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        $this->guard('admin')->logout();

        request()->session()->flush();

        request()->session()->regenerate();

        return redirect('/admin/login');
    }

    public function picture(){
        $res = DB::table('godown')->whereNotNull('godown_pic')->where('status',0)->skip(0)->take(5)->get();
        $res = json_decode(json_encode($res),true);
        $data1['status'] = 1;
        foreach ($res as $v){
            if (!$v['godown_pic']){
                $update_res = DB::table('godown')->where('id',$v['id'])->update($data1);
                continue;
            }
            $update_res = DB::table('godown')->where('id',$v['id'])->update($data1);
            $godown_pic = explode(',',$v['godown_pic']);
            $data['godown_pic'] = '';
            $img_size = 0;
            foreach ($godown_pic as $url){
                $img_size = ceil(filesize('.'.$url) / 1000); //获取文件大小
                if($img_size > 500){
                    $new_url = $this->imageThumbnail('.'.$url,$v['id']);
                    $data['godown_pic'] .= $new_url.',';
                }else{
                    $data['godown_pic'] .= $url.',';
                }
            }
            if ($data['godown_pic'] != ''){
                $data['godown_pic'] = substr($data['godown_pic'],0,strlen($data['godown_pic'])-1);
            }
            if ($data['godown_pic'] != $v['godown_pic']){   //有修改就去修改数据库
                $update_res = DB::table('godown')->where('id',$v['id'])->update($data);
                if (!$update_res){
                    Log::info('update imgs this id '.$v['id'].' error:erroe');
                }
            }

        }
        echo 'ok1122';die;
    }

    //图片制作缩略图
    public function imageThumbnail($old_src,$id){
        //成功返回1，格式不符合返回2，生成图片失败返回3
        try{
            //成功返回1，格式不符合返回2，生成图片失败返回3
            ini_set('memory_limit','2048M');
            ini_set("gd.jpeg_ignore_warning", 1);
            $info = pathinfo($old_src);
            $data = getimagesize($old_src);
            if (!$info || !$data){
                $old_src = substr($old_src,1);
                return $old_src;
            }
            $file_name = $info['basename'];
            $width = $data[0]/2;
            $height = $data[1]/2;
            $new_path = './uploads/thumbnail/'.date('Ymd',time());
            if (!is_dir($new_path)){
                mkdir($new_path,0777,true);
            }
            $new_src = $new_path.'/'.$file_name;
            $new_width = $width;
            $new_height= $height;
            $rate=100;

            $old_info = getimagesize($old_src);
            switch($old_info[2]){
                case 1:$im = @imagecreatefromgif($old_src);break;
                case 2:$im = @imagecreatefromjpeg($old_src);break;
                case 3:$im = @imagecreatefrompng($old_src);break;
                case 4:$im = @imagecreatefromjpeg("/img/swf.jpg");break;
                case 6:return false;
            }
            if(!$im) return 2;
            $old_width = imagesx($im);
            $old_height = imagesy($im);
            if($old_width<$new_width && $old_height<$new_height){
                imagejpeg($im,$new_src,$rate);
                imagedestroy($im);
                return 1;
            }
            $x_rate = $old_width/$new_width;
            $y_rate = $old_height/$new_height;
            if($x_rate<$y_rate){
                $dst_x = ceil($old_width/$y_rate);
                $dst_y = $new_height-1;
                $new_start_x = 0;
                $new_start_y = 0;
            }else {
                $dst_x = $new_width;
                $y_rate = $old_height / $new_height;
                if ($x_rate < $y_rate) {
                    $dst_x = ceil($old_width / $y_rate);
                    $dst_y = $new_height - 1;
                    $new_start_x = 0;
                    $new_start_y = 0;
                } else {
                    $dst_x = $new_width;
                    $dst_y = ceil($old_height / $x_rate);
                    $new_start_x = 0;
                    $new_start_y = 0;
                }
                $newim = imagecreatetruecolor($dst_x, $dst_y);//先压缩
                $bg = imagecolorallocate($newim, 255, 255, 255);
                imagefilledrectangle($newim, 0, 0, $dst_x, $dst_y, $bg); //画个大小一致矩形充当背景

                imagecopyresampled($newim, $im, 0, 0, 0, 0, $dst_x, $dst_y, $old_width, $old_height);

                $cutim = imagecreatetruecolor($dst_x, $dst_y);//对图像进行截图
                imagecopyresampled($cutim, $newim, 0, 0, $new_start_x, $new_start_y, $new_width, $new_height, $new_width, $new_height);
                imagejpeg($cutim, $new_src, $rate);//对图像进行截图

                imagedestroy($im);
                imagedestroy($newim);
                $a = imagedestroy($cutim);

                if ($a) {
                    $img_size = ceil(filesize($new_src) / 1000); //获取文件大小
                    if ($img_size > 500){
                        $this->imageThumbnail($new_src,$id);
                    }
                    $new_src = substr($new_src,1);
                    return $new_src;
                } else {
                    return false;
                }
            }
        }catch (\Exception $e){
            Log::info('update imgs this id '.$id.' error:',array('errorinfo'=>json_encode($e)));
        }
    }


}

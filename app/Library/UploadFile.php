<?php

namespace App\Library;
use App\Models\Files;

class UploadFile{
    
    private $result = array("status"=>0,"msg"=>"","data"=>array());
    private $path;
    private $photo;

    private $config = array(
        "upload_dir" => "./uploads/",
        "type" => array("image/jpg","image/png","image/jpeg","image/bmp","image/gif")
    );

    public function __construct($config) {
         $this->config = $config;
    }

    /**
     * 图片上传
     *
     * @param type $file 原图片路径
     * @param type $percent 大小压缩比例
     * @param type $quality 质量压缩比例
     */
    public function upload($file, $percent=1, $quality=100) {
        $tmp_name = time().rand(10000,99999);
        $this->path = $this->config["upload_dir"].date("Ymd");
        if (!(is_dir($this->path))) {
            mkdir($this->path,0777,true);
            chmod($this->path,0777);
        }
        
        //if ($file->getClientSize()>5*1024*1024) {
        //    $this->result["status"] = 1;
        //    $this->result["msg"] = "上传失败,文件大小不能超过5M";
        //    return $this->result;
        //}
        
        if (!(in_array($file->getMimeType(),$this->config["type"]))) {
            $this->result["status"] = 1;
            $this->result["msg"] = "上传失败,上传的文件类型不允许上传";
	    	    return $this->result;
        }
        
        try {
           	$newfile = $tmp_name.".".strtolower(str_replace("image/","",$file->getMimeType()));
           	$file_path = $this->path."/".$newfile;
           	$file->move($this->path,$newfile);
           	//$this->result["data"] = $this->resetPicture($file_path,strtolower($type[1]),$percent,$quality);
	   		    $this->result["data"] = "/".str_replace("./","",$file_path);
        } catch (Exception $e) {
            $this->result["status"] = 1;
            $this->result["msg"] = "上传失败";
            return $this->result;
        }
        
        $this->result["status"] = 0;
        $this->result["msg"] = "上传成功";
        return $this->result;
    }

    /**
     * 图片压缩
     *
     * @param type $file 原图片路径
     * @param type $type 图片类型
     * @param type $percent 0-1 大小压缩比例
     * @param type $quality 0-100 质量压缩比例
     */
    private  function resetPicture($file, $type, $percent, $quality) {
        $filesize = getimagesize($file);
        $height = $filesize[1];
        $width = $filesize[0];
        $p_img = imagecreatetruecolor($width * $percent, $height * $percent);
        switch($type) {
            case "jpg":
                $img = imagecreatefromjpeg($file);
                break;
            case "jpeg":
                $img = imagecreatefromjpeg($file);
                break;
            case "png":
                $img = imagecreatefrompng($file);
                break;
            case "gif":
                $img = imagecreatefromgif($file);
                break;
            case "bmp":
                $img = imagecreatefromwbmp($file);
                break;
            default:"";
        }
        imagecopyresampled($p_img,$img,0,0,0,0,$width* $percent,$height* $percent,$width,$height);
        $time = time();
        $newfile = $this->path."/".$time.".jpg";
        $refile =  $this->photo."/".$time.".jpg";
        imagejpeg($p_img,$newfile,$quality);
        if (in_array($type,array("gif","png","bmp","jpeg"))) {
            //@unlink($file);
        }
        imagedestroy($img);
        return $refile;
    }
    
    /**
     * 视频上传
     *
     * @param $file
     * @param $filetype
     */
    public function uploadVideo($file, $filetype) {
         $tmp_name = time();
         $this->path = $this->config["upload_dir"].date("Ymd");
         if (!(is_dir($this->path))) {
            mkdir($this->path,0777,true);
            chmod($this->path,0777);
         }

//       if(!(in_array($filetype,$this->config["type"])))
//       {
//          $this->result["status"] = 1;
//          $this->result["msg"] = "上传失败,上传的文件类型不允许上传";
//	    	return $this->result;
//       }

         try {
           	$newfile = $tmp_name.".".strtolower(str_replace("video/","",$filetype));
           	$file_path = $this->path."/".$newfile;
           	$file->move($this->path,$newfile);
           	//$this->result["data"] = $this->resetPicture($file_path,strtolower($type[1]),$percent,$quality);
	   		    $this->result["data"] = "/".str_replace("./","",$file_path);
        } catch (Exception $e) {
            $this->result["status"] = 1;
            $this->result["msg"] = "上传失败";
            return $this->result;
        }
        
        $this->result["status"] = 0;
        $this->result["msg"] = "上传成功";
        return $this->result;
    }

}
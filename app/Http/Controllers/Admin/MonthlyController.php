<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/9
 * Time: 16:38
 */

namespace App\Http\Controllers\Admin;
use App\Models\Admin\Monthly;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;


class MonthlyController extends Controller
{
    public $result = array("status"=>0,'msg'=>'请求成功','data'=>"");
  
    public function index(Request $request){
        if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
            $sortOrder = $request->post("sortOrder");   //排序（desc，asc）
            $pageNumber = $request->post("pageNumber");  //当前页码
            $pageSize = $request->post("pageSize");   //一页显示的条数
            $start = ($pageNumber-1)*$pageSize;   //开始位置
            $search = $request->post("search",'');  //搜索条件

            $total = Monthly::from('monthly as mo');
            $rows = Monthly::from('monthly as mo');

            if(trim($search)){
                $total->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%' )
                        ->orWhere('total_price', '=', '%'.$search.'%' )
                        ->orWhere('cur_price', '=', '%'.$search.'%' );
                });
                $rows->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%' )
                        ->orWhere('total_price', '=', '%'.$search.'%' )
                        ->orWhere('cur_price', '=', '%'.$search.'%' );
                });
            }

            $data['total'] = $total->count();

            if($pageSize != 'All'){
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $rows->skip($start)->take($pageSize);
            }

            $data['rows'] = $rows->orderBy($sortName, $sortOrder)->get();

            return response()->json($data);
        }
        return view('admin.monthly.index');
    }

    //套餐增加
    public function create()
    {
        $data["name"] = "";
        $data["month"]   = 1;
        $data["total_price"] = "";
        $data["cur_price"] = "";
        $data["discount"]  = '';
      	$data["month_price"]  = '';

        return view('admin.monthly.create',$data);
    }

    //套餐增加
    public function store(Request $request)
    {
      
        $data["name"]   = $request->input("name");
        $data["month"]   = $request->input("month");
        $data["total_price"]   = $request->input("total_price");
        $data["discount"]   = $request->input("discount");
        $data["cur_price"] = $request->input("cur_price");
		$data["month_price"] = $request->input("month_price");
      
        if($data["discount"]<=0 || $data["discount"] >10){
            return redirect('/admin/monthly')->withErrors('折扣填写有误！！');
        }

        $data['created_at'] = Carbon::now()->toDateTimeString();
        $data['updated_at'] = Carbon::now()->toDateTimeString();

        $id = Monthly::insertGetId($data);
        if($id){
            return redirect('/admin/monthly')->withSuccess('添加成功！');
        }else{
            return redirect('/admin/monthly')->withErrors('添加失败！');
        }
    }

    //套餐修改
    public function edit($id)
    {
        $data = Monthly::find((int)$id);
        return view('admin.monthly.edit', $data);
    }

    //套餐更新
    public function update(Request $request, $id)
    {
        $data["name"]   = $request->input("name");
        $data["month"]   = $request->input("month");
        $data["total_price"]   = $request->input("total_price");
        $data["discount"]   = $request->input("discount");
        $data["cur_price"] = $request->input("cur_price");
		$data["month_price"] = $request->input("month_price");
      
        if($data["discount"]<=0 || $data["discount"] >10){
            return redirect('/admin/goods')->withErrors('折扣填写有误！！');
        }

        $data['updated_at'] = Carbon::now()->toDateTimeString();

        $id = Monthly::where('id','=',$id)->update($data);
        if($id){
            return redirect('/admin/monthly')->withSuccess('修改成功！');
        }else{
            return redirect('/admin/monthly')->withErrors('修改失败！');
        }
    }

  	public function ajax(Request $request, $id){
		if ($request->ajax()) {
            $data["status"] = $request->input("status");
          	if($data["status"] == 1){
              	$count = Monthly::where('status','=',1)->count();
            	if($count >= 3){
                	$this->result['msg'] = "上架套餐已满3个！！";
                	$this->result['status'] = 1;
                  	return response()->json($this->result);
                }
            }
                    
            if($request->input("c") == "status"){
                if($data["status"] != 1 && $data["status"] != 0){
                    $this->result['msg'] = "参数错误！";
                    $this->result['status'] = 1;
                    return response()->json($this->result);
                }
            }
            
            $res = Monthly::where("id","=",$id)->update($data);
            if(!$res){
                $this->result['msg'] = "更新失败！";
                $this->result['status'] = 1;
            }
            return response()->json($this->result);
        }
    }
  
    //套餐删除
    public function destroy($id)
    {
        if(Monthly::where('id','=',$id)->delete()){
            return redirect('/admin/monthly')->withSuccess('删除成功！');
        }else{
            return redirect('/admin/monthly')->withErrors('删除失败！');
        }
    }
}
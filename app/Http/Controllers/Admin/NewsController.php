<?php
	
namespace App\Http\Controllers\Admin;

use App\Models\Admin\AdminUser;
use Illuminate\Http\Request;
use App\Models\Admin\News;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

class NewsController extends Controller{
	
	public $result = array("status"=>0,'msg'=>'请求成功','data'=>"");
	//企业列表
	public function index(Request $request){
		if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
			$sortOrder = $request->post("sortOrder");   //排序（desc，asc）
			$pageNumber = $request->post("pageNumber");  //当前页码
			$pageSize = $request->post("pageSize");   //一页显示的条数
			$search = $request->post("search",'');  //搜索条件
			
			$total = News::from('news as n');
			$rows = News::from('news as n');
			
	        if(trim($search)){
	        	$total->where(function ($query) use ($search) {
                    $query->where('title', 'LIKE', '%' . $search . '%');
                });
	        	$rows->where(function ($query) use ($search) {
                    $query->where('title', 'LIKE', '%' . $search . '%');
                });
	        }
	        
	        $data['total'] = $total->count();

            if($pageSize != 'All'){
                $start = ($pageNumber-1)*$pageSize;   //开始位置
                $rows->skip($start)->take($pageSize);
            }

	        $data['rows'] = $rows->orderBy($sortName, $sortOrder)
					        ->get();
	        
	        return response()->json($data);
        }
        return view('admin.news.index');
	}
	
	//新闻增加
	public function create()
    {
        $data["title"] = "";
		$data["content"] = '';
		$data["type"] = '';
		
        return view('admin.news.create',$data);
    }

    //新闻添加
    public function store(Request $request)
    {
		$data["title"] = $request->input("title");
		$data["content"] = $request->input("content");
        $data["type"] = $request->input("type");
		
		$data["created_at"] = Carbon::now();
		$data["updated_at"] = Carbon::now();
		
		$res = News::insertGetId($data);
		if($res){
            return redirect('/admin/news')->withSuccess("添加成功");
        }else{
            return redirect('/admin/news')->withErrors("添加失败");
        }
		
    }

    //新闻修改
    public function edit($id)
    {
        $data = News::find((int)$id);
        if (!$data) return redirect('/admin/news')->withErrors("找不到数据!");
        return view('admin.news.edit', $data);
    }

    //新闻更新
    public function update(Request $request, $id)
    {
        $data["title"] = $request->input("title");
        $data["type"] = $request->input("type");
		$data["content"] = $request->input("content");
		
		$res = News::where("id","=",$id)->update($data);
		if($res){
            return redirect('/admin/news')->withSuccess("修改成功");
        }else{
            return redirect('/admin/news')->withErrors("修改失败");
        }
		
    }

    //新闻删除
    public function destroy($id)
    {
        $tag = News::find((int)$id);
        if($tag->delete()){
            return redirect()->back()->withSuccess("删除成功");
        }

    }
}

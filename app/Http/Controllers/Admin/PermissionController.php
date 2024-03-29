<?php

namespace App\Http\Controllers\Admin;

use App\Events\permChangeEvent;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Requests\PermissionCreateRequest;
use App\Http\Requests\PermissionUpdateRequest;
use App\Http\Controllers\Controller;
use App\Models\Admin\Permission;
use Cache, Event;

class PermissionController extends Controller
{
    protected $fields = [
        'name'        => '',
        'label'       => '',
        'description' => '',
        'cid'         => 0,
        'icon'        => '',
    ];

	//权限列表
    public function index(Request $request, $cid = 0)
    {
        $cid = (int)$cid;
        
        if($request->ajax()){
	        $sortName = $request->post("sortName");    //排序列名
			$sortOrder = $request->post("sortOrder");   //排序（desc，asc）
			$pageNumber = $request->post("pageNumber");  //当前页码
			$pageSize = $request->post("pageSize");   //一页显示的条数
			$start = ($pageNumber-1)*$pageSize;   //开始位置
			$search = $request->post("search",'');  //搜索条件
			$cid = $request->post('cid',0);
			
			$total = Permission::where('cid',$cid);
			$rows = Permission::where('cid',$cid);
			
	        if(trim($search)){
	        	$total->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('label', 'like', '%' . $search . '%');
                });
	        	$rows->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('label', 'like', '%' . $search . '%');
                });
	        }
	        
	        $data['total'] = $total->count();
	        $data['rows'] = $rows->skip($start)->take($pageSize)
					        ->orderBy($sortName, $sortOrder)
					        ->get();
	        
	        return response()->json($data);
	    }
        
        $datas['cid'] = $cid;
        if ($cid > 0) {
            $datas['data'] = Permission::find($cid);
        }

        return view('admin.permission.index', $datas);
    }

    //新增权限
    public function create(int $cid)
    {
        $data = [];
        foreach ($this->fields as $field => $default) {
            $data[$field] = old($field, $default);
        }
        $data['cid'] = $cid;

        return view('admin.permission.create', $data);
    }

    //添加权限
    public function store(PermissionCreateRequest $request)
    {
        $permission = new Permission();
        foreach (array_keys($this->fields) as $field) {
            $permission->$field = $request->get($field, $this->fields[$field]);
        }
        $permission->save();
        Event::fire(new permChangeEvent());
        event(new \App\Events\userActionEvent('\App\Models\Admin\Permission', $permission->id, 1, '添加了权限:' . $permission->name . '(' . $permission->label . ')'));

        return redirect('/admin/permission/' . $permission->cid)->withSuccess('添加成功！');
    }
    
    //修改权限
    public function edit($id)
    {
        $permission = Permission::find((int)$id);
        if (!$permission) return redirect('/admin/permission')->withErrors("找不到该权限!");
        $data = ['id' => (int)$id];
        foreach (array_keys($this->fields) as $field) {
            $data[$field] = old($field, $permission->$field);
        }

        return view('admin.permission.edit', $data);
    }

    //更新权限
    public function update(PermissionUpdateRequest $request, $id)
    {
        $permission = Permission::find((int)$id);
        foreach (array_keys($this->fields) as $field) {
            $permission->$field = $request->get($field, $this->fields[$field]);
        }
        $permission->save();
        Event::fire(new permChangeEvent());
        event(new \App\Events\userActionEvent('\App\Models\Admin\Permission', $permission->id, 3, '修改了权限:' . $permission->name . '(' . $permission->label . ')'));

        return redirect('admin/permission/' . $permission->cid)->withSuccess('修改成功！');
    }

	//删除权限
    public function destroy($id)
    {
        $child = Permission::where('cid', $id)->first();

        if ($child) {
            return redirect()->back()
                ->withErrors("请先将该权限的子权限删除后再做删除操作!");
        }
        $tag = Permission::find((int)$id);
        foreach ($tag->roles as $v) {
            $tag->roles()->detach($v->id);
        }
        if ($tag) {
            $tag->delete();
        } else {
            return redirect()->back()
                ->withErrors("删除失败");
        }
        Event::fire(new permChangeEvent());
        event(new \App\Events\userActionEvent('\App\Models\Admin\Permission', $tag->id, 2, '删除了权限:' . $tag->name . '(' . $tag->label . ')'));

        return redirect()->back()
            ->withSuccess("删除成功");
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Admin\Role;
use Auth;

class RoleController extends Controller
{
    protected $fields = [
        'name' => '',
        'description' => '',
        'permissions' => [],
    ];


    //角色列表
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
			$sortOrder = $request->post("sortOrder");   //排序（desc，asc）
			$pageNumber = $request->post("pageNumber");  //当前页码
			$pageSize = $request->post("pageSize");   //一页显示的条数
			$start = ($pageNumber-1)*$pageSize;   //开始位置
			$search = $request->post("search",'');  //搜索条件
			
			$total = Role::from('admin_roles');
			$rows = Role::from('admin_roles');
			
	        if(trim($search)){
	        	$total->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
	        	$rows->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
	        }
	        
	        $data['total'] = $total->count();
	        $data['rows'] = $rows->skip($start)->take($pageSize)
					        ->orderBy($sortName, $sortOrder)
					        ->get();
	        
	        return response()->json($data);
        }
        return view('admin.role.index');
    }

    //添加角色
    public function create()
    {
        $data = [];
        foreach ($this->fields as $field => $default) {
            $data[$field] = old($field, $default);
        }
        $arr = Permission::all()->toArray();
        foreach ($arr as $v) {
            $data['permissionAll'][$v['cid']][] = $v;
        }
        return view('admin.role.create', $data);
    }

    //增加角色
    public function store(Request $request)
    {
        $role = new Role();
        foreach (array_keys($this->fields) as $field) {
            $role->$field = $request->input($field);
        }
        unset($role->permissions);
        $role->save();
        if (is_array($request->input('permissions'))) {
            $role->permissions()->sync($request->input('permissions',[]));
        }
        event(new \App\Events\userActionEvent('\App\Models\Admin\Role',$role->id,1,"用户".auth('admin')->user()->username."{".auth('admin')->user()->id."}添加角色".$role->name."{".$role->id."}"));
        return redirect('/admin/role')->withSuccess('添加成功！');
    }

	//修改角色
    public function edit($id)
    {
        $role = Role::find((int)$id);
        if (!$role) return redirect('/admin/role')->withErrors("找不到该角色!");
        $permissions = [];
        if ($role->permissions) {
            foreach ($role->permissions as $v) {
                $permissions[] = $v->id;
            }
        }
        $role->permissions = $permissions;
        foreach (array_keys($this->fields) as $field) {
            $data[$field] = old($field, $role->$field);
        }
        $arr = Permission::all()->toArray();
        foreach ($arr as $v) {
            $data['permissionAll'][$v['cid']][] = $v;
        }
        $data['id'] = (int)$id;
        return view('admin.role.edit', $data);
    }

    //更新角色
    public function update(Request $request, $id)
    {
        $role = Role::find((int)$id);
        foreach (array_keys($this->fields) as $field) {
            $role->$field = $request->input($field);
        }
        unset($role->permissions);
        $role->save();

        $role->permissions()->sync($request->input('permissions',[]));
        event(new \App\Events\userActionEvent('\App\Models\Admin\Role',$role->id,3,"用户".auth('admin')->user()->username."{".auth('admin')->user()->id."}编辑角色".$role->name."{".$role->id."}"));
        return redirect('/admin/role')->withSuccess('修改成功！');
    }

    //删除角色
    public function destroy($id)
    {
        $role = Role::find((int)$id);
        foreach ($role->users as $v){
            $role->users()->detach($v);
        }

        foreach ($role->permissions as $v){
            $role->permissions()->detach($v);
        }

        if ($role) {
            $role->delete();
        } else {
            return redirect()->back()
                ->withErrors("删除失败");
        }
        event(new \App\Events\userActionEvent('\App\Models\Admin\Role',$role->id,2,"用户".auth('admin')->user()->username."{".auth('admin')->user()->id."}删除角色".$role->name."{".$role->id."}"));
        return redirect()->back()
            ->withSuccess("删除成功");
    }
}

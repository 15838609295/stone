<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Role;
use App\Models\Admin\AdminUser;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
	public $result = array("status"=>0,'msg'=>'请求成功','data'=>"");
    protected $fields = [
        'name'  => '',
        'email' => '',
        'roles' => [],
    ];

    //管理员列表
    public function index(Request $request)
    {
		if ($request->ajax()) {
            $sortName = $request->post("sortName");    //排序列名
			$sortOrder = $request->post("sortOrder");   //排序（desc，asc）
			$pageNumber = $request->post("pageNumber");  //当前页码
			$pageSize = $request->post("pageSize");   //一页显示的条数
			$start = ($pageNumber-1)*$pageSize;   //开始位置
			$search = $request->post("search",'');  //搜索条件
			
			$total = AdminUser::from('admin_users as au');
			$rows = AdminUser::from('admin_users as au');
			
	        if(trim($search)){
	        	$total->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
	        	$rows->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
	        }
	        
	        $data['total'] = $total->count();
	        $data['rows'] = $rows->skip($start)->take($pageSize)
					        ->orderBy($sortName, $sortOrder)
					        ->get();
	        
	        return response()->json($data);
        }
        return view('admin.user.index');
    }

    //管理员添加
    public function create()
    {
        $data = [];
        foreach ($this->fields as $field => $default) {
            $data[$field] = old($field, $default);
        }
        $data['rolesAll'] = Role::all()->toArray();

        return view('admin.user.create', $data);
    }

    //管理员增加
    public function store(Requests\AdminUserCreateRequest $request)
    {
        $user = new AdminUser();
        foreach (array_keys($this->fields) as $field) {
            $user->$field = $request->get($field);
        }
        $user->password = bcrypt($request->get('password'));
        unset($user->roles);
        $user->save();
        if (is_array($request->get('roles'))) {
            $user->giveRoleTo($request->get('roles'));
        }
        return redirect('/admin/user')->withSuccess('添加成功！');
    }

    //找不到方法是显示
    public function show($id)
    {
        //
    }

    //管理员修改
    public function edit($id)
    {
        $user = AdminUser::find((int)$id);
        if (!$user) return redirect('/admin/user')->withErrors("找不到该用户!");
        $roles = [];
        if ($user->roles) {
            foreach ($user->roles as $v) {
                $roles[] = $v->id;
            }
        }
        $user->roles = $roles;
        foreach (array_keys($this->fields) as $field) {
            $data[$field] = old($field, $user->$field);
        }
        $data['id'] = (int)$id;
        
        return view('admin.user.edit', $data);
    }

    //管理员更新
    public function update(Requests\AdminUserUpdateRequest $request, $id)
    {
        $user = AdminUser::find((int)$id);
        foreach (array_keys($this->fields) as $field) {
            $user->$field = $request->get($field);
        }
        unset($user->roles);
        if ($request->get('password') != '') {
            $user->password = bcrypt($request->get('password'));

        }

        $user->save();
        $user->giveRoleTo($request->get('roles', []));

        return redirect('/admin/user')->withSuccess('添加成功！');
    }

    //管理员删除
    public function destroy($id)
    {
        $tag = AdminUser::find((int)$id);
        foreach ($tag->roles as $v) {
            $tag->roles()->detach($v);
        }
        if ($tag && $tag->id != 1) {
            $tag->delete();
        } else {
            return redirect()->back()
                ->withErrors("删除失败");
        }

        return redirect()->back()
            ->withSuccess("删除成功");
    }
    
    //异步更新
    public function ajax(Request $request, $id)
    {
    	
        if ($request->ajax()) {
			$data=array();
            
            //更改密码
            if($request->input("c") == "updpass"){
            	$data["password"] = bcrypt($request->input("password"));
            }
            
            $res = AdminUser::where("id","=",$id)->update($data);
            if(!$res){
                $this->request['msg'] = "更新失败！";
                $this->request['status'] = 1;
            }
            return response()->json($this->result);
		}
	}
}

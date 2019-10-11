<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('login', 'LoginController@showLoginForm')->name('admin.login');
Route::post('login', 'LoginController@login');
Route::get('logout', 'LoginController@logout');
Route::post('logout', 'LoginController@logout');

Route::any('compressedPictures', 'LoginController@picture');

Route::get('/', 'IndexController@index');


Route::get('index', ['as' => 'admin.index', 'uses' => function () {
    return redirect('/admin/index');
}]);

//更新管理员信息
Route::post('updateuser/ajax/{id}', 'UserController@ajax'); 

Route::group(['middleware' => ['auth:admin', 'menu', 'authAdmin']], function () {
	//首页
	Route::get('index', ['as' => 'admin.index.home', 'uses' => 'IndexController@index']);

    //权限管理路由
    Route::get('permission/{cid}/create', ['as' => 'admin.permission.create', 'uses' => 'PermissionController@create']);
    Route::get('permission/manage', ['as' => 'admin.permission.manage', 'uses' => 'PermissionController@index']);
    Route::get('permission/{cid?}', ['as' => 'admin.permission.index', 'uses' => 'PermissionController@index']);
    Route::post('permission/index', ['as' => 'admin.permission.index', 'uses' => 'PermissionController@index']); //查询
    Route::resource('permission', 'PermissionController', ['names' => ['update' => 'admin.permission.edit', 'store' => 'admin.permission.create']]);


    //角色管理路由
    Route::get('role/index', ['as' => 'admin.role.index', 'uses' => 'RoleController@index']);
    Route::post('role/index', ['as' => 'admin.role.index', 'uses' => 'RoleController@index']);
    Route::resource('role', 'RoleController', ['names' => ['update' => 'admin.role.edit', 'store' => 'admin.role.create']]);


    //用户管理路由
    Route::get('user/index', ['as' => 'admin.user.index', 'uses' => 'UserController@index']);  //用户管理
    Route::post('user/index', ['as' => 'admin.user.index', 'uses' => 'UserController@index']);
    Route::resource('user', 'UserController', ['names' => ['update' => 'admin.role.edit', 'store' => 'admin.role.create']]);


	//企业列表路由
	Route::post('company/deleteCompany/{id}', ['as' => 'admin.company.index', 'uses' => 'CompanyController@deleteCompany']);  //企业列表
	Route::any('company/ajax/{id}', ['as' => 'admin.company.index', 'uses' => 'CompanyController@ajax']); //注销企业
	Route::any('company/index', ['as' => 'admin.company.index', 'uses' => 'CompanyController@index']);  //企业列表
  	Route::any('company/uploadLogo', [ 'uses' => 'CompanyController@uploadLogo']);  //上传企业图片
   
	//用户列表路由
	Route::any('members/index', ['as' => 'admin.members.index', 'uses' => 'MembersController@index']);  //用户列表

	//用户列表路由
	Route::any('worklog/index', ['as' => 'admin.worklog.index', 'uses' => 'WorklogController@index']);  //企业日志
	
	//商品品种列表
	Route::any('goodsattr/index', ['as' => 'admin.goodsattr.index', 'uses' => 'GoodsattrController@index']);  //商品品种列表
	
	//仓库列表
	Route::any('depots/index', ['as' => 'admin.depots.index', 'uses' => 'DepotsController@index']);  //商品仓库列表
	
	//入库列表
	Route::any('godown/index', ['as' => 'admin.godown.index', 'uses' => 'GodownController@index']);  //商品入库列表
	
	//调度列表
	Route::any('dispatch/index', ['as' => 'admin.dispatch.index', 'uses' => 'DispatchController@index']);  //商品调度列表
	
	//开切列表
	Route::any('opencut/index', ['as' => 'admin.opencut.index', 'uses' => 'OpencutController@index']);  //商品开切列表
	
	//销售列表
	Route::any('sale/index', ['as' => 'admin.sale.index', 'uses' => 'SaleController@index']);  //商品销售列表
	
	//系统配置
	Route::post('configs/update', ['as' => 'admin.configs.index', 'uses' => 'ConfigsController@update']);  
	Route::any('configs/index', ['as' => 'admin.configs.index', 'uses' => 'ConfigsController@index']);
    Route::any('monthly/index', ['as' => 'admin.monthly.index', 'uses' => 'MonthlyController@index']);
    Route::resource('monthly', 'MonthlyController', ['names' => ['update' => 'admin.monthly.edit', 'store' => 'admin.monthly.create']]);
	Route::any('monthly/ajax/{id}', ['as' => 'admin.monthly.edit', 'uses' => 'MonthlyController@ajax']); 


    //新闻管理路由
    Route::any('news/index', ['as' => 'admin.news.index', 'uses' => 'NewsController@index']);  //新闻管理
    Route::resource('news', 'NewsController', ['names' => ['update' => 'admin.news.edit', 'store' => 'admin.news.create']]);

	//企业列表二级菜单
	Route::any('company/{id}/users', 'CompanyController@users');
    Route::any('company/{id}/stocks', 'CompanyController@stocks');
    Route::any('company/{id}/depots', 'CompanyController@depots');
    Route::any('company/{id}/images', 'ImageController@index');
    Route::any('company/{id}/sales', 'SaleController@sales');
    Route::any('company/{id}/salelog', 'SaleController@salelog');

	// 运营概况
    Route::any('operate/userinfo', ['as' => 'admin.operate_user.index', 'uses' => 'OperateController@userinfo']);
    Route::any('operate_user/index', ['as' => 'admin.operate_user.index', 'uses' => 'OperateController@user']);
    Route::any('operate/companyinfo', ['as' => 'admin.operate_company.index', 'uses' => 'OperateController@companyinfo']);
    Route::any('operate_company/index', ['as' => 'admin.operate_company.index', 'uses' => 'OperateController@company']);

    //市场概况
    Route::any('market_godown/info', ['as' => 'admin.market_godown.index', 'uses' => 'MarketController@info']);
    Route::any('market_godown/index', ['as' => 'admin.market_godown.index', 'uses' => 'MarketController@godownList']);
    Route::any('market_godown/authentication', ['uses' => 'MarketController@authentication']);
    Route::any('market_godown/unsetauthentication', ['uses' => 'MarketController@unsetauthentication']);
    Route::any('market_godown/upperShelf', ['uses' => 'MarketController@upperShelf']);
    Route::any('market_godown/lowerShelf', ['uses' => 'MarketController@lowerShelf']);
    Route::any('companyinfo/index', ['as' => 'admin.companyinfo.index', 'uses' => 'CompanyinfoController@index']);

    // 历史记录
    Route::any('recharge/index', ['as' => 'admin.recharge.index', 'uses' => 'RecordController@rechargeList']);
    Route::any('apply/index', ['as' => 'admin.apply.index', 'uses' => 'RecordController@applyList']);

});

Route::get('/', function () {
    return redirect('/admin/index');
});


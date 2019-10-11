<?php

//内部接口
Route::any('getCompanyLog','Api\ApiController@getCompanyLog');  //获取企业日志
Route::any('createGoodsAttr','Api\ApiController@createGoodsAttr');  //增加品种
Route::any('deleteGoodsAttr','Api\ApiController@deleteGoodsAttr');  //删除品种
Route::any('updateGoodsAttr','Api\ApiController@updateGoodsAttr');  //修改品种
Route::any('createDepots','Api\ApiController@createDepots');  //增加仓库
Route::any('deleteDepots','Api\ApiController@deleteDepots');  //删除仓库
Route::any('updateDepots','Api\ApiController@updateDepots');  //修改仓库
Route::any('getDepots','Api\ApiController@getDepots');  //查询仓库
Route::any('getApplyMembers','Api\ApiController@getApplyMembers');  //员工申请列表查询
Route::any('updateApplyMembers','Api\ApiController@updateApplyMembers');  //员工申请列表更新
Route::any('getMembers','Api\ApiController@getMembers');  //员工列表
Route::any('deleteMembers','Api\ApiController@deleteMembers');  //员工删除
Route::any('appointMembers','Api\ApiController@appointMembers');  //任命主管

Route::any('getGoDown','Api\ApiController@getGoDown');  //查询产品信息
Route::any('updateGoDownImg','Api\ApiController@updateGoDownImg');  //修改产品图片
Route::any('joinGoDown','Api\ApiController@joinGoDown');  //产品入库
Route::any('deleteGoDown','Api\ApiController@deleteGoDown');  //入库单删除
Route::any('updateGoDown','Api\ApiController@updateGoDown');  //入库单修改
Route::any('getGodownList','Api\ApiController@getGodownList');  //入库单列表
Route::any('createOpencut','Api\ApiController@createOpencut');  //产品开切
Route::any('deleteOpencut','Api\ApiController@deleteOpencut');  //开切删除
Route::any('updateOpencut','Api\ApiController@updateOpencut');  //开切修改
Route::any('getOpencutList','Api\ApiController@getOpencutList');  //开切列表
Route::any('createDispatch','Api\ApiController@createDispatch');  //产品调库
Route::any('deleteDispatch','Api\ApiController@deleteDispatch');  //调库删除
Route::any('updateDispatch','Api\ApiController@updateDispatch');  //调库修改
Route::any('getDispatchList','Api\ApiController@getDispatchList');  //调库删除
Route::any('createSale','Api\ApiController@createSale');  //产品销售
Route::any('deleteSale','Api\ApiController@deleteSale');  //销售删除
Route::any('updateSale','Api\ApiController@updateSale');  //销售修改
Route::any('getSaleList','Api\ApiController@getSaleList');  //销售列表

//微信接口
Route::any('getWXACodeUnlimit','Api\WechatController@getWXACodeUnlimit');  //获取小程序码
Route::any('getUserInfo','Api\WechatController@getUserInfo');  //获取用户信息
Route::any('getUsers','Api\WechatController@getUsers');  //根据openid获取用户信息
Route::any('logoutCompany','Api\WechatController@logoutCompany');  //注销企业
Route::any('updateCompanyPass','Api\WechatController@updateCompanyPass');  //修改企业邀请码
Route::any('databaseList','Api\WechatController@databaseList');  //数据库列表
Route::any('getGodownLog','Api\WechatController@getGodownLog');  //产品操作记录
Route::any('getStock','Api\WechatController@getStock');  //按品种查询库存
Route::any('getDepotStock','Api\WechatController@getDepotStock');  //按仓库查询库存
Route::any('getGoodsAttrStock','Api\WechatController@getGoodsAttrStock');  //按品种查询各仓库库存
Route::any('getGoodsAttrSale','Api\WechatController@getGoodsAttrSale');  //按品种统计销量
Route::any('getCurSaleMoney','Api\WechatController@getCurSaleMoney');  //按品种区分类型销售
Route::any('getLastSaleMoney','Api\WechatController@getLastSaleMoney');  //按品种区分类型销售
Route::any('goPay','Api\PayController@goPay');  //微信支付
Route::any('notify','Api\PayController@notify');  //异步通知接口
Route::any('turnCompany','Api\WechatController@turnCompany');  //转让企业接口
Route::any('receiveCompany','Api\WechatController@receiveCompany');  //接受拒绝企业接口
Route::any('turnCompanyList','Api\WechatController@turnCompanyList');  //转让企业列表
Route::any('getTransferCompanyStatus','Api\WechatController@getTransferCompanyStatus');  //转让企业状态

//公共接口
Route::any('uploadImsage','Api\CommonController@uploadImsage');  // 图片上传
Route::any('getMonthly','Api\CommonController@getMonthly');  //获取套餐信息
Route::any('createCompany','Api\CommonController@createCompany');  //创建企业
Route::any('joinCompany','Api\CommonController@joinCompany');  //加入企业
Route::any('sosoCompany','Api\CommonController@sosoCompany');  //搜索获取公司接口
Route::any('getCompany','Api\CommonController@getCompany');  //获取公司接口
Route::any('getNews','Api\CommonController@getNews');  //获取公司资讯
Route::any('updateLoginTime','Api\CommonController@updateLoginTime');  //更新登陆时间
Route::any('getCompanyGoDown','Api\CommonController@getCompanyGoDown');  //获取公司产品信息
Route::any('getCompanyGoDownList','Api\CommonController@getCompanyGoDownList');  //获取单个公司产品信息列表
Route::any('getGoodsAttr','Api\CommonController@getGoodsAttr');  //查询品种

Route::any('companyList','Api\CommonController@companyList');  //公司列表
Route::any('productList','Api\CommonController@productList');  //产品列表
Route::any('getOpenid','Api\CommonController@getOpenid');  //获取openid
Route::any('collection','Api\CommonController@collection');  //用户收藏
Route::any('myCollection','Api\CommonController@myCollection');  //我的收藏
Route::any('getMemInfo','Api\CommonController@getUserInfo');  //获取用户信息
Route::any('cancelCollction','Api\CommonController@cancelCollction');  //取消收藏
Route::any('goodsIdInfo','Api\CommonController@goodsIdInfo');  //产品详情
Route::any('sellCompanyList','Api\CommonController@sellCompanyList');  //产品所售卖公司列表


// 账号相关接口
Route::any('getCompanyUserInfo','Api\UserController@getCompanyUserInfo');  //查询公司用户信息
Route::any('getCompanyUserLoginLog','Api\UserController@getCompanyUserLoginLog');  //查询公司用户登录记录
Route::any('getCompanyUserActionLog','Api\UserController@getCompanyUserActionLog');  //查询公司用户操作记录
Route::any('getCompanyUserGalleyLog','Api\UserController@getCompanyUserGalleyLog');  //查询公司用户图库记录
Route::any('updateUser','Api\UserController@updateUser');  //修改用户信息

// 企业相关接口
Route::any('getCompanyLoginLog','Api\UserController@getCompanyLoginLog');  //查询企业登录记录
Route::any('getCompanyActionLog','Api\UserController@getCompanyActionLog');  //查询企业操作记录
Route::any('getCompanyGalleyLog','Api\UserController@getCompanyGalleyLog');  //查询企业图库记录
Route::any('getCompanyVisitLog','Api\UserController@getCompanyVisitLog');  //查询企业访问记录

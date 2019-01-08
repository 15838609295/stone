<!DOCTYPE html>
<html lang="cn-zh">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="renderer" content="webkit">
    <link rel="shortcut icon" href="/assets/img/favicon.ico" />
    <link href="/assets/css/backend.css" rel="stylesheet">
    <!--  layui  -->
    <link href="/layui/css/layui.css" rel="stylesheet">
    <!-- loading -->
    <link href="/dist/css/load/load.css" rel="stylesheet">
    @yield('css')
</head>
<body class="hold-transition skin-green sidebar-mini fixed" id="tabs">
    <div class="wrapper">
        <header id="header" class="main-header">
            @include('admin.common.header')
        </header>
        <!-- Left side column. contains the logo and sidebar -->
        <aside class="main-sidebar">
            @include('admin.common.menu')
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper tab-content tab-addtabs">
            <!-- Content Header (Page header) -->
            <section class="content-header">
                <h1>
                  @yield('pageHeader')
                  <small>@yield('pageDesc')</small>
                </h1>
                <ol class="breadcrumb">
                  <li>
                      <a href="/admin"><i class="fa fa-dashboard"></i> 控制面板</a>
                  </li>
                  <li class="active">Here</li>
                </ol>
            </section>
            <!-- /.Content Header (Page header) -->
            <section class="content">
                @yield('content')
            </section>
        </div>
        <!-- /.content-wrapper -->

        <footer class="main-footer hide">
            <div class="pull-right hidden-xs"></div>
            <strong>Copyright &copy; 2017-2018 <a href="https://www.fastadmin.net">Fastadmin</a>.</strong> All rights reserved.
        </footer>

        <div class="control-sidebar-bg"></div>
        
        <div class="modal fade" id="modal-respassword" tabIndex="-1">
		    <div class="modal-dialog">
		        <div class="modal-content">
		            <div class="modal-header">
		                <button type="button" class="close" data-dismiss="modal">
		                    ×
		                </button>
		                <h4 class="modal-title">修改密码</h4>
		            </div>
		            <div class="modal-body">
		                <div class="form-group">
		                    <label for="exampleInputEmail1">新密码</label>
		                    <input type="password" class="form-control" id="new-password" placeholder="请输入新密码">
		                </div>
		                <div class="form-group">
		                    <label for="exampleInputEmail1">确认新密码</label>
		                    <input type="password" class="form-control" id="renew-password" placeholder="请再次输入新密码">
		                </div>
		            </div>
		            <div class="modal-footer">
		                <input type="hidden" name="_token" value="{{ csrf_token() }}">
		                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
		                <div class="btn btn-danger" id="respassword-update">
		                    <i class="fa fa-times-circle"></i>确认
		                </div>
		            </div>
		        </div>
		    </div>
		</div>
        
    </div>



<script type="text/javascript" charset="utf-8" src="/assets/js/../libs/jquery/dist/jquery.min.js"></script>
{{--<!-- Bootstrap 3.3.6 -->--}}
<script src="/assets/js/../libs/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="/assets/js/../libs/toastr/toastr.js"></script>
<script src="/assets/js/../libs/layer/dist/layer.js"></script>
<script src="/assets/libs/bootstrap-table/dist/bootstrap-table.js"></script>
<script src="/assets/libs/bootstrap-table/src/locale/bootstrap-table-zh-CN.js"></script>
<script src="/assets/js/../libs/jquery-slimscroll/jquery.slimscroll.js"></script>
<script src="/assets/js/adminlte.js"></script>
<script src="/assets/libs/layui/layui.js"></script>

@yield('js')
<script>
	$(document).ready(function(){
		
		//修改密码
		$(".updpass").click(function () {
	        $("#modal-respassword").modal();
	    });
	    
	    $("#respassword-update").click(function () {
	        var password = $("#new-password").val();
	        var password_confirmation = $("#renew-password").val();
	
	        if(!password || !password_confirmation){
	            return false;
	        }
	        if(password.length<=5 || password!=password_confirmation){
	        	return false;
	        }
	        
	        $('#modal-respassword').css({
				zIndex:"0"
			});
			$('#loading').css({
				display:"block"
			});
	        
	        $.post(
	    		"/admin/updateuser/ajax/{{auth('admin')->user()->id}}",
	    		{password:password,password_confirmation:password_confirmation,c:'updpass'},
	    		function(d){
	    			if(d.status==0){
	    				location.href='/admin/logout';
	    			}else{
	    				alert("修改失败");
	    				window.location.reload();
	    			}
	    		}
	    	);
	
	    });
		
	});
</script>

</body>
</html>
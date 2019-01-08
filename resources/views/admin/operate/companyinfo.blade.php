@extends('admin.layouts.base')

@section('title','企业概况')

@section('pageHeader','')

@section('pageDesc','使用详情')

@section('css')

@stop

@section('content')
<div class="row">
	<div class="box" style="border-top:0;">
	    @include('admin.partials.errors')
	    @include('admin.partials.success')
	</div>
</div>
<div class="panel panel-default panel-intro">
    <div class="panel-body">
        <div id="myTabContent" class="tab-content">
            <div class="tab-pane fade active in" id="one">
                <div class="widget-body no-padding">
                    <div class="bootstrap-table">
                        <table id="table" class="table table-striped table-bordered table-hover" width="100%"></table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@stop

@section('js')
<script>

    $(function () {
        var table = $("#table");
        // 初始化表格
        table.bootstrapTable({
            method: "POST",  //使用get请求到服务器获取数据
            contentType:"application/x-www-form-urlencoded; charset=UTF-8", 
            url: "/admin/operate/companyinfo", //获取数据的Servlet地址
            ajaxOptions:{
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                }
            },

            sidePagination: 'server', 
            toolbar: "#toolbar", //工具栏
            search: false, //是否启用快速搜索
            cache: false,
            commonSearch: true, //是否启用通用搜索
            searchFormVisible: false, //是否始终显示搜索表单
            titleForm: '', //为空则不显示标题，不定义默认显示：普通搜索
            idTable: 'commonTable',
            showExport: true,
            exportDataType: "all",
            exportTypes: ['json', 'xml', 'csv', 'txt', 'doc', 'excel'],
            pageSize: 10,
            pageNumber: 1, // 首页页码
            pageList: [10, 25, 50, 'All'],
            pagination: true,
            clickToSelect: true, //是否启用点击选中
            dblClickToEdit: true, //是否启用双击编辑
            singleSelect: false, //是否启用单选
            showRefresh: false,
            locale: 'zh-CN',
            showToggle: true,
            showColumns: true,
            pk: 'id',
            sortable: true,      //是否启用排序
            sortName: 'id',
            sortOrder: 'desc',
            cardView: false, //卡片视图
            checkOnInit: true, //是否在初始化时判断
            escape: true, //是否对内容进行转义

            //设置为undefined可以获取pageNumber，pageSize，searchText，sortName，sortOrder
            //设置为limit可以获取limit, offset, search, sort, order
            queryParamsType : "undefined",

            //得到查询的参数
            queryParams : queryParams,
            onLoadSuccess: function(data){  //加载成功时执行
                console.log(data);
                layer.msg("加载成功");
            },
            onLoadError: function(){  //加载失败时执行
                layer.msg("加载数据失败", {time : 1500, icon : 2});
            },
            columns: [[
                {field:'created_at', title: '时间'},
                {
                	field:'type',
                	title: '类型',
                	formatter: function (value, row, index) {
                		if(row['type']==0){
                            return '登录';
                        }else{
                		    return '操作';
                        }
                	}
                },
                {field:'content', title: '内容'},
                {field:'realname', title: '姓名'},
                {field:'identity', title: '身份'},
                {field:'company_name', title: '所属企业'},
            ]]
        });
        
        //筛选条件函数
        function queryParams(params) {
            //这里的键的名字和控制器的变量名必须一直，这边改动，控制器也需要改成一样的
            var temp = {
                pageSize: this.pageSize,   //一页显示的条数
                pageNumber: this.pageNumber,  //当前页码
                sortName: this.sortName,      //排序列名
                sortOrder: this.sortOrder, //排序（desc，asc）
                search: this.searchText,
                company_id: {{$company_id}}
            };
            return temp;
        }
    });
</script>
<script>
	
	//修改企业状态
	function upstatus(id,s,obj) {
		$('#loading').css({display:"block"});
	    $.post(
	    	"/admin/company/ajax/"+id,
	    	{c:"status",status:s},
	    	function(data){
	    		
	            if(data.status == 0){
	                var c,i,t;
	                if(s==1){
	                    c = "label label-success";
	                    i = 1;
	                    t = "正常";
	                }
	                if(s==1){
	                    c = "label label-danger";
	                    i = 0;
	                    t = "注销";
	                }
	                $(obj).attr("class",c);
	                $(obj).text(t);
	                $(obj).attr("onclick","upstatus("+id+","+i+",this);");
	            }
	        }
	    );
	    
	}
	
	//审批企业状态
	function setstatus(id,s){
		$('#loading').css({display:"block"});
		$.post(
	    	"/admin/company/ajax/"+id,
	    	{c:"setstatus",status:s},
	    	function(data){
	            if(data.status != 0){
	                alert('更新失败');
	            }
	            window.location.reload();
	        }
	    );
	}
</script>

<script>
	$('document').ready(function(){
		//删除企业
		$(document).on('click','.delBtn', function () {
	        var id = $(this).attr('attr');
	        $('input[name=del_company_id]').val(id);
	        $("#modal-delete").modal();
	        $('.modal-backdrop').css({zIndex:'0'});
	    });
		
		//删除企业
		$(document).on('click','.delete_company', function () {
			var id = $('input[name=del_company_id]').val();
			$('#loading').css({display:"block"});
			
			$.post(
		    	"/admin/company/ajax/"+id,
		    	{c:"setdelete",id:id},
		    	function(data){
		            if(data.status != 0){
		                alert('删除失败');
		            }
		            window.location.reload();
		        }
		    );
		});
	});
</script>
@stop
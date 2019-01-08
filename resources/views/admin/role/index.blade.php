@extends('admin.layouts.base')

@section('title','角色管理')

@section('pageHeader','角色管理')

@section('pageDesc','DashBoard')

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
	                    	<!--  工具栏     -->
	                    	<div id="toolbar">
							    @if(Gate::forUser(auth('admin')->user())->check('admin.permission.create'))
							        <a href="/admin/role/create" class="btn btn-success btn-md"><i class="fa fa-plus-circle"></i> 添加角色 </a>
							    @endif
							</div>
	                        <table id="table" class="table table-striped table-bordered table-hover" width="100%"></table>
	                    </div>
	                </div>
	            </div>
	        </div>
	    </div>
	</div>
    
    <div class="modal fade" id="modal-delete" tabIndex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        ×
                    </button>
                    <h4 class="modal-title">提示</h4>
                </div>
                <div class="modal-body">
                    <p class="lead">
                        <i class="fa fa-question-circle fa-lg"></i>
                        确认要删除这个角色吗?
                    </p>
                </div>
                <div class="modal-footer">
                    <form class="deleteForm" method="POST" action="/admin/role">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fa fa-times-circle"></i>确认
                        </button>
                    </form>
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
            url: "/admin/role/index", //获取数据的Servlet地址
            ajaxOptions:{
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                }
            },

            sidePagination: 'server', 
            toolbar: "#toolbar", //工具栏
            search: true, //是否启用快速搜索
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
            sortOrder: 'asc',
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
                {field:'id', title: 'ID',sortable: true},
                {field:'name', title: '角色名称'},
                {field:'description', title: '角色概述'},
                {field:'created_at', title: '创建时间',sortable: true},
                {field:'updated_at', title: '更新时间',sortable: true},
                {
                    title: '操作',
                    field: 'action',
                    align: 'center',
                    formatter: function (value, row, index) {
                        var html ='';
                        html += ' <a href="/admin/role/' + row['id'] + '/edit" class="btn btn-xs btn-success btn-editone" title="编辑"><i class="fa fa-pencil"></i> </a> ';
                        html += ' <a href="#" attr="' + row['id'] + '" class="btn btn-xs btn-danger btn-delone delBtn" title="删除"><i class="fa fa-trash"></i> </a>';
                        return html;
                    }
                }
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
                search: this.searchText
            };
            return temp;
        }
    });  
</script>
<script>
	$(document).ready(function(){
		$(document).on('click','.delBtn', function () {
            var id = $(this).attr('attr');
            $('.deleteForm').attr('action', '/admin/role/' + id);
            $("#modal-delete").modal();
            $('.modal-backdrop').css({zIndex:'0'});
        });
	});
</script>
@stop
@extends('admin.layouts.base')

@section('title','套餐列表')

@section('pageHeader','套餐列表')

@section('pageDesc','DashBoard')

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
                        <!--  搜索栏     -->
                        <div style="border-radius:2px;margin-bottom:10px;background:#f5f5f5;padding:20px;">
                            <div class="row" style="background:#f5f5f5;">
                                <div class="col-xs-12 col-sm-6 col-md-3">
                                    <div class="col-xs-6">
                                        <label class="control-label">关键字</label><br>
                                        <input type="text" class="form-control" name="sosodoc" id="sosodoc" />
                                    </div>
                                    <div class="col-xs-6">
                                        <label class="control-label" style="visibility:hidden">搜索</label><br>
                                        <span class="btn btn-success btn-block" id="soso">搜索</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    	<!--  工具栏     -->
                    	<div id="toolbar">
                    		@if(Gate::forUser(auth('admin')->user())->check('admin.goods.create'))
                    		<a class="btn btn-success" href="/admin/monthly/create" title="添加">
                    			<i class="fa fa-plus"></i>添加
                    		</a>
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
                    确认要删除这个套餐吗?
                </p>
            </div>
            <div class="modal-footer">
                <form class="deleteForm" method="POST" action="/admin/monthly">
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
            url: "/admin/monthly/index", //获取数据的Servlet地址
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
                {field:'id', title: 'ID',sortable: true},
                {field:'name', title: '套餐名称'},
                {field:'month', title: '时长（单位：天）'},
                {field:'total_price', title: '总价'},
                {field:'cur_price', title: '优惠价'},
                {field:'discount', title: '折扣'},
                {
                	field:'status',
                	title: '状态',
                	formatter: function (value, row, index) {
                        var html = '';
		                if(row['status']==1){
		                    html = '<a onclick="upstatus('+row['id']+',0,this);" class="label label-success">已上架</a>';
		                }else{
		                    html = '<a onclick="upstatus('+row['id']+',1,this);" class="label label-danger">已下架</a>';
		                }
		                return html;
                    }
                 },
                {
                    title: '操作',
                    field: 'action',
                    align: 'center',
                    formatter: function (value, row, index) {
                        var html ='';

                        @if(Gate::forUser(auth('admin')->user())->check('admin.monthly.edit'))
                        html += '<a href="/admin/monthly/' + row['id'] + '/edit" class="btn btn-xs btn-success btn-editone"><i class="fa fa-pencil"></i> </a> ';
                        @endif
                        
                        @if(Gate::forUser(auth('admin')->user())->check('admin.monthly.destroy'))
                        html += '<a href="#" attr="' + row['id'] + '" class="btn btn-xs btn-danger btn-delone delBtn"><i class="fa fa-trash"></i> </a>';
                        @endif
                        
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
                search: $('#sosodoc').val()
            };
            return temp;
        }
    });
</script>
<script>
	$(document).ready(function(){
		$(document).on('click','.delBtn', function () {
            var id = $(this).attr('attr');
            $('.deleteForm').attr('action', '/admin/monthly/' + id);
            $("#modal-delete").modal();
            $('.modal-backdrop').css({zIndex:'0'});
       });

        //自定义搜索事件
        $('#soso').click(function(){
            $('#table').bootstrapTable('refresh');
        });
	});
</script>
<script>
function upstatus(id,s,obj) {
  $('#loading').css({display:"block"});
  $.post(
    "/admin/monthly/ajax/"+id,
    {c:"status",status:s},
    function(data){

      if(data.status == 0){
        var c,i,t;
        if(s==1){
          c = "label label-success";
          i = 0;
          t = "已上架";
        }
        if(s==0){
          c = "label label-danger";
          i = 1;
          t = "已下架";
        }
        $(obj).attr("class",c);
        $(obj).text(t);
        $(obj).attr("onclick","upstatus("+id+","+i+",this);");
      }else{
      	alert(data.msg);
      }
    }
  );

}
</script>
@stop
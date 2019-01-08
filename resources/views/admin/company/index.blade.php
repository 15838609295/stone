@extends('admin.layouts.base')

@section('title','企业管理')

@section('pageHeader','')

@section('pageDesc','企业管理')

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

                        <table id="table" class="table table-striped table-bordered table-hover" width="100%"></table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-comm" tabIndex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">设置企业有效期</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="col-md-3 control-label">有效时间</label>
                    <div class="col-md-5">
                        <input type="hidden" name="company_id" id="company_id" value="">
                        <input type="text" class="date1 form-control" id="nexttime1" name="nexttime1"  style="display:inline;width:auto;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <a href="#" class="btn btn-danger upd_time">
                    <i class="fa fa-times-circle"></i>确认
                </a>
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
                    确认要删除这个企业吗?
                </p>
            </div>
            <div class="modal-footer">
                <form class="deleteForm" method="POST" action="/admin/company/deleteCompany">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
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
            url: "/admin/company/index", //获取数据的Servlet地址
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
            pageSize: 50,
            pageNumber: 1, // 首页页码
            // pageList: [10, 25, 50, 'All'],
            pageList: [50],
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
                {
                	field:'company_name',
                	title: '企业名称',
                	sortable: true,
                	formatter: function (value, row, index) {
                		return '<a href="/admin/company/'+row['id']+'/users">'+row['company_name']+'</a>';
                	}
                },
                {
                	field:'created_at',
                	title: '创建时间',
                	sortable: true,
                	formatter: function (value, row, index) {
                		return row['created_at'].substring(0,10);
                	}
                },
                {field:'realname', title: '企业主姓名'},
                {field:'mobile', title: '电话'},
                {field:'company_number', title: '企业ID'},
                {field:'company_pass', title: '企业密码'},
                {field:'account_number', title: '账号数量',sortable: true},
                {
                    field:'volid_time',
                    title: '企业有效期',
                    formatter: function (value, row, index) {
                        if(row['volid_time'] == '' || row['volid_time'] == null){
                            return '<span class="label label-danger">失效</span>';
                        }
                        var now = new Date();
                        var curtime = now.getFullYear() + "-" +((now.getMonth()+1)<10?"0":"")+(now.getMonth()+1)+"-"+(now.getDate()<10?"0":"")+now.getDate();

                        if(row['volid_time'] < curtime){
                            return '<span class="label label-danger">失效</span>';
                        }

                        return row['volid_time'].substring(0,10);
                    }
                },
                {
                    field:'action',
                    title: '操作',
                    formatter: function (value, row, index) {
                        var html = '';
                        html += '<a href="#" title="设置有效期" attr="'+ row['id'] +'" class="commbtnBtn btn btn-xs btn-success btn-editone">设置有效期 </a> ';
                      
                        html += '<a href="#" title="删除" attr="'+ row['id'] +'" class="deleteBtn btn btn-xs btn-danger btn-editone">删除 </a> ';
                        return html;
                    }
                },
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
    //初始化时间插件
    layui.use('laydate', function(){
        var laydate = layui.laydate;

        //常规用法
        laydate.render({
            elem: '#nexttime1'
            ,type: 'datetime'
        });

    });
</script>
<script>
    $(document).ready(function () {
        //自定义搜索事件
        $('#soso').click(function(){
            $('#table').bootstrapTable('refresh');
        });

        $(document).on('click','.commbtnBtn', function () {
            var id = $(this).attr('attr');
            $('#nexttime1').val('');
            $('#company_id').val(id);
            $("#modal-comm").modal();
            $('.modal-backdrop').css({zIndex:'0'});
        });
      
       $(document).on('click','.deleteBtn', function () {
            var id = $(this).attr('attr');
            $('.deleteForm').attr('action', '/admin/company/deleteCompany/' + id);
            $("#modal-delete").modal();
            $('.modal-backdrop').css({zIndex:'0'});
        });

        $(document).on('click','.upd_time',function () {
            var id = $('#company_id').val();
            var volid_time = $('#nexttime1').val();

            $.post(
                '/admin/company/ajax/'+id,
                {'volid_time':volid_time},
                function (d) {
                    if(d.status == 0){
                        alert('修改成功！！！');
                    }else{
                        alert('修改失败！！！');
                    }
                    window.location.reload();
                }
            );
        })

    });
</script>
@stop
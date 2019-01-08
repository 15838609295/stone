@extends('admin.layouts.base')

@section('title','企业概况')

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
@stop

@section('js')
<script>

    $(function () {
        var table = $("#table");
        // 初始化表格
        table.bootstrapTable({
            method: "POST",  //使用get请求到服务器获取数据
            contentType:"application/x-www-form-urlencoded; charset=UTF-8",
            url: "/admin/operate_company/index", //获取数据的Servlet地址
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
                {field:'company_name', title: '企业名称'},
                {field:'cur_month_d', title: '本月登录次数',sortable: true},
                {field:'last_month_d', title: '上月登录次数',sortable: true},
                {field:'total_d', title: '总登录次数',sortable: true},
                {field:'cur_month_c', title: '本月操作次数',sortable: true},
                {field:'last_month_c', title: '上月操作次数',sortable: true},
                {field:'total_c', title: '总操作次数',sortable: true},
                {field:'total_j_d', title: '月均登录次数',sortable: true},
                {field:'total_j_c', title: '月均操作次数',sortable: true},
                {field:'user_number', title: '账号数量',sortable: true},
                {
                    field:'created_at',
                    title: '创建时间',
                    sortable: true,
                    formatter: function (value, row, index) {
                        if( row['created_at'] == '' || row['created_at'] == null){
                            return '';
                        }
                        return row['created_at'].substring(0,10);
                    }
                },
                {
                    field:'login_time',
                    title: '最近登录',
                    sortable: true,
                    formatter: function (value, row, index) {
                        if( row['login_time'] == '' || row['login_time'] == null){
                            return '';
                        }
                        return row['login_time'].substring(0,16);
                    }
                },
                {field:'realname', title: '企业主姓名'},
                {
                    title: '详情',
                    field: 'action',
                    align: 'center',
                    formatter: function (value, row, index) {
                        return '<a href="/admin/operate/companyinfo?company_id='+row['id']+'" class="btn btn-xs btn-success btn-editone">使用详情</a> ';
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
    $(document).ready(function () {
        //自定义搜索事件
        $('#soso').click(function(){
            $('#table').bootstrapTable('refresh');
        });
    });
</script>
@stop
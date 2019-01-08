@extends('admin.layouts.base')

@section('title','企业管理')

@section('pageHeader','')

@section('pageDesc')
<a href="/admin/company/index">企业管理</a>  >>  <a href="/admin/company/{{$id}}/stocks">{{$name}}</a>
@stop

@section('css')
@stop

@section('content')
    <div class="layui-tab layui-tab-brief" lay-filter="docDemoTabBrief">
        <ul class="layui-tab-title">
            <li><a href="/admin/company/{{$id}}/users">员工列表</a></li>
            <li class="layui-this">实时库存</li>
            <li><a href="/admin/company/{{$id}}/depots">仓库信息</a></li>
            <li><a href="/admin/company/{{$id}}/images">商品图库</a></li>
            <li><a href="/admin/company/{{$id}}/sales">销售情况</a></li>
            <li><a href="/admin/company/{{$id}}/salelog">销售趋势</a></li>
        </ul>
        <div class="layui-tab-content" style="height: 100px;">
            <div class="layui-tab-item layui-show">
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
            url: "/admin/company/{{$id}}/stocks", //获取数据的Servlet地址
            ajaxOptions:{
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                }
            },

            sidePagination: 'server',
            toolbar: "#toolbar", //工具栏
            search: false, //是否启用快速搜索
            cache: false,
            commonSearch: false, //是否启用通用搜索
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
                {field:'goods_attr_name', title: '产品名称'},
                {
                    field:'type_h',
                    title: '荒料',
                    sortable: true,
                    formatter: function (value, row, index) {
                        return row['type_h']+' m³';
                    }
                },
                {
                    field:'type_d',
                    title: '大板',
                    sortable: true,
                    formatter: function (value, row, index) {
                        return row['type_d']+' m²';
                    }
                },
                {
                    field:'total',
                    title: '折合',
                    sortable: true,
                    formatter: function (value, row, index) {
                        return row['total']+' m²';
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
@stop
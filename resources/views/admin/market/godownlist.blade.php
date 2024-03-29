@extends('admin.layouts.base')

@section('title','产品列表')

@section('pageHeader','')

@section('pageDesc','市场概况')

@section('css')

@stop

@section('content')
    <style>
        .btn-gray{
            background-color: #b7b7b7;
            color: #fff;
        }

        .btn-gray:hover {
            color: #fff;
            background-color: #7a7a7a;
            border-color: #7a7a7a;
        }
    </style>
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
            url: "/admin/market_godown/index", //获取数据的Servlet地址
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
                {field:'goods_attr_name', title: '产品名称'},
                {field:'company_number', title: '在售企业数',sortable: true},
                {
                	field:'market_h',
                	title: '市场荒料存量',
                	sortable: true,
                	formatter: function (value, row, index) {
                		return row['market_h']+' m³';
                	}
                },
                {
                    field:'market_d',
                    title: '市场大板存量',
                    sortable: true,
                    formatter: function (value, row, index) {
                        return row['market_d']+' m²';
                    }
                },
                {
                    field:'sale_h',
                    title: '上月销售荒料',
                    sortable: true,
                    formatter: function (value, row, index) {
                        return row['sale_h']+' m³';
                    }
                },
                {
                    field:'sale_d',
                    title: '上月销售大板',
                    sortable: true,
                    formatter: function (value, row, index) {
                        return row['sale_d']+' m²';
                    }
                },
                {
                    field:'sale_t',
                    title: '上月销售收入',
                    sortable: true,
                    formatter: function (value, row, index) {
                        return row['sale_t']+' 元';
                    }
                },
                {
                    field:'money_t',
                    title: '收入',
                    sortable: true,
                    formatter: function (value, row, index) {
                        return row['money_t']+' 元';
                    }
                },
                {
                    field:'action',
                    title: '详情',
                    sortable: true,
                    formatter: function (value, row, index) {
                        var html= '';
                        html += '<a href="/admin/market_godown/info?goods_attr_name='+row['goods_attr_name']+'" class="btn btn-xs btn-success btn-editone">产品详情</a> ';
                        if (row['authentication'] == '0'){
                            html += '<a href="#" title="未认证" attr="'+ row['goods_attr_name'] +'" class="commbtnBtn btn btn-xs btn-gray btn-authentication">未认证 </a> ';
                        }else{
                            html += '<a href="#" title="已认证" attr="'+ row['goods_attr_name'] +'" class="commbtnBtn btn btn-xs btn-warning btn-unsetauthentication">已认证 </a> ';
                        }
                        if (row['status'] == '0'){
                            html += '<a href="#" title="已下架" attr="'+ row['goods_attr_name'] +'" class="deleteBtn btn btn-xs  btn-gray btn-upperShelf">已下架 </a> ';
                        }else{
                            html += '<a href="#" title="已上架" attr="'+ row['goods_attr_name'] +'" class="deleteBtn btn btn-xs  btn-info btn-lowerShelf">已上架 </a> ';
                        }
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
    $(document).ready(function () {
        //自定义搜索事件
        $('#soso').click(function(){
            $('#table').bootstrapTable('refresh');
        });
    });
</script>
<script>
    //认证
    $(document).on('click','.btn-authentication',function () {
        var goods_attr_name = $(this).attr('attr');
        $.post(
            '/admin/market_godown/authentication',
            {'name':goods_attr_name},
            function (d) {
                if(d.status == 0){
                    alert('修改成功！！！');
                }else{
                    alert('修改失败！！！');
                }
                window.location.reload();
            }
        );
    });
    //取消认证
    $(document).on('click','.btn-unsetauthentication',function () {
        var goods_attr_name = $(this).attr('attr');
        $.post(
            '/admin/market_godown/unsetauthentication',
            {'name':goods_attr_name},
            function (d) {
                if(d.status == 0){
                    alert('修改成功！！！');
                }else{
                    alert('修改失败！！！');
                }
                window.location.reload();
            }
        );
    });
    //上架
    $(document).on('click','.btn-upperShelf',function () {
        var goods_attr_name = $(this).attr('attr');
        $.post(
            '/admin/market_godown/upperShelf',
            {'name':goods_attr_name},
            function (d) {
                if(d.status == 0){
                    alert('修改成功！！！');
                }else{
                    alert('修改失败！！！');
                }
                window.location.reload();
            }
        );
    });
    //下架
    $(document).on('click','.btn-lowerShelf',function () {
        var goods_attr_name = $(this).attr('attr');
        $.post(
            '/admin/market_godown/lowerShelf',
            {'name':goods_attr_name},
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
</script>
@stop

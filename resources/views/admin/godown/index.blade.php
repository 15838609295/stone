@extends('admin.layouts.base')

@section('title','入库列表')

@section('pageHeader','')

@section('pageDesc','入库列表')

@section('css')
<style>
table.table-bordered tbody td{
    line-height: 67px;
}
</style>
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
            url: "/admin/godown/index", //获取数据的Servlet地址
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
                	field:'godown_pic',
                	title: '入库图像',
                	formatter: function (value, row, index) {
                		var str = '';
                        if(!row['godown_pic']){
                            row['godown_pic'] = "/defaultpic.gif";
                        }
                        str = '<img src="'+ row['godown_pic'] +'" width="50px;">';
                        return str;	
                	}
                },
                {
                	field:'type',
                	title: '产品类型',
                	formatter: function (value, row, index) {
                		if(row['type']==1){
                			return '大板';
                		}else{
                			return '荒料';
                		}	
                	}
                },
                {field:'goods_attr_name', title: '品种名称'},
                {field:'depot_name', title: '仓库名称'},
                {field:'godown_no', title: '产品编号'},
                {field:'godown_number', title: '产品数量'},
                {
                	field:'godown_weight',
                	title: '重量或体积',
                	formatter: function (value, row, index) {
                		if(row['type']==1){
                			return row['godown_weight']+'㎡';
                		}else{
                			return row['godown_weight']+'吨';
                		}	
                	}
                },
                {
                	field:'godown_length',
                	title: '产品尺寸',
                	formatter: function (value, row, index) {
                		return row['godown_length']+' * '+row['godown_width']+' * '+row['godown_height']+' mm';
                	}
                },
                {
                	field:'godown_status',
                	title: '产品状态',
                	formatter: function (value, row, index) {
                		if(row['godown_status']==0){
                			return '<span class="label label-success">未用</span>';
                		}else{
                			return '<span class="label label-danger">已用</span>';
                		}	
                	}	
                },
                {
                	field:'no_start',
                	title: '大板的序号',
                	formatter: function (value, row, index) {
                		if(row['type']==1){
                			return row['no_start']+'至'+row['no_end'];
                		}else{
                			return ' ';
                		}	
                	}
                },
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
                search: this.searchText
            };
            return temp;
        }
    });
</script>
@stop
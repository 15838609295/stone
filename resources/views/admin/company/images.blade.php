@extends('admin.layouts.base')

@section('title','企业管理')

@section('pageHeader','')

@section('pageDesc',"企业管理   >>   $name")

@section('css')
@stop

@section('content')
    <div class="layui-tab layui-tab-brief" lay-filter="docDemoTabBrief">
        <ul class="layui-tab-title">
            <li><a href="/admin/company/{{$id}}/users">员工列表</a></li>
            <li><a href="/admin/company/{{$id}}/stocks">实时库存</a></li>
            <li><a href="/admin/company/{{$id}}/depots">仓库信息</a></li>
            <li class="layui-this">商品图库</li>
            <li><a href="/admin/company/{{$id}}/sales">销售情况</a></li>
            <li><a href="/admin/company/{{$id}}/salelog">销售趋势</a></li>
        </ul>
      
       <div class="layui-tab row" style="background:#fff;margin-top:10px;padding:10px;">
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="col-xs-6">
                <label class="control-label">产品</label><br>
                <select name="goods_attr_name" id="goods_attr_name" class="group form-control">
                    <option value="">全部</option>
                    @foreach($goodsattr as $v)
                        <option value="{{ $v->goods_attr_name }}" @if($goods_attr_name==$v->goods_attr_name) selected @endif>{{ $v->goods_attr_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xs-6">
                <label class="control-label">状态</label><br>
                <select name="type" id="type" class="group form-control">
                    <option value="2">全部</option>
                    <option value="0" @if($type==0) selected @endif>荒料</option>
                    <option value="1" @if($type==1) selected @endif>大板</option>
                </select>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6 col-md-3">
            <div class="col-xs-6">
                <label class="control-label" style="visibility:hidden">搜索</label><br>
                <span class="btn btn-success btn-block" id="soso">搜索</span>
            </div>
        </div>
    </div>
      
        <div class="layui-tab-content" style="width:100%;height: 100px;background:#fff;display:inline-table;">
            <div class="layui-tab-item layui-show">
                <ul class="site-doc-icon site-doc-anim">
                    @foreach($data as $k=>$v)
                        <li style="width:200px;float:left;margin-right:25px;">
                            <div class="mailbox-attachment-icon has-img">
                                <img style="width:100%;" src="{{empty($v->godown_pic) ? '/wu.png':$v->godown_pic[0]}}" />
                            </div>
                            <div class="mailbox-attachment-info">
                              	<span class="mailbox-attachment-size">
                                    品种：{{$v->goods_attr_name}}
                                </span>
                                <span class="mailbox-attachment-size">
                                    编号：{{$v->godown_no}}
                                </span>
                              	@if($v->type == 0)
                              	<span class="mailbox-attachment-size">
                                    状态：荒料
                                </span>
                              	<span class="mailbox-attachment-size">
                                    剩余：{{$v->godown_weight}}m³
                                </span>
                              	<span class="mailbox-attachment-size">
                                    尺寸：{{$v->godown_length}}*{{$v->godown_width}}*{{$v->godown_height}}
                                </span>
                              	@else
                             	<span class="mailbox-attachment-size">
                                    状态：大板
                                </span>
                              	<span class="mailbox-attachment-size">
                                    剩余：{{$v->godown_number}}件
                                </span>
                              	<span class="mailbox-attachment-size">
                                    尺寸：{{$v->godown_length}}*{{$v->godown_width}}
                                </span>
                              	@endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
    $(document).ready(function () {
        $('#soso').click(function () {
            var type = $('#type').val();
            var goods_attr_name = $('#goods_attr_name').val();

            window.location.href = "/admin/company/{{$id}}/images?type="+type+"&goods_attr_name="+goods_attr_name;
        });
    });
</script>
@stop
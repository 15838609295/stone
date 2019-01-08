@extends('admin.layouts.base')

@section('title','系统配置')

@section('pageHeader','系统配置')

@section('pageDesc','DashBoard')

@section('css')
<style>
@media (max-width: 375px) {
    .edit-form tr td input{width:100%;}
    .edit-form tr th:first-child,.edit-form tr td:first-child{
        width:20%;
    }
    .edit-form tr th:last-child,.edit-form tr td:last-child{
        display: none;
    }
}
.hidden_class{
	display:none;
}
td,th{
	font-size:14px;
	line-height:30px !important;
}
</style>
@stop

@section('content')
	<div class="box" style="border-top:0;">
		@include('admin.partials.errors')
        @include('admin.partials.success')
	</div>
	<div class="panel panel-default panel-intro">
		<div class="panel-heading" style="padding-bottom:0;border-bottom:0">
	        <ul class="nav nav-tabs">
	            <li class="active"><a href="javascript:void(0)" onclick="onclick_tab('wechat')" data-toggle="tab">小程序配置</a></li>
				<li><a href="javascript:void(0)" onclick="onclick_tab('wxpay')" data-toggle="tab">微信支付配置</a></li>
				<li><a href="javascript:void(0)" onclick="onclick_tab('company')" data-toggle="tab">企业配置</a></li>
			</ul>
	    </div>

		<div class="panel-body">
			<div id="myTabContent" class="tab-content">
				<div class="tab-pane fade active in" id="basic">
					<div class="widget-body no-padding">
						<form class="" role="form" data-toggle="validator" method="POST" action="/admin/configs/update">
							<table class="table"  style="width:100%;background-color:#fff;border:1px solid #fff;">
								<thead>
									<tr>
										<th width="15%">属性</th>
										<th width="70%">属性值</th>
									</tr>
								</thead>
    
								<tbody class="wechat_class hidden_class" style="display:table-header-group;">
									<tr>
										<td width="15%">微信小程序appid</td>
										<td width="70%">
											<div class="row">
												<div class="col-sm-8 col-xs-12">
													<input type="text" name="wechat_appid" value="{{ $wechat_appid }}" class="form-control" />
												</div>
												<div class="col-sm-4"></div>
											</div>

										</td>
									</tr>
									<tr>
										<td>微信小程序secret</td>
										<td>
											<div class="row">
												<div class="col-sm-8 col-xs-12">
													<input type="text" name="wechat_secret" value="{{ $wechat_secret }}" class="form-control" />
												</div>
												<div class="col-sm-4"></div>
											</div>

										</td>
									</tr>
								</tbody>
								<tbody class="wxpay_class hidden_class">
								<tr>
									<td>微信支付商户号</td>
									<td>
										<div class="row">
											<div class="col-sm-8 col-xs-12">
												<input type="text" name="mch_id" value="{{ $mch_id }}" class="form-control" />
											</div>
											<div class="col-sm-4"></div>
										</div>

									</td>
								</tr>
								<tr>
									<td>微信支付密钥</td>
									<td>
										<div class="row">
											<div class="col-sm-8 col-xs-12">
												<input type="text" name="mch_key" value="{{ $mch_key }}" class="form-control" />
											</div>
											<div class="col-sm-4"></div>
										</div>

									</td>
								</tr>
								<tr>
									<td>异步通知地址</td>
									<td>
										<div class="row">
											<div class="col-sm-8 col-xs-12">
												<input type="text" name="notify_url" value="{{ $notify_url }}" class="form-control" />
											</div>
											<div class="col-sm-4"></div>
										</div>

									</td>
								</tr>
								</tbody>
								<tbody class="company_class hidden_class">
								<tr>
									<td>新建企业试用期</td>
									<td>
										<div class="row">
											<div class="col-sm-8 col-xs-12">
												<input type="number" name="test_time" value="{{ $test_time }}" class="form-control" />
											</div>
											<div class="col-sm-4">单位为（天）</div>
										</div>

									</td>
								</tr>
								</tbody>
								<tfoot>
									<tr>
										<td></td>
										<td>
											<input type="hidden" name="id" value="{{ $id }}" />
											<button type="submit" class="btn btn-success btn-embossed">确定</button>
										</td>
									</tr>
								</tfoot>
							</table>
						</form>
					</div>
				</div>
				
				<!--  分割的div  -->
				
			</div>
		</div>
	</div>

</div>
@stop

@section('js')
<script>
	// 选项卡选择事件
	function onclick_tab(str){
		$('.hidden_class').css({
			display:"none"
		});
		$('.'+str+'_class').css({
			display:"table-header-group"
		});
	}
</script>    
@stop
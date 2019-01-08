<div class="form-group">
    <label class="col-md-3 control-label">名称</label>
    <div class="col-md-5">
        <input type="text" class="form-control" name="name" value="{{ $name }}" autofocus>
    </div>
</div>

<div class="form-group">
    <label class="col-md-3 control-label">时长（单位：天）</label>
    <div class="col-md-5">
        <input type="number" class="form-control" name="month" value="{{ $month }}" autofocus>
    </div>
</div>

<div class="form-group">
    <label class="col-md-3 control-label">总价</label>
    <div class="col-md-5">
        <input type="text" class="form-control" name="total_price" value="{{ $total_price }}" autofocus>
    </div>
</div>

<div class="form-group">
    <label class="col-md-3 control-label">月均价</label>
    <div class="col-md-5">
        <input type="text" class="form-control" name="month_price" value="{{ $month_price }}" autofocus>
    </div>
</div>

<div class="form-group">
    <label class="col-md-3 control-label">实付价</label>
    <div class="col-md-5">
        <input type="text" class="form-control" name="cur_price" value="{{ $cur_price }}" autofocus>
    </div>
  	<div>
        注：这个是实际支付价格
    </div>
</div>

<div class="form-group">
    <label class="col-md-3 control-label">折扣</label>
    <div class="col-md-5">
        <input type="text" class="form-control" name="discount" value="{{ $discount }}" autofocus>
    </div>
    <div>
        例：9折优惠写个9,9.6折优惠就写9.6
    </div>
</div>









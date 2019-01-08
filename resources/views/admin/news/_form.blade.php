<div class="form-group">
    <label for="tag" class="col-md-3 control-label">标题</label>
    <div class="col-md-5">
        <input type="text" class="form-control" name="title" id="tag" value="{{ $title }}" autofocus>
    </div>
</div>

<div class="form-group">
    <label for="tag" class="col-md-3 control-label">公告类型</label>
    <div class="col-md-5">
        <input type="text" class="form-control" name="type" id="tag" value="{{ $type}}" autofocus>
    </div>
</div>

<div class="form-group">
    <label for="tag" class="col-md-3 control-label">新闻内容</label>
    <div class="col-md-5">
        <script id="content" name="content" type="text/plain" >{!! $content !!}</script>
    </div>
</div>



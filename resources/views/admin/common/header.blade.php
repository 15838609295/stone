<!-- Logo -->
<a href="javascript:;" class="logo hidden-xs">
    <!-- 迷你模式下Logo的大小为50X50 -->
    <span class="logo-mini">石材</span>
    <!-- 普通模式下Logo -->
    <span class="logo-lg"><b>石材</b>助手</span>
</a>
<!-- 顶部通栏样式 -->
<nav class="navbar navbar-static-top">
    <!-- 边栏切换按钮-->
    <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
        <span class="sr-only">Toggle navigation</span>
    </a>

    <div id="nav" class="pull-left">
        <!--如果不想在顶部显示角标,则给ul加上disable-top-badge类即可-->
        <ul class="nav nav-tabs nav-addtabs disable-top-badge" role="tablist">
        </ul>
    </div>

    <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">

            {{--<li>--}}
                {{--<a href="__PUBLIC__" target="_blank"><i class="fa fa-home" style="font-size:14px;"></i></a>--}}
            {{--</li>--}}

            {{--<li class="dropdown notifications-menu hidden-xs">--}}
                {{--<a href="#" class="dropdown-toggle" data-toggle="dropdown">--}}
                    {{--<i class="fa fa-bell-o"></i>--}}
                    {{--<span class="label label-warning"></span>--}}
                {{--</a>--}}
                {{--<ul class="dropdown-menu">--}}
                    {{--<li class="header">{:__('Latest news')}</li>--}}
                    {{--<li>--}}
                        {{--<!-- FastAdmin最新更新信息,你可以替换成你自己站点的信息,请注意修改public/assets/js/backend/index.js文件 -->--}}
                        {{--<ul class="menu">--}}

                        {{--</ul>--}}
                    {{--</li>--}}
                    {{--<li class="footer"><a href="#" target="_blank">{:__('View more')}</a></li>--}}
                {{--</ul>--}}
            {{--</li>--}}

            {{--<li class="hidden-xs">--}}
                {{--<a href="javascript:;" data-toggle="checkupdate" title="{:__('Check for updates')}">--}}
                    {{--<i class="fa fa-refresh"></i>--}}
                {{--</a>--}}
            {{--</li>--}}

            {{--<li>--}}
                {{--<a href="javascript:;" data-toggle="dropdown" title="{:__('Wipe cache')}">--}}
                    {{--<i class="fa fa-trash"></i>--}}
                {{--</a>--}}
                {{--<ul class="dropdown-menu wipecache">--}}
                    {{--<li><a href="javascript:;" data-type="all"><i class="fa fa-trash"></i> {:__('Wipe all cache')}</a></li>--}}
                    {{--<li class="divider"></li>--}}
                    {{--<li><a href="javascript:;" data-type="content"><i class="fa fa-file-text"></i> {:__('Wipe content cache')}</a></li>--}}
                    {{--<li><a href="javascript:;" data-type="template"><i class="fa fa-file-image-o"></i> {:__('Wipe template cache')}</a></li>--}}
                    {{--<li><a href="javascript:;" data-type="addons"><i class="fa fa-rocket"></i> {:__('Wipe addons cache')}</a></li>--}}
                {{--</ul>--}}
            {{--</li>--}}

            {{--<li class="hidden-xs">--}}
                {{--<a href="#" data-toggle="fullscreen"><i class="fa fa-arrows-alt"></i></a>--}}
            {{--</li>--}}

            <!-- 账号信息下拉框 -->
            <li class="dropdown user user-menu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    <img src="/123.png" class="user-image" alt="{{auth('admin')->user()->name}}">
                    <span class="hidden-xs">{{auth('admin')->user()->name}}</span>
                </a>
                <ul class="dropdown-menu">
                    <!-- User image -->
                    <li class="user-header">
                        <img src="/123.png" class="img-circle" alt="">
                        <p>
                            {{auth('admin')->user()->name}}
                            <small>{{date('Y-m-d H:i',strtotime(auth('admin')->user()->updated_at))}}</small>
                        </p>
                    </li>

                    <!-- Menu Footer-->
                    <li class="user-footer">
                        <div class="pull-left">
                            <a href="#" class="btn btn-primary updpass"><i class="fa fa-user"></i>
                                修改密码</a>
                        </div>
                        <div class="pull-right">
                            <a href="/admin/logout" class="btn btn-danger"><i class="fa fa-sign-out"></i>
                                注销</a>
                        </div>
                    </li>
                </ul>
            </li>

        </ul>
    </div>
</nav>
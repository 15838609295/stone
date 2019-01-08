<!-- sidebar: style can be found in sidebar.less -->
<section class="sidebar" style="height: auto;">
    <!-- Sidebar user panel -->
    <div class="user-panel">
        <div class="pull-left image">
            <img src="/123.png" class="img-circle" alt="User Image">
        </div>
        <div class="pull-left info">
            <p>{{auth('admin')->user()->name}}</p>
            <i class="fa fa-circle text-success"></i> 在线
        </div>
    </div>



    <!-- search form -->
    <form action="" method="get" class="sidebar-form" onsubmit="return false;">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="">
            <span class="input-group-btn">
                <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
                </button>
            </span>
            <div class="menuresult list-group sidebar-form hide">
            </div>
        </div>
    </form>
    <!-- /.search form -->

    <!-- sidebar menu: : style can be found in sidebar.less -->
    <!--如果想始终显示子菜单,则给ul加上show-submenu类即可-->
    <ul class="sidebar-menu">
        <?php $comData=Request::get('comData_menu'); ?>
        @foreach($comData['top'] as $v)
        <li class="treeview @if(in_array($v['id'],$comData['openarr'])) active @endif">
            <a href="#"><i class="fa {{ $v['icon'] }}"></i>
                <span>{{$v['label']}}</span>
                @if($comData[$v['id']])<i class="fa fa-angle-left pull-right"></i>@endif
            </a>
            <ul class="treeview-menu">
                @foreach($comData[$v['id']] as $vv)
                    <li @if(in_array($vv['id'],$comData['openarr'])) class="active" @endif>
                        <a href="{{URL::route($vv['name'])}}" addtabs="{{$vv['id']}}" url="{{URL::route($vv['name'])}}">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <i class="fa fa-circle-o"></i>
                            {{$vv['label']}}
                        </a>
                    </li>
                @endforeach
            </ul>
        </li>
        @endforeach
    </ul>
</section>
<!-- /.sidebar -->
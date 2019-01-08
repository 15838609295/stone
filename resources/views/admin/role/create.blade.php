@extends('admin.layouts.base')

@section('title','角色添加')

@section('pageHeader','角色添加')

@section('pageDesc','DashBoard')
@section('css')
<style>
    .all-check{
        cursor:pointer
    }
</style>
@stop
@section('content')
    <div class="main animsition">
        <div class="container-fluid">

            <div class="row">
                <div class="">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">角色添加</h3>
                        </div>
                        <div class="panel-body">

                            @include('admin.partials.errors')
                            @include('admin.partials.success')

                            <form class="form-horizontal" role="form" method="POST" action="/admin/role">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" name="cove_image"/>
                                @include('admin.role._form')
                                <div class="form-group">
                                    <div class="col-md-7 col-md-offset-3">
                                        <button type="submit" class="btn btn-primary btn-md">
                                            <i class="fa fa-plus-circle"></i>
                                            添加
                                        </button>
                                    </div>
                                </div>

                            </form>

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
            $('.all-check').on('click', function () {
                var inp = $(this).next(".col-md-6").find("input[type='checkbox']");
                inp.each(function(index,element){
                    if(element.checked == false){
                        element.checked=true;
                    }else{
                        element.checked=false;
                    }
                });
            });
        });
    </script>
@stop

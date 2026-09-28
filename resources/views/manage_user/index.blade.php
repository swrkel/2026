@extends('layouts.app')
@section('title', 'Users')

@section('content')
<link rel="stylesheet" href="{{ asset('css/user-management-actions.css') }}?v=059">

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang( 'user.users' )
        <small>@lang( 'user.manage_users' )</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content user-management-list-page">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'user.all_users' )])
        @can('user.create')
            @slot('tool')
                <div class="box-tools pull-right">
                    <a class="btn btn-primary" 
                    href="{{action('ManageUserController@create')}}" >
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</a>
                 </div>
                 <hr>
            @endslot
        @endcan
        @can('user.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="users_table">
                    <thead>
                        <tr>
                            <th>@lang( 'business.username' )</th>
                            <th>@lang( 'user.name' )</th>
                            <th>@lang( 'user_group.user_group' )</th>
                            <th>@lang( 'user.designation' )</th>
                            <th>@lang( 'user.role' )</th>
                            <th>@lang( 'lang_v1.sales_agent' )</th>
                            <th>@lang( 'lang_v1.language' )</th>
                            <th>@lang( 'business.reCAPTCHA' )</th>
                            @if($petro_module)
                            @can('pump_operator.access_code')
                            <th>@lang( 'lang_v1.pass_code' )</th>
                            @endcan
                            @endif
                            <th>@lang( 'messages.action' )</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade user_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

    @include('layouts.partials.erp-action-dropdown-global-fix')
</section>
<!-- /.content -->
@stop
@section('javascript')
<script type="text/javascript">
    // Users table
    $(document).ready( function(){
        var users_table = $('#users_table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '/users',
                        cache: false
                    },
                    "columns":[
                        {"data":"username"},
                        {"data":"full_name"},
                        {"data":"user_group"},
                        {"data":"designation"},
                        {"data":"role"},
                        {"data":"sales_agent"},
                        {"data":"language"},
                        {"data":"reCAPTCHA"},
                        @if($petro_module)
                        @can('pump_operator.access_code')
                        {"data":"pump_operator_passcode"},
                        @endcan
                        @endif
                        {"data":"action", "orderable": false, 'searchable': false}
                    ],
                    drawCallback: function () {
                        $('[data-toggle="tooltip"]').tooltip({container: 'body'});
                    }
                });
                
        $(document).on('click', 'button.change_recaptcha_user_button', function(){
            swal({
              title: LANG.sure,
              text: LANG.confirm_change_recapcha_user,
              icon: "warning",
              buttons: true,
              dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var href = $(this).data('href');
                    var data = $(this).serialize();
                    $.ajax({
                        method: "GET",
                        url: href,
                        dataType: "json",
                        data: data,
                        success: function(result){
                            if(result.success == true){
                                toastr.success(result.msg);
                                users_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
             });
        });
        $(document).on('click', '.delete_user_button', function(event){
            event.preventDefault();
            swal({
              title: LANG.sure,
              text: LANG.confirm_delete_user,
              icon: "warning",
              buttons: true,
              dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    var href = $(this).data('href');
                    var data = $(this).serialize();
                    $.ajax({
                        method: "DELETE",
                        url: href,
                        dataType: "json",
                        data: data,
                        success: function(result){
                            if(result.success == true){
                                toastr.success(result.msg);
                                users_table.ajax.reload();
                            } else {
                                toastr.error(result.msg);
                            }
                        }
                    });
                }
             });
        });
        
    });
    
    
</script>
@endsection

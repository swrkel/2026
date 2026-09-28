@extends('layouts.app')

@section('title', __('user_group.user_groups'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('user_group.user_groups')</h1>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('user_group.user_groups')])
        @can('user_group.create')
            @slot('tool')
                <div class="box-tools pull-right">
                    <a class="btn btn-primary" href="{{ action('UserGroupController@create') }}">
                        <i class="fa fa-plus"></i> @lang('user_group.add_user_group')</a>
                </div>
            @endslot
        @endcan

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="user_groups_table">
                <thead>
                    <tr>
                        <th>@lang('user_group.name')</th>
                        <th>@lang('user_group.description')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function(){
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#user_groups_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ action("UserGroupController@index") }}',
            type: 'GET',
            data: function(d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
            }
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'description', name: 'description' },
            { data: 'action', name: 'action', searchable: false, orderable: false }
        ],
        pageLength: 25,
        responsive: true
    });

    $(document).on('click', '.delete_user_group', function(){
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
                            $('#user_groups_table').DataTable().ajax.reload();
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

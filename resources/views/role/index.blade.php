@extends('layouts.app')
@section('title', __('user.roles'))

@section('content')
<link rel="stylesheet" href="{{ asset('css/user-management-actions.css') }}?v=059">

@php
    $businessId = (int) request()->session()->get('user.business_id');
    $packageDetails = [];
    $subscription = Modules\Superadmin\Entities\Subscription::active_subscription($businessId);
    if (!empty($subscription)) {
        $packageDetails = (array) $subscription->package_details;
    }
@endphp

<section class="content-header">
    <h1>
        @lang('user.roles')
        <small>@lang('user.manage_roles')</small>
    </h1>
</section>

<section class="content user-management-list-page">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('user.all_roles')])
        @can('roles.create')
            @slot('tool')
                @if(!empty($packageDetails['um_add_role']))
                    <div class="box-tools pull-right">
                        <a class="btn btn-primary" href="{{ action('RoleController@create') }}">
                            <i class="fa fa-plus"></i> @lang('messages.add')
                        </a>
                    </div>
                @endif
                <hr>
            @endslot
        @endcan

        @can('roles.view')
            <div class="table-responsive role-list-table-wrapper">
                <table class="table table-bordered table-striped" id="roles_table">
                    <thead>
                        <tr>
                            <th>@lang('user.roles')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcan
    @endcomponent

    @include('layouts.partials.erp-action-dropdown-global-fix')
</section>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        var newRoleId = @json(session('new_role_id'));
        var rolesTable = $('#roles_table').DataTable({
            processing: true,
            serverSide: true,
            stateSave: false,
            order: [],
            ajax: {
                url: '/roles',
                cache: false,
                data: function (data) {
                    data.highlight_role_id = newRoleId || 0;
                }
            },
            buttons: [],
            columns: [
                {data: 'name', name: 'name'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            drawCallback: function () {
                $('[data-toggle="tooltip"]').tooltip({container: 'body'});
                if (newRoleId) {
                    var $newRow = $('#roles_table tbody tr.role-row-new').first();
                    if ($newRow.length) {
                        $newRow.attr('tabindex', '-1').focus();
                    }
                    // Highlight only the first load after creation.
                    newRoleId = null;
                }
            }
        });

        $(document).on('click', '.delete_role_button', function (event) {
            event.preventDefault();
            var $button = $(this);

            swal({
                title: LANG.sure,
                text: LANG.confirm_delete_role,
                icon: 'warning',
                buttons: true,
                dangerMode: true
            }).then(function (willDelete) {
                if (!willDelete) {
                    return;
                }

                $.ajax({
                    method: 'DELETE',
                    url: $button.data('href'),
                    dataType: 'json',
                    success: function (result) {
                        if (result.success === true) {
                            toastr.success(result.msg);
                            rolesTable.ajax.reload(null, false);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function () {
                        toastr.error(LANG.something_went_wrong || 'Something went wrong.');
                    }
                });
            });
        });
    });
</script>
@endsection

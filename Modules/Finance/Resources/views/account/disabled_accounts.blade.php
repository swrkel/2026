@extends('layouts.app')
@section('title', __('account.manage_your_disabled_account'))

@section('content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-8">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('account.manage_your_disabled_account')</h4>
                <ul class="breadcrumbs pull-left" style="margin-top:15px">
                    <li><a href="#">Finance Module</a></li>
                    <li><span>@lang('account.manage_your_disabled_account')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                {!! Form::select('location_id', $business_locations, null, [
                    'class' => 'form-control select2',
                    'placeholder' => __('petro::lang.all'),
                    'style' => 'width:100%',
                ]) !!}
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('account.all_your_disabled_accounts')])
        <div class="table-responsive finance-disabled-account-table-wrap">
            <table class="table table-bordered table-striped" id="finance_disabled_account_table" style="width:100%">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.name')</th>
                        <th>@lang('lang_v1.account_type')</th>
                        <th>@lang('lang_v1.account_sub_type')</th>
                        <th>@lang('account.account_group')</th>
                        <th>@lang('account.account_number')</th>
                        <th>@lang('lang_v1.balance')</th>
                        <th>@lang('lang_v1.added_by')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <div class="modal fade account_model" tabindex="-1" role="dialog"></div>
</section>
@endsection

@section('css')
<style>
    .finance-disabled-account-table-wrap { overflow: visible; }
    #finance_disabled_account_table_wrapper .dataTables_scrollBody { overflow: visible !important; }
    #finance_disabled_account_table .dropdown-menu { z-index: 1060; }
    @if(!$account_access)
    #finance_disabled_account_table .dataTables_empty {
        color: {{ $disabled_message_color }};
        font-size: {{ (int) $disabled_message_font_size }}px;
    }
    @endif
</style>
@endsection

@section('javascript')
<script>
$(function () {
    var disabledAccountTable = $('#finance_disabled_account_table').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        searchDelay: 300,
        pageLength: 25,
        order: [[0, 'asc']],
        language: {
            emptyTable: @json(!$account_access ? $disabled_message : __('account.no_data_available_in_table'))
        },
        ajax: {
            url: @json(route('finance.account.disabled')),
            data: function (request) {
                request.location_id = $('#location_id').val();
            },
            dataSrc: function (json) {
                if (json && json.error) {
                    toastr.error(json.error);
                }
                return json && Array.isArray(json.data) ? json.data : [];
            },
            error: function (xhr) {
                var message = xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message)
                    ? (xhr.responseJSON.error || xhr.responseJSON.message)
                    : @json(__('messages.something_went_wrong'));
                toastr.error(message);
            }
        },
        columns: [
            {data: 'name', name: 'DA.name'},
            {data: 'parent_account_type_name', name: 'DAT_PARENT.name'},
            {data: 'account_type_name', name: 'DAT_SUB.name'},
            {data: 'account_group', name: 'DAG.name'},
            {data: 'account_number', name: 'DA.account_number'},
            {data: 'balance', name: 'balance', searchable: false, orderable: false},
            {data: 'added_by', name: 'DU.first_name'},
            {data: 'action', name: 'action', searchable: false, orderable: false}
        ],
        drawCallback: function () {
            __currency_convert_recursively($('#finance_disabled_account_table'));
        },
        rowCallback: function (row, data) {
            var visible = data && data.DT_RowAttr ? parseInt(data.DT_RowAttr['data-visible'], 10) : 1;
            $(row).toggleClass('hide', visible === 0);
        }
    });

    $('#location_id').on('change', function () {
        disabledAccountTable.ajax.reload();
    });

    function accountStatusRequest(button, confirmationText) {
        swal({
            title: confirmationText || LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function (confirmed) {
            if (!confirmed) return;

            $.get($(button).data('url'))
                .done(function (result) {
                    if (result && result.success) {
                        toastr.success(result.msg);
                        disabledAccountTable.ajax.reload(null, false);
                    } else {
                        toastr.error(result && result.msg ? result.msg : @json(__('messages.something_went_wrong')));
                    }
                })
                .fail(function (xhr) {
                    toastr.error(xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : @json(__('messages.something_went_wrong')));
                });
        });
    }

    $(document).on('click', '.close_account', function () {
        accountStatusRequest(this, LANG.sure);
    });

    $(document).on('click', '.disable_status_account', function () {
        accountStatusRequest(this, LANG.sure);
    });
});
</script>
@endsection

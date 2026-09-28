@extends('layouts.app')
@section('title', __('account.fixed_assets'))

@section('content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-8">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('account.fixed_assets')</h4>
                <ul class="breadcrumbs pull-left" style="margin-top:15px">
                    <li><a href="#">Finance Module</a></li>
                    <li><span>@lang('account.fixed_assets')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<section class="content">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                <label for="fixed_asset_date_range">@lang('report.date_range'):</label>
                <input type="text" id="fixed_asset_date_range" class="form-control" readonly>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="fixed_asset_location">@lang('account.asset_location'):</label>
                <select id="fixed_asset_location" class="form-control select2" style="width:100%">
                    <option value="">@lang('petro::lang.all')</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="fixed_asset_name">@lang('account.asset_name'):</label>
                <select id="fixed_asset_name" class="form-control select2" style="width:100%">
                    <option value="">@lang('petro::lang.all')</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                <label for="fixed_asset_created_by">@lang('account.created_by'):</label>
                <select id="fixed_asset_created_by" class="form-control select2" style="width:100%">
                    <option value="">@lang('petro::lang.all')</option>
                </select>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('account.fixed_assets')])
        @slot('tool')
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-primary btn-modal"
                    data-href="{{ route('finance.fixed-assets.create') }}"
                    data-container=".fixed_asset_add_modal">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </button>
            </div>
        @endslot

        <div class="table-responsive finance-fixed-assets-wrap">
            <table class="table table-bordered table-striped" id="fixed_assets_table" style="width:100%">
                <thead>
                    <tr>
                        <th>@lang('account.date')</th>
                        <th>@lang('account.account')</th>
                        <th>@lang('account.asset_name')</th>
                        <th>@lang('account.asset_location')</th>
                        <th>@lang('account.account_no')</th>
                        <th>@lang('account.amount')</th>
                        <th>@lang('account.action')</th>
                    </tr>
                </thead>
            </table>
        </div>

        <div class="modal fade fixed_asset_add_modal" role="dialog"></div>
        <div class="modal fade fixed_asset_edit_modal" role="dialog"></div>
    @endcomponent
</section>
@endsection

@section('css')
<style>
    .finance-fixed-assets-wrap { overflow: visible; }
    #fixed_assets_table_wrapper .dataTables_scrollBody { overflow: visible !important; }
    #fixed_assets_table .dropdown-menu { z-index: 1060; }
</style>
@endsection

@section('javascript')
<script>
$(function () {
    $('.select2').select2({width: '100%'});

    var start = moment().startOf('year');
    var end = moment().endOf('year');
    var fixedAssetsTable = null;

    $('#fixed_asset_date_range').daterangepicker(dateRangeSettings, function (selectedStart, selectedEnd) {
        start = selectedStart;
        end = selectedEnd;
        $('#fixed_asset_date_range').val(
            selectedStart.format(moment_date_format) + ' - ' + selectedEnd.format(moment_date_format)
        );
        if (fixedAssetsTable) {
            fixedAssetsTable.ajax.reload();
        }
    });
    $('#fixed_asset_date_range').data('daterangepicker').setStartDate(start);
    $('#fixed_asset_date_range').data('daterangepicker').setEndDate(end);
    $('#fixed_asset_date_range').val(
        start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
    );

    fixedAssetsTable = $('#fixed_assets_table').DataTable({
        processing: true,
        serverSide: true,
        deferRender: true,
        searchDelay: 300,
        pageLength: 25,
        order: [[0, 'desc']],
        ajax: {
            url: @json(route('finance.fixed-assets.index')),
            data: function (request) {
                request.start_date = start.format('YYYY-MM-DD');
                request.end_date = end.format('YYYY-MM-DD');
                request.location_id = $('#fixed_asset_location').val();
                request.asset_name = $('#fixed_asset_name').val();
                request.created_by = $('#fixed_asset_created_by').val();
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
            {data: 'date_of_operation', name: 'FA.date_of_operation'},
            {data: 'account_name', name: 'FAA.name'},
            {data: 'asset_name', name: 'FA.asset_name'},
            {data: 'asset_location', name: 'FA.asset_location'},
            {data: 'account_no', name: 'FAA.account_number'},
            {data: 'amount', name: 'FA.amount', searchable: false},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        drawCallback: function () {
            __currency_convert_recursively($('#fixed_assets_table'));
        }
    });

    // Load filter choices independently. The first page of asset records does
    // not wait for these lists, which removes the indefinite Processing state.
    $.getJSON(@json(route('finance.fixed-assets.filter-options')))
        .done(function (options) {
            function appendOptions(selector, items, valueField, textField) {
                var element = $(selector);
                (items || []).forEach(function (item) {
                    var value = valueField ? item[valueField] : item;
                    var text = textField ? item[textField] : item;
                    element.append(new Option(text, value, false, false));
                });
                element.trigger('change.select2');
            }

            appendOptions('#fixed_asset_location', options.locations || []);
            appendOptions('#fixed_asset_name', options.names || []);
            appendOptions('#fixed_asset_created_by', options.users || [], 'id', 'name');
        });

    $('#fixed_asset_location, #fixed_asset_name, #fixed_asset_created_by').on('change', function () {
        if (fixedAssetsTable) {
            fixedAssetsTable.ajax.reload();
        }
    });

    $(document).on('click', '.fixed_asset_edit', function (event) {
        event.preventDefault();
        $('.fixed_asset_edit_modal').load($(this).attr('href'), function () {
            $(this).modal('show');
        });
    });

    $(document).on('click', '.delete_fixed_asset', function (event) {
        event.preventDefault();
        var url = $(this).data('href');

        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function (confirmed) {
            if (!confirmed) return;

            $.ajax({
                method: 'DELETE',
                url: url,
                dataType: 'json'
            }).done(function (result) {
                if (result && result.success) {
                    toastr.success(result.msg);
                    fixedAssetsTable.ajax.reload(null, false);
                } else {
                    toastr.error(result && result.msg ? result.msg : @json(__('messages.something_went_wrong')));
                }
            }).fail(function (xhr) {
                toastr.error(xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : @json(__('messages.something_went_wrong')));
            });
        });
    });
});
</script>
@endsection

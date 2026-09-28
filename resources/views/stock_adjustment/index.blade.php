@extends('layouts.app')
@section('title', __('stock_adjustment.stock_adjustments'))

@section('content')

<section class="content-header main-content-inner">
  <h1>@lang('stock_adjustment.stock_adjustments')</h1>
</section>

<section class="content">
  @component('components.widget', ['class' => 'box-primary', 'title' => __('stock_adjustment.all_stock_adjustments')])
    @slot('tool')
      <div class="box-tools">
        <a class="btn pull-right btn-primary" href="{{ action([\App\Http\Controllers\StockAdjustmentController::class, 'create']) }}">
          <i class="fa fa-plus"></i> @lang('messages.add')
        </a>
      </div>
    @endslot

    {{-- ================== Filters (AJAX) ================== --}}
    <div class="row" style="margin-bottom:10px">
      <div class="col-md-4">
        <label>@lang('report.date_range')</label>
        <input type="text" id="sa_date_range" class="form-control" readonly
               value="{{ \Carbon\Carbon::parse($start_date ?? now()->startOfMonth())->format(config('constants.default_date_format')) }} - {{ \Carbon\Carbon::parse($end_date ?? now()->endOfMonth())->format(config('constants.default_date_format')) }}">
      </div>

      <div class="col-md-3">
        <label>@lang('business.location')</label>
        <select id="sa_location_id" class="form-control">
          @foreach(($locations ?? []) as $id => $name)
            <option value="{{ $id }}" {{ isset($first_location_id) && (string)$first_location_id === (string)$id ? 'selected' : '' }}>
              {{ $name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2">
        <label>@lang('stock_adjustment.adjustment_type')</label>
        <select id="sa_adjustment_type" class="form-control">
          <option value="all" selected>All</option>
          <option value="normal">Normal</option>
          <option value="abnormal">Abnormal</option>
        </select>
      </div>

      <div class="col-md-2">
        <label>@lang('stock_adjustment.stock_adjustment_type')</label>
        <select id="sa_stock_adjustment_type" class="form-control">
          <option value="all" selected>All</option>
          <option value="increase">Increase</option>
          <option value="decrease">Decrease</option>
        </select>
      </div>

     
    </div>

    <div class="table-responsive">
      <table style="width: 100%" class="table table-bordered table-striped ajax_view" id="stock_adjustment_table">
        <thead>
          <tr>
            <th>@lang('messages.action')</th>
            <th>@lang('messages.date')</th>
            <th>@lang('purchase.ref_no')</th>
            <th>@lang('business.location')</th>
            <th>@lang('stock_adjustment.adjustment_type')</th>
            <th>@lang('stock_adjustment.stock_adjustment_type')</th>
            <th>@lang('stock_adjustment.total_amount')</th>
            <th>@lang('stock_adjustment.total_amount_recovered')</th>
            <th>@lang('stock_adjustment.reason_for_stock_adjustment')</th>
            <th>@lang('lang_v1.added_by')</th>
          </tr>
        </thead>
      </table>
    </div>
  @endcomponent
</section>
<div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

@endsection

@section('javascript')
  <script>

$(document).on('click', '.delete_stock_adjustment', function (e) {
  e.preventDefault();

  var href = $(this).data('href');               
  var $btn = $(this);

  swal({
    title: LANG.sure || 'Are you sure?',
    text: LANG.confirm_delete || 'This action cannot be undone.',
    icon: 'warning',
    buttons: true,
    dangerMode: true,
  }).then(function (willDelete) {
    if (!willDelete) return;

    $.ajax({
      url: href,
      method: 'DELETE', 
      data: {
        _token: $('meta[name="csrf-token"]').attr('content') // مهم جداً
      },
      success: function (result) {
        if (result && result.success) {
          toastr.success(result.msg || (LANG.deleted_successfully || 'Deleted successfully'));
          if ($.fn.DataTable.isDataTable('#stock_adjustment_table')) {
            $('#stock_adjustment_table').DataTable().ajax.reload(null, false);
          }
          $('.view_modal').modal('hide');
        } else {
          toastr.error(result.msg || (LANG.something_went_wrong || 'Something went wrong'));
        }
      },
      error: function (xhr) {
        if (xhr.status === 403) {
          toastr.error(LANG.permission_denied || 'Permission denied');
        } else if (xhr.status === 404) {
          toastr.error('Record not found');
        } else {
          toastr.error(LANG.something_went_wrong || 'Something went wrong');
        }
      }
    });
  });
});





    $(document).ready(function(){

      window.sa_manualDate = true;

      // Date RangePicker
      if ($('#sa_date_range').length == 1) {
        $('#sa_date_range').daterangepicker(dateRangeSettings, function(start, end){
          $('#sa_date_range').val(
            start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
          );
          window.sa_manualDate = true;
          if (window.stock_adjustment_table) {
            window.stock_adjustment_table.ajax.reload();
          }
        });

        // Keep the server-selected month/date range. Do not reset to browser month,
        // because it can move the filter to the next month on some tenant/browser timezone settings.
        $('#sa_date_range').data('daterangepicker').setStartDate(moment('{{ $start_date }}', 'YYYY-MM-DD'));
        $('#sa_date_range').data('daterangepicker').setEndDate(moment('{{ $end_date }}', 'YYYY-MM-DD'));
        $('#sa_date_range').val(
          moment('{{ $start_date }}', 'YYYY-MM-DD').format(moment_date_format) + ' - ' +
          moment('{{ $end_date }}', 'YYYY-MM-DD').format(moment_date_format)
        );
      }

      function sa_collectFilters() {
        var payload = {
          location_id: $('#sa_location_id').val(),
          adjustment_type: $('#sa_adjustment_type').val() || 'all',
          stock_adjustment_type: $('#sa_stock_adjustment_type').val() || 'all'
        };

        if (window.sa_manualDate && $('#sa_date_range').val()) {
          payload.start_date = $('#sa_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
          payload.end_date   = $('#sa_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
        }

        return payload;
      }

      // DataTable
      window.stock_adjustment_table = $('#stock_adjustment_table').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[1, 'desc']],
        ajax: {
          url: '{{ action([\App\Http\Controllers\StockAdjustmentController::class, "index"]) }}',
          data: function (d) {
            var f = sa_collectFilters();
            Object.assign(d, f);
          }
        },
        columnDefs: [
          { "width": "10%", "targets": 0, orderable:false, searchable:false }
        ],
        columns: [
          { data: 'action', name: 'action', orderable:false, searchable:false },
          { data: 'transaction_date', name: 'transactions.transaction_date' },
          { data: 'ref_no', name: 'transactions.ref_no' },
          { data: 'location_name', name: 'BL.name' },
          { data: 'adjustment_type', name: 'transactions.adjustment_type' },
          { data: 'stock_adjustment_type', name: 'transactions.stock_adjustment_type' },
          { data: 'final_total', name: 'transactions.final_total', className:'text-right' },
          { data: 'total_amount_recovered', name: 'transactions.total_amount_recovered', className:'text-right' },
          { data: 'additional_notes', name: 'transactions.additional_notes' },
          { data: 'added_by', name: 'u.first_name' }
        ],
        @include('layouts.partials.datatable_export_button')
        fnDrawCallback: function(oSettings){
          __currency_convert_recursively($('#stock_adjustment_table'));
        }
      });

      $('#sa_location_id, #sa_adjustment_type, #sa_stock_adjustment_type').on('change', function(){
        window.stock_adjustment_table.ajax.reload();
      });

      $('#sa_apply_filters').on('click', function(e){
        e.preventDefault();
        window.sa_manualDate = true;
        window.stock_adjustment_table.ajax.reload();
      });

    });
  </script>
@endsection

@extends('layouts.app')
@section('title', __('settlementsw::lang.list_settlement'))

@section('content')
<style>
.settlement-sw-filter-box,.settlement-sw-list-box{border-radius:8px;box-shadow:0 1px 6px rgba(0,0,0,.08);}
#settlement_sw_add_btn{min-width:75px;font-weight:600;}
#settlement_sw_add_btn i{margin-right:4px;}

/* List Settlement: increase the Actions dropdown height by a further 20%. */
#list_settlement tbody td:first-child > .btn-group > .dropdown-toggle {
    min-height:46px !important;
    padding:11px 14px !important;
    display:inline-flex !important;
    align-items:center;
    justify-content:center;
    white-space:nowrap;
}
#list_settlement tbody td:first-child > .btn-group > .dropdown-toggle .caret{margin-left:6px;}

/* Settlement list typography standard: 50% larger than the former 10px data text. */
#list_settlement th,
#list_settlement td {
    font-size:15px !important;
    line-height:1.35 !important;
    vertical-align:middle !important;
}
#list_settlement td a,
#list_settlement td .btn,
#list_settlement td .label,
#list_settlement td .badge {font-size:15px !important;}
</style>



<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('settlementsw::lang.settlement_sw')</a></li>
                    <li><span>@lang( 'settlementsw::lang.mange_list_settlement_sw') </span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner">
    @if(!empty($message)) {!! $message !!} @endif
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid settlement-sw-filter-box">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-filter"></i> @lang('report.filters')</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                        {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('settlementsw::lang.all'), 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('pump_operator', __('settlementsw::lang.pump_operator').':') !!}
                        {!! Form::select('pump_operator', $pump_operators, null, ['class' => 'form-control select2', 'placeholder' => __('settlementsw::lang.all')]); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('settlement_no', __('settlementsw::lang.settlement_number').':') !!}
                        {!! Form::select('settlement_no', $settlement_nos, null, ['class' => 'form-control select2', 'placeholder' => __('settlementsw::lang.all')]); !!}
                    </div>
                </div>
            
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('date_range', __('report.date_range') . ':') !!}
                        {!! Form::text('date_range', @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'expense_date_range', 'readonly']); !!}
                    </div>
                </div>
            
            </div>
        </div>
    </div>
        </div>
    </div>

    <div class="box box-primary settlement-sw-list-box">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('settlementsw::lang.list_settlement')</h3>
            <div class="box-tools pull-right">
                <a id="settlement_sw_add_btn"
                   class="btn btn-primary"
                   href="{{ url('/settlement-sw/create') }}"
                   onclick="window.location.href=this.href; return false;">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </a>
            </div>
        </div>
        <div class="box-body">
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="list_settlement">
            <thead>
                <tr>
                    <th class="notexport">@lang('messages.action')</th>
                    <th>@lang('settlementsw::lang.status')</th>
                    <th>@lang('settlementsw::lang.settlement_date')</th>
                    <th>@lang('settlementsw::lang.settlement_no')</th>
                    <th>@lang('settlementsw::lang.shift_number')</th>
                    <th>@lang('settlementsw::lang.pump_operator_name')</th>
                    <th>@lang('settlementsw::lang.pumps')</th>
                    <th>@lang('settlementsw::lang.location')</th>
                    <th>@lang('settlementsw::lang.shift')</th>
                    <th>@lang('settlementsw::lang.note')</th> 
                    <th>@lang('settlementsw::lang.total_amnt')</th>
                    <th>@lang('settlementsw::lang.added_user')</th>
                </tr>
            </thead>
        </table>
    </div>
        </div>
    </div>

    <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div id="settlement_print" class="container"></div>
</section>
<!-- /.content -->

@endsection
@section('javascript')
<script type="text/javascript">
    $(document).ready( function(){
    var columns = [
            { data: 'action', searchable: false, orderable: false },
            { data: 'status', name: 'status' },
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'settlement_no', name: 'settlement_no' },
            { data: 'shift_number', name: 'pump_operator_assignments.shift_number' },
            { data: 'pump_operator_name', name: 'pump_operators.name' },
            { data: 'pump_nos', name: 'pump_nos', searchable: false },
            { data: 'location_name', name: 'business_locations.name' },
            { data: 'shift', name: 'shift', searchable: false},
            { data: 'note', name: 'note' },
            { data: 'total_amount', name: 'total_amount' },
            { data: 'created_by',searchable: false, name: 'created_by' }
        ];
  
    list_settlement = $('#list_settlement').DataTable({
        processing: true,
        serverSide: true,
        aaSorting: [[0, 'desc']],
        ajax: {
            url: '{{ route('settlement-sw.index') }}',
            data: function(d) {
                d.location_id = $('select#location_id').val();
                d.pump_operator = $('select#pump_operator').val();
                d.settlement_no = $('select#settlement_no').val();
                var drp = $('input#expense_date_range').data('daterangepicker');
                if (drp) {
                    d.start_date = drp.startDate.format('YYYY-MM-DD');
                    d.end_date = drp.endDate.format('YYYY-MM-DD');
                }
            },
        },
        columnDefs: [ {
            "targets": 0,
            "orderable": false,
            "searchable": false
        } ],
        columns: columns,
        fnDrawCallback: function(oSettings) {
            $('#list_settlement tbody tr.footer-total').remove();
            total_amount = 0.00;
            $("#list_settlement tbody tr").each(function(){
                let number = $(this).find("td").eq(-2).text();
                if (number !== 'No data available in table') {
                    total_amount += parseFloat(number.replace(/,/g, ''));
                }
            });
            
            total_amount = total_amount === 0 ? '0.00' : parseFloat(total_amount).toLocaleString(undefined, {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2,
                          useGrouping: true
                        });
            $('#list_settlement tbody').append('<tr class="bg-gray font-17 footer-total text-center"><td colspan="10" class="text-right"><strong>Total</strong></td><td><strong>'+total_amount+'</strong></td><td></td></tr>');
        },
    });
    $('#location_id, #pump_operator, #pump_operator, #settlement_no, #type, #expense_date_range').change(function(){
        list_settlement.ajax.reload();
    });

    $(document).on('click', 'a.delete_settlement_button', function(e) {
		e.preventDefault();
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).attr('href');
                var data = $(this).serialize();
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                        list_settlement.ajax.reload();
                    },
                });
            }
        });
    });

    $(document).on('click', 'a.delete_reference_button', function(e) {
		var page_details = $(this).closest('div.page_details')
		e.preventDefault();
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).attr('href');
                var data = $(this).serialize();
                console.log(href);
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        if (result.success == true) {
                            page_details.remove();
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                        list_settlement.ajax.reload();
                    },
                });
            }
        });
    });
});

$(document).on('click', '.edit_contact_button', function(e) {
    e.preventDefault();
    $('div.pump_operator_modal').load($(this).attr('href'), function() {
        $(this).modal('show');
    });
});

$('#location_id').select2();


//save settlement
$(document).on('click', '.print_settlement_button', function () {
    var url = $(this).data('href');
    $.ajax({
        method: 'get',
        url: url,
        data: {},
        success: function(result) {
            $('#settlement_print').html(result);

            var divToPrint=document.getElementById('settlement_print');

            var newWin=window.open('','Print-Ledger');
        
            newWin.document.open();
        
            newWin.document.write('<html><body onload="window.print()">'+divToPrint.innerHTML+'</body></html>');
        
            newWin.document.close();
            
        },
    });
});

$('#settlement_print').css('visibility', 'hidden');
</script>
@endsection
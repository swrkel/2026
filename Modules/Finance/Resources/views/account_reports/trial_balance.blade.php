@extends('layouts.app')
@section('title', __( 'account.trial_balance' ))

@section('content')


<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang( 'account.trial_balance')</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">Account Reports</a></li>
                    <li><span>@lang( 'account.trial_balance')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="row no-print">
        <div class="col-sm-12">
            <div class="col-sm-3 col-xs-6 pull-left">
                <label for="business_location">@lang('account.business_locations'):</label>
                {!! Form::select('business_location', $business_locations, null, ['class' => 'form-control select2',
                'placeholder' =>__('lang_v1.all'), 'style' => 'width: 100%', 'id' => 'business_location']) !!}
            </div>
            <div class="col-sm-6 col-xs-6 text-center">
                <h3>{{request()->session()->get('business.name')}}</h3>
                <div class="clearfix"></div>
                <h5 style="margin:0px;" id="date_show"></h5>
            </div>
            <div class="col-sm-3 col-xs-6 pull-right">
                <label for="date_range">@lang('messages.filter_by_date'):</label>
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                    </span>
                    <input type="text" id="date_range" value="{{@format_date('now')}}" class="form-control" readonly>
                </div>
                
                <input type="text" id="srch" value="" class="form-control" placeholder="Search">
                
            </div>
        </div>
    </div>
    <br>
    <div class="box box-solid">
        <div class="box-header print_section">
            <h3 class="box-title">{{session()->get('business.name')}} - @lang( 'account.trial_balance') - <span
                    id="hidden_date">{{@format_date('now')}}</span></h3>
        </div>
        <div class="box-body">
            <table class="table table-striped table-bordered" id="trial_balance_table">
                <thead>
                    <tr class="bg-gray">
                        <th>@lang('account.account_number')</th>
                        <th>@lang('account.account_name')</th>
                        <th>@lang('account.debit')</th>
                        <th>@lang('account.credit')</th>
                    </tr>
                </thead>
                @if($account_access)
                <tbody>
                 
                </tbody>
                @endif
                <tbody id="account_balances_details">
                    @if(!$account_access)
                    <tr class="text-center"
                        style="color: {{App\System::getProperty('not_enalbed_module_user_color')}}; font-size: {{App\System::getProperty('not_enalbed_module_user_font_size')}}px;">
                        <td colspan="3"> {{App\System::getProperty('not_enalbed_module_user_message')}}</td>
                    </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr class="bg-gray">
                        <th colspan="2" class="text-right">@lang('sale.total')</th>
                        <td>
                            <span class="remote-data display_currency " data-currency_symbol="true" id="total_debit">
                                @if($account_access)
                                <i class="fa fa-refresh fa-spin fa-fw"></i>
                                @endif
                            </span>
                        </td>
                        <td>
                            <span class="remote-data display_currency" data-currency_symbol="true" id="total_credit">
                                @if($account_access)
                                <i class="fa fa-refresh fa-spin fa-fw"></i>
                                @endif
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</section>
<!-- /.content -->
@stop
@section('javascript')

<script type="text/javascript">
    var trial_balance_table = null;

    function financeTbPicker() {
        return $('#date_range').length === 1 ? $('#date_range').data('daterangepicker') : null;
    }

    function financeTbUpdateDateLabels(startDate, endDate) {
        var picker = financeTbPicker();

        if (!startDate && picker) {
            startDate = picker.startDate;
        }

        if (!endDate && picker) {
            endDate = picker.endDate;
        }

        if (!startDate || !endDate) {
            return;
        }

        var label = startDate.format(moment_date_format) + ' To ' + endDate.format(moment_date_format);
        $('#date_range').val(startDate.format(moment_date_format) + ' - ' + endDate.format(moment_date_format));
        $('#date_show').text(label);
        $('#hidden_date').text(label);
    }

    function financeTbReload() {
        financeTbUpdateDateLabels();
        if (trial_balance_table) {
            trial_balance_table.ajax.reload(null, false);
        }
    }

    $(document).ready(function () {
        if ($('#date_range').length === 1) {
            var tbDateSettings = $.extend(true, {}, dateRangeSettings, {
                autoUpdateInput: false,
                opens: 'left'
            });

            $('#date_range').daterangepicker(tbDateSettings, function (start, end) {
                financeTbUpdateDateLabels(start, end);
                financeTbReload();
            });

            var picker = financeTbPicker();
            if (picker) {
                financeTbUpdateDateLabels(picker.startDate, picker.endDate);
            }

            $('#date_range').off('apply.daterangepicker.finance_tb_rewrite').on('apply.daterangepicker.finance_tb_rewrite', function (ev, picker) {
                financeTbUpdateDateLabels(picker.startDate, picker.endDate);
                financeTbReload();
            });

            $('#date_range').off('cancel.daterangepicker.finance_tb_rewrite').on('cancel.daterangepicker.finance_tb_rewrite', function () {
                $(this).val('');
                $('#date_show').text('');
                $('#hidden_date').text('');
                financeTbReload();
            });
        }

        $('#business_location').off('change.finance_tb_rewrite').on('change.finance_tb_rewrite', function () {
            financeTbReload();
        });

        $('#srch').off('keyup.finance_tb_rewrite').on('keyup.finance_tb_rewrite', function () {
            financeTbReload();
        });
    });

    @if($account_access)
    $(document).ready(function () {
        trial_balance_table = $('#trial_balance_table').DataTable({
            processing: true,
            serverSide: false,
            searching: false,
            autoWidth: false,
            ajax: {
                url: "{{ route('finance.trial-balance.rewrite-data') }}",
                data: function (d) {
                    var picker = financeTbPicker();
                    d.start_date = picker ? picker.startDate.format('YYYY-MM-DD') : '';
                    d.end_date = picker ? picker.endDate.format('YYYY-MM-DD') : '';
                    d.location_id = $('select#business_location').val();
                    d.srch = $('#srch').val();
                },
                dataSrc: function (json) {
                    $('#total_debit').html('<span class="display_currency" data-currency_symbol="true">' + (json.total_debit || 0) + '</span>');
                    $('#total_credit').html('<span class="display_currency" data-currency_symbol="true">' + (json.total_credit || 0) + '</span>');
                    return json.data || [];
                }
            },
            @include('layouts.partials.datatable_export_button')
            columnDefs: [
                {
                    targets: [2, 3],
                    orderable: false,
                    searchable: false,
                    width: '20%'
                }
            ],
            columns: [
                {data: 'account_number', name: 'account_number'},
                {data: 'name', name: 'name'},
                {data: 'debit', name: 'debit', className: 'debit text-right'},
                {data: 'credit', name: 'credit', className: 'credit text-right'}
            ],
            fnDrawCallback: function () {
                __currency_convert_recursively($('#trial_balance_table'));
            }
        });
    });
    @endif
</script>

@endsection
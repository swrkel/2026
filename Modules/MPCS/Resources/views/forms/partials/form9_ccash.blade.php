<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            {!! Form::open(['url' => request()->url(), 'method' => 'get', 'id' => 'f9c_credit_filter']) !!}
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('form_16a_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('form_16a_date_range', $start_date . ' ~ ' . $end_date, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'form_16a_date_range', 'readonly']); !!}
                </div>
            </div>
            <div class="col-sm-2" style="margin-top: 25px">
                <button type="submit" class="btn btn-primary pull-right">@lang('report.apply_filters')</button>
            </div>
            {!! Form::close() !!}
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12" id="title">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('mpcs::lang.f9c_credit_details')])
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="f9c_credit_sales_table">
                    <thead>
                        <tr>
                            <th>@lang('mpcs::lang.settlement_date')</th>
                            <th>@lang('mpcs::lang.bill_no')</th>
                            <th>@lang('mpcs::lang.our_ref')</th>
                            <th>@lang('mpcs::lang.customer')</th>
                            <th>@lang('mpcs::lang.product')</th>
                            <th>@lang('mpcs::lang.quantity')</th>
                            <th>@lang('mpcs::lang.amount')</th>
                            <th>@lang('mpcs::lang.location')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($f9c_credit_data as $sale)
                            <tr>
                                <td>{{ $sale->settlement_date }}</td>
                                <td>{{ $sale->invoice_no }}</td>
                                <td>{{ $sale->our_ref }}</td>
                                <td>{{ $sale->customer }}</td>
                                <td>{{ $sale->description }}</td>
                                <td class="text-right">{{ $sale->balance_qty }}</td>
                                <td class="text-right">{{ $sale->final_total }}</td>
                                <td>{{ $sale->location }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">@lang('lang_v1.no_data')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

<script type="text/javascript">
$(document).ready(function() {
    $('#form_16a_date_range').daterangepicker({
        ranges: ranges,
        autoUpdateInput: false,
        locale: {
            format: moment_date_format,
            cancelLabel: LANG.clear,
            applyLabel: LANG.apply,
            customRangeLabel: LANG.custom_range,
        },
    });
    
    $('#form_16a_date_range').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(
            picker.startDate.format(moment_date_format) +
                ' - ' +
                picker.endDate.format(moment_date_format)
        );
    });

    $('#form_16a_date_range').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });
});
</script>
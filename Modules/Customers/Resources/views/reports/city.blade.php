@extends('layouts.app')
@section('title', __('customers::lang.customer_city_report'))

@section('content')
<section class="content-header customers-module-header">
    <h1>@lang('customers::lang.customer_city_report')</h1>
    <small>@lang('customers::lang.customer_city_report_subtitle')</small>
</section>

<section class="content customers-city-report-page">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('customers::lang.city_wise_customers')</h3>
            <div class="box-tools pull-right">
                <a href="{{ route('customers.reports.summary') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> @lang('customers::lang.summary_report')</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped" id="customers_city_report_table">
                <thead>
                    <tr>
                        <th>@lang('customers::lang.city')</th>
                        <th class="text-right">@lang('customers::lang.total_customers')</th>
                        <th class="text-right">@lang('customers::lang.total_credit_limit')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($citySummary as $row)
                        <tr>
                            <td>{{ $row->city }}</td>
                            <td class="text-right">{{ number_format($row->total) }}</td>
                            <td class="text-right">{{ number_format((float) $row->credit_limit_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">@lang('customers::lang.no_records_found')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function () {
    if ($.fn.DataTable) {
        $('#customers_city_report_table').DataTable({
            dom: 'Bfrtip',
            buttons: ['csv', 'excel', 'pdf', 'print', 'colvis'],
            order: [[1, 'desc']]
        });
    }
});
</script>
@endsection

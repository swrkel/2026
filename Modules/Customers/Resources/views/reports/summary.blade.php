@extends('layouts.app')
@section('title', __('customers::lang.customer_summary_report'))

@section('content')
<section class="content-header">
    <h1>@lang('customers::lang.customer_summary_report')</h1>
    <small>@lang('customers::lang.customer_summary_subtitle')</small>
</section>

<section class="content customers-report-page">
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-aqua">
                <div class="inner"><h3>{{ number_format($totalCustomers) }}</h3><p>@lang('customers::lang.total_customers')</p></div>
                <div class="icon"><i class="fa fa-users"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-green">
                <div class="inner"><h3>{{ number_format($customersWithMobile) }}</h3><p>@lang('customers::lang.with_mobile')</p></div>
                <div class="icon"><i class="fa fa-phone"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-yellow">
                <div class="inner"><h3>{{ number_format($customersWithEmail) }}</h3><p>@lang('customers::lang.with_email')</p></div>
                <div class="icon"><i class="fa fa-envelope"></i></div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="small-box bg-purple">
                <div class="inner"><h3>{{ number_format($totalCreditLimit, 2) }}</h3><p>@lang('customers::lang.total_credit_limit')</p></div>
                <div class="icon"><i class="fa fa-credit-card"></i></div>
            </div>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('customers::lang.top_cities')</h3>
            <div class="box-tools pull-right">
                <a href="{{ route('customers.index') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> @lang('messages.back')
                </a>
            </div>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>@lang('customers::lang.city')</th>
                            <th class="text-right">@lang('customers::lang.total')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($citySummary as $row)
                            <tr>
                                <td>{{ $row->city }}</td>
                                <td class="text-right">{{ number_format($row->total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">@lang('customers::lang.no_records_found')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

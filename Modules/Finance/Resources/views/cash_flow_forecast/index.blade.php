@extends('layouts.app')

@section('title', 'Cash Flow Forecasts')

@section('content')

<section class="content-header">
    <h1>
        Cash Flow Forecasts
    </h1>
</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">
            <h3 class="box-title">
                Forecast Register
            </h3>

            <div class="box-tools pull-right">
                <a href="{{ route('finance.cash_flow_forecast.create') }}"
                   class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i>
                    Add Forecast
                </a>
            </div>
        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>
                    <tr>
                        <th>No</th>
                        <th>Type</th>
                        <th>Module</th>
                        <th>Category</th>
                        <th>Subject</th>
                        <th>Expected Date</th>
                        <th class="text-right">Expected Amount</th>
                        <th>Probability</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($forecasts as $forecast)
                        <tr>
                            <td>{{ $forecast->forecast_no }}</td>
                            <td>{{ ucfirst($forecast->forecast_type) }}</td>
                            <td>{{ $forecast->module }}</td>
                            <td>{{ $forecast->category }}</td>
                            <td>{{ $forecast->subject }}</td>
                            <td>{{ $forecast->expected_date }}</td>
                            <td class="text-right">
                                {{ number_format($forecast->expected_amount, 2) }}
                            </td>
                            <td>{{ $forecast->probability_percent }}%</td>
                            <td>
                                <span class="label label-info">
                                    {{ ucfirst($forecast->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>

            <div class="text-center">
                {{ $forecasts->links() }}
            </div>

        </div>

    </div>

</section>

@endsection
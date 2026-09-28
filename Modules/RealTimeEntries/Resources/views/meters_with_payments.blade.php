@extends('layouts.app')

@section('title', __('realtimeentries::lang.meters_with_payments'))

@section('content')
    <section class="content">
        <h3>@lang('realtimeentries::lang.meters_with_payments')</h3>

        {{-- 🔹 Filters --}}
        <form method="GET" action="{{ route('realtime.meters-with-payments') }}" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    {!! Form::label('date_range', __('realtimeentries::lang.date_range')) !!}
                    {!! Form::text('date_range', request('date_range'), [
                        'class' => 'form-control date_range_picker',
                        'id' => 'date_range',
                        'readonly',
                    ]) !!}
                </div>
                <div class="col-md-3">
                    {!! Form::label('pump_operator_id', __('realtimeentries::lang.pump_operator')) !!}
                    {!! Form::select('pump_operator_id', $pump_operators, request('pump_operator_id'), [
                        'class' => 'form-control select2',
                        'placeholder' => __('realtimeentries::lang.all'),
                    ]) !!}
                </div>
                <div class="col-md-3">
                    {!! Form::label('shift_number', __('realtimeentries::lang.shift_number')) !!}
                    {!! Form::select('shift_number', $shifts, request('shift_number'), [
                        'class' => 'form-control select2',
                        'placeholder' => __('realtimeentries::lang.all'),
                    ]) !!}
                </div>
                <div class="col-md-3">
                    {!! Form::label('payment_method', __('realtimeentries::lang.payment_method')) !!}
                    {!! Form::select('payment_method', $payment_methods, request('payment_method'), [
                        'class' => 'form-control select2',
                        'placeholder' => __('realtimeentries::lang.all'),
                    ]) !!}
                </div>
            </div>
            <div class="mt-2">
                <button type="submit" class="btn btn-primary">@lang('realtimeentries::lang.filter')</button>
                <a href="{{ route('realtime.meters-with-payments') }}" class="btn btn-secondary">@lang('realtimeentries::lang.reset')</a>
            </div>
        </form>

        {{-- 🔹 Table --}}
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>@lang('realtimeentries::lang.date')</th>
                        <th>@lang('realtimeentries::lang.time')</th>
                        <th>@lang('realtimeentries::lang.pump_operator')</th>
                        <th>@lang('realtimeentries::lang.shift_number')</th>
                        <th>@lang('realtimeentries::lang.pump_number')</th>
                        <th>@lang('realtimeentries::lang.current_meter')</th>
                        <th>@lang('realtimeentries::lang.payment_method')</th>
                        <th>@lang('realtimeentries::lang.payment_amount')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $row)
                        <tr>
                            <td>{{ $row->date }}</td>
                            <td>{{ $row->time }}</td>
                            <td>{{ $row->pump_operator }}</td>
                            <td>{{ $row->shift_number }}</td>
                            <td>{{ $row->pump_number }}</td>
                            <td>{{ $row->current_meter }}</td>
                            <td>{{ $row->payment_method }}</td>
                            <td>{{ number_format($row->payment_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">@lang('realtimeentries::lang.no_records_found')</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
@section('javascript')
    <script>
        $(document).ready(function() {})

        if ($('#date_range').length == 1) {
            $('#date_range').daterangepicker(dateRangeSettings, function(start, end) {
                $('#date_range').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
            });
            $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#product_sr_date_filter').val('');
            });
            $('#date_range')
                .data('daterangepicker')
                .setStartDate(moment().startOf('year'));
            $('#date_range')
                .data('daterangepicker')
                .setEndDate(moment().endOf('year'));
        }
    </script>
@endsection

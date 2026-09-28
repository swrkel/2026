@extends('layouts.app')
@section('title', __('stocktransfernew::lang.transfer_schedules'))
@section('content')
<section class="content-header"><h1>@lang('stocktransfernew::lang.transfer_schedules')</h1></section>
<section class="content stn-page">
    <div class="stn-toolbar">
        <a href="{{ route('stock-transfer-new.schedules.create') }}" class="btn btn-primary">@lang('stocktransfernew::lang.add_schedule')</a>
        <a href="{{ route('stock-transfer-new.schedules.logs') }}" class="btn btn-default">@lang('stocktransfernew::lang.execution_logs')</a>
    </div>
    <div class="box box-solid"><div class="box-body table-responsive">
        <table class="table table-bordered table-striped stn-table">
            <thead><tr><th>@lang('stocktransfernew::lang.name')</th><th>@lang('stocktransfernew::lang.type')</th><th>@lang('stocktransfernew::lang.next_run')</th><th>@lang('stocktransfernew::lang.status')</th><th>@lang('stocktransfernew::lang.action')</th></tr></thead>
            <tbody>@foreach($schedules as $schedule)<tr>
                <td>{{ $schedule->schedule_name }}</td><td>{{ ucfirst(str_replace('_',' ', $schedule->schedule_type)) }}</td><td>{{ $schedule->next_run_date }} {{ $schedule->run_time }}</td><td><span class="label label-info">{{ $schedule->status }}</span></td>
                <td>
                    @if($schedule->status === 'active')<form method="post" action="{{ route('stock-transfer-new.schedules.pause',$schedule) }}">@csrf<button class="btn btn-xs btn-warning">@lang('stocktransfernew::lang.pause')</button></form>@endif
                    @if($schedule->status === 'paused')<form method="post" action="{{ route('stock-transfer-new.schedules.resume',$schedule) }}">@csrf<button class="btn btn-xs btn-success">@lang('stocktransfernew::lang.resume')</button></form>@endif
                </td>
            </tr>@endforeach</tbody>
        </table>
        {{ $schedules->links() }}
    </div></div>
</section>
@endsection

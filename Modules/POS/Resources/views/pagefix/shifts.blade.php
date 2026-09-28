@extends('pos::layouts.app', ['title' => $title ?? 'POS Shifts'])

@section('pos_page_description', 'Open, monitor and close POS register shifts with cash movement and variance details.')
@section('pos_page_actions')
    <a href="{{ url('/pos-module') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
    <a href="{{ url('/pos-module/shifts/open') }}" class="btn btn-primary btn-sm"><i class="fa fa-play"></i> Open Shift</a>
    <a href="{{ url('/pos-module/shifts/current') }}" class="btn btn-success btn-sm"><i class="fa fa-clock-o"></i> Current Shift</a>
@endsection

@section('pos_content')
@if(!empty($warnings))
    <div class="alert alert-warning"><i class="fa fa-warning"></i> {{ implode(' ', array_unique($warnings)) }} The page is still available.</div>
@endif

@php
    $cp = (int) session('business.currency_precision', 2);
    $cards = [
        ['label' => 'Open Shifts', 'value' => number_format((float) data_get($dashboard, 'open_shifts', 0)), 'icon' => 'fa-play', 'hint' => 'Currently active', 'tone' => '', 'url' => url('/pos-module/shifts/current')],
        ['label' => 'Closed Today', 'value' => number_format((float) data_get($dashboard, 'closed_today', 0)), 'icon' => 'fa-check', 'hint' => 'Completed shifts', 'tone' => 'success'],
        ['label' => 'Cash In Today', 'value' => number_format((float) data_get($dashboard, 'cash_in_today', 0), $cp), 'icon' => 'fa-arrow-down', 'hint' => 'Cash drawer entries', 'tone' => 'warning'],
        ['label' => 'Cash Out Today', 'value' => number_format((float) data_get($dashboard, 'cash_out_today', 0), $cp), 'icon' => 'fa-arrow-up', 'hint' => 'Cash drawer exits', 'tone' => 'purple'],
    ];
@endphp
<div class="ch-kpi-grid ch-standard-grid">
    @foreach($cards as $card)
        @include('pos::pagefix.partials.kpi-card', ['card' => $card])
    @endforeach
</div>

<div class="ch-card">
    <div class="ch-card-header">
        <div><h3 class="ch-card-title"><i class="fa fa-clock-o text-primary"></i> POS Register Shifts</h3><div class="ch-card-subtitle">Search shifts and review opening, closing and variance values.</div></div>
        <div class="ch-quick-actions">
            <a class="btn btn-success btn-sm" href="{{ url('/pos-module/shifts/open') }}"><i class="fa fa-play"></i> Open Shift</a>
            <a class="btn btn-info btn-sm" href="{{ url('/pos-module/shifts/current') }}"><i class="fa fa-clock-o"></i> Current Shift</a>
        </div>
    </div>
    <div class="ch-card-body">
        <div class="ch-toolbar">
            <div style="position:relative;min-width:280px;max-width:460px;flex:1;">
                <input type="text" class="form-control" id="pos-shift-filter" placeholder="Search shift number, register or status" style="padding-right:38px;">
                <i class="fa fa-search" style="position:absolute;right:14px;top:13px;color:#64748b;"></i>
            </div>
            <div>
                <button type="button" class="btn btn-default" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered pos-standard-table" id="pos-shift-table">
                <thead><tr><th>Action</th><th>Shift No</th><th>Register</th><th>Opened At</th><th>Closed At</th><th class="text-right">Opening Amount</th><th class="text-right">Actual Closing</th><th class="text-right">Variance</th><th>Approval</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($shifts as $shift)
                    @php($shiftId = data_get($shift, 'id'))
                    <tr>
                        <td>
                            @if($shiftId)
                                <a class="btn btn-xs btn-default" href="{{ url('/pos-module/shifts/' . $shiftId . '/summary') }}"><i class="fa fa-list-alt"></i> Summary</a>
                                @if(data_get($shift, 'status') === 'open')
                                    <a class="btn btn-xs btn-danger" href="{{ url('/pos-module/shifts/' . $shiftId . '/close') }}"><i class="fa fa-stop"></i> Close</a>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ data_get($shift, 'session_no', data_get($shift, 'id', '-')) }}</td>
                        <td>{{ data_get($shift, 'register_name', '-') }}</td>
                        <td>{{ data_get($shift, 'opened_at', '-') }}</td>
                        <td>{{ data_get($shift, 'closed_at', '-') }}</td>
                        <td class="text-right">{{ number_format((float) data_get($shift, 'opening_amount', 0), $cp) }}</td>
                        <td class="text-right">{{ number_format((float) data_get($shift, 'actual_closing_amount', 0), $cp) }}</td>
                        <td class="text-right {{ (float) data_get($shift, 'variance_amount', 0) != 0 ? 'text-red' : '' }}">{{ number_format((float) data_get($shift, 'variance_amount', 0), $cp) }}</td>
                        <td><span class="label label-{{ data_get($shift, 'approval_status', 'approved') === 'pending' ? 'warning' : 'success' }}">{{ ucfirst((string) data_get($shift, 'approval_status', 'approved')) }}</span></td>
                        <td><span class="label label-{{ data_get($shift, 'status') === 'open' ? 'success' : 'default' }}">{{ ucfirst((string) data_get($shift, 'status', '-')) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="10"><div class="empty-state"><i class="fa fa-clock-o fa-2x"></i><h4>No POS shifts found</h4><p>Use Open Shift to begin register operations.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if(is_object($shifts) && method_exists($shifts, 'links'))
            <div style="margin-top:16px;">{{ $shifts->links() }}</div>
        @endif
    </div>
</div>
@endsection

@section('pos_scripts')
<script>
(function () {
    var input = document.getElementById('pos-shift-filter');
    var table = document.getElementById('pos-shift-table');
    if (!input || !table) return;
    input.addEventListener('input', function () {
        var needle = (input.value || '').toLowerCase();
        Array.prototype.forEach.call(table.querySelectorAll('tbody tr'), function (row) {
            row.style.display = row.textContent.toLowerCase().indexOf(needle) !== -1 ? '' : 'none';
        });
    });
})();
</script>
@endsection

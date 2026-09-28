@extends('pumperdashboardnew::layouts.operator')
@section('title', 'Pump Operations')
@section('pone_content')
@php
    $mode = strtolower((string) request('mode', 'all'));
    if (!in_array($mode, ['all', 'receive', 'current', 'close'], true)) $mode = 'all';

    $modeLabels = [
        'all' => ['title' => 'Pump Operations', 'description' => 'Receive, confirm, monitor and close assigned pumps.'],
        'receive' => ['title' => 'Receive Pump', 'description' => 'Select an assigned pump, receive it and complete the second confirmation.'],
        'current' => ['title' => 'Enter Current Meter', 'description' => 'Select a received and confirmed pump to enter its current meter.'],
        'close' => ['title' => 'Close Pump', 'description' => 'Select a received and confirmed pump to enter the final meter and close it.'],
    ];

    $visibleAssignments = $assignments->filter(static function ($assignment) use ($mode): bool {
        if ($mode === 'receive') {
            return $assignment->status === 'assigned'
                || ($assignment->status === 'open' && empty($assignment->confirmed_at));
        }
        if (in_array($mode, ['current', 'close'], true)) {
            return $assignment->status === 'open' && !empty($assignment->confirmed_at);
        }
        return true;
    });
@endphp

<div class="pone-page-head">
    <div>
        <div class="pone-page-kicker">Pump Operator Display 2</div>
        <h1>{{ $modeLabels[$mode]['title'] }}</h1>
        <p>Shift {{ $shift->shift_number }} · {{ $modeLabels[$mode]['description'] }}</p>
    </div>
    <div class="pone-actions">
        <a class="pone-btn pone-btn-light" href="{{ route('pumper-dashboard-new.operator.pumps.closed-statement') }}?paper_size=A4">Closed Pumps Statement</a>
        <a class="pone-btn pone-btn-light" href="{{ route('pumper-dashboard-new.operator.dashboard') }}">Dashboard</a>
    </div>
</div>

<div class="pone-grid pone-grid-4" style="margin-bottom:16px">
    <div class="pone-stat"><div class="pone-stat-label">Assigned Pumps</div><div class="pone-stat-value">{{ $assignments->count() }}</div></div>
    <div class="pone-stat"><div class="pone-stat-label">Waiting to Receive</div><div class="pone-stat-value">{{ $assignments->where('status','assigned')->count() }}</div></div>
    <div class="pone-stat"><div class="pone-stat-label">Open Pumps</div><div class="pone-stat-value">{{ $assignments->where('status','open')->count() }}</div></div>
    <div class="pone-stat"><div class="pone-stat-label">Closed Pumps</div><div class="pone-stat-value">{{ $assignments->where('status','closed')->count() }}</div></div>
</div>

<div class="pone-panel">
    <div class="pone-panel-body pone-panel-body-compact">
        <div class="pone-table-wrap" style="border:0;border-radius:0">
            <table class="pone-table">
                <thead>
                    <tr>
                        <th>Pump</th><th>Product</th><th class="pone-text-right">Opening</th><th class="pone-text-right">Current</th>
                        <th class="pone-text-right">Testing</th><th class="pone-text-right">Sold Qty</th><th class="pone-text-right">Unit Price</th>
                        <th class="pone-text-right">Amount</th><th>Status</th><th>Confirmation</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($visibleAssignments as $a)
                    @php($pump = $pumpMasters->get($a->pump_id))
                    <tr>
                        <td><strong>{{ $pump->pump_name ?? $pump->pump_no ?? ('Pump '.$a->pump_id) }}</strong><div class="pone-muted">#{{ $a->pump_id }}</div></td>
                        <td>{{ $pump->product_name ?? '-' }}</td>
                        <td class="pone-text-right pone-number">{{ number_format((float)$a->opening_meter,3) }}</td>
                        <td class="pone-text-right pone-number">{{ number_format((float)$a->current_meter,3) }}</td>
                        <td class="pone-text-right pone-number">{{ number_format((float)$a->testing_quantity,3) }}</td>
                        <td class="pone-text-right pone-number">{{ number_format((float)$a->sold_quantity,3) }}</td>
                        <td class="pone-text-right pone-number">{{ number_format((float)$a->unit_price,4) }}</td>
                        <td class="pone-text-right pone-number"><strong>{{ number_format((float)$a->amount,4) }}</strong></td>
                        <td><span class="pone-badge pone-badge-{{ $a->status }}">{{ $a->status }}</span></td>
                        <td>
                            <div class="pone-muted">Received: {{ $a->accepted_at ? $a->accepted_at->format('H:i') : 'Pending' }}</div>
                            <div class="pone-muted">Confirmed: {{ $a->confirmed_at ? $a->confirmed_at->format('H:i') : 'Pending' }}</div>
                        </td>
                        <td>
                            <div class="pone-actions">
                                <a class="pone-btn pone-btn-light pone-btn-sm" href="{{ route('pumper-dashboard-new.operator.pumps.history',$a) }}">History</a>

                                @if($mode === 'all' || $mode === 'receive')
                                    @if($a->status === 'assigned')
                                        <form method="post" action="{{ route('pumper-dashboard-new.operator.pumps.accept',$a) }}" data-confirm-message="Receive this pump assignment?">
                                            @csrf<input type="hidden" name="note"><button class="pone-btn pone-btn-primary pone-btn-sm">Receive Pump</button>
                                        </form>
                                    @elseif($a->status === 'open' && !$a->confirmed_at)
                                        <form method="post" action="{{ route('pumper-dashboard-new.operator.pumps.confirm',$a) }}" data-confirm-message="Second confirmation: verify this is the correct pump before continuing.">
                                            @csrf<input type="hidden" name="note"><button class="pone-btn pone-btn-warning pone-btn-sm">Confirm Pump</button>
                                        </form>
                                    @endif
                                @endif

                                @if(($mode === 'all' || $mode === 'current') && $a->status === 'open' && $a->confirmed_at)
                                    <a class="pone-btn pone-btn-cyan pone-btn-sm" href="{{ route('pumper-dashboard-new.operator.pumps.current',$a) }}">Current Meter</a>
                                @endif

                                @if(($mode === 'all' || $mode === 'close') && $a->status === 'open' && $a->confirmed_at)
                                    <a class="pone-btn pone-btn-danger pone-btn-sm" href="{{ route('pumper-dashboard-new.operator.pumps.close.form',$a) }}">Close Pump</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="pone-empty">No pumps are available for this operation.</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="pone-text-right">Shift Totals</th>
                        <th class="pone-text-right">{{ number_format((float)$assignments->sum('sold_quantity'),3) }}</th>
                        <th></th>
                        <th class="pone-text-right">{{ number_format((float)$assignments->sum('amount'),4) }}</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

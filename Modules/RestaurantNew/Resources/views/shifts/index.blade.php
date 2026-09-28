@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::lang.cashier_shifts'))
@section('content')
<div class="rn-page rn-shift-page">
    <div class="rn-header-card"><h3>{{ __('restaurantnew::lang.cashier_shifts') }}</h3><p>Opening cash, cash in/out, closing cash and shortage/excess.</p></div>
    <div class="rn-card">
        <form method="POST" action="{{ route('restaurantnew.shifts.open') }}" class="rn-grid-form">
            @csrf
            <input type="hidden" name="business_id" value="{{ session('business.id') }}">
            <div><label>Opening Cash</label><input name="opening_cash" type="number" step="0.0001" class="form-control" required></div>
            <div><label>Opening Note</label><input name="opening_note" class="form-control"></div>
            <div><button class="btn btn-success rn-btn">Open Shift</button></div>
        </form>
    </div>
    <div class="rn-card">
        <table class="table table-bordered table-striped rn-datatable">
            <thead><tr><th>Shift No</th><th>Opened</th><th>Status</th><th>Opening</th><th>Cash Sales</th><th>Expected</th><th>Counted</th><th>Short/Excess</th></tr></thead>
            <tbody>
            @foreach($shifts as $shift)
                <tr><td>{{ $shift->shift_no }}</td><td>{{ optional($shift->opened_at)->format('Y-m-d H:i') }}</td><td>{{ ucfirst($shift->status) }}</td><td>{{ number_format($shift->opening_cash, 4) }}</td><td>{{ number_format($shift->cash_sales, 4) }}</td><td>{{ number_format($shift->expected_cash, 4) }}</td><td>{{ number_format($shift->counted_cash ?? 0, 4) }}</td><td>{{ number_format($shift->shortage_excess, 4) }}</td></tr>
            @endforeach
            </tbody>
        </table>
        {{ $shifts->links() }}
    </div>
</div>
@endsection

@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::crm.customer_360'))
@section('content')
<div class="rn-page rn-customer-360">
    <div class="rn-toolbar"><h3>{{ $profile->customer_name }} - 360°</h3></div>
    <div class="rn-kpi-grid"><div class="rn-kpi-card"><span>Visits</span><strong>{{ $profile->visit_count }}</strong></div><div class="rn-kpi-card"><span>Lifetime Spend</span><strong>{{ number_format($profile->lifetime_spend, 2) }}</strong></div><div class="rn-kpi-card"><span>Points</span><strong>{{ optional($loyalty)->available_points ?? 0 }}</strong></div></div>
    <div class="rn-card"><h4>Recent Visits</h4><table class="table table-sm"><thead><tr><th>Date</th><th>Type</th><th>Table</th><th>Guests</th><th>Amount</th></tr></thead><tbody>@foreach($visits as $visit)<tr><td>{{ $visit->visited_at }}</td><td>{{ $visit->visit_type }}</td><td>{{ $visit->table_no }}</td><td>{{ $visit->guest_count }}</td><td>{{ number_format($visit->net_total, 2) }}</td></tr>@endforeach</tbody></table></div>
</div>
@endsection

@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::crm.customers'))
@section('content')
<div class="rn-page rn-crm-customers">
    <div class="rn-toolbar"><h3>{{ __('restaurantnew::crm.customers') }}</h3><input class="form-control" placeholder="Search customers"></div>
    <div class="rn-card"><table class="table table-bordered table-striped rn-table"><thead><tr><th>Code</th><th>Name</th><th>Mobile</th><th>Visits</th><th>Spend</th><th>VIP</th><th>Action</th></tr></thead><tbody>
        @foreach($customers as $customer)
        <tr><td>{{ $customer->customer_code }}</td><td>{{ $customer->customer_name }}</td><td>{{ $customer->mobile }}</td><td>{{ $customer->visit_count }}</td><td>{{ number_format($customer->lifetime_spend, 2) }}</td><td>{{ $customer->vip_level }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('restaurantnew.crm.customers.show', $customer->id) }}">360°</a></td></tr>
        @endforeach
    </tbody></table>{{ $customers->links() }}</div>
</div>
@endsection

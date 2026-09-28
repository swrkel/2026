@extends('customers::layouts.action', ['title' => 'Customer Info'])
@section('customer_action_body')
<table class="table table-bordered">
    <tr><th>Customer Code</th><td>{{ $customer->contact_id }}</td></tr>
    <tr><th>Name</th><td>{{ $customer->name }}</td></tr>
    <tr><th>Mobile</th><td>{{ $customer->mobile }}</td></tr>
    <tr><th>Email</th><td>{{ $customer->email }}</td></tr>
    <tr><th>Credit Limit</th><td>{{ number_format((float) $customer->credit_limit, 2) }}</td></tr>
</table>
@endsection

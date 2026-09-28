@extends('pos::layouts.app')
@section('pos_content')
<div class="row">
  <div class="col-md-3"><div class="card"><div class="card-body"><strong>Total Customers</strong><h2>{{ $stats['total'] }}</h2></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><strong>Credit Customers</strong><h2>{{ $stats['credit'] }}</h2></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><strong>Active Customers</strong><h2>{{ $stats['active'] }}</h2></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><strong>Total Due</strong><h2>{{ number_format($stats['balance'], 2) }}</h2></div></div></div>
</div>
<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;gap:10px;align-items:center;">
    <strong>Customers</strong>
    <a class="btn btn-primary" href="{{ route('pos.customers.create') }}">+ Add Customer</a>
  </div>
  <div class="card-body">
    <form method="get" class="row" style="margin-bottom:14px;">
      <div class="col-md-6"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search code, name, mobile, NIC, email"></div>
      <div class="col-md-3"><select class="form-control" name="customer_type"><option value="">All Types</option><option value="walk_in" @selected(request('customer_type')==='walk_in')>Walk-in</option><option value="credit" @selected(request('customer_type')==='credit')>Credit</option><option value="loyalty" @selected(request('customer_type')==='loyalty')>Loyalty</option></select></div>
      <div class="col-md-3"><button class="btn btn-primary">Search</button> <a class="btn btn-default" href="{{ route('pos.customers.index') }}">Reset</a></div>
    </form>
    <div style="overflow:auto;">
      <table class="table">
        <thead><tr><th>Code</th><th>Name</th><th>Mobile</th><th>Type</th><th class="text-right">Credit Limit</th><th class="text-right">Balance</th><th>Status</th><th class="text-center">Action</th></tr></thead>
        <tbody>
        @forelse($customers as $c)
          <tr>
            <td>{{ $c->customer_code }}</td><td>{{ $c->name }}</td><td>{{ $c->mobile }}</td><td>{{ ucfirst(str_replace('_',' ', $c->customer_type)) }}</td>
            <td class="text-right">{{ number_format($c->credit_limit,2) }}</td><td class="text-right">{{ number_format($c->balance_amount,2) }}</td><td>{{ ucfirst($c->status) }}</td>
            <td class="text-center"><a class="btn btn-default" href="{{ route('pos.customers.show',$c->id) }}">Ledger</a> <a class="btn btn-primary" href="{{ route('pos.customers.edit',$c->id) }}">Edit</a></td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center">No customers found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div>{{ method_exists($customers,'links') ? $customers->links() : '' }}</div>
  </div>
</div>
@endsection

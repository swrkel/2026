@extends('pos::layouts.app')
@section('pos_content')
<div class="box box-solid ch-card">
    <div class="box-header with-border ch-card-header">
        <h3 class="box-title"><i class="fa fa-exchange"></i> Product Exchanges</h3>
        <div class="box-tools pull-right"><a class="btn btn-success btn-sm" href="{{ route('pos.exchanges.create') }}"><i class="fa fa-plus"></i> New Exchange</a><a class="btn btn-default btn-sm" href="{{ route('pos.returns.index') }}"><i class="fa fa-undo"></i> Returns</a></div>
    </div>
    <div class="box-body">
        <form method="get" class="row ch-filter-row"><div class="col-md-6"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search exchange / sale / invoice"></div><div class="col-md-4"><button class="btn btn-primary"><i class="fa fa-search"></i> Search</button> <a class="btn btn-default" href="{{ route('pos.exchanges.index') }}">Reset</a></div></form>
        <div class="table-responsive"><table class="table table-hover ch-table"><thead><tr><th>Exchange No</th><th>Sale No</th><th>Date</th><th>Customer</th><th class="text-right">Return Total</th><th class="text-right">New Total</th><th class="text-right">Difference</th><th>Status</th></tr></thead><tbody>
        @forelse($exchanges as $exchange)
            <tr><td><strong>{{ $exchange->exchange_no }}</strong></td><td>{{ $exchange->sale_no ?? $exchange->invoice_no }}</td><td>{{ $exchange->exchange_date }}</td><td>{{ $exchange->customer_name ?: 'Walk-in' }}</td><td class="text-right">{{ number_format((float)$exchange->return_total, 4) }}</td><td class="text-right">{{ number_format((float)$exchange->new_total, 4) }}</td><td class="text-right">{{ number_format((float)$exchange->difference_amount, 4) }}</td><td><span class="label label-success">{{ ucfirst($exchange->status ?? 'final') }}</span></td></tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted">No exchanges found.</td></tr>
        @endforelse
        </tbody></table></div>
        @if(method_exists($exchanges,'links')) {{ $exchanges->links() }} @endif
    </div>
</div>
@endsection

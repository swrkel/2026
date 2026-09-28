@extends('beautysaloons::layout')
@section('beauty_content')
<div class="bs-page">
    <h3>Prepaid Package Utilization Report</h3>
    <div class="table-responsive bs-table-scroll"><table class="table table-bordered"><thead><tr><th>Sale Date</th><th>Customer</th><th>Package ID</th><th>Sale Amount</th><th>Remaining</th><th>Status</th></tr></thead><tbody>
        @foreach($sales as $sale)<tr><td>{{ $sale->sale_date }}</td><td>{{ $sale->customer_name }}</td><td>{{ $sale->prepaid_package_id }}</td><td class="text-right">{{ number_format($sale->sale_amount, 2) }}</td><td class="text-right">{{ number_format($sale->remaining_value, 2) }}</td><td>{{ $sale->status }}</td></tr>@endforeach
    </tbody></table></div>
    {{ $sales->links() }}
</div>
@endsection

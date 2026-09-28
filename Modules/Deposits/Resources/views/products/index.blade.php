@extends('layouts.app')
@section('title', 'Deposit Products')
@section('content')
<section class="content-header no-print"><h1>Deposit Products</h1></section>
<section class="content no-print">
@include('deposits::layouts.nav')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Deposit Products</h3><div class="box-tools"><a href="{{ route('deposits.products.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add</a></div></div>
<div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Name</th><th>Code</th><th>Type</th><th>Rate</th><th>Term</th><th>Min</th><th>Max</th><th>Renewal</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($products as $product)<tr><td>{{ $product->name }}</td><td>{{ $product->code }}</td><td>{{ ucwords(str_replace('_',' ', $product->type)) }}</td><td>{{ number_format($product->interest_rate, 4) }}%</td><td>{{ $product->term_months }}</td><td>{{ number_format($product->minimum_amount,2) }}</td><td>{{ $product->maximum_amount ? number_format($product->maximum_amount,2) : '-' }}</td><td>{{ ucfirst($product->renewal_policy) }}</td><td>{{ ucfirst($product->status) }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('deposits.products.edit', $product->id) }}">Edit</a><form action="{{ route('deposits.products.delete', $product->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-danger" onclick="return confirm('Delete?')">Delete</button></form></td></tr>@empty<tr><td colspan="10" class="text-center">No records found</td></tr>@endforelse
</tbody></table>@if(method_exists($products, 'links')) {{ $products->links() }} @endif</div></div>
</section>
@endsection

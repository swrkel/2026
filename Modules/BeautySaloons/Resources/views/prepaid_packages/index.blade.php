@extends('beautysaloons::layout')
@section('beauty_content')
<div class="bs-page">
    <div class="bs-page-header"><h3>Prepaid Packages</h3><a href="{{ route('beauty-saloons.prepaid-packages.create') }}" class="btn btn-primary">Add Package</a></div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="table-responsive bs-table-scroll"><table class="table table-bordered table-striped">
        <thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Price</th><th>Validity Days</th><th>Status</th></tr></thead><tbody>
        @forelse($packages as $package)<tr><td>{{ $package->package_code }}</td><td>{{ $package->package_name }}</td><td>{{ $package->package_type }}</td><td class="text-right">{{ number_format($package->sale_price, 2) }}</td><td>{{ $package->valid_days }}</td><td>{{ ucfirst($package->status) }}</td></tr>@empty
        <tr><td colspan="6" class="text-center">No prepaid packages found.</td></tr>@endforelse
        </tbody>
    </table></div>
    {{ $packages->links() }}
    <hr>
    <h4>Sell Prepaid Package</h4>
    <form method="POST" action="{{ route('beauty-saloons.prepaid-packages.sell') }}" class="bs-form-card">
        @csrf
        <div class="row"><div class="col-md-3"><label>Package ID</label><input name="prepaid_package_id" class="form-control" required></div><div class="col-md-3"><label>Customer ID</label><input name="customer_id" class="form-control"></div><div class="col-md-3"><label>Customer Name</label><input name="customer_name" class="form-control"></div><div class="col-md-3"><label>Sale Amount</label><input name="sale_amount" class="form-control input_number"></div></div>
        <div class="text-right mt-2"><button class="btn btn-success bs-big-save">Save Sale</button></div>
    </form>
</div>
@endsection

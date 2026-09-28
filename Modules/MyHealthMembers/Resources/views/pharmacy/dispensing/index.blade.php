@extends('layouts.app')
@section('title', 'Medicine Dispensing')
@section('content')
<section class="content-header"><h1>Medicine Dispensing</h1></section>
<section class="content">
@if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
<form method="GET" class="mb-3"><div class="input-group"><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search dispense no, member code, name, mobile, NIC"><span class="input-group-btn"><button class="btn btn-primary">Search</button></span></div></form>
<a href="{{ route('myhealth.pharmacy.dispensing.create') }}" class="btn btn-success mb-3">New Dispense</a> <a href="{{ route('myhealth.pharmacy.dashboard') }}" class="btn btn-default mb-3">Dashboard</a>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Dispense No</th><th>Date</th><th>Member</th><th>Status</th><th>Total</th><th>Action</th></tr></thead><tbody>
@foreach($dispenses as $dispense)<tr><td>{{ $dispense->dispense_no }}</td><td>{{ $dispense->dispense_date }}</td><td>{{ optional($dispense->member)->myhealth_code }} - {{ optional($dispense->member)->name }}</td><td>{{ ucfirst(str_replace('_',' ', $dispense->status)) }}</td><td>{{ number_format($dispense->total_amount, 4) }}</td><td><a href="{{ route('myhealth.pharmacy.dispensing.show', $dispense->id) }}" class="btn btn-xs btn-primary">View</a></td></tr>@endforeach
</tbody></table></div>{{ $dispenses->links() }}
</section>
@endsection

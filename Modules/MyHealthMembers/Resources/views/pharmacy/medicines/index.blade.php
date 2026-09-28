@extends('layouts.app')
@section('title', 'Medicines')
@section('content')
<section class="content-header"><h1>Medicine Master</h1></section>
<section class="content">
    @if(session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <form method="GET" class="mb-3"><div class="input-group"><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search medicine"><span class="input-group-btn"><button class="btn btn-primary">Search</button></span></div></form>
    <a href="{{ route('myhealth.pharmacy.medicines.create') }}" class="btn btn-success mb-3">Add Medicine</a>
    <a href="{{ route('myhealth.pharmacy.dashboard') }}" class="btn btn-default mb-3">Dashboard</a>
    <div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Code</th><th>Name</th><th>Generic</th><th>Brand</th><th>Category</th><th>Strength</th><th>Stock</th><th>Reorder</th><th>Status</th></tr></thead><tbody>
        @foreach($medicines as $medicine)
            <tr><td>{{ $medicine->medicine_code }}</td><td>{{ $medicine->medicine_name }}</td><td>{{ $medicine->generic_name }}</td><td>{{ $medicine->brand }}</td><td>{{ $medicine->category }}</td><td>{{ $medicine->strength }}</td><td>{{ number_format($medicine->batches->sum('available_qty'), 4) }}</td><td>{{ number_format($medicine->reorder_level, 4) }}</td><td>{{ $medicine->is_active ? 'Active' : 'Inactive' }}</td></tr>
        @endforeach
    </tbody></table></div>
    {{ $medicines->links() }}
</section>
@endsection

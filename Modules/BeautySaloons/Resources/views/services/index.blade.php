@extends('beautysaloons::layouts.app')
@section('content')
<section class="content-header"><h1>Beauty Services</h1></section>
<section class="content">
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    <div class="box"><div class="box-header"><a href="{{ route('beautysaloons.services.create') }}" class="btn btn-primary">Add Service</a></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped bs-service-table">
            <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Duration</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @foreach($services as $service)
                <tr>
                    <td>{{ $service->service_code }}</td><td>{{ $service->name }}</td><td>{{ optional($service->category)->name }}</td>
                    <td>{{ $service->duration_minutes }} mins</td><td>{{ $service->is_active ? 'Active' : 'Inactive' }}</td>
                    <td><a class="btn btn-xs btn-info" href="{{ route('beautysaloons.services.edit', $service->id) }}">Edit</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $services->links() }}
    </div></div>
</section>
@endsection

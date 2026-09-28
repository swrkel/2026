@extends('layouts.app')
@section('title', 'Laboratory Test Catalogue')
@section('content')
<section class="content-header"><h1>Laboratory Test Catalogue</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Tests</h3><div class="box-tools"><a href="{{ route('myhealth.laboratory.catalogue.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add Test</a></div></div>
<div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Code</th><th>Test Name</th><th>Department</th><th>Sample</th><th>Normal Range</th><th>Price</th><th>Status</th></tr></thead><tbody>
@forelse($tests as $test)<tr><td>{{ $test->test_code }}</td><td>{{ $test->test_name }}</td><td>{{ $test->department }}</td><td>{{ $test->sample_type }}</td><td>{{ $test->normal_range }}</td><td>{{ number_format((float)$test->price, 2) }}</td><td>{{ ucfirst($test->status) }}</td></tr>@empty<tr><td colspan="7" class="text-center">No tests found.</td></tr>@endforelse
</tbody></table>{{ $tests->links() }}</div></div>
</section>
@endsection

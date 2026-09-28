@extends('layouts.app')
@section('title', 'Vaccines')
@section('content')
<section class="content-header"><h1>Vaccine Master <a href="{{ route('myhealth.vaccination.vaccines.create') }}" class="btn btn-primary btn-sm pull-right"><i class="fa fa-plus"></i> Add</a></h1></section>
<section class="content"><div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Manufacturer</th><th>Storage</th><th>Status</th></tr></thead><tbody>
@forelse($vaccines as $v)<tr><td>{{ $v->vaccine_code }}</td><td>{{ $v->vaccine_name }}</td><td>{{ $v->vaccine_type }}</td><td>{{ $v->manufacturer }}</td><td>{{ $v->storage_temperature }}</td><td>{{ ucfirst($v->status) }}</td></tr>@empty<tr><td colspan="6" class="text-center">No vaccines found.</td></tr>@endforelse
</tbody></table>{{ $vaccines->links() }}
</div></div></section>
@endsection

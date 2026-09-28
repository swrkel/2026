@extends('autoservice::layouts.master')
@section('title','Mechanics')
@section('autoservice_content')
<div class="box"><div class="box-header"><h3 class="box-title">Mechanics</h3><a href="{{ route('autoservice.mechanics.create') }}" class="btn btn-primary btn-sm pull-right">Add Mechanic</a></div><div class="box-body"><table class="table table-bordered"><tr><th>Code</th><th>Name</th><th>Mobile</th><th>Speciality</th><th>Rate</th><th>Status</th><th>Action</th></tr>@foreach($mechanics as $m)<tr><td>{{ $m->mechanic_code }}</td><td>{{ $m->name }}</td><td>{{ $m->mobile }}</td><td>{{ $m->speciality }}</td><td>{{ number_format($m->hourly_rate,2) }}</td><td>{{ $m->is_active ? 'Active':'Inactive' }}</td><td><a class="btn btn-xs btn-info" href="{{ route('autoservice.mechanics.edit',$m->id) }}">Edit</a></td></tr>@endforeach</table>{{ $mechanics->links() }}</div></div>
@endsection

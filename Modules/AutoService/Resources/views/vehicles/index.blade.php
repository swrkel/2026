@extends('autoservice::layouts.master')
@section('title','Auto Service Vehicles')
@section('autoservice_content')
<div class="box"><div class="box-header"><a class="btn btn-success pull-right" href="{{ route('autoservice.vehicles.create') }}">Add Vehicle</a></div><div class="box-body table-responsive"><table class="table table-bordered"><thead><tr><th>Reg No</th><th>Make</th><th>Model</th><th>Odometer</th><th>Next Service</th><th>Action</th></tr></thead><tbody>@foreach($vehicles as $v)<tr><td>{{ $v->registration_no }}</td><td>{{ $v->make }}</td><td>{{ $v->model }}</td><td>{{ $v->current_odometer }}</td><td>{{ $v->next_service_date }}</td><td><a class="btn btn-xs btn-primary" href="{{ route('autoservice.vehicles.edit',$v->id) }}">Edit</a> <a class="btn btn-xs btn-info" href="{{ route('autoservice.vehicles.timeline',$v->id) }}">Timeline</a></td></tr>@endforeach</tbody></table>{{ $vehicles->links() }}</div></div>
@endsection

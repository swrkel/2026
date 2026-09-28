@extends('autoservice::layouts.master')
@section('title',$appointment->id ? 'Edit Appointment' : 'Add Appointment')
@section('autoservice_content')
<form method="post" action="{{ $appointment->id ? route('autoservice.appointments.update',$appointment->id) : route('autoservice.appointments.store') }}">@csrf @if($appointment->id) @method('PUT') @endif
<div class="box"><div class="box-body row">
<div class="col-md-4"><label>Customer</label><select name="contact_id" class="form-control"><option value="">Please Select</option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected($appointment->contact_id==$c->id)>{{ $c->name }} {{ $c->mobile ? ' - '.$c->mobile : '' }}</option>@endforeach</select></div>
<div class="col-md-4"><label>Vehicle</label><select name="vehicle_id" class="form-control"><option value="">Please Select</option>@foreach($vehicles as $v)<option value="{{ $v->id }}" @selected($appointment->vehicle_id==$v->id)>{{ $v->registration_no }} - {{ $v->make }} {{ $v->model }}</option>@endforeach</select></div>
<div class="col-md-4"><label>Appointment Date/Time</label><input type="datetime-local" name="appointment_at" value="{{ $appointment->appointment_at ? date('Y-m-d\\TH:i', strtotime($appointment->appointment_at)) : '' }}" class="form-control"></div>
<div class="col-md-4"><label>Service Type</label><input type="text" name="service_type" value="{{ $appointment->service_type }}" class="form-control"></div>
<div class="col-md-4"><label>Status</label><select name="status" class="form-control">@foreach(['scheduled','confirmed','arrived','cancelled','completed'] as $st)<option value="{{ $st }}" @selected(($appointment->status ?: 'scheduled')==$st)>{{ ucfirst($st) }}</option>@endforeach</select></div>
<div class="col-md-12"><label>Customer Note</label><textarea name="customer_note" class="form-control">{{ $appointment->customer_note }}</textarea></div>
<div class="col-md-12"><label>Internal Note</label><textarea name="internal_note" class="form-control">{{ $appointment->internal_note }}</textarea></div>
</div><div class="box-footer"><button class="btn btn-primary">Save</button><a href="{{ route('autoservice.appointments.index') }}" class="btn btn-default">Back</a></div></div>
</form>
@endsection

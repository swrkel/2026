@extends('layouts.app')
@section('title', 'Add Theatre Room')
@section('content')
<section class="content-header"><h1>Add Theatre Room</h1></section>
<section class="content"><form method="POST" action="{{ route('myhealth.operation_theatre.theatres.store') }}">@csrf<div class="box box-primary"><div class="box-body"><div class="row">
<div class="col-md-3 form-group"><label>Room Code *</label><input type="text" name="room_code" class="form-control" required></div>
<div class="col-md-5 form-group"><label>Room Name *</label><input type="text" name="room_name" class="form-control" required></div>
<div class="col-md-2 form-group"><label>Type</label><input type="text" name="room_type" class="form-control" value="general"></div>
<div class="col-md-2 form-group"><label>Floor</label><input type="text" name="floor" class="form-control"></div>
<div class="col-md-3 form-group"><label>Status</label><select name="status" class="form-control"><option value="available">Available</option><option value="occupied">Occupied</option><option value="maintenance">Maintenance</option></select></div>
<div class="col-md-9 form-group"><label>Equipment Notes</label><textarea name="equipment_notes" class="form-control"></textarea></div>
</div></div><div class="box-footer"><button class="btn btn-primary">Save</button></div></div></form></section>
@endsection

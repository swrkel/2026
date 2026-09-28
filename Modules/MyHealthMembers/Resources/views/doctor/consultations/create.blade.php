@extends('layouts.app')
@section('title', 'Add Consultation')

@section('content')
<section class="content-header"><h1>Add Consultation - {{ $member->name }}</h1></section>
<section class="content">
    <form method="POST" action="{{ route('myhealth.consultations.store', $member->id) }}">
        @csrf
        <div class="box box-primary">
            <div class="box-body">
                <div class="form-group"><label>Doctor</label>{!! Form::select('doctor_id', $doctors, null, ['class' => 'form-control select2', 'placeholder' => 'Select Doctor']) !!}</div>
                <div class="form-group"><label>Date *</label><input type="date" name="consultation_date" value="{{ date('Y-m-d') }}" class="form-control" required></div>
                <div class="form-group"><label>Time</label><input type="time" name="consultation_time" class="form-control"></div>
                <div class="form-group"><label>Visit Type</label><input type="text" name="visit_type" class="form-control"></div>
                <div class="form-group"><label>Chief Complaint</label><textarea name="chief_complaint" class="form-control"></textarea></div>
                <div class="form-group"><label>Summary</label><textarea name="summary" class="form-control"></textarea></div>
            </div>
            <div class="box-footer"><button class="btn btn-primary">Save Consultation</button></div>
        </div>
    </form>
</section>
@endsection

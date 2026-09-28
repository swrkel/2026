@extends('layouts.app')
@section('title', 'Doctor Records')

@section('content')
<section class="content-header">
    <h1>Doctor Records - {{ $member->name }}</h1>
</section>

<section class="content">
    <a href="{{ route('myhealth.consultations.index', $member->id) }}" class="btn btn-info mb-3">Consultations</a>

    <div class="box box-primary">
        <div class="box-header"><h3 class="box-title">Medical History</h3></div>
        <div class="box-body">
            <form method="POST" action="{{ route('myhealth.doctor.history.save', $member->id) }}">
                @csrf
                <div class="form-group"><label>Allergies</label><textarea name="allergies" class="form-control">{{ $history->allergies }}</textarea></div>
                <div class="form-group"><label>Chronic Conditions</label><textarea name="chronic_conditions" class="form-control">{{ $history->chronic_conditions }}</textarea></div>
                <div class="form-group"><label>Current Medications</label><textarea name="current_medications" class="form-control">{{ $history->current_medications }}</textarea></div>
                <div class="form-group"><label>Past Surgeries</label><textarea name="past_surgeries" class="form-control">{{ $history->past_surgeries }}</textarea></div>
                <div class="form-group"><label>Family History</label><textarea name="family_history" class="form-control">{{ $history->family_history }}</textarea></div>
                <button class="btn btn-primary">Save Medical History</button>
            </form>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header"><h3 class="box-title">Add Diagnosis</h3></div>
        <div class="box-body">
            <form method="POST" action="{{ route('myhealth.doctor.diagnosis.store', $member->id) }}">
                @csrf
                <div class="form-group"><label>Date</label><input type="date" name="diagnosis_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control"></div>
                <div class="form-group"><label>Symptoms</label><textarea name="symptoms" class="form-control"></textarea></div>
                <div class="form-group"><label>Diagnosis</label><textarea name="diagnosis" class="form-control"></textarea></div>
                <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
                <button class="btn btn-primary">Save Diagnosis</button>
            </form>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header"><h3 class="box-title">Add Prescription</h3></div>
        <div class="box-body">
            <form method="POST" action="{{ route('myhealth.doctor.prescription.store', $member->id) }}">
                @csrf
                <div class="form-group"><label>Date</label><input type="date" name="prescription_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                <div class="form-group"><label>Prescription *</label><textarea name="prescription_details" class="form-control" required></textarea></div>
                <div class="form-group"><label>Instructions</label><textarea name="instructions" class="form-control"></textarea></div>
                <button class="btn btn-primary">Save Prescription</button>
            </form>
        </div>
    </div>
</section>
@endsection

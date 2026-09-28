@extends('layouts.app')

@section('title', __('My Health - Consultation'))

@section('content')
<section class="content-header">
    <h1>{{ __('Doctor Consultation') }} <small>{{ $member->myhealth_code }} - {{ $member->name }}</small></h1>
</section>

<section class="content">
    @if($errors->any())
        <div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="row">
        <div class="col-md-3">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">{{ __('Patient Summary') }}</h3></div>
                <div class="box-body">
                    <p><strong>{{ __('Code') }}:</strong> {{ $member->myhealth_code }}</p>
                    <p><strong>{{ __('Name') }}:</strong> {{ $member->name }}</p>
                    <p><strong>{{ __('Mobile') }}:</strong> {{ $member->mobile }}</p>
                    <p><strong>{{ __('NIC') }}:</strong> {{ $member->nic_no }}</p>
                    <p><strong>{{ __('Blood Group') }}:</strong> {{ $member->blood_group }}</p>
                </div>
            </div>

            <div class="box box-warning">
                <div class="box-header with-border"><h3 class="box-title">{{ __('Recent Clinical History') }}</h3></div>
                <div class="box-body">
                    <h5><strong>{{ __('Diagnoses') }}</strong></h5>
                    <ul class="list-unstyled">
                        @forelse($clinical['diagnoses'] as $diagnosis)
                            <li>{{ optional($diagnosis->diagnosis_date)->format('Y-m-d') ?? $diagnosis->diagnosis_date }} - {{ $diagnosis->title }}</li>
                        @empty
                            <li class="text-muted">{{ __('No recent diagnosis') }}</li>
                        @endforelse
                    </ul>
                    <h5><strong>{{ __('Prescriptions') }}</strong></h5>
                    <ul class="list-unstyled">
                        @forelse($clinical['prescriptions'] as $prescription)
                            <li>{{ $prescription->prescription_date }} - {{ \Illuminate\Support\Str::limit($prescription->prescription_details, 40) }}</li>
                        @empty
                            <li class="text-muted">{{ __('No recent prescription') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <form method="POST" action="{{ route('myhealth.doctor.portal.consultation.store', $member->id) }}">
                @csrf
                <div class="box box-success">
                    <div class="box-header with-border"><h3 class="box-title">{{ __('Clinical Consultation') }}</h3></div>
                    <div class="box-body">
                        <div class="row">
                            <div class="col-md-4"><div class="form-group"><label>{{ __('Date') }} *</label><input type="date" name="consultation_date" class="form-control" value="{{ date('Y-m-d') }}" required></div></div>
                            <div class="col-md-4"><div class="form-group"><label>{{ __('Time') }}</label><input type="time" name="consultation_time" class="form-control" value="{{ date('H:i') }}"></div></div>
                            <div class="col-md-4"><div class="form-group"><label>{{ __('Doctor') }}</label><select name="doctor_id" class="form-control"><option value="">{{ __('Select') }}</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->name }}</option>@endforeach</select></div></div>
                        </div>

                        <ul class="nav nav-tabs" role="tablist">
                            <li class="active"><a href="#complaint" data-toggle="tab">{{ __('Complaint') }}</a></li>
                            <li><a href="#examination" data-toggle="tab">{{ __('Examination') }}</a></li>
                            <li><a href="#diagnosis" data-toggle="tab">{{ __('Diagnosis') }}</a></li>
                            <li><a href="#prescription" data-toggle="tab">{{ __('Prescription') }}</a></li>
                            <li><a href="#plan" data-toggle="tab">{{ __('Plan & Follow-up') }}</a></li>
                        </ul>
                        <div class="tab-content" style="padding-top:15px;">
                            <div class="tab-pane active" id="complaint">
                                <div class="form-group"><label>{{ __('Visit Type') }}</label><input type="text" name="visit_type" class="form-control" placeholder="OPD / Emergency / Follow-up"></div>
                                <div class="form-group"><label>{{ __('Chief Complaint') }}</label><textarea name="chief_complaint" class="form-control" rows="3"></textarea></div>
                                <div class="form-group"><label>{{ __('History of Present Illness') }}</label><textarea name="history_present_illness" class="form-control" rows="4"></textarea></div>
                            </div>
                            <div class="tab-pane" id="examination">
                                <div class="form-group"><label>{{ __('Vital Signs') }}</label><textarea name="vital_signs" class="form-control" rows="3" placeholder="BP, Pulse, Temperature, SPO2, Weight..."></textarea></div>
                                <div class="form-group"><label>{{ __('Examination Notes') }}</label><textarea name="examination_notes" class="form-control" rows="5"></textarea></div>
                            </div>
                            <div class="tab-pane" id="diagnosis">
                                <div class="form-group"><label>{{ __('Diagnosis Title') }}</label><input type="text" name="diagnosis_title" class="form-control"></div>
                                <div class="form-group"><label>{{ __('Diagnosis Notes') }}</label><textarea name="diagnosis_notes" class="form-control" rows="5"></textarea></div>
                            </div>
                            <div class="tab-pane" id="prescription">
                                <div class="form-group"><label>{{ __('Prescription Details') }}</label><textarea name="prescription_details" class="form-control" rows="6" placeholder="Medicine, strength, dosage, frequency, duration, quantity"></textarea></div>
                                <div class="form-group"><label>{{ __('Instructions') }}</label><textarea name="prescription_instructions" class="form-control" rows="3"></textarea></div>
                            </div>
                            <div class="tab-pane" id="plan">
                                <div class="form-group"><label>{{ __('Investigation Plan') }}</label><textarea name="investigation_plan" class="form-control" rows="3"></textarea></div>
                                <div class="form-group"><label>{{ __('Treatment Plan') }}</label><textarea name="treatment_plan" class="form-control" rows="3"></textarea></div>
                                <div class="row">
                                    <div class="col-md-4"><div class="form-group"><label>{{ __('Follow-up Date') }}</label><input type="date" name="follow_up_date" class="form-control"></div></div>
                                </div>
                                <div class="form-group"><label>{{ __('Consultation Summary') }}</label><textarea name="summary" class="form-control" rows="4"></textarea></div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> {{ __('Save Consultation') }}</button>
                        <a href="{{ route('myhealth.doctor.portal.dashboard') }}" class="btn btn-default">{{ __('Cancel') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

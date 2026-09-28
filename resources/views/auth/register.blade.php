<div class="col-md-12">
    <hr/>
</div>

<!-- My Health Registration Fields -->
<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('gender','Gender:*') !!}
        {!! Form::select('gender', ['male'=>'Male','female'=>'Female','other'=>'Other'], null, ['class'=>'form-control','style'=>'width:300px','placeholder'=>'Select Gender']) !!}
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('marital_status','Marital Status:*') !!}
        {!! Form::select('marital_status', ['single'=>'Single','married'=>'Married','divorced'=>'Divorced'], null, ['class'=>'form-control','style'=>'width:300px','placeholder'=>'Select Marital Status']) !!}
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('blood_group','Blood Group') !!}
        {!! Form::select('blood_group', ['A+'=>'A+','A-'=>'A-','B+'=>'B+','B-'=>'B-','O+'=>'O+','O-'=>'O-','AB+'=>'AB+','AB-'=>'AB-'], null, ['class'=>'form-control','style'=>'width:200px','placeholder'=>'Select Blood Group']) !!}
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('time_zone','Time / Zone') !!}
        {!! Form::select('time_zone', $timezones, null, ['class'=>'form-control','style'=>'width:250px','placeholder'=>'Select Time Zone']) !!}
    </div>
</div>

<div class="col-md-12">
    <div class="form-group">
        {!! Form::label('profile_image','Upload Your Image') !!}
        {!! Form::file('profile_image', ['class'=>'form-control','style'=>'width:400px']) !!}
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('height_feet','Height (Feet)') !!}
        {!! Form::number('height_feet', null, ['class'=>'form-control','style'=>'width:120px','placeholder'=>'Feet']) !!}
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('height_inches','Height (Inches)') !!}
        {!! Form::number('height_inches', null, ['class'=>'form-control','style'=>'width:120px','placeholder'=>'Inches']) !!}
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('weight','Weight (Kg)') !!}
        {!! Form::number('weight', null, ['class'=>'form-control','style'=>'width:200px','step'=>'0.01','placeholder'=>'Weight']) !!}
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        {!! Form::label('guardian_name','Guardian Name') !!}
        {!! Form::text('guardian_name', null, ['class'=>'form-control','style'=>'width:300px','placeholder'=>'Guardian Name']) !!}
    </div>
</div>

<div class="col-md-12">
    <div class="form-group">
        {!! Form::label('known_allergies','Any Known Allergies') !!}
        {!! Form::textarea('known_allergies', null, ['class'=>'form-control','style'=>'width:400px','rows'=>2,'placeholder'=>'Known Allergies']) !!}
    </div>
</div>

<div class="col-md-12">
    <div class="form-group">
        {!! Form::label('referral_code','Referral Code') !!}
        {!! Form::text('referral_code', 0, ['class'=>'form-control','style'=>'width:200px','placeholder'=>'Please enter the Referral code if any']) !!}
    </div>
</div>

<div class="col-md-12">
    <div class="form-group">
        {!! Form::label('notes','Notes') !!}
        {!! Form::textarea('notes', null, ['class'=>'form-control','style'=>'width:400px','rows'=>2,'placeholder'=>'Notes']) !!}
    </div>
</div>

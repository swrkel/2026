@extends('layouts.app')
@section('title', 'Request My Health Access')
@section('content')
<section class="content-header"><h1>Request My Health Access</h1></section>
<section class="content">
    <div class="box box-primary">
        <form method="POST" action="{{ route('myhealth.access.store') }}">
            @csrf
            <div class="box-body">
                <div class="row">
                    <div class="col-md-4 form-group"><label>Member Code *</label><input type="text" name="member_code" class="form-control" required></div>
                    <div class="col-md-8 form-group"><label>Purpose *</label><input type="text" name="purpose" class="form-control" required></div>
                </div>
                <label>Sections Requested</label>
                <div class="row">
                    @foreach(['profile'=>'Profile','history'=>'Medical History','prescriptions'=>'Prescriptions','labs'=>'Lab Results','documents'=>'Documents','billing'=>'Billing'] as $key=>$label)
                        <div class="col-md-3"><label><input type="checkbox" name="access_sections[]" value="{{ $key }}"> {{ $label }}</label></div>
                    @endforeach
                </div>
                <p class="help-block">An OTP/consent code will be generated for the member to approve this access.</p>
            </div>
            <div class="box-footer"><button class="btn btn-primary">Create Access Request</button></div>
        </form>
    </div>
</section>
@endsection

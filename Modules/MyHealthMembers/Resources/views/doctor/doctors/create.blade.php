@extends('layouts.app')
@section('title', 'Add My Health Doctor')

@section('content')
<section class="content-header"><h1>Add My Health Doctor</h1></section>
<section class="content">
    <form method="POST" action="{{ route('myhealth.doctors.store') }}">
        @csrf
        <div class="box box-primary">
            <div class="box-body">
                <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label>Registration No</label><input type="text" name="registration_no" class="form-control"></div>
                <div class="form-group"><label>Specialization</label><input type="text" name="specialization" class="form-control"></div>
                <div class="form-group"><label>Mobile</label><input type="text" name="mobile" class="form-control"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
            </div>
            <div class="box-footer"><button class="btn btn-primary">Save Doctor</button></div>
        </div>
    </form>
</section>
@endsection

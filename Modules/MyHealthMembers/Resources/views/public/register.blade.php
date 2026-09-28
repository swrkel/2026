@extends('layouts.app')
@section('title', 'My Health Registration')

@section('content')
<section class="content-header">
    <h1>My Health Self Registration</h1>
</section>

<section class="content">
    <form method="POST" action="{{ route('myhealth.public.register.store') }}">
        @csrf
        <div class="box box-primary">
            <div class="box-body">
                <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" required></div>
                <div class="form-group"><label>Mobile</label><input type="text" name="mobile" class="form-control"></div>
                <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control"></div>
                <div class="form-group"><label>NIC No</label><input type="text" name="nic_no" class="form-control"></div>
                <div class="form-group"><label>Passport No</label><input type="text" name="passport_no" class="form-control"></div>
            </div>
            <div class="box-footer"><button class="btn btn-primary" type="submit">Register</button></div>
        </div>
    </form>
</section>
@endsection

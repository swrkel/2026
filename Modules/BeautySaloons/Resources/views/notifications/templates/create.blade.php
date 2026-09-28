@extends('beautysaloons::layout')
@section('beauty_content')
<div class="container-fluid bs-notifications">
    <h3>Add Notification Template</h3>
    <form method="POST" action="{{ route('beautysaloons.notifications.templates.store') }}" class="card card-body">
        @csrf
        <div class="row">
            <div class="col-md-3 form-group"><label>Code</label><input name="code" class="form-control" required></div>
            <div class="col-md-3 form-group"><label>Name</label><input name="name" class="form-control" required></div>
            <div class="col-md-3 form-group"><label>Category</label><input name="category" class="form-control"></div>
            <div class="col-md-3 form-group"><label>Channel</label><select name="channel" class="form-control"><option value="sms">SMS</option><option value="email">Email</option><option value="push">Push</option><option value="whatsapp">WhatsApp</option><option value="in_app">In App</option></select></div>
            <div class="col-md-3 form-group"><label>Language</label><input name="language" value="en" class="form-control"></div>
            <div class="col-md-6 form-group"><label>Subject</label><input name="subject" class="form-control"></div>
            <div class="col-md-3 form-group"><label>Status</label><select name="is_active" class="form-control"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div class="col-md-12 form-group"><label>Body</label><textarea name="body" class="form-control" rows="8" required></textarea><small>Use variables like @{{customer_name}}, @{{appointment_date}}, @{{amount}}</small></div>
        </div>
        <button class="btn btn-success">Save Template</button>
    </form>
</div>
@endsection

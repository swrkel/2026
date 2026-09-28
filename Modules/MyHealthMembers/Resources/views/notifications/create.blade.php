@extends('layouts.app')
@section('title', 'Add My Health Notification')

@section('content')
<section class="content-header"><h1>Add My Health Notification</h1></section>
<section class="content">
    <div class="box box-primary">
        <form method="POST" action="{{ route('myhealth.notifications.store') }}">
            @csrf
            <div class="box-body">
                @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <div class="row">
                    <div class="col-md-4"><div class="form-group"><label>Member ID *</label><input type="number" name="member_id" class="form-control" required></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Channel *</label><select name="channel" class="form-control" required><option value="sms">SMS</option><option value="email">Email</option><option value="whatsapp">WhatsApp</option></select></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Purpose</label><input type="text" name="purpose" class="form-control" placeholder="appointment, lab, prescription"></div></div>
                </div>
                <div class="form-group"><label>Subject</label><input type="text" name="subject" class="form-control"></div>
                <div class="form-group"><label>Message *</label><textarea name="message" class="form-control" rows="5" required></textarea></div>
            </div>
            <div class="box-footer"><button class="btn btn-primary" type="submit">Queue Notification</button><a href="{{ route('myhealth.notifications.index') }}" class="btn btn-default">Cancel</a></div>
        </form>
    </div>
</section>
@endsection

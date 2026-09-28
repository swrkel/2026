@extends('layouts.app')
@section('title', $template->exists ? 'Edit Notification Template' : 'Add Notification Template')

@section('content')
<section class="content-header"><h1>{{ $template->exists ? 'Edit' : 'Add' }} Notification Template</h1></section>
<section class="content">
    <div class="box box-primary">
        <form method="POST" action="{{ $template->exists ? route('myhealth.notifications.templates.update', $template) : route('myhealth.notifications.templates.store') }}">
            @csrf
            @if($template->exists) @method('PUT') @endif
            <div class="box-body">
                @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                <div class="row">
                    <div class="col-md-4"><div class="form-group"><label>Code *</label><input type="text" name="code" class="form-control" value="{{ old('code', $template->code) }}" required></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Channel *</label><select name="channel" class="form-control" required><option value="sms" @selected(old('channel', $template->channel) === 'sms')>SMS</option><option value="email" @selected(old('channel', $template->channel) === 'email')>Email</option><option value="whatsapp" @selected(old('channel', $template->channel) === 'whatsapp')>WhatsApp</option></select></div></div>
                </div>
                <div class="form-group"><label>Subject</label><input type="text" name="subject" class="form-control" value="{{ old('subject', $template->subject) }}"></div>
                <div class="form-group"><label>Body *</label><textarea name="body" class="form-control" rows="6" required>{{ old('body', $template->body) }}</textarea><p class="help-block">Supported placeholders: {member_code}, {name}, {appointment_date}, {passcode}</p></div>
                <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active ?? true))> Active</label>
            </div>
            <div class="box-footer"><button class="btn btn-primary" type="submit">Save</button><a href="{{ route('myhealth.notifications.templates.index') }}" class="btn btn-default">Cancel</a></div>
        </form>
    </div>
</section>
@endsection

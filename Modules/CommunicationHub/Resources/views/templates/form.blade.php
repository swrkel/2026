@extends('layouts.app')
@section('title', $title ?? 'Communication Hub')
@section('content')
<section class="content-header"><h1>{{ $title ?? 'Communication Hub' }}</h1></section>
<section class="content">
@if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

@php($title = $template->exists ? 'Edit Template' : 'Add Template')
<form method="POST" action="{{ $template->exists ? route('communicationhub.templates.update',$template) : route('communicationhub.templates.store') }}">@csrf @if($template->exists) @method('PUT') @endif
<div class="box box-primary"><div class="box-body"><div class="row"><div class="col-md-3"><label>Code</label><input class="form-control" name="code" value="{{ old('code',$template->code) }}" required></div><div class="col-md-3"><label>Name</label><input class="form-control" name="name" value="{{ old('name',$template->name) }}" required></div><div class="col-md-3"><label>Category</label><input class="form-control" name="category" value="{{ old('category',$template->category) }}"></div><div class="col-md-3"><label>Channel</label><select class="form-control" name="channel"><option value="sms">SMS</option><option value="email">Email</option><option value="whatsapp">WhatsApp</option><option value="push">Push</option></select></div><div class="col-md-12"><label>Subject</label><input class="form-control" name="subject" value="{{ old('subject',$template->subject) }}"></div><div class="col-md-12"><label>Body</label><textarea class="form-control" rows="8" name="body" required>{{ old('body',$template->body) }}</textarea><p class="help-block">Use placeholders like {MemberName}, {OTP}, {BusinessName}.</p></div></div></div><div class="box-footer"><button class="btn btn-primary">Save</button></div></div></form>
</section>
@endsection

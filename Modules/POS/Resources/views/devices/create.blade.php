@extends('pos::layouts.app')
@section('content')
<h3>{{ __('pos::messages.add_device') }}</h3>
<form method="post" action="{{ route('pos.devices.store') }}">@csrf
<input class="form-control" name="name" placeholder="{{ __('pos::messages.device_name') }}">
<input class="form-control" name="type" placeholder="{{ __('pos::messages.device_type') }}">
<textarea class="form-control" name="note" placeholder="{{ __('pos::messages.note') }}"></textarea>
<button class="btn btn-primary">{{ __('pos::messages.save') }}</button>
</form>
@endsection

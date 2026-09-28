@extends('pos::layouts.app')
@section('content')
<h3>{{ __('pos::messages.devices') }}</h3>
<table class="table table-bordered table-striped">
<thead><tr><th>{{ __('pos::messages.device_name') }}</th><th>{{ __('pos::messages.device_type') }}</th><th>{{ __('pos::messages.status') }}</th></tr></thead>
<tbody>@foreach($devices as $device)<tr><td>{{ $device->name }}</td><td>{{ $device->type }}</td><td>{{ $device->status }}</td></tr>@endforeach</tbody>
</table>
{{ $devices->links() }}
@endsection

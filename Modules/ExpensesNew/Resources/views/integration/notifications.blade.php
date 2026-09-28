@extends('layouts.app')
@section('title', __('expensesnew::lang.notifications'))
@section('content')
<section class="content-header"><h1>{{ __('expensesnew::lang.notifications') }}</h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>Event</th><th>Channels</th><th>Status</th></tr></thead><tbody>@foreach($templates as $template)<tr><td>{{ $template->event_key }}</td><td>{{ $template->channels_json }}</td><td>{{ $template->is_active ? 'Active' : 'Inactive' }}</td></tr>@endforeach</tbody></table></div></div></section>
@endsection

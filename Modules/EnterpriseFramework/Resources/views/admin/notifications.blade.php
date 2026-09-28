@extends('enterpriseframework::layout', ['title' => 'Enterprise Notification Center'])

@section('efw_content')
<div class="box box-primary"><div class="box-body">@foreach($notifications as $notification)<div class="alert alert-info"><strong>{{ $notification['module'] }}:</strong> {{ $notification['title'] }} - {{ $notification['message'] }}</div>@endforeach</div></div>
@endsection

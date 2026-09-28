@extends('pos::layouts.app')
@section('content')
<h3>{{ __('pos::messages.enterprise_monitor') }}</h3>
<div class="row">
@foreach(['open_registers','active_sessions','today_sales','pending_print_jobs','offline_devices'] as $key)
<div class="col-md-3"><div class="box"><div class="box-body"><strong>{{ __('pos::messages.'.$key) }}</strong><br>{{ $key === 'today_sales' ? number_format((float)$today_sales, 4) : $$key }}</div></div></div>
@endforeach
</div>
@endsection

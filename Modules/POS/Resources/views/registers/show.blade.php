@extends('pos::layouts.app', ['title' => __('pos::messages.register_details')])
@section('pos_content')
<div class="box box-primary"><div class="box-body">
@if($register)
    <dl class="dl-horizontal"><dt>{{ __('pos::messages.name') }}</dt><dd>{{ $register->name }}</dd><dt>{{ __('pos::messages.code') }}</dt><dd>{{ $register->code ?? '-' }}</dd><dt>{{ __('pos::messages.status') }}</dt><dd>{{ !empty($register->is_active) ? __('pos::messages.active') : __('pos::messages.inactive') }}</dd><dt>{{ __('pos::messages.note') }}</dt><dd>{{ $register->note ?? '-' }}</dd></dl>
@else <p class="text-muted">{{ __('pos::messages.no_records_found') }}</p> @endif
</div></div>
@endsection

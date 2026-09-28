@extends('pos::layouts.app', ['title' => __('pos::messages.add_register')])
@section('pos_content')
<div class="box box-primary"><div class="box-body"><form method="POST" action="{{ route('pos.registers.store') }}">@include('pos::registers._form')</form></div></div>
@endsection

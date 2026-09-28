@extends('pos::layouts.app', ['title' => __('pos::messages.edit_register')])
@section('pos_content')
<div class="box box-primary"><div class="box-body"><form method="POST" action="{{ route('pos.registers.update', $register->id ?? 0) }}">@method('PUT')@include('pos::registers._form')</form></div></div>
@endsection

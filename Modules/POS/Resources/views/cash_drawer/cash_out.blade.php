@extends('pos::layouts.app', ['title' => __('pos::messages.cash_out')])
@section('pos_content')<div class="box box-primary"><div class="box-body"><form method="POST" action="{{ route('pos.cash_drawer.cash_out') }}">@include('pos::cash_drawer._movement_form')</form></div></div>@endsection

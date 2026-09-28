@extends('suppliers::layouts.app')
@section('title', __('suppliers::lang.import_opening_balance'))
@section('suppliers_content')
<section class="content-header"><h1>@lang('suppliers::lang.import_opening_balance')</h1></section>
<section class="content main-content-inner"><div class="box box-primary"><div class="box-body">@include('suppliers::partials.tabs', ['active' => 'opening_balance'])<p>@lang('suppliers::lang.opening_balance_placeholder')</p></div></div></section>
@endsection

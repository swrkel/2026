@extends('suppliers::layouts.app')
@section('title', __('suppliers::lang.import_suppliers'))
@section('suppliers_content')
<section class="content-header"><h1>@lang('suppliers::lang.import_suppliers')</h1></section>
<section class="content main-content-inner"><div class="box box-primary"><div class="box-body">@include('suppliers::partials.tabs', ['active' => 'imports'])<p>@lang('suppliers::lang.import_placeholder')</p></div></div></section>
@endsection

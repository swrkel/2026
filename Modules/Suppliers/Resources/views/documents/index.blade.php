@extends('suppliers::layouts.app')
@section('title', __('suppliers::lang.documents'))
@section('suppliers_content')
<section class="content-header"><h1>@lang('suppliers::lang.documents')</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
@if(isset($supplier)) @include('suppliers::partials.tabs', ['supplier' => $supplier, 'active' => 'documents']) @endif
<p>This page is separated for easy maintenance and will be expanded in the next Supplier package.</p>
</div></div></section>
@endsection

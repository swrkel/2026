@extends('layouts.app')
@section('title', __('purchase::lang.edit_purchase'))

@section('content')
<section class="content-header"><h1>@lang('purchase::lang.edit_purchase')</h1></section>
<section class="content">
    {!! Form::model($purchase, ['route' => ['purchase.entries.update', $purchase->id], 'method' => 'put', 'id' => 'purchase_entry_form']) !!}
        @include('purchase::entries.partials.form')
        <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
@include('purchase::layouts.runtime')
<script>{!! file_get_contents(module_path('Purchase', 'Resources/assets/js/purchase-entry-edit.js')) !!}</script>
@endsection

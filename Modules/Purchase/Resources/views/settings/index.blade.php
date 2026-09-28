@extends('layouts.app')
@section('title', __('purchase::lang.purchase_settings'))

@section('content')
<section class="content-header">
    <h1>@lang('purchase::lang.purchase_settings')</h1>
</section>
<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            @include('purchase::settings.partials.numbering')
        </div>
    </div>
</section>
@endsection

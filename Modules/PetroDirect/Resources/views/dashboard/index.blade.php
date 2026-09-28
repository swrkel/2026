@extends('layouts.app')

@section('title', __('petrodirect::lang.petro_direct'))

@section('content')
<section class="content-header">
    <h1>@lang('petrodirect::lang.petro_direct')</h1>
    <small>Standalone direct fuel station operations module</small>
</section>

<section class="content">
    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('petrodirect::lang.dashboard')</h3>
        </div>
        <div class="box-body">
            <div class="alert alert-info" style="margin-bottom:0;">
                Petro Direct is running from its own module routes, controllers, views, assets, permissions and isolation rules.
            </div>
        </div>
    </div>
</section>
@endsection

@extends('layouts.app')

@section('title', __('messages.error'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('messages.error')</h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-danger">
                <h4><i class="icon fa fa-ban"></i> @lang('messages.error')!</h4>
                {{ $msg ?? 'Something went wrong' }}
            </div>
            
            <a href="{{ action('\Modules\Petro\Http\Controllers\PumpOperatorController@index') }}" class="btn btn-primary">
                <i class="fa fa-arrow-left"></i> @lang('messages.back')
            </a>
        </div>
    </div>
</section>
@endsection

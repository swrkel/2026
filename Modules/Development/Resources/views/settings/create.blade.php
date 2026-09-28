@extends('layouts.app')

@section('title', __('development::lang.add_module'))

@section('content')
<!-- Content Header -->
<section class="content-header">
    <h1>@lang('development::lang.add_module')</h1>
</section>

<!-- Main Content -->
<section class="content">
    {!! Form::open(['route' => 'development.settings.store', 'method' => 'POST', 'id' => 'add_module_form']) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('name', __('development::lang.name') . ':*') !!}
                            {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('development::lang.name')]) !!}
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 text-right">
            <button type="submit" class="btn btn-primary">@lang('development::lang.general.save')</button>
            <a href="{{ route('development.settings.index') }}" class="btn btn-default">@lang('development::lang.general.cancel')</a>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@endsection

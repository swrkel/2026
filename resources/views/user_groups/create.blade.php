@extends('layouts.app')

@section('title', __('user_group.add_user_group'))

@section('content')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('user_group.add_user_group')</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['url' => action('UserGroupController@store'), 'method' => 'post', 'id' => 'user_group_add_form']) !!}
    @component('components.widget')
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('name', __('user_group.name') . ':*') !!}
                    {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('user_group.name')]) !!}
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('description', __('user_group.description') . ':') !!}
                    {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('user_group.description')]) !!}
                </div>
            </div>
        </div>
    @endcomponent
    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary pull-right">
                @lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function(){
    $('#user_group_add_form').validate({
        rules: {
            name: {
                required: true,
            },
        },
    });
});
</script>
@endsection

@extends('layouts.app')
@section('title', __('expense.add_expense_category'))

@section('content')
<section class="content-header">
    <h1>@lang('expense.add_expense_category')</h1>
</section>
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        {!! Form::open(['url' => action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseCategoryController@store'), 'method' => 'post']) !!}
        <div class="row">
            <div class="col-md-6"><div class="form-group">{!! Form::label('name', __('expense.category_name') . ':*') !!}{!! Form::text('name', null, ['class' => 'form-control', 'required']) !!}</div></div>
            <div class="col-md-6"><div class="form-group">{!! Form::label('expense_code', __('vat::lang.expense_code') . ':') !!}{!! Form::text('expense_code', null, ['class' => 'form-control']) !!}</div></div>
        </div>
        <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
        <a href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseCategoryController@index') }}" class="btn btn-default pull-right" style="margin-right: 8px;">@lang('messages.close')</a>
        <div class="clearfix"></div>
        {!! Form::close() !!}
    @endcomponent
</section>
@endsection

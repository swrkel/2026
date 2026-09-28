@extends('layouts.app')
@section('title', __('expense.edit_expense_category'))

@section('content')
<section class="content-header">
    <h1>@lang('expense.edit_expense_category')</h1>
</section>
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        {!! Form::open(['url' => action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseCategoryController@update', [$expense_category->id]), 'method' => 'PUT']) !!}
        <div class="row">
            <div class="col-md-6"><div class="form-group">{!! Form::label('name', __('expense.category_name') . ':*') !!}{!! Form::text('name', $expense_category->name, ['class' => 'form-control', 'required']) !!}</div></div>
            <div class="col-md-6"><div class="form-group">{!! Form::label('expense_code', __('vat::lang.expense_code') . ':') !!}{!! Form::text('expense_code', $expense_category->expense_code, ['class' => 'form-control']) !!}</div></div>
        </div>
        <button type="submit" class="btn btn-primary pull-right">@lang('messages.update')</button>
        <a href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatExpenseCategoryController@index') }}" class="btn btn-default pull-right" style="margin-right: 8px;">@lang('messages.close')</a>
        <div class="clearfix"></div>
        {!! Form::close() !!}
    @endcomponent
</section>
@endsection

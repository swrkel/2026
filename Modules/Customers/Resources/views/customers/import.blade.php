@extends('customers::layouts.master')

@section('title', __('customers::lang.customers') . ' Import')

@section('content')
<section class="content-header">
    <h1>Customers Import</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Import Customers</h3>
        </div>
        {!! Form::open(['route' => 'customers.import.post', 'method' => 'post', 'files' => true]) !!}
        <div class="box-body">
            <p class="help-block">
                Upload CSV with columns: name, mobile, email, credit_limit, address_line_1, city.
                This page is owned by the standalone Customers module and does not call Contact import controllers.
            </p>
            <div class="form-group">
                {!! Form::label('contacts_csv', 'CSV File:*') !!}
                {!! Form::file('contacts_csv', ['class' => 'form-control', 'required' => true, 'accept' => '.csv,text/csv,text/plain']) !!}
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">Import Customers</button>
            <a href="{{ route('customers.index') }}" class="btn btn-default">Back</a>
        </div>
        {!! Form::close() !!}
    </div>
</section>
@endsection

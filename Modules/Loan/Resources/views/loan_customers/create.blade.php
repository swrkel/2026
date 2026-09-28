@extends('layouts.app')
@section('title', 'Add Loan Customer')

@section('content')
<section class="content" style="padding:0;">
    @if ($errors->any())
        <div class="alert alert-danger" style="margin:15px 18px;">
            <ul style="margin-bottom:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {!! Form::open(['url' => route('loan.customers.store'), 'method' => 'post', 'files' => true, 'id' => 'loan_customer_form']) !!}
        @section('loan_customer_buttons')
            <a href="{{ route('loan.customers.index') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back</a>
            <button type="submit" name="submit_type" value="save" class="btn btn-primary"><i class="fa fa-save"></i> Save Loan Customer</button>
            <button type="submit" name="submit_type" value="save_and_new" class="btn btn-success"><i class="fa fa-plus"></i> Save & New</button>
            <a href="{{ route('loan.customers.index') }}" class="btn btn-default"><i class="fa fa-times"></i> Cancel</a>
        @endsection
        @include('loan::loan_customers._form')
    {!! Form::close() !!}
</section>
@endsection

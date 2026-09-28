@extends('layouts.app')

@section('title', __('customers::lang.add_customer'))

@section('content')
<section class="content-header">
    <h1>@lang('customers::lang.add_customer') <small>Contacts Customer form parity</small></h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Add Customer</h3>
            <div class="box-tools"><a href="{{ route('customers.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a></div>
        </div>

        {!! Form::open(['route' => 'customers.store', 'method' => 'post', 'id' => 'customers_module_add_form', 'files' => true]) !!}
        <div class="box-body">
            @if ($errors->any())
                <div class="alert alert-danger"><strong>Please fix the following errors:</strong><ul style="margin-bottom:0;">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @include('customers::customers.partials.form', ['customer' => null])
        </div>
        <div class="box-footer text-right">
            <a href="{{ route('customers.index') }}" class="btn btn-default">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Customer</button>
        </div>
        {!! Form::close() !!}
    </div>
</section>
@endsection

@section('model-scritps')
    @parent
    @include('customers::customers.partials.form_scripts')
@endsection


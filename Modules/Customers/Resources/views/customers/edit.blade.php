@extends('layouts.app')

@section('title', __('customers::lang.edit_customer'))

@section('content')
<section class="content-header">
    <h1>@lang('customers::lang.edit_customer') <small>Contacts Customer form parity</small></h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Edit Customer</h3>
            <div class="box-tools"><a href="{{ route('customers.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a></div>
        </div>

        {!! Form::model($customer, ['route' => ['customers.update', $customer->id], 'method' => 'put', 'id' => 'customers_module_edit_form', 'files' => true, 'novalidate' => 'novalidate']) !!}
        <div class="box-body">
            @if ($errors->any())
                <div class="alert alert-danger"><strong>Please fix the following errors:</strong><ul style="margin-bottom:0;">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @include('customers::customers.partials.form', ['customer' => $customer])
        </div>
        <div class="box-footer text-right">
            <a href="{{ route('customers.index') }}" class="btn btn-default">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update Customer</button>
        </div>
        {!! Form::close() !!}
    </div>
</section>
@endsection

@section('model-scritps')
    @parent
    @include('customers::customers.partials.form_scripts')
@endsection


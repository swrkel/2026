@extends('beautysaloons::layouts.app')
@section('content')
<section class="content-header"><h1>@lang('beautysaloons::customers.edit_customer')</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
<form method="POST" action="{{ route('beautysaloons.customers.update', $customer->id) }}">@csrf @method('PUT') @include('beautysaloons::customers.form')
<div class="text-right"><button type="submit" class="btn btn-success btn-lg bs-save-btn"><i class="fa fa-save"></i> Update</button></div>
</form></div></div></section>
@endsection

@extends('beautysaloons::layouts.app')
@section('content')
<section class="content-header"><h1>@lang('beautysaloons::customers.add_customer')</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
<form method="POST" action="{{ route('beautysaloons.customers.store') }}">@csrf @include('beautysaloons::customers.form')
<div class="text-right"><button type="submit" class="btn btn-success btn-lg bs-save-btn"><i class="fa fa-save"></i> Save</button></div>
</form></div></div></section>
@endsection

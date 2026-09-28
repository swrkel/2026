@extends('beautysaloons::layouts.app')
@section('content')
<section class="content-header"><h1>{{ $customer->full_name }}</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
<p><strong>Mobile:</strong> {{ $customer->mobile }}</p>
<p><strong>Email:</strong> {{ $customer->email }}</p>
<p><strong>Allergies:</strong> {{ $customer->allergies }}</p>
<hr><h4>@lang('beautysaloons::customers.visit_history')</h4>
<form method="POST" action="{{ route('beautysaloons.customers.visit-notes.store', $customer->id) }}">@csrf
<div class="row"><div class="col-md-3"><input type="text" name="visit_date" class="form-control bs_datepicker" value="{{ date('Y-m-d') }}"></div><div class="col-md-9"><textarea name="note" class="form-control" placeholder="Visit notes"></textarea></div></div><br>
<button class="btn btn-primary">Add Note</button></form>
</div></div></section>
@endsection

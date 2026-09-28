@extends('beautysaloons::layouts.app')
@section('content')
<section class="content-header"><h1>{{ __('beautysaloons::beautysaloons.pos') }}</h1></section>
<section class="content"><form method="POST" action="{{ route('beauty-saloons.pos.store') }}">@csrf
<div class="box"><div class="box-body"><p>POS billing form scaffold for services, products, packages and payments.</p></div><div class="box-footer text-right"><button class="btn btn-primary btn-lg">{{ __('messages.save') }}</button></div></div>
</form></section>
@endsection

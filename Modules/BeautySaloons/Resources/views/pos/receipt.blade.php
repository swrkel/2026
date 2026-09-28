@extends('beautysaloons::layouts.app')
@section('content')
<section class="content"><div class="box"><div class="box-body"><h3>{{ __('beautysaloons::beautysaloons.receipt') }}</h3><p>Receipt #{{ $sale->id ?? '' }}</p></div></div></section>
@endsection

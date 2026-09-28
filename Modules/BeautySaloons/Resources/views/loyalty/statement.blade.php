@extends('beautysaloons::layouts.app')
@section('title', __('beautysaloons::loyalty.customer_statement'))
@section('content')
<section class="content-header"><h1>@lang('beautysaloons::loyalty.customer_statement')</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body">
    <p>@lang('beautysaloons::loyalty.earned'): {{ $summary['earned'] }}</p>
    <p>@lang('beautysaloons::loyalty.redeemed'): {{ $summary['redeemed'] }}</p>
    <p><strong>@lang('beautysaloons::loyalty.balance'): {{ $summary['balance'] }}</strong></p>
</div></div></section>
@endsection

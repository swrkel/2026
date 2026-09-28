@extends('beautysaloons::layouts.app')
@section('title', __('beautysaloons::loyalty.loyalty_tiers'))
@section('content')
<section class="content-header"><h1>@lang('beautysaloons::loyalty.loyalty_tiers')</h1></section>
<section class="content"><div class="box box-primary"><div class="box-body"><table class="table table-bordered" id="bs_loyalty_tiers_table"></table></div></div></section>
@endsection

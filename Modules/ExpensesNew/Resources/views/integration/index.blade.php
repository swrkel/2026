@extends('layouts.app')
@section('title', __('expensesnew::lang.integration_bridge'))
@section('content')
<section class="content-header"><h1>{{ __('expensesnew::lang.integration_bridge') }}</h1></section>
<section class="content"><div class="expnew-command-card"><h3>Optional Module Cost Posting</h3><p>Other standalone modules may post cost transactions through this bridge without writing directly to Expenses-New tables.</p></div></section>
@endsection

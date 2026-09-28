@extends('pricechangenew::layouts.app')
@section('pcn_page_title', 'Add Price Change')
@section('pcn_page_subtitle', 'Prepare a controlled price change draft for one or more permitted locations.')
@section('pcn_page_actions')
<a href="{{ route('pricechangenew.dashboard') }}" class="btn btn-default btn-sm"><i class="fa fa-dashboard"></i> Dashboard</a>
<a href="{{ route('pricechangenew.changes.index') }}" class="btn btn-success btn-sm"><i class="fa fa-list"></i> List Price Changes</a>
@endsection
@section('pcn_content')
<form method="POST" action="{{ route('pricechangenew.changes.store') }}" id="pcn_price_change_form" autocomplete="off">
    @csrf
    @include('pricechangenew::changes.partials.form', ['change' => null])
</form>
@endsection

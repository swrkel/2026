@extends('pricechangenew::layouts.app')
@section('pcn_page_title', 'Edit Price Change')
@section('pcn_page_subtitle', $change->reference_no . ' — only draft records can be edited.')
@section('pcn_page_actions')
<a href="{{ route('pricechangenew.changes.show', $change->id) }}" class="btn btn-default btn-sm"><i class="fa fa-eye"></i> View Draft</a>
<a href="{{ route('pricechangenew.changes.index') }}" class="btn btn-success btn-sm"><i class="fa fa-list"></i> List Price Changes</a>
@endsection
@section('pcn_content')
<form method="POST" action="{{ route('pricechangenew.changes.update', $change->id) }}" id="pcn_price_change_form" autocomplete="off">
    @csrf
    @method('PUT')
    @include('pricechangenew::changes.partials.form', ['change' => $change])
</form>
@endsection

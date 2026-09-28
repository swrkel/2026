@extends('restaurantnew::layouts.app')
@section('title', __('restaurantnew::equipment.title'))
@section('content')
<div class="rn-pos-page rn-equipment-page">
    <div class="rn-page-header"><h3>{ __('restaurantnew::equipment.spare_parts') }</h3></div>
    <div class="rn-toolbar">
        <input type="text" class="form-control rn-search" placeholder="{ __('restaurantnew::equipment.search') }">
        <button class="btn btn-primary">{ __('restaurantnew::equipment.export') }</button>
        <button class="btn btn-default">{ __('restaurantnew::equipment.print') }</button>
    </div>
    <div class="box rn-pos-box"><div class="box-body table-responsive"><table class="table table-bordered table-striped rn-datatable"><thead><tr><th>Part Code</th><th>Part Name</th><th>Stock</th><th>Reorder</th><th>Cost</th><th>Action</th></tr></thead><tbody></tbody></table></div></div>
</div>
@endsection

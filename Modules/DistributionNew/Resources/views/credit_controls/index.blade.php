@extends('layouts.app')
@section('title', __('distributionnew::lang.credit_controls'))
@section('content')
<section class="content-header"><h1>{ __('distributionnew::lang.credit_controls') }</h1></section>
<section class="content">
  <div class="box disnew-pos-card">
    <div class="box-header with-border">
      <h3 class="box-title">{ __('distributionnew::lang.list') }</h3>
      <div class="box-tools"><a href="{ route('distribution-new.credit_controls.create') }" class="btn btn-primary btn-sm text-white"><i class="fa fa-plus"></i> { __('messages.add') }</a></div>
    </div>
    <div class="box-body table-responsive">
      <table class="table table-bordered table-striped disnew-datatable">
        <thead><tr><th>ID</th><th>{ __('messages.action') }</th><th>{ __('business.business') }</th><th>{ __('distributionnew::lang.status') }</th><th>{ __('messages.date') }</th></tr></thead>
        <tbody>@foreach($records as $record)<tr><td>{ $record->id }</td><td><a class="btn btn-xs btn-info text-white" href="{ route('distribution-new.credit_controls.edit',$record->id) }">{ __('messages.edit') }</a></td><td>{ $record->business_id }</td><td>{ $record->status ?? '' }</td><td>{ $record->created_at }</td></tr>@endforeach</tbody>
      </table>
      { $records->links() }
    </div>
  </div>
</section>
@endsection

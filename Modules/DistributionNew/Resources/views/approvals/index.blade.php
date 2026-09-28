@extends('layouts.app')
@section('title', __('distributionnew::lang.approvals'))
@section('content')
<section class="content-header"><h1>@lang('distributionnew::lang.approvals')</h1></section>
<section class="content">
 <div class="box box-solid disnew-pos-card">
  <div class="box-body table-responsive">
   <table class="table table-bordered table-striped" id="disnew_approvals_table">
    <thead><tr><th>#</th><th>@lang('distributionnew::lang.reference')</th><th>@lang('distributionnew::lang.status')</th><th>@lang('distributionnew::lang.requested_at')</th><th>@lang('distributionnew::lang.action')</th></tr></thead>
    <tbody>@foreach($requests as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->reference_type }} #{{ $row->reference_id }}</td><td>{{ ucfirst($row->status) }}</td><td>{{ @format_datetime($row->requested_at) }}</td><td>@if($row->status=='pending')<form method="post" action="{{ action([Modules\DistributionNew\Http\Controllers\DisnewApprovalController::class,'approve'],[$row->id]) }}">@csrf<button class="btn btn-xs btn-primary">@lang('distributionnew::lang.approve')</button></form>@endif</td></tr>@endforeach</tbody>
   </table>
   {{ $requests->links() }}
  </div>
 </div>
</section>
@endsection

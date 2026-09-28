@extends('layouts.app')
@section('title', __('stocktransfernew::lang.audit_security'))
@section('content')
<section class="content-header"><h1>@lang('stocktransfernew::lang.audit_security')</h1></section>
<section class="content stn-audit-page">

<div class="box stn-card"><div class="box-header"><h3 class="box-title">@lang('stocktransfernew::lang.audit_log')</h3></div>
<div class="box-body">
<form method="get" class="row stn-filter-row"><div class="col-md-3"><input name="transfer_no" value="{{ request('transfer_no') }}" class="form-control" placeholder="Transfer No"></div><div class="col-md-3"><input name="event" value="{{ request('event') }}" class="form-control" placeholder="Event"></div><div class="col-md-2"><input type="date" name="from" value="{{ request('from') }}" class="form-control"></div><div class="col-md-2"><input type="date" name="to" value="{{ request('to') }}" class="form-control"></div><div class="col-md-2"><button class="btn btn-primary btn-block">Search</button></div></form>
<div class="table-responsive"><table class="table table-bordered table-striped stn-table"><thead><tr><th>Date</th><th>Event</th><th>Entity</th><th>Transfer</th><th>User</th><th>IP</th><th>Meta</th></tr></thead><tbody>
@forelse($logs as $log)<tr><td>{{ @format_datetime($log->created_at) }}</td><td>{{ $log->event }}</td><td>{{ $log->entity_type }} #{{ $log->entity_id }}</td><td>{{ data_get($log->meta,'transfer_no') }}</td><td>{{ $log->user_id }}</td><td>{{ $log->ip_address }}</td><td><small>{{ json_encode($log->meta) }}</small></td></tr>@empty<tr><td colspan="7" class="text-center">No audit records found</td></tr>@endforelse
</tbody></table></div>{{ $logs->links() }}</div></div>
</section>
@endsection

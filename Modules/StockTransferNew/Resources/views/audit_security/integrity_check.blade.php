@extends('layouts.app')
@section('title', __('stocktransfernew::lang.audit_security'))
@section('content')
<section class="content-header"><h1>@lang('stocktransfernew::lang.audit_security')</h1></section>
<section class="content stn-audit-page">

<div class="box stn-card"><div class="box-header"><h3 class="box-title">Integrity Check</h3><form method="post" action="{{ route('stock-transfer-new.audit-security.integrity-check.run') }}" class="pull-right">@csrf<button class="btn btn-primary">Run Check</button></form></div><div class="box-body"><div class="table-responsive"><table class="table table-bordered stn-table"><thead><tr><th>Date</th><th>Status</th><th>Total Findings</th><th>Checked By</th><th>Findings</th></tr></thead><tbody>
@forelse($checks as $check)<tr><td>{{ $check->checked_at }}</td><td><span class="label label-{{ $check->status == 'passed' ? 'success' : 'danger' }}">{{ $check->status }}</span></td><td>{{ $check->total_findings }}</td><td>{{ $check->checked_by }}</td><td><pre class="stn-pre">{{ json_encode($check->findings, JSON_PRETTY_PRINT) }}</pre></td></tr>@empty<tr><td colspan="5" class="text-center">No checks run yet</td></tr>@endforelse
</tbody></table></div>{{ $checks->links() }}</div></div>
</section>
@endsection

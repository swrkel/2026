@extends('layouts.app')
@section('title', __('stocktransfernew::lang.audit_security'))
@section('content')
<section class="content-header"><h1>@lang('stocktransfernew::lang.audit_security')</h1></section>
<section class="content stn-audit-page">

<div class="box stn-card"><div class="box-header"><h3 class="box-title">Active / Released Locks</h3></div><div class="box-body"><div class="table-responsive"><table class="table table-bordered stn-table"><thead><tr><th>Transfer</th><th>Type</th><th>Reason</th><th>Locked By</th><th>Locked At</th><th>Released At</th><th>Action</th></tr></thead><tbody>
@forelse($locks as $lock)<tr><td>{{ $lock->transfer_id }}</td><td>{{ $lock->lock_type }}</td><td>{{ $lock->reason }}</td><td>{{ $lock->locked_by }}</td><td>{{ $lock->locked_at }}</td><td>{{ $lock->released_at }}</td><td>@if(!$lock->released_at)<form method="post" action="{{ route('stock-transfer-new.audit-security.locks.release',$lock) }}">@csrf<input name="reason" class="form-control input-sm" placeholder="Release reason"><button class="btn btn-xs btn-warning" style="margin-top:4px">Release</button></form>@endif</td></tr>@empty<tr><td colspan="7" class="text-center">No locks found</td></tr>@endforelse
</tbody></table></div>{{ $locks->links() }}</div></div>
</section>
@endsection

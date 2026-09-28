@extends('layouts.app')
@section('title', __('stocktransfernew::lang.audit_security'))
@section('content')
<section class="content-header"><h1>@lang('stocktransfernew::lang.audit_security')</h1></section>
<section class="content stn-audit-page">

<div class="box stn-card"><div class="box-header"><h3 class="box-title">Duplicate Request Protection</h3></div><div class="box-body"><div class="table-responsive"><table class="table table-bordered stn-table"><thead><tr><th>Action</th><th>Key</th><th>Status</th><th>User</th><th>Expires</th><th>Action</th></tr></thead><tbody>
@forelse($keys as $key)<tr><td>{{ $key->action }}</td><td>{{ $key->duplicate_key }}</td><td>{{ $key->status }}</td><td>{{ $key->user_id }}</td><td>{{ $key->expires_at }}</td><td>@if($key->status == 'active')<form method="post" action="{{ route('stock-transfer-new.audit-security.duplicate-keys.void',$key) }}">@csrf<button class="btn btn-xs btn-danger">Void</button></form>@endif</td></tr>@empty<tr><td colspan="6" class="text-center">No duplicate keys found</td></tr>@endforelse
</tbody></table></div>{{ $keys->links() }}</div></div>
</section>
@endsection

@extends('pumperdashboardnew::layouts.admin')
@section('pone_content')
<div class="pone-page-head">
    <div><h1>Pump Operator Profiles</h1><p>Pumper Dashboard-New-owned operator profiles used by the exclusive Petro PD-New settlement source contract.</p></div>
    @can('pumper_dashboard_new.operators.manage')
    <form method="post" action="{{ route('pumper-dashboard-new.admin.operators.sync') }}">@csrf<button class="pone-btn pone-btn-primary">Synchronize PONE Operators</button></form>
    @endcan
</div>
<div class="pone-alert pone-alert-info">Operator login identity and module settings stay in Pumper Dashboard-New. Petro PD-New stores only a local read mapping when a closed shift is imported.</div>
<div class="pone-panel"><div class="pone-panel-body"><div class="pone-table-wrap"><table class="pone-table">
<thead><tr><th>ID</th><th>Operator</th><th>Linked PD Operator ID</th><th>User ID</th><th>Location</th><th>Login</th><th>Status</th><th>Last Login</th><th>Action</th></tr></thead>
<tbody>
@forelse($operators as $operator)
    @php($formId = 'pone-operator-'.$operator->id)
    <tr>
        <td><form id="{{ $formId }}" method="post" action="{{ route('pumper-dashboard-new.admin.operators.update',$operator) }}">@csrf @method('PUT')</form>{{ $operator->id }}</td>
        <td><strong>{{ $operator->display_name }}</strong></td>
        <td>{{ $operator->pd_operator_id }}</td>
        <td>{{ $operator->user_id }}</td>
        <td><select form="{{ $formId }}" name="location_id" style="min-width:150px;padding:7px;border:1px solid #cfd8e6;border-radius:7px" @cannot('pumper_dashboard_new.operators.manage') disabled @endcannot><option value="">All / inherited</option>@foreach($locations as $location)<option value="{{ $location->id }}" {{ (int)$operator->location_id===(int)$location->id?'selected':'' }}>{{ $location->name }}</option>@endforeach</select></td>
        <td><input form="{{ $formId }}" type="hidden" name="login_enabled" value="0"><label><input form="{{ $formId }}" type="checkbox" name="login_enabled" value="1" {{ $operator->login_enabled?'checked':'' }} @cannot('pumper_dashboard_new.operators.manage') disabled @endcannot> Enabled</label></td>
        <td><select form="{{ $formId }}" name="status" style="padding:7px;border:1px solid #cfd8e6;border-radius:7px" @cannot('pumper_dashboard_new.operators.manage') disabled @endcannot><option value="active" {{ $operator->status==='active'?'selected':'' }}>Active</option><option value="inactive" {{ $operator->status==='inactive'?'selected':'' }}>Inactive</option></select></td>
        <td>{{ optional($operator->last_login_at)->format('Y-m-d H:i') ?: '-' }}</td>
        <td>@can('pumper_dashboard_new.operators.manage')<button form="{{ $formId }}" class="pone-btn pone-btn-success pone-btn-sm">Save</button>@else<span class="pone-badge pone-badge-not_required">View only</span>@endcan</td>
    </tr>
@empty
<tr><td colspan="9" class="pone-empty">No Pumper Dashboard-New operator profiles with linked users were found.</td></tr>
@endforelse
</tbody></table></div></div></div>
@endsection

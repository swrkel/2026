@extends('stocktransfernew::layouts.app')
@section('content')
@include('stocktransfernew::partials.header',['title'=>'Stock Transfer-New Settings','subtitle'=>'Standalone workflow controls and permissions reference'])
<div class="stn-card">
<form method="post" action="{{ route('stock-transfer-new.settings.store') }}">@csrf
<div class="row">
 <div class="col-md-3"><label><input type="checkbox" name="approval_required" value="1" {{ $settings->approval_required ? 'checked' : '' }}> Approval required</label></div>
 <div class="col-md-3"><label><input type="checkbox" name="allow_partial_receive" value="1" {{ $settings->allow_partial_receive ? 'checked' : '' }}> Allow partial receive</label></div>
 <div class="col-md-3"><label><input type="checkbox" name="require_stock_before_dispatch" value="1" {{ ($settings->require_stock_before_dispatch ?? false) ? 'checked' : '' }}> Require stock before dispatch</label></div>
 <div class="col-md-3"><label><input type="checkbox" name="auto_generate_document_numbers" value="1" {{ ($settings->auto_generate_document_numbers ?? true) ? 'checked' : '' }}> Auto document numbers</label></div>
</div>
<button class="btn btn-success stn-btn" type="submit">Save Settings</button>
</form>
</div>
<div class="stn-card"><h4>Permission Keys</h4><table class="table table-bordered"><thead><tr><th>Permission Key</th><th>Description</th></tr></thead><tbody>@foreach($permissions as $key=>$label)<tr><td><code>{{ $key }}</code></td><td>{{ $label }}</td></tr>@endforeach</tbody></table></div>
@endsection

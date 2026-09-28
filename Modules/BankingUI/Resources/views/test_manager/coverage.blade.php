@extends('bankingui::layouts.master')
@section('banking_content')
@include('bankingui::test_manager._nav')
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Banking Module Coverage</h3></div><form method="POST" action="{{ route('banking.test-manager.coverage.update') }}">@csrf<div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Module</th><th>Status</th><th>Dev %</th><th>UI %</th><th>UAT %</th><th>Prod %</th><th>Notes</th></tr></thead><tbody>
@foreach($rows as $row)
<tr><td>{{ $row->module_name }}</td><td><select name="coverage[{{ $row->id }}][status]" class="form-control input-sm">@foreach(config('bankingui.test_manager.statuses') as $s)<option value="{{ $s }}" @selected($row->status==$s)>{{ $s }}</option>@endforeach</select></td><td><input type="number" min="0" max="100" name="coverage[{{ $row->id }}][development_percent]" value="{{ $row->development_percent }}" class="form-control input-sm"></td><td><input type="number" min="0" max="100" name="coverage[{{ $row->id }}][ui_tested_percent]" value="{{ $row->ui_tested_percent }}" class="form-control input-sm"></td><td><input type="number" min="0" max="100" name="coverage[{{ $row->id }}][uat_percent]" value="{{ $row->uat_percent }}" class="form-control input-sm"></td><td><input type="number" min="0" max="100" name="coverage[{{ $row->id }}][production_ready_percent]" value="{{ $row->production_ready_percent }}" class="form-control input-sm"></td><td><input type="text" name="coverage[{{ $row->id }}][notes]" value="{{ $row->notes }}" class="form-control input-sm"></td></tr>
@endforeach
</tbody></table></div><div class="box-footer"><button class="btn btn-primary">Save Coverage</button></div></form></div>
@endsection

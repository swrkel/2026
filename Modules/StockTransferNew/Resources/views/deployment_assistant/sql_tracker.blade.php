@extends('layouts.app')
@section('title', 'Stock Transfer New SQL Tracker')
@section('content')
<section class="content-header stn-pos-header"><h1>SQL Execution Tracker</h1></section>
<section class="content stn-deployment-page">
    <div class="row">
        <div class="col-md-5">
            <div class="box stn-box"><div class="box-header with-border"><h3 class="box-title">Mark SQL as Executed</h3></div>
                <form method="post" action="{{ route('stock-transfer-new.deployment.sql-executed') }}">@csrf
                    <div class="box-body">
                        <div class="form-group"><label>Script Name</label><input name="script_name" class="form-control" required></div>
                        <div class="form-group"><label>Database Scope</label><select name="database_scope" class="form-control"><option value="tenant">Tenant DB</option><option value="master">Master DB</option></select></div>
                        <div class="form-group"><label>Remarks</label><textarea name="remarks" class="form-control"></textarea></div>
                    </div>
                    <div class="box-footer"><button class="btn btn-primary">Save Execution</button></div>
                </form>
            </div>
        </div>
        <div class="col-md-7">
            <div class="box stn-box"><div class="box-header with-border"><h3 class="box-title">Required Scripts</h3></div>
                <div class="box-body"><ul class="stn-script-list">@foreach($requiredScripts as $script)<li>{{ $script }}</li>@endforeach</ul></div>
            </div>
            <div class="box stn-box"><div class="box-header with-border"><h3 class="box-title">Execution History</h3></div>
                <div class="box-body table-responsive"><table class="table table-bordered"><thead><tr><th>Script</th><th>Scope</th><th>Date</th><th>Remarks</th></tr></thead><tbody>
                @foreach($executions as $execution)<tr><td>{{ $execution->script_name }}</td><td>{{ $execution->database_scope }}</td><td>{{ $execution->executed_at }}</td><td>{{ $execution->remarks }}</td></tr>@endforeach
                </tbody></table></div>
            </div>
        </div>
    </div>
</section>
@endsection
@push('css')<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stn_041.css') }}">@endpush

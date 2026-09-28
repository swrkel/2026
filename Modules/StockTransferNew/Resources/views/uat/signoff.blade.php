@extends('layouts.app')
@section('title', __('stocktransfernew::uat.signoff'))
@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-uat.css') }}">
<div class="stn-uat-wrap">
    <div class="stn-uat-hero"><h1>{{ __('stocktransfernew::uat.signoff') }}</h1></div>
    <form method="post" action="{{ url('/stock-transfer-new/uat/signoff') }}" class="stn-signoff-form">
        @csrf
        <input name="area" placeholder="Area / Page / Workflow" class="form-control">
        <input name="signed_by" placeholder="Signed by" class="form-control">
        <select name="status" class="form-control"><option value="pending">Pending</option><option value="passed">Passed</option><option value="failed">Failed</option></select>
        <input name="remarks" placeholder="Remarks" class="form-control">
        <button class="btn btn-primary">Save</button>
    </form>
    <table class="table table-bordered"><thead><tr><th>Area</th><th>Signed By</th><th>Status</th><th>Remarks</th><th>Date</th></tr></thead><tbody>
        @forelse($records as $record)
            <tr><td>{{ $record['area'] ?? '' }}</td><td>{{ $record['signed_by'] ?? '' }}</td><td>{{ $record['status'] ?? '' }}</td><td>{{ $record['remarks'] ?? '' }}</td><td>{{ $record['created_at'] ?? '' }}</td></tr>
        @empty
            <tr><td colspan="5">No sign-off records yet.</td></tr>
        @endforelse
    </tbody></table>
</div>
@endsection

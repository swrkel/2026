@extends('bankinginternetbanking::layouts.master')
@section('banking_internet_content')
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">{{ $title ?? 'Messages' }}</h3></div>
    <div class="box-body">
        <div class="alert alert-info">This page is ready for UI testing and next workflow connection. It is kept inside the standalone Internet Banking module.</div>
        <table class="table table-bordered table-striped">
            <thead><tr><th>Section</th><th>Status</th><th>Notes</th></tr></thead>
            <tbody>
                <tr><td>{{ $title ?? 'Messages' }}</td><td><span class="label label-success">Available</span></td><td>Standalone route and view loaded successfully.</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

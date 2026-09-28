@extends('bankinginternetbanking::layouts.master')
@section('banking_internet_content')
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Reports</h3></div>
    <div class="box-body">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Report</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($reports as $key => $report)
                    <tr><td>{{ $report }}</td><td><span class="label label-warning">Shell Ready</span></td><td><button class="btn btn-xs btn-default" disabled>Open</button></td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

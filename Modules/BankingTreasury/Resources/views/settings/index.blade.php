@extends('bankingtreasury::layouts.master')
@section('banking_treasury_content')
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $title }}</h3>
    </div>
    <div class="box-body table-responsive">
        <div class="bkg-toolbar clearfix">
            <input type="text" class="form-control input-sm bkg-search" placeholder="Search">
            <div class="btn-group pull-right">
                <button class="btn btn-default btn-sm">CSV</button>
                <button class="btn btn-default btn-sm">Excel</button>
                <button class="btn btn-default btn-sm">PDF</button>
                <button class="btn btn-default btn-sm">Print</button>
                <button class="btn btn-default btn-sm">Columns</button>
            </div>
        </div>
        <table class="table table-bordered table-striped">
            <thead><tr><th>#</th><th>Setting</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($settings as $i => $row)
                @if(is_array($row))
                    <tr><td>{{ $i + 1 }}</td><td>{{ $row['name'] ?? $row['code'] ?? '-' }}</td><td><span class="label label-info">{{ $row['status'] ?? 'Ready' }}</span></td></tr>
                @else
                    <tr><td>{{ $i + 1 }}</td><td>{{ $row }}</td><td><span class="label label-info">Ready for UI testing</span></td></tr>
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

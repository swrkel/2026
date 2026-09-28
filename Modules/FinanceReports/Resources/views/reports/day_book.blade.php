@extends('layouts.app')
@section('title', 'Day Book - New')
@section('content')
<section class="content-header"><h1>Day Book - New <small>{{ $location_id ? 'Branch / Location wise' : 'Consolidated' }}</small></h1></section>
<section class="content">
<div class="row no-print"><div class="col-sm-12"><div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Filters</h3></div><div class="box-body"><form method="GET" action="{{ route('finance-reports.day-book-new') }}"><div class="row"><div class="col-md-3"><label>Branch / Location</label><select name="location_id" class="form-control select2"><option value="all">Consolidated - All Locations</option>@foreach($locations as $id => $name)<option value="{{ $id }}" {{ (string)$location_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div><div class="col-md-3"><label>Date</label><input type="date" name="date" value="{{ $date }}" class="form-control"></div><div class="col-md-3"><label>&nbsp;</label>{{-- data-auto-filter-button: this report has its own filter form rather than
     using financereports::layouts.filter, so it needs the hook naming here too.
     The global auto-filter script matches only "Apply", "Apply Filters" and
     "Filter" by name - narrow on purpose, so it can never auto-click a Save or
     Delete button - and "Generate" is not among them. --}}
                        <button class="btn btn-primary btn-block" type="submit" data-auto-filter-button><i class="fa fa-search"></i> Generate</button></div></div></form></div></div></div></div>
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Day Book</h3>@include('financereports::layouts.toolbar')</div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr><th>Date</th><th>Account</th><th>Type</th><th>Note</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr></thead>
            <tbody>@forelse($report['rows'] as $row)<tr><td>{{ @format_date($row->operation_date) }}</td><td>{{ $row->account_name }}</td><td>{{ $row->sub_type ?? $row->type }}</td><td>{{ $row->note ?? $row->description ?? '' }}</td><td class="text-right">{{ number_format($row->debit, 4) }}</td><td class="text-right">{{ number_format($row->credit, 4) }}</td></tr>@empty<tr><td colspan="6" class="text-center">No records found.</td></tr>@endforelse</tbody>
            <tfoot><tr><th colspan="4">Records: {{ $report['totals']['records'] }}</th><th class="text-right">{{ number_format($report['totals']['debit'], 4) }}</th><th class="text-right">{{ number_format($report['totals']['credit'], 4) }}</th></tr></tfoot>
        </table>
    </div>
</div>
</section>
@endsection

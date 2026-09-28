@extends('layouts.app')
@section('title', 'Exception Report - New')
@section('content')
<section class="content-header"><h1>Exception Report - New <small>{{ $location_id ? 'Branch / Location wise' : 'Consolidated' }}</small></h1></section>
<section class="content">
<div class="row no-print"><div class="col-sm-12"><div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Filters</h3></div><div class="box-body"><form method="GET"><div class="row"><div class="col-md-4"><label>Branch / Location</label><select name="location_id" class="form-control select2"><option value="all">Consolidated - All Locations</option>@foreach($locations as $id => $name)<option value="{{ $id }}" {{ (string)$location_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div><div class="col-md-4"><label>As At Date</label><input type="date" name="as_at" value="{{ $as_at }}" class="form-control"></div><div class="col-md-4"><label>&nbsp;</label>{{-- data-auto-filter-button: this report has its own filter form rather than
     using financereports::layouts.filter, so it needs the hook naming here too.
     The global auto-filter script matches only "Apply", "Apply Filters" and
     "Filter" by name - narrow on purpose, so it can never auto-click a Save or
     Delete button - and "Generate" is not among them. --}}
                        <button class="btn btn-primary btn-block" type="submit" data-auto-filter-button><i class="fa fa-search"></i> Generate</button></div></div></form></div></div></div></div>
<div class="box box-danger"><div class="box-header with-border"><h3 class="box-title">Financial Exceptions</h3>@include('financereports::layouts.toolbar')</div><div class="box-body table-responsive"><table class="table table-bordered table-striped table-condensed"><thead><tr><th>Exception Type</th><th>Description</th><th>Severity</th><th class="text-right">Amount / Count</th></tr></thead><tbody>@forelse($report['rows'] as $row)<tr><td>{{ $row->type }}</td><td>{{ $row->description }}</td><td>{{ $row->severity }}</td><td class="text-right">{{ number_format($row->amount, 4) }}</td></tr>@empty<tr><td colspan="4" class="text-center">No exceptions found.</td></tr>@endforelse</tbody><tfoot><tr><th colspan="3">Records: {{ $report['totals']['records'] }}</th><th class="text-right">{{ number_format($report['totals']['amount'], 4) }}</th></tr></tfoot></table></div></div>
</section>
@endsection

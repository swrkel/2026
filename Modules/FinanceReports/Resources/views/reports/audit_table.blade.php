@extends('layouts.app')
@section('title', $title)
@section('content')
<section class="content-header"><h1>{{ $title }} <small>{{ $location_id ? 'Branch / Location wise' : 'Consolidated' }}</small></h1></section>
<section class="content">
<div class="row no-print"><div class="col-sm-12"><div class="box box-solid"><div class="box-header with-border"><h3 class="box-title">Filters</h3></div><div class="box-body"><form method="GET"><div class="row"><div class="col-md-3"><label>Branch / Location</label><select name="location_id" class="form-control select2"><option value="all">Consolidated - All Locations</option>@foreach($locations as $id => $name)<option value="{{ $id }}" {{ (string)$location_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div><div class="col-md-3"><label>Start Date</label><input type="date" name="start_date" value="{{ $start }}" class="form-control"></div><div class="col-md-3"><label>End Date</label><input type="date" name="end_date" value="{{ $end }}" class="form-control"></div><div class="col-md-3"><label>&nbsp;</label>{{-- data-auto-filter-button: this report has its own filter form rather than
     using financereports::layouts.filter, so it needs the hook naming here too.
     The global auto-filter script matches only "Apply", "Apply Filters" and
     "Filter" by name - narrow on purpose, so it can never auto-click a Save or
     Delete button - and "Generate" is not among them. --}}
                        <button class="btn btn-primary btn-block" type="submit" data-auto-filter-button><i class="fa fa-search"></i> Generate</button></div></div></form></div></div></div></div>
<div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">{{ $title }}</h3>@include('financereports::layouts.toolbar')</div><div class="box-body table-responsive">
<table class="table table-bordered table-striped table-condensed"><thead><tr>@foreach($report['headers'] as $header)<th>{{ $header }}</th>@endforeach</tr></thead><tbody>@forelse($report['rows'] as $row)<tr>@foreach($row as $col)<td>{{ $col }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($report['headers']) }}" class="text-center">No records found.</td></tr>@endforelse</tbody><tfoot><tr><th colspan="{{ count($report['headers']) }}">Records: {{ $report['totals']['records'] ?? 0 }}</th></tr></tfoot></table>
</div></div>
</section>
@endsection

@extends('layouts.app')
@section('title', 'Journal Register - New')
@section('content')
<section class="content-header"><h1>Journal Register - New <small>{{ $location_id ? 'Branch / Location wise' : 'Consolidated' }}</small></h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => route('finance-reports.journal-register-new')])
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Journal Register</h3>@include('financereports::layouts.toolbar')</div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped table-condensed">
            <thead><tr><th>Date</th><th>Account</th><th>Voucher / Type</th><th>Note</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr></thead>
            <tbody>
                @forelse($report['rows'] as $row)
                    <tr><td>{{ @format_date($row->operation_date) }}</td><td>{{ $row->account_name }}</td><td>{{ $row->voucher_no }}</td><td>{{ $row->note ?? $row->description ?? '' }}</td><td class="text-right">{{ number_format($row->debit, 4) }}</td><td class="text-right">{{ number_format($row->credit, 4) }}</td></tr>
                @empty
                    <tr><td colspan="6" class="text-center">No records found.</td></tr>
                @endforelse
            </tbody>
            <tfoot><tr><th colspan="4">Records: {{ $report['totals']['records'] }}</th><th class="text-right">{{ number_format($report['totals']['debit'], 4) }}</th><th class="text-right">{{ number_format($report['totals']['credit'], 4) }}</th></tr></tfoot>
        </table>
    </div>
</div>
</section>
@endsection

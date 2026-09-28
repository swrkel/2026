@extends('layouts.app')
@section('title', 'General Ledger - New')
@section('content')
<section class="content-header"><h1>General Ledger - New <small>{{ $location_id ? 'Branch / Location wise' : 'Consolidated' }}</small></h1></section>
<section class="content">
@include('financereports::layouts.filter', ['action' => route('finance-reports.general-ledger-new')])
<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">General Ledger</h3>@include('financereports::layouts.toolbar')</div>
    <div class="box-body table-responsive">
        @forelse($report['ledgers'] as $ledger)
            <h4 style="margin-top:20px;">{{ $ledger->account->name }} @if($ledger->account->account_number) <small>({{ $ledger->account->account_number }})</small> @endif</h4>
            <table class="table table-bordered table-striped table-condensed">
                <thead><tr><th>Date</th><th>Reference</th><th>Note</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Running Balance</th></tr></thead>
                <tbody>
                    <tr><td colspan="5"><strong>Opening Balance</strong></td><td class="text-right"><strong>{{ number_format($ledger->opening_balance, 4) }}</strong></td></tr>
                    @foreach($ledger->rows as $row)
                        <tr>
                            <td>{{ @format_date($row->operation_date) }}</td>
                            <td>{{ $row->sub_type ?? $row->type }}</td>
                            <td>{{ $row->note ?? $row->description ?? '' }}</td>
                            <td class="text-right">{{ number_format($row->debit, 4) }}</td>
                            <td class="text-right">{{ number_format($row->credit, 4) }}</td>
                            <td class="text-right">{{ number_format($row->running_balance, 4) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr><th colspan="3">Total / Closing</th><th class="text-right">{{ number_format($ledger->debit, 4) }}</th><th class="text-right">{{ number_format($ledger->credit, 4) }}</th><th class="text-right">{{ number_format($ledger->closing_balance, 4) }}</th></tr></tfoot>
            </table>
        @empty
            <div class="alert alert-info">No ledger transactions found for the selected filters.</div>
        @endforelse
    </div>
</div>
</section>
@endsection

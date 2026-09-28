@extends('layouts.app')

@section('title', 'Loan Ledger')

@section('content')
<section class="content-header">
    <h1>Loan Ledger <small>Customer loan transaction ledger</small></h1>
</section>

<section class="content loan-ledger-page">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-filter"></i> Filters</h3>
        </div>
        <div class="box-body">
            <form method="GET" action="{{ route('loan.ledger.index') }}" id="loan-ledger-filter-form">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Loan Customer</label>
                            {!! Form::select('customer_id', $customers, $filters['customer_id'] ?? null, ['class' => 'form-control select2', 'placeholder' => 'All Customers']) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Loan</label>
                            {!! Form::select('loan_id', $loans, $filters['loan_id'] ?? null, ['class' => 'form-control select2', 'placeholder' => 'All Loans']) !!}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>From Date</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>To Date</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <div class="btn-group btn-block">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Filter</button>
                            <a href="{{ route('loan.ledger.index') }}" class="btn btn-default">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="info-box bg-aqua"><span class="info-box-icon"><i class="fa fa-arrow-up"></i></span><div class="info-box-content"><span class="info-box-text">Total Debit</span><span class="info-box-number">{{ number_format($summary->total_debit, 2) }}</span></div></div>
        </div>
        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="info-box bg-green"><span class="info-box-icon"><i class="fa fa-arrow-down"></i></span><div class="info-box-content"><span class="info-box-text">Total Credit</span><span class="info-box-number">{{ number_format($summary->total_credit, 2) }}</span></div></div>
        </div>
        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="info-box bg-yellow"><span class="info-box-icon"><i class="fa fa-balance-scale"></i></span><div class="info-box-content"><span class="info-box-text">Balance</span><span class="info-box-number">{{ number_format($summary->balance, 2) }}</span></div></div>
        </div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-list"></i> Ledger Entries</h3>
            <div class="box-tools pull-right loan-ledger-toolbar">
                <a class="btn btn-default btn-sm" href="{{ route('loan.ledger.export', request()->query()) }}"><i class="fa fa-file-excel-o"></i> CSV</a>
                <a class="btn btn-default btn-sm" target="_blank" href="{{ route('loan.ledger.print', request()->query()) }}"><i class="fa fa-print"></i> Print</a>
                <a class="btn btn-primary btn-sm" href="{{ route('loan.ledger.statement', request()->query()) }}"><i class="fa fa-file-text-o"></i> Statement</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped" id="loan_ledger_table">
                <thead>
                    <tr>
                        <th>Transaction Date</th>
                        <th>System Entered Date & Time</th>
                        <th>Reference No</th>
                        <th>Description</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->transaction_date }}</td>
                            <td>{{ $row->system_date }}</td>
                            <td>{{ $row->reference_no }}</td>
                            <td>{{ $row->description }}</td>
                            <td class="text-right">{{ number_format($row->debit, 2) }}</td>
                            <td class="text-right">{{ number_format($row->credit, 2) }}</td>
                            <td class="text-right"><strong>{{ number_format($row->balance, 2) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No ledger entries found.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Total</th>
                        <th class="text-right">{{ number_format($summary->total_debit, 2) }}</th>
                        <th class="text-right">{{ number_format($summary->total_credit, 2) }}</th>
                        <th class="text-right">{{ number_format($summary->balance, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
$(document).ready(function(){
    if ($.fn.select2) { $('.select2').select2({width: '100%'}); }
    if ($.fn.DataTable) {
        $('#loan_ledger_table').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            responsive: true,
            dom: 'Bfrtip',
            buttons: ['csv', 'excel', 'pdf', 'print', 'colvis']
        });
    }
});
</script>
@endsection

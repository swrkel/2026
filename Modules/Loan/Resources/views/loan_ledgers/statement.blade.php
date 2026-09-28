@extends('layouts.app')

@section('title', 'Loan Statement')

@section('content')
<section class="content-header">
    <h1>Loan Statement <small>Customer / loan wise statement</small></h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-file-text-o"></i> Statement</h3>
            <div class="box-tools pull-right">
                <a href="{{ route('loan.ledger.index', request()->query()) }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back to Ledger</a>
                <a href="{{ route('loan.ledger.print', request()->query()) }}" target="_blank" class="btn btn-primary btn-sm"><i class="fa fa-print"></i> Print</a>
            </div>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-4"><strong>Statement Date:</strong> {{ date('Y-m-d H:i') }}</div>
                <div class="col-md-4"><strong>From:</strong> {{ $filters['date_from'] ?? '-' }}</div>
                <div class="col-md-4"><strong>To:</strong> {{ $filters['date_to'] ?? '-' }}</div>
            </div>
            <hr>
            @include('loan::loan_ledgers.partials_statement_table', ['rows' => $rows, 'summary' => $summary])
        </div>
    </div>
</section>
@endsection

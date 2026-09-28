@extends('layouts.app')
@section('title', 'Loan Arrears Aging')

@section('content')
<section class="content-header no-print"><h1>Loan Arrears Aging <small>Location-aware monitoring</small></h1></section>
<section class="content no-print">
    <div class="box box-danger">
        <div class="box-header with-border"><h3 class="box-title">Aging Buckets</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>Bucket</th><th class="text-right">Accounts</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                    @foreach($aging as $bucket => $row)
                        <tr>
                            <td>{{ str_replace('_', '-', $bucket) }} days</td>
                            <td class="text-right">{{ number_format($row['count'] ?? 0) }}</td>
                            <td class="text-right">{{ number_format($row['amount'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

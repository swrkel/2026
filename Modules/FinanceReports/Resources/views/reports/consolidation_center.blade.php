@extends('layouts.app')
@section('title', 'Consolidation Center - New')
@section('content')
<section class="content-header"><h1>Consolidation Center - New</h1></section>
<section class="content">
    @include('financereports::layouts.filter', ['locations' => $locations, 'location_id' => $context->location_id, 'start' => $context->start_date, 'end' => $context->end_date])
    @include('financereports::layouts.toolbar')
    <div class="box box-primary"><div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Branch / Location</th><th class="text-right">Income</th><th class="text-right">Expenses</th><th class="text-right">Net Profit</th><th class="text-right">Assets</th><th class="text-right">Liabilities</th><th class="text-right">Equity</th></tr></thead>
            <tbody>
                @foreach($report['rows'] as $row)
                    <tr>
                        <td>{{ $row['location'] }}</td>
                        <td class="text-right">{{ number_format($row['income'], 4) }}</td>
                        <td class="text-right">{{ number_format($row['expenses'], 4) }}</td>
                        <td class="text-right">{{ number_format($row['net_profit'], 4) }}</td>
                        <td class="text-right">{{ number_format($row['assets'], 4) }}</td>
                        <td class="text-right">{{ number_format($row['liabilities'], 4) }}</td>
                        <td class="text-right">{{ number_format($row['equity'], 4) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot><tr><th>Consolidated Total</th><th class="text-right">{{ number_format($report['totals']['income'], 4) }}</th><th class="text-right">{{ number_format($report['totals']['expenses'], 4) }}</th><th class="text-right">{{ number_format($report['totals']['net_profit'], 4) }}</th><th class="text-right">{{ number_format($report['totals']['assets'], 4) }}</th><th class="text-right">{{ number_format($report['totals']['liabilities'], 4) }}</th><th class="text-right">{{ number_format($report['totals']['equity'], 4) }}</th></tr></tfoot>
        </table>
        <p><strong>Balance Sheet Difference:</strong> {{ number_format($report['difference'], 4) }}</p>
    </div></div>
</section>
@endsection

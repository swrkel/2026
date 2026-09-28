@extends('stocktakingnew::layouts.app')
@section('stk_title', 'Stock Accuracy Report')
@section('stk_content')
<section class="stk-card">
    <form method="get" class="stk-report-filter">@include('stocktakingnew::partials.report_filters')</form>
    <div class="table-responsive">
        <table class="stk-table">
            <thead>
                <tr><th>No</th><th>Date</th><th>Products</th><th>System Qty</th><th>Counted Qty</th><th>Variance Qty</th><th>Variance Value</th><th>Qty Accuracy</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php($accuracy = $row->system_qty_total != 0
                        ? max(0, 100 - (abs((float) $row->variance_qty_total) / abs((float) $row->system_qty_total) * 100))
                        : ($row->variance_qty_total == 0 ? 100 : 0))
                    <tr>
                        <td>{{ $row->stock_take_no }}</td>
                        <td>{{ optional($row->count_date)->format('d M Y') }}</td>
                        <td class="text-right">{{ $row->line_count }}</td>
                        <td class="text-right">{{ number_format((float) $row->system_qty_total, 4) }}</td>
                        <td class="text-right">{{ number_format((float) $row->counted_qty_total, 4) }}</td>
                        <td class="text-right">{{ number_format((float) $row->variance_qty_total, 4) }}</td>
                        <td class="text-right">{{ number_format((float) $row->variance_value_total, 4) }}</td>
                        <td><strong>{{ number_format($accuracy, 2) }}%</strong><div class="stk-progress"><span style="width:{{ min(100, $accuracy) }}%"></span></div></td>
                        <td>@include('stocktakingnew::partials.status', ['status' => $row->status])</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="stk-empty">No completed counts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rows->links() }}
</section>
@endsection

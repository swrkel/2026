@extends('stocktakingnew::layouts.app')
@section('stk_title', 'Counting Progress Report')
@section('stk_content')
<section class="stk-card">
    <form method="get" class="stk-report-filter">@include('stocktakingnew::partials.report_filters')</form>
    <div class="table-responsive">
        <table class="stk-table">
            <thead>
                <tr><th>No</th><th>Date</th><th>Title</th><th>Method</th><th>Total Lines</th><th>Counted</th><th>Pending</th><th>Completion</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php($percentage = $row->line_count ? round($row->counted_line_count / $row->line_count * 100, 2) : 0)
                    <tr>
                        <td><a href="{{ route('stock-taking-new.sessions.show', $row) }}">{{ $row->stock_take_no }}</a></td>
                        <td>{{ optional($row->count_date)->format('d M Y') }}</td>
                        <td>{{ $row->title }}</td>
                        <td>{{ ucfirst($row->count_method) }}</td>
                        <td class="text-right">{{ $row->line_count }}</td>
                        <td class="text-right">{{ $row->counted_line_count }}</td>
                        <td class="text-right">{{ max(0, $row->line_count - $row->counted_line_count) }}</td>
                        <td><div class="stk-progress"><span style="width:{{ $percentage }}%"></span></div><small>{{ number_format($percentage, 2) }}%</small></td>
                        <td>@include('stocktakingnew::partials.status', ['status' => $row->status])</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="stk-empty">No sessions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rows->links() }}
</section>
@endsection

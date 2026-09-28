@extends('layouts.app')
@section('title', __('distributionnew::lang.sales_kpi_report'))
@section('content')
<section class="content-header"><h1>{{ __('distributionnew::lang.sales_kpi_report') }}</h1></section>
<section class="content"><div class="box disnew-pos-card"><div class="box-body table-responsive">
  <div class="well well-sm"><strong>Toolbar:</strong> Search | Date Range | CSV | Excel | PDF | Print | Column Visibility</div>
  <table class="table table-bordered table-striped disnew-datatable"><thead><tr><th>ID</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead><tbody>@isset($rows) @foreach($rows as $row)<tr><td>{{ $row->id }}</td><td>{{ $row->snapshot_date ?? $row->period_start ?? $row->commission_date ?? '' }}</td><td>{{ $row->net_sales ?? $row->target_amount ?? $row->commission_amount ?? '' }}</td><td>{{ $row->status ?? '' }}</td></tr>@endforeach @endisset</tbody></table>
  @isset($rows) {{ $rows->links() }} @endisset
</div></div></section>
@endsection

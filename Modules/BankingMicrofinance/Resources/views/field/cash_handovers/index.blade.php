@extends('bankingmicrofinance::layouts.page')
@section('bkg_mfi_content')
<h4>Cash Handovers</h4>
<div class="bkg-mfi-toolbar"><input class="form-control input-sm" placeholder="Search"><button class="btn btn-primary btn-sm">CSV</button><button class="btn btn-success btn-sm">Excel</button><button class="btn btn-danger btn-sm">PDF</button><button class="btn btn-default btn-sm">Print</button><button class="btn btn-info btn-sm">Column Visibility</button></div>
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Officer</th><th>Center</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse(($rows ?? []) as $row)<tr><td>{{ $row->created_at ?? '' }}</td><td>{{ $row->officer_name ?? '' }}</td><td>{{ $row->center_name ?? '' }}</td><td>{{ number_format((float)($row->total_amount ?? $row->amount ?? 0), 4) }}</td><td>{{ $row->status ?? '' }}</td><td><button class="btn btn-xs btn-primary">Action</button></td></tr>@empty<tr><td colspan="6">No records found.</td></tr>@endforelse</tbody></table>
@endsection

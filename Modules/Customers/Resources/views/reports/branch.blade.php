@extends('layouts.app')
@section('title', __('customers::lang.customer_branch_report'))
@section('content')
<section class="content-header"><h1>@lang('customers::lang.customer_branch_report')</h1><small>@lang('customers::lang.customer_branch_report_subtitle')</small></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">@lang('customers::lang.customers_by_branch')</h3></div>
        <div class="box-body table-responsive">
            @if(empty($branchColumn))
                <div class="alert alert-warning">@lang('customers::lang.branch_column_missing_note')</div>
            @endif
            <table class="table table-bordered table-striped">
                <thead><tr><th>@lang('customers::lang.branch_location')</th><th class="text-right">@lang('customers::lang.total')</th></tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr><td>{{ $row->location_name }}</td><td class="text-right">{{ number_format($row->total) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted">@lang('customers::lang.no_records_found')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

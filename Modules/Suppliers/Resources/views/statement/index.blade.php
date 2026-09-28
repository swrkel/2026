@extends('suppliers::layouts.app')

@section('title', __('suppliers::lang.supplier_statement'))

@section('suppliers_content')
<section class="content-header">
    <h1>@lang('suppliers::lang.supplier_statement')</h1>
</section>
<section class="content">
    @include('suppliers::ledger.partials.filters')
    @include('suppliers::ledger.partials.summary-cards', ['summary' => $statement['summary']])
    <div class="box box-primary">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped supplier-statement-table">
                <thead>
                    <tr>
                        <th>@lang('suppliers::lang.date')</th>
                        <th>@lang('suppliers::lang.type')</th>
                        <th>@lang('suppliers::lang.reference')</th>
                        <th>@lang('suppliers::lang.business_location')</th>
                        <th>@lang('suppliers::lang.description')</th>
                        <th class="text-right">@lang('suppliers::lang.debit')</th>
                        <th class="text-right">@lang('suppliers::lang.credit')</th>
                        <th class="text-right">@lang('suppliers::lang.balance')</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statement['rows'] as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td><td>{{ $row['type'] }}</td><td>{{ $row['reference'] }}</td><td>{{ $row['location'] }}</td><td>{{ $row['description'] }}</td>
                            <td class="text-right">{{ number_format($row['debit'], config('suppliers.currency_precision', 2)) }}</td>
                            <td class="text-right">{{ number_format($row['credit'], config('suppliers.currency_precision', 2)) }}</td>
                            <td class="text-right">{{ number_format($row['balance'], config('suppliers.currency_precision', 2)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">@lang('suppliers::lang.no_records_found')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('modules/suppliers/js/suppliers/statement/index.js') }}"></script>
@endsection

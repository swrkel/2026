@extends('suppliers::layouts.app')

@section('title', __('suppliers::lang.supplier_aging'))

@section('suppliers_content')
<section class="content-header"><h1>@lang('suppliers::lang.supplier_aging')</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead><tr><th>@lang('suppliers::lang.current')</th><th>1-30</th><th>31-60</th><th>61-90</th><th>90+</th><th>@lang('suppliers::lang.total')</th></tr></thead>
                <tbody><tr>
                    <td class="text-right">{{ number_format($aging['current'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($aging['days_1_30'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($aging['days_31_60'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($aging['days_61_90'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($aging['over_90'] ?? 0, 2) }}</td>
                    <td class="text-right">{{ number_format($aging['total'] ?? 0, 2) }}</td>
                </tr></tbody>
            </table>
        </div>
    </div>
</section>
@endsection

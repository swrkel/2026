@extends('stockadjustmentnew::layouts.app')

@section('san_title', isset($reportMode) ? 'Stock Adjustment Report' : 'Stock Adjustments')

@section('san_content')
@php($qtyDecimals = (int) ($settings['quantity_decimals'] ?? 4))
<div class="san-toolbar san-adjustments-toolbar">
    @unless(isset($reportMode))
        <a class="btn btn-success" href="{{ route('stock-adjustment-new.adjustments.create') }}">
            Create Adjustment
        </a>
    @endunless

    <input
        type="search"
        class="form-control"
        placeholder="Search"
        aria-label="Search stock adjustments"
        data-san-adjustment-search
    >

    <button type="button" class="btn btn-default" data-san-export-table="csv">CSV</button>
    <button type="button" class="btn btn-default" data-san-export-table="excel">Excel</button>
    <button type="button" class="btn btn-default" data-san-export-table="pdf">PDF</button>
    <button type="button" class="btn btn-default" data-san-print-table>Print</button>
    <button type="button" class="btn btn-default" data-san-column-visibility>Column Visibility</button>
</div>

<div class="san-panel san-adjustments-panel">
    <div class="table-responsive">
        <table
            id="san-adjustments-table"
            class="table table-bordered table-hover"
            data-san-adjustments-table
        >
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Adjustment No</th>
                    <th>Type</th>
                    <th>Adjustment Type</th>
                    <th>Location</th>
                    <th>Store</th>
                    <th>Total Items</th>
                    <th>Total Quantity</th>
                    <th>Created By</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($adjustments as $row)
                    <tr>
                        <td data-order="{{ optional($row->adjustment_date)->format('Y-m-d') }}">
                            {{ optional($row->adjustment_date)->format('Y-m-d') ?: '—' }}
                        </td>
                        <td>{{ $row->adjustment_no ?: '—' }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', (string) $row->adjustment_type)) }}</td>
                        <td>{{ ucfirst((string) ($row->stock_adjustment_type ?: 'increase')) }}</td>
                        <td>{{ $row->location_id ?: 'All' }}</td>
                        <td>{{ $row->store_id ?: 'All' }}</td>
                        <td class="text-right">{{ (int) ($row->lines_count ?? 0) }}</td>
                        <td class="text-right">{{ number_format((float) $row->total_qty, $qtyDecimals) }}</td>
                        <td>{{ $row->created_by ?: '—' }}</td>
                        <td>
                            <a
                                href="{{ route('stock-adjustment-new.adjustments.show', $row) }}"
                                class="btn btn-xs btn-primary"
                            >
                                View
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if(method_exists($adjustments, 'links'))
    {{ $adjustments->links() }}
@endif
@endsection

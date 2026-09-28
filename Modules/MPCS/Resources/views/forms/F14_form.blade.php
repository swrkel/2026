@extends('layouts.app')
@section('title', __('mpcs::lang.F14_form'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1> @lang('mpcs::lang.F14_form')
        <small>@lang( 'mpcs::lang.F14_form', ['contacts' => __('mpcs::lang.mange_F14_form') ])</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            {!! Form::open(['url' => url('/mpcs/F14'), 'method' => 'get', 'id' => 'f14_report_filter']) !!}
            <div class="col-md-4">
                <div class="form-group f14-filter-field">
                    <label for="f14_start_date">@lang('report.date_range'):</label>
                    <div class="f14-date-range-fields">
                        <input type="date"
                               name="f14_start_date"
                               id="f14_start_date"
                               class="form-control f14-native-control"
                               value="{{ $start_date }}"
                               autocomplete="off">
                        <span class="f14-date-separator">to</span>
                        <input type="date"
                               name="f14_end_date"
                               id="f14_end_date"
                               class="form-control f14-native-control"
                               value="{{ $end_date }}"
                               autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group f14-filter-field">
                    <label for="f14_location_id">@lang('purchase.business_location'):</label>
                    <select name="f14_location_id"
                            id="f14_location_id"
                            class="form-control f14-native-control"
                            data-no-select2="1"
                            autocomplete="off">
                        @foreach($business_locations as $id => $name)
                            <option value="{{ $id }}" {{ (string) $location_id === (string) $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-2" style="margin-top: 25px">
                <button type="submit" id="submit_btn" class="btn btn-primary">
                    @lang('report.apply_filters')
                </button>
            </div>
            {!! Form::close() !!}
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            @slot('tool')
                <button type="button" class="btn btn-primary pull-right" id="print_f14_bills">
                    <i class="fa fa-print"></i> @lang('messages.print')
                </button>
            @endslot

            <div class="f14-bills-grid f14-a4-sheet" id="f14_print_area">
                @forelse($credit_sales as $sale)
                    @php
                        $stationName = $sale->location ?: $business_name;
                        $orderNo = $sale->order_no ?: ($sale->invoice_no ?: '-');
                        $ourReference = $sale->settlement_reference
                            ?: ($sale->our_ref ?: ($sale->invoice_no ?: '-'));
                        $voucherNo = $sale->voucher_no ?: $orderNo;
                    @endphp
                    <article class="f14-bill-card">
                        <div class="f14-bill-heading">
                            <div class="f14-station-name">{{ $stationName }}</div>
                            <div>@lang('mpcs::lang.F14_form')</div>
                            <div class="f14-form-mark">F14</div>
                        </div>

                        <div class="f14-bill-details">
                            <div><b>@lang('mpcs::lang.date'):</b> {{ $sale->settlement_date_display }}</div>
                            <div><b>@lang('mpcs::lang.bill_no'):</b> {{ $sale->credit_bill_id }}</div>
                            <div><b>@lang('mpcs::lang.customer'):</b> {{ $sale->customer ?: '-' }}</div>
                            <div><b>@lang('mpcs::lang.order_no'):</b> {{ $orderNo }}</div>
                            <div><b>@lang('mpcs::lang.vehicle_no'):</b> {{ $sale->customer_reference ?: '-' }}</div>
                            <div><b>@lang('mpcs::lang.our_ref'):</b> {{ $ourReference }}</div>
                            <div class="f14-detail-wide"><b>@lang('mpcs::lang.tel'):</b> {{ $sale->tel ?: '-' }}</div>
                        </div>

                        <div class="f14-bill-table-wrap">
                            <table class="f14-bill-table">
                                <colgroup>
                                    <col style="width:18%">
                                    <col style="width:14%">
                                    <col style="width:28%">
                                    <col style="width:18%">
                                    <col style="width:22%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>@lang('mpcs::lang.voucher_no')</th>
                                        <th class="text-right">@lang('mpcs::lang.balance_qty')</th>
                                        <th>@lang('mpcs::lang.description')</th>
                                        <th class="text-right">@lang('mpcs::lang.unit_price')</th>
                                        <th class="text-right">@lang('mpcs::lang.amount')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sale->lines as $line)
                                        <tr>
                                            <td>{{ $line->voucher_no ?: $voucherNo }}</td>
                                            <td class="text-right">{{ $line->balance_qty }}</td>
                                            <td>{{ $line->description ?: '-' }}</td>
                                            <td class="text-right">{{ $line->unit_price }}</td>
                                            <td class="text-right">{{ $line->line_total }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-right">@lang('mpcs::lang.total_amount')</th>
                                        <th class="text-right">{{ $sale->final_total }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </article>
                @empty
                    <div class="f14-no-data text-center">@lang('lang_v1.no_data')</div>
                @endforelse
            </div>

            @if($credit_sales->hasPages())
                @php
                    $currentPage = $credit_sales->currentPage();
                    $lastPage = $credit_sales->lastPage();
                    $firstVisiblePage = max(1, $currentPage - 2);
                    $lastVisiblePage = min($lastPage, $currentPage + 2);
                @endphp
                <div class="f14-pagination-row">
                    <div class="f14-pagination-summary">
                        Showing {{ $credit_sales->firstItem() }}–{{ $credit_sales->lastItem() }}
                        of {{ $credit_sales->total() }} bills
                    </div>
                    <nav aria-label="F14 bill pages">
                        <ul class="pagination f14-pagination">
                            <li class="{{ $credit_sales->onFirstPage() ? 'disabled' : '' }}">
                                @if($credit_sales->onFirstPage())
                                    <span aria-hidden="true">&laquo;</span>
                                @else
                                    <a href="{{ $credit_sales->previousPageUrl() }}" aria-label="Previous">&laquo;</a>
                                @endif
                            </li>

                            @for($pageNumber = $firstVisiblePage; $pageNumber <= $lastVisiblePage; $pageNumber++)
                                <li class="{{ $pageNumber === $currentPage ? 'active' : '' }}">
                                    @if($pageNumber === $currentPage)
                                        <span>{{ $pageNumber }}</span>
                                    @else
                                        <a href="{{ $credit_sales->url($pageNumber) }}">{{ $pageNumber }}</a>
                                    @endif
                                </li>
                            @endfor

                            <li class="{{ $credit_sales->hasMorePages() ? '' : 'disabled' }}">
                                @if($credit_sales->hasMorePages())
                                    <a href="{{ $credit_sales->nextPageUrl() }}" aria-label="Next">&raquo;</a>
                                @else
                                    <span aria-hidden="true">&raquo;</span>
                                @endif
                            </li>
                        </ul>
                    </nav>
                </div>
            @endif
            @endcomponent
        </div>
    </div>

</section>
<!-- /.content -->

@endsection
@section('javascript')
<style>
    .f14-filter-field .f14-native-control {
        width: 100% !important;
        min-width: 0 !important;
        height: 34px;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
    .f14-date-range-fields {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        align-items: center;
        gap: 8px;
    }
    .f14-date-separator {
        white-space: nowrap;
        font-weight: 600;
    }
    #f14_report_filter .select2-container {
        display: none !important;
    }
    .f14-bills-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        grid-auto-rows: minmax(0, 1fr);
        gap: 4mm;
        align-items: stretch;
    }
    .f14-a4-sheet {
        width: 100%;
        max-width: 297mm;
        min-height: 190mm;
        margin: 0 auto;
        padding: 6mm;
        box-sizing: border-box;
        background: #fff;
        box-shadow: 0 1px 8px rgba(0, 0, 0, 0.12);
    }
    .f14-bill-card {
        position: relative;
        width: 100%;
        min-width: 0;
        max-width: 100%;
        border: 1px solid #555;
        background: #fff;
        padding: 8px;
        overflow: hidden !important;
        contain: layout paint;
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .f14-bill-heading {
        position: relative;
        min-height: 52px;
        margin-bottom: 8px;
        text-align: center;
        line-height: 1.45;
        font-size: 12px;
    }
    .f14-station-name {
        font-weight: 700;
    }
    .f14-form-mark {
        position: absolute;
        top: 0;
        right: 0;
        font-weight: 600;
    }
    .f14-bill-details {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 4px 8px;
        margin-bottom: 8px;
        font-size: 11px;
    }
    .f14-detail-wide {
        grid-column: 1 / -1;
    }
    .f14-bill-table-wrap {
        display: block !important;
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        min-height: 0 !important;
        margin: 0 !important;
        overflow: hidden !important;
        border: 0 !important;
        contain: inline-size;
    }
    .f14-bill-table {
        display: table !important;
        width: 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        border-collapse: collapse !important;
        border-spacing: 0 !important;
        margin: 0 !important;
        table-layout: fixed !important;
        font-size: 8px !important;
        line-height: 1.05 !important;
    }
    .f14-bill-table > thead > tr > th,
    .f14-bill-table > tbody > tr > td,
    .f14-bill-table > tfoot > tr > th {
        padding: 2px 2px !important;
        width: auto !important;
        min-width: 0 !important;
        max-width: none !important;
        border: 1px solid #777 !important;
        vertical-align: middle;
        overflow: hidden;
        text-overflow: ellipsis;
        overflow-wrap: anywhere;
    }
    .f14-bill-table > thead > tr > th {
        background: #f1f4f7;
        white-space: normal;
        word-break: normal;
        overflow-wrap: break-word;
        text-align: center;
        font-size: 7.5px !important;
        line-height: 1.05 !important;
        padding: 2px 1px !important;
    }
    .f14-bill-table > tbody > tr > td:not(:nth-child(3)),
    .f14-bill-table > tfoot > tr > th {
        white-space: nowrap;
    }
    .f14-bill-table > tbody > tr > td:nth-child(3) {
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: normal;
        text-overflow: clip;
    }
    .f14-no-data {
        grid-column: 1 / -1;
        padding: 30px;
    }
    .f14-pagination-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 18px;
        flex-wrap: wrap;
    }
    .f14-pagination-summary {
        color: #5f6b76;
        font-size: 13px;
    }
    .f14-pagination {
        margin: 0;
    }
    @media (max-width: 900px) {
        .f14-date-range-fields {
            grid-template-columns: 1fr;
        }
        .f14-date-separator {
            display: none;
        }
        .f14-bills-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .f14-a4-sheet {
            min-height: auto;
        }
    }
    @media (max-width: 600px) {
        .f14-bills-grid {
            grid-template-columns: 1fr;
        }
    }
    @media print {
        @page {
            size: A4 landscape;
            margin: 0;
        }
        body * {
            visibility: hidden;
        }
        #f14_print_area,
        #f14_print_area * {
            visibility: visible;
        }
        #f14_print_area {
            position: absolute;
            top: 0;
            left: 0;
            width: 297mm;
            max-width: 297mm;
            height: 210mm;
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            grid-template-rows: repeat(3, minmax(0, 1fr));
            grid-auto-flow: row;
            gap: 4mm;
            padding: 8mm;
            margin: 0;
            box-sizing: border-box;
            box-shadow: none;
            align-items: stretch;
            align-content: stretch;
            overflow: hidden;
        }
        .f14-bill-card {
            min-height: 0;
            padding: 2.5mm;
            margin: 0;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .f14-bill-heading {
            min-height: 11mm;
            margin-bottom: 1.5mm;
            line-height: 1.25;
            font-size: 10pt;
        }
        .f14-bill-details {
            gap: 0.8mm 1.5mm;
            margin-bottom: 1.5mm;
            font-size: 8.5pt;
            line-height: 1.18;
        }
        .f14-bill-table {
            font-size: 7.8pt;
            line-height: 1.12;
        }
        .f14-bill-table > thead > tr > th,
        .f14-bill-table > tbody > tr > td,
        .f14-bill-table > tfoot > tr > th {
            padding: 1mm 0.6mm;
        }
    }
</style>
<script type="text/javascript">
    $(document).ready(function() {
        var filterForm = document.getElementById('f14_report_filter');
        var $location = $('#f14_location_id');

        // F14 uses native controls intentionally. Remove any Select2 instance and
        // direct change handlers added by global report scripts so a selection is
        // never cleared or submitted before the user clicks Apply Filters.
        if ($location.hasClass('select2-hidden-accessible') && $.fn.select2) {
            $location.select2('destroy');
        }
        $location.removeClass('select2 select2-hidden-accessible').removeAttr('data-select2-id');
        $location.siblings('.select2-container').remove();
        $('#f14_start_date, #f14_end_date, #f14_location_id').off('change input');

        if (filterForm) {
            filterForm.addEventListener('change', function(event) {
                event.stopPropagation();
            }, true);
            filterForm.addEventListener('input', function(event) {
                event.stopPropagation();
            }, true);
        }

        $('#f14_report_filter').on('submit.f14', function() {
            var startDate = $('#f14_start_date').val();
            var endDate = $('#f14_end_date').val();
            if (!startDate || !endDate) {
                toastr.error('Please select a date range.');
                return false;
            }
            $('#submit_btn').prop('disabled', true);
        });

        $('#print_f14_bills').on('click', function() {
            var source = document.getElementById('f14_print_area');
            if (!source) return;

            var printWindow = window.open('', '_blank', 'width=1200,height=850');
            if (!printWindow) {
                window.print();
                return;
            }
            printWindow.document.open();
            printWindow.document.write(`<!doctype html><html><head><title>F14</title><style>
                @page { size:A4 landscape; margin:6mm; }
                html,body { margin:0; padding:0; font-family:Arial,sans-serif; color:#000; }
                * { box-sizing:border-box; }
                .f14-bills-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); grid-template-rows:repeat(3,minmax(0,1fr)); gap:3mm; width:100%; height:198mm; overflow:hidden; }
                .f14-bill-card { border:1px solid #444; padding:2mm; min-width:0; overflow:hidden; break-inside:avoid; }
                .f14-bill-heading { position:relative; text-align:center; min-height:10mm; margin-bottom:1mm; font-size:9.5pt; line-height:1.15; }
                .f14-station-name { font-weight:700; }
                .f14-form-mark { position:absolute; top:0; right:0; font-weight:700; }
                .f14-bill-details { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.6mm 1mm; margin-bottom:1mm; font-size:8pt; line-height:1.08; }
                .f14-detail-wide { grid-column:1/-1; }
                table { width:100%; border-collapse:collapse; table-layout:fixed; font-size:6.5pt; line-height:1.05; }
                th,td { border:1px solid #555; padding:.4mm .35mm; vertical-align:middle; overflow-wrap:normal; word-break:normal; }
                th { text-align:center; font-weight:700; font-size:6.2pt; line-height:1.05; white-space:normal; }
                tbody td:not(:nth-child(3)),tfoot th { white-space:nowrap; }
                tbody td:nth-child(3) { overflow-wrap:break-word; }
                .text-right { text-align:right; }
                .f14-no-data { grid-column:1/-1; text-align:center; }
            </style></head><body>${source.outerHTML}</body></html>`);
            printWindow.document.close();
            printWindow.focus();
            printWindow.onload = function() { printWindow.print(); };
            printWindow.onafterprint = function() { printWindow.close(); };
        });
    });
</script>
@endsection

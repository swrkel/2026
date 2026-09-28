{{-- Modified by Engr. Alex -- task 7882: Issue 4 - fix column headers/order, BILL REF format, Qty no separation line, total row; Issue 6 - report footer --}}
<div class="row" style="margin-bottom: 15px;">
    <div class="col-md-6 col-sm-12">
        <div class="form-group">
            {!! Form::label('pumper_details_date_range', __('report.date_range') . ':') !!}
            {!! Form::text('pumper_details_date_range', null, [
                'class' => 'form-control',
                'id' => 'pumper_details_date_range',
                'readonly',
                'placeholder' => __('lang_v1.select_a_date_range'),
            ]) !!}
        </div>
        <p class="text-muted" id="report_date_range">Date Range: —</p>
    </div>
    <div class="col-md-6 col-sm-12 text-right" style="padding-top: 24px;">
        <button type="button" class="btn btn-primary" id="lioc_save_statement">
            <i class="fa fa-save"></i> @lang('messages.save')
        </button>
        <button type="button" class="btn btn-success" id="lioc_save_and_print_statement">
            <i class="fa fa-print"></i> Save and Print
        </button>
    </div>
</div>

<div class="row">
    <div class="top-header">
        <div class="business-header">
            <h2>{{ $business->name ?? 'Business Name' }}</h2>
            <div>
                @if($location)
                    {{ $location->landmark ?? $location->address_1 ?? '' }}
                    @if($location->city || $location->state || $location->country)
                        {{ ', ' . implode(', ', array_filter([$location->city, $location->state, $location->country])) }}
                    @endif
                @else
                    Business Address
                @endif
            </div>
            <div>T.P : {{ $location->mobile ?? $location->alternate_number ?? '' }}</div>
        </div>
    </div>

    <div class="row" style="margin-bottom: 20px;">
        <div class="col-md-12 table-responsive">
            <table class="invoice-table" id="sales-table">
                <thead>
                    <tr>
                        <th colspan="2" style="text-align: left; border-right: none;">
                            BILL REF: <span id="lioc-bill-ref-text">{{ trim(($settings->prefix ?? '') . ' ' . ($settings->start_number ?? '')) }} ({{ $settings->constant_value ?? '' }})</span>
                        </th>
                        <th colspan="4" style="text-align: right; border-left: none;">
                            PERIOD: <span id="period-display">{{ $period_display ?? '-' }}</span>
                        </th>
                    </tr>
                    <tr>
                        <th class="invoice-index">S/No</th>
                        <th>Name</th>
                        <th class="invoice-qty">Amount</th>
                        <th>Order Date</th>
                        <th>Vehicle/Order No</th>
                        <th class="lioc-col-description">Description</th>
                    </tr>
                </thead>
                <tbody id="sales-table-tbody">
                    @if(!empty($final_sells) && $final_sells->count())
                        @include('reportscustomized::partials.sales_table_rows')
                    @endif
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold">
                        <td>Total</td>
                        <td class="text-right">{{ $settings->constant_value ?? '' }}</td>
                        <td class="text-right invoice-amount" id="total-amount-cell">
                            @if(isset($total_amount)){{ number_format($total_amount, 2) }}@endif
                        </td>
                        <td id="lioc-tfoot-order-date">{{ isset($final_sells) && $final_sells->count() ? \Carbon\Carbon::parse($final_sells->last()->transaction_date)->format('d.m.Y') : '' }}</td>
                        <td id="lioc-tfoot-vehicle">{{ ($settings->prefix ?? '') }} {{ date('Y') }}/{{ $settings->start_number ?? '' }}</td>
                        <td class="lioc-col-description" id="lioc-tfoot-description">{{ trim(($settings->prefix ?? '') . ' ' . ($settings->start_number ?? '')) }}@if(!empty($settings->constant_value)) ({{ $settings->constant_value }})@endif</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="row" style="margin-top: 40px;">
        <div class="col-md-12 text-center">
            <p><strong>Manager Signature</strong></p>
        </div>
    </div>

    @if(!empty($report_footer))
    {{-- Modified by Engr. Alex -- task 7882: Issue 6 - show report footer from Super Admin Application Settings (admin_reports_footer) --}}
    <div class="row" style="margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px;">
        <div class="col-md-12 text-center" style="color: red;">
            {!! $report_footer !!}
        </div>
    </div>
    @endif
</div>
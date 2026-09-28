<div class="f15-daily-page" id="f15_daily_page">
    @include('mpcs::forms.partials.f15_daily_report_styles')

    <div class="f15-toolbar no-print">
        <div class="f15-toolbar-title">
            <h3>F15 Daily Report - New</h3>
            <span>Daily purchases, stock values, sales and deductions</span>
        </div>
        <div class="f15-toolbar-controls">
            <div class="form-group">
                <label for="f15_daily_location">Business Location</label>
                {!! Form::select('location_id', $locations, $selectedLocationId, [
                    'class' => 'form-control select2',
                    'id' => 'f15_daily_location',
                    'required' => true,
                ]) !!}
            </div>
            <div class="form-group">
                <label for="f15_daily_date">Date</label>
                <input type="date" class="form-control" id="f15_daily_date" value="{{ $selectedDate }}" required>
            </div>
            <button type="button" class="btn btn-primary" id="f15_daily_load">
                <i class="fa fa-refresh"></i> Load
            </button>
            <button type="button" class="btn btn-success" id="f15_daily_save">
                <i class="fa fa-save"></i> Save Report
            </button>
            <button type="button" class="btn btn-info" id="f15_daily_print">
                <i class="fa fa-print"></i> Print Document
            </button>
        </div>
    </div>

    <div class="f15-reset-note no-print" id="f15_reset_note" style="display:none;">
        F22 was saved on this date. The Previous Day column has therefore been reset to 0.00.
    </div>

    <div class="f15-report-sheet" id="f15_daily_print_area" aria-busy="false">
        <div class="f15-report-header">
            {{-- IS2029: empty spacer, mirroring the meta column on the right so
                 the heading between them is genuinely centred on the sheet. --}}
            <div class="f15-report-spacer" aria-hidden="true"></div>
            <div class="f15-report-heading">
                <h2 id="f15_location_name">Business Location Name</h2>
                <h3>Daily Report</h3>
            </div>
            <div class="f15-report-meta">
                <button type="button" class="btn btn-primary no-print" id="f15_header_print">Print Document</button>
                <div><strong>Date:</strong> <span id="f15_report_date">{{ $selectedDate }}</span></div>
                <div><strong>F 15 No:</strong> <span id="f15_form_no">-</span></div>
            </div>
        </div>

        @php
            $rows = [
                ['section' => 'Purchases & Additions'],
                ['no' => 1, 'key' => 'f18_oil_purchase', 'description' => 'F 18 Oil Purchase'],
                ['no' => 2, 'key' => 'f18_gas_purchase', 'description' => 'F 18 Gas Purchase'],
                ['no' => 3, 'key' => 'oil_purchase', 'description' => 'Oil Purchase'],
                ['no' => 4, 'key' => 'gas_purchase', 'description' => 'Gas Purchase'],
                ['no' => 5, 'key' => 'purchase_subtotal', 'description' => 'Sub Total (1 + 2 + 3 + 4)', 'class' => 'f15-total-row'],
                ['no' => 6, 'key' => 'price_increment', 'description' => 'Price Increment'],
                ['no' => 7, 'key' => 'changes_addition', 'description' => 'Changes', 'manual' => true],
                ['no' => 8, 'key' => 'purchases_total', 'description' => 'Total (5 + 6 + 7)', 'class' => 'f15-total-row'],
                ['no' => 9, 'key' => 'oil_opening_stock', 'description' => 'Oil Opening Stock'],
                ['no' => 10, 'key' => 'gas_opening_stock', 'description' => 'Gas Opening Stock'],
                ['no' => 11, 'key' => 'section_grand_total', 'description' => 'Section Grand Total (8 + 9 + 10)', 'class' => 'f15-grand-row'],
                ['section' => 'Sales & Deductions'],
                ['no' => 12, 'key' => 'oil_cash_sale', 'description' => 'Oil Cash Sale'],
                ['no' => 13, 'key' => 'gas_cash_sale', 'description' => 'Gas Cash Sale'],
                ['no' => 14, 'key' => 'oil_credit_sale', 'description' => 'Oil Credit Sale'],
                ['no' => 15, 'key' => 'gas_credit_sale', 'description' => 'Gas Credit Sale'],
                ['no' => 16, 'key' => 'sales_subtotal', 'description' => 'Total (12 + 13 + 14 + 15)', 'class' => 'f15-total-row'],
                ['no' => 17, 'key' => 'changes_deduction', 'description' => 'Changes - 18 F', 'manual' => true],
                ['no' => 18, 'key' => 'price_reduction', 'description' => 'Price Reduction'],
                ['no' => 19, 'key' => 'damaged', 'description' => 'Damaged', 'manual' => true],
                ['no' => 20, 'key' => 'others', 'description' => 'Others', 'manual' => true],
                ['no' => 21, 'key' => 'total_return', 'description' => 'Total Return', 'manual' => true],
                ['no' => 22, 'key' => 'total_sale', 'description' => 'Total Sale (16 + 17 + 18 + 19 + 20 + 21)', 'class' => 'f15-total-row'],
                // IS2029 item 2: numbered 23.
                ['no' => '23', 'key' => 'balance_stock_sale_price', 'description' => 'Balance Stock in Sale Price', 'class' => 'f15-balance-row'],
                // IS2029: Grand Total numbered 24.
                ['no' => '24', 'key' => 'grand_total', 'description' => 'Grand Total', 'class' => 'f15-grand-row'],
            ];
        @endphp

        <div class="table-responsive">
            <table class="f15-daily-table" id="f15_daily_table">
                {{-- IS2029: the three amount columns now carry distinct classes so
                     each can be sized on its own. They happen to share a width
                     today, but the request treats them as three separate columns
                     and a future change to one should not move the other two. --}}
                <colgroup>
                    <col class="f15-col-no">
                    <col class="f15-col-description">
                    <col class="f15-col-amount f15-col-previous">
                    <col class="f15-col-amount f15-col-today">
                    <col class="f15-col-amount f15-col-asof">
                </colgroup>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Description</th>
                        <th>Previous Day</th>
                        <th>Today</th>
                        {{-- IS2009: renamed from "Total Value" as requested. The
                             underlying key stays 'total' throughout the service and
                             the scripts - only the visible heading changes. --}}
                        <th>As of Today</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @if(isset($row['section']))
                            <tr class="f15-section-row">
                                <td colspan="5">{{ $row['section'] }}</td>
                            </tr>
                        @else
                            <tr class="{{ $row['class'] ?? '' }}" data-row-key="{{ $row['key'] }}">
                                <td class="f15-row-no">{{ $row['no'] }}</td>
                                <td class="f15-description">{{ $row['description'] }}</td>
                                <td class="f15-amount f15-previous" data-value="0">0.00</td>
                                <td class="f15-amount f15-today" data-value="0">
                                    @if(!empty($row['manual']))
                                        <input type="number"
                                               step="0.01"
                                               class="form-control f15-manual-input"
                                               name="{{ $row['key'] }}"
                                               value="0.00"
                                               aria-label="{{ $row['description'] }} Today value">
                                    @else
                                        <span>0.00</span>
                                    @endif
                                </td>
                                <td class="f15-amount f15-total" data-value="0">0.00</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="f15-notes-block">
            <label for="f15_notes">Notes</label>
            <textarea class="form-control" id="f15_notes" rows="2" maxlength="5000"></textarea>
        </div>

        <div class="f15-signatures">
            <div class="f15-signature-card">
                <input type="text" class="form-control" id="f15_prepared_by" value="{{ auth()->user()->username ?? '' }}" placeholder="Prepared By">
                <strong>Prepared By</strong>
                <label>Date: <input type="date" id="f15_prepared_date" value="{{ $selectedDate }}"></label>
            </div>
            <div class="f15-signature-card">
                <input type="text" class="form-control" id="f15_checked_by" placeholder="Checked By">
                <strong>Checked By</strong>
                <label>Date: <input type="date" id="f15_checked_date"></label>
            </div>
            <div class="f15-signature-card">
                <input type="text" class="form-control" id="f15_approved_by" placeholder="Approved By">
                <strong>Approved By</strong>
                <label>Date: <input type="date" id="f15_approved_date"></label>
            </div>
        </div>

        <div class="f15-bottom-print no-print">
            <button type="button" class="btn btn-primary" id="f15_bottom_print">
                <i class="fa fa-print"></i> Print Document
            </button>
        </div>
    </div>
</div>

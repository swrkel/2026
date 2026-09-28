{{--
    MA-002 BUILD MARKER.

    Issues 8, 9 and 10 have now been reported four times against code that is
    correct in every archive sent to me. Rather than guess again, this partial
    now announces itself, so ONE screenshot settles whether the new version is
    actually reaching the browser.

    In the page source (Ctrl+U or DevTools > Elements) you will see the HTML
    comment below. In the browser console you will see the log line.

    If you see them  -> the new partial IS live, and the Cash row problem is
                        something else (styling or data) and I will chase that.
    If you do NOT    -> the server is still serving an older copy of this file,
                        and no amount of further code change will help until
                        that is resolved.
--}}
<!-- MA-002 closing_shift_summary BUILD=2026-08-04-P24 CASH_ROW=present -->
<script>try{console.log('MA-002 marker: closing_shift_summary BUILD=2026-08-04-P24 (Cash row present)');}catch(e){}</script>
@php
    /*
     * PDB-006 / IS1466
     * Professional Close Shift details panel.
     * This partial is loaded by AJAX into #closing_shift_summary.
     * PDB-008 fixes S272: after Shortage/Excess OK, Close Shift button displays when balance is settled.
     */
    $payments = $payments ?? (object) [];
    $cash = (float) ($payments->cash ?? 0);
    $credit = (float) ($payments->credit ?? 0);
    $card = (float) ($payments->card ?? 0);
    $cheque = (float) ($payments->cheque ?? 0);
    $other = (float) ($payments->other ?? 0);
    $shortage_excess = (float) ($payments->shortage_excess ?? 0);

    /*
     * PDB-008 / S272 final:
     * Use total payments INCLUDING shortage/excess adjustments for settlement balance.
     * PumperDayEntryController sets total_paid without shortage/excess for display,
     * therefore Close Shift button stayed hidden after clicking OK on Shortage/Excess.
     */
    $normal_payment_total = $cash + $credit + $card + $cheque + $other;
    $settlement_payment_total = (float) ($payments->total ?? ($normal_payment_total + $shortage_excess));
    $total_paid = $settlement_payment_total;

    /*
     |--------------------------------------------------------------------------
     | IS2010 #1: the Total Payment FIGURE SHOWN to the operator.
     |--------------------------------------------------------------------------
     |
     | Required formula:  Total payment = Cash + Card + Credit sales
     |
     | The box used to print $total_paid, which is $payments->total - a figure
     | that also carries cheque, other and the shortage/excess adjustment. On a
     | shift where those balance out it reads back as the total SALE value, which
     | is what was reported: "now shows total sale amount as total payment".
     |
     | This is a DISPLAY value only and is deliberately separate from
     | $settlement_payment_total. That variable drives $balance_to_settle and the
     | Close Shift button (PDB-008 / S272), which must keep including cheque,
     | other and shortage/excess or the button would stop appearing once a
     | shortage is settled. Changing it would break closing a shift.
     */
    $total_payment_display = $cash + $card + $credit;

    $total_sales = (float) ($day_entries->sum('amount') ?? 0);
    $other_sale_total = (float) ($other_sale ?? 0);
    $balance_to_settle = $total_sales + $other_sale_total - $settlement_payment_total;

    $is_shift_closed = !empty($this_shift) && (int) $this_shift->status === 2;
    // Petro shift statuses 0 and 1 are both open states; status 2 is closed.
    $is_shift_open = !empty($this_shift) && in_array((int) $this_shift->status, [0, 1], true);
@endphp

<style>
/* PDB-006: Professional Close Shift summary panel */
.close-shift-professional-wrap {
    width: 100%;
    max-width: 1180px;
    margin: 18px auto 24px auto;
    padding: 0 10px;
}
.close-shift-professional-card {
    background: #ffffff;
    border: 1px solid #e4edf7;
    border-radius: 18px;
    box-shadow: 0 16px 38px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}
.close-shift-professional-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 16px 20px;
    background: linear-gradient(135deg, #f8fbff, #eef6ff);
    border-bottom: 1px solid #e4edf7;
}
.close-shift-professional-title {
    margin: 0;
    font-size: 18px;
    font-weight: 800;
    color: #1f2d3d;
    line-height: 1.3;
}
.close-shift-professional-subtitle {
    margin-top: 3px;
    font-size: 13px;
    color: #64748b;
    font-weight: 600;
}
.close-shift-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    border-radius: 999px;
    font-size: 15.6px; /* IS1715: 20% increase from 13px */
    font-weight: 800;
    white-space: nowrap;
}
.close-shift-status-badge.closed {
    color: #166534;
    background: #dcfce7;
    border: 1px solid #bbf7d0;
}
.close-shift-status-badge.open {
    color: #92400e;
    background: #fef3c7;
    border: 1px solid #fde68a;
}
.close-shift-professional-body {
    padding: 18px 20px 20px 20px;
}
.close-shift-highlight-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}
.close-shift-highlight-box {
    border: 1px solid #e5edf6;
    border-radius: 14px;
    background: #ffffff;
    padding: 14px 15px;
    min-height: 92px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
}
.close-shift-highlight-label {
    color: #64748b;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .03em;
    margin-bottom: 8px;
}
.close-shift-highlight-value {
    color: #0f172a;
    font-size: 22px;
    line-height: 1.15;
    font-weight: 900;
    word-break: break-word;
}
.close-shift-highlight-box.primary {
    background: linear-gradient(135deg, #eff6ff, #ffffff);
    border-color: #bfdbfe;
}
.close-shift-highlight-box.success {
    background: linear-gradient(135deg, #ecfdf5, #ffffff);
    border-color: #bbf7d0;
}
.close-shift-highlight-box.warning {
    background: linear-gradient(135deg, #fffbeb, #ffffff);
    border-color: #fde68a;
}
.close-shift-highlight-box.danger {
    background: linear-gradient(135deg, #fff1f2, #ffffff);
    border-color: #fecdd3;
}
.close-shift-details-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}
.close-shift-section {
    border: 1px solid #e5edf6;
    border-radius: 16px;
    background: #ffffff;
    overflow: hidden;
}
.close-shift-section-title {
    padding: 12px 15px;
    background: #f8fafc;
    border-bottom: 1px solid #e5edf6;
    font-size: 14px;
    font-weight: 900;
    color: #334155;
    text-transform: uppercase;
    letter-spacing: .02em;
}
.close-shift-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 10px 15px;
    border-bottom: 1px solid #f1f5f9;
}
.close-shift-line:last-child {
    border-bottom: none;
}
.close-shift-professional-card #closing_shift_cash_total_row {
    display: flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    min-height: 42px;
}
.close-shift-line-label {
    color: #475569;
    font-size: 14px;
    font-weight: 700;
}
.close-shift-line-value {
    color: #111827;
    font-size: 15px;
    font-weight: 800;
    text-align: right;
    word-break: break-word;
}
.close-shift-line-value.negative {
    color: #15803d;
}
.close-shift-line-value.positive {
    color: #b91c1c;
}
.close-shift-action-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e5edf6;
}
.close-shift-action-footer .btn,
.close-shift-action-footer a.btn,
.close-shift-action-footer button.btn {
    border-radius: 10px !important;
    padding: 10px 18px !important;
    font-weight: 800 !important;
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.10);
}
.close-shift-btn-other {
    background: #f59e0b !important;
    color: #ffffff !important;
    border: none !important;
}
.close-shift-btn-close {
    background: #2563eb !important;
    color: #ffffff !important;
    border: none !important;
}
/* MA-008: Print Close Shift Summary. Slate rather than blue so it reads as a
   secondary action and cannot be mistaken for the Close Shift button itself. */
.close-shift-btn-print {
    background: #475569 !important;
    color: #ffffff !important;
    border: none !important;
}
.close-shift-btn-print:hover,
.close-shift-btn-print:focus {
    background: #334155 !important;
    color: #ffffff !important;
}
.close-shift-btn-shortage {
    background: #dc2626 !important;
    color: #ffffff !important;
    border: none !important;
}
.close-shift-btn-excess {
    background: #16a34a !important;
    color: #ffffff !important;
    border: none !important;
}
.close-shift-empty-note {
    padding: 14px 16px;
    border-radius: 12px;
    background: #f8fafc;
    color: #64748b;
    font-weight: 700;
}
@media (max-width: 991px) {
    .close-shift-highlight-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .close-shift-details-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 575px) {
    .close-shift-professional-wrap {
        padding: 0 4px;
    }
    .close-shift-professional-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .close-shift-highlight-grid {
        grid-template-columns: 1fr;
    }
    .close-shift-line {
        align-items: flex-start;
        flex-direction: column;
        gap: 4px;
    }
    .close-shift-line-value {
        text-align: left;
    }
    .close-shift-action-footer {
        justify-content: stretch;
    }
    .close-shift-action-footer .btn,
    .close-shift-action-footer a.btn,
    .close-shift-action-footer button.btn {
        width: 100%;
    }
}
</style>

<div class="col-md-12">
    <div class="close-shift-professional-wrap">
        <div class="close-shift-professional-card">
            <div class="close-shift-professional-header">
                <div>
                    <h3 class="close-shift-professional-title">@lang('pumperdashboard::lang.close_shift')</h3>
                    <div class="close-shift-professional-subtitle">
                        @if(!empty($shift_number))
                            @lang('pumperdashboard::lang.shift_number'): {{ $shift_number }}
                        @else
                            @lang('pumperdashboard::lang.shift')
                        @endif
                    </div>
                </div>

                @if($is_shift_closed)
                    <span class="close-shift-status-badge closed">
                        <i class="fa fa-check-circle"></i> @lang('pumperdashboard::lang.shift_closed')
                    </span>
                @elseif($is_shift_open)
                    <span class="close-shift-status-badge open">
                        <i class="fa fa-clock-o"></i> @lang('pumperdashboard::lang.shift_open')
                    </span>
                @endif
            </div>

            <div class="close-shift-professional-body">
                <div class="close-shift-highlight-grid">
                    <div class="close-shift-highlight-box primary">
                        <div class="close-shift-highlight-label">@lang('pumperdashboard::lang.total_sale_closed_pumps')</div>
                        <div class="close-shift-highlight-value">{{ @num_format($total_sales) }}</div>
                    </div>

                    <div class="close-shift-highlight-box success">
                        <div class="close-shift-highlight-label">@lang('pumperdashboard::lang.total_payments')</div>
                        {{-- IS2010 #1: Cash + Card + Credit sales. See the note at the top. --}}
                        <div class="close-shift-highlight-value">{{ @num_format($total_payment_display) }}</div>
                    </div>

                    <div class="close-shift-highlight-box {{ $balance_to_settle > 0 ? 'danger' : ($balance_to_settle < 0 ? 'warning' : 'success') }}">
                        <div class="close-shift-highlight-label">@lang('pumperdashboard::lang.balance_to_settle')</div>
                        <div class="close-shift-highlight-value" id="balance_to_settle">
                            {{ @num_format($balance_to_settle) }}
                            @if($balance_to_settle < 0)
                                <small style="font-size:12px; font-weight:800;">@lang('pumperdashboard::lang.excess')</small>
                            @endif
                        </div>
                    </div>

                    <div class="close-shift-highlight-box warning">
                        <div class="close-shift-highlight-label">@lang('pumperdashboard::lang.current_balance_to_operator')</div>
                        <div class="close-shift-highlight-value" id="current_balance_to_operator">{{ @num_format($shortage_excess) }}</div>
                    </div>
                </div>

                <div class="close-shift-details-grid">
                    <div class="close-shift-section">
                        <div class="close-shift-section-title">
                            <i class="fa fa-info-circle"></i> Shift Details
                        </div>

                        @if($is_shift_closed)
                            <div class="close-shift-line">
                                <span class="close-shift-line-label">@lang('pumperdashboard::lang.shift_closed')</span>
                                <span class="close-shift-line-value">{{ $shift_number }}</span>
                            </div>
                        @endif

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('pumperdashboard::lang.total_other_sales')</span>
                            <span class="close-shift-line-value">{{ @num_format($other_sale_total) }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('pumperdashboard::lang.balance_to_settle')</span>
                            <span class="close-shift-line-value {{ $balance_to_settle > 0 ? 'positive' : ($balance_to_settle < 0 ? 'negative' : '') }}">
                                {{ @num_format($balance_to_settle) }}
                                @if($balance_to_settle < 0)
                                    <span>@lang('pumperdashboard::lang.excess')</span>
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="close-shift-section">
                        <div class="close-shift-section-title">
                            <i class="fa fa-money"></i> Payment Summary
                        </div>

                        <div id="closing_shift_cash_total_row" class="close-shift-line close-shift-payment-cash-line" data-summary-row="cash" style="display:flex !important; visibility:visible !important; opacity:1 !important;">
                            <span class="close-shift-line-label">@lang('pumperdashboard::lang.cash')</span>
                            <span class="close-shift-line-value">{{ @num_format($cash) }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('pumperdashboard::lang.credit_sales')</span>
                            <span class="close-shift-line-value">{{ @num_format($credit) }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('pumperdashboard::lang.credit_cards')</span>
                            <span class="close-shift-line-value">{{ @num_format($card) }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('pumperdashboard::lang.cheque_sales')</span>
                            <span class="close-shift-line-value">{{ @num_format($cheque) }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('pumperdashboard::lang.other')</span>
                            <span class="close-shift-line-value">{{ @num_format($other) }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('pumperdashboard::lang.balance_to_settle')</span>
                            <span class="close-shift-line-value {{ $balance_to_settle > 0 ? 'positive' : ($balance_to_settle < 0 ? 'negative' : '') }}">
                                {{ @num_format($balance_to_settle) }}
                                @if($balance_to_settle < 0)
                                    <span>@lang('pumperdashboard::lang.excess')</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <div class="close-shift-action-footer">
                    {{--
                        MA-008: "In the Close shift page, need a 'Print Close shift
                        Summary' button in a visible way for the user to take the
                        print of the selected closed shift."

                        Shown for the shift currently on screen, open or closed, so
                        a summary can be reprinted without closing the shift again.
                        Opens in a new tab so the Close Shift page is never
                        navigated away from with work in progress.
                    --}}
                    @if(!empty($this_shift->id))
                        <a class="btn btn-flat close-shift-btn-print"
                           target="_blank" rel="noopener"
                           href="{{ route('pumperdashboard.close-shift.summary.print', $this_shift->id) }}">
                            <i class="fa fa-print"></i> @lang('pumperdashboard::lang.print_close_shift_summary')
                        </a>
                    @endif

                    @if(!empty(auth()->user()->pump_operator_id) && $is_shift_open)
                        <a class="btn btn-flat btn-modal close-shift-btn-other"
                           href="#" data-container=".view_modal"
                           data-href="{{ action('\Modules\\PumperDashboard\\Http\\Controllers\\PumpOperatorPaymentController@otherSales', $this_shift->id) }}">
                            <i class="fa fa-plus-circle"></i> @lang('pumperdashboard::lang.other_sales')
                        </a>
                    @endif

                    @if(!empty(auth()->user()->pump_operator_id) && $is_shift_open)
                        @if(($unconfirmed_pumps_count ?? 0) == 0 && ($unclosed_pumps_count ?? 0) == 0)
                            <a id="close_shift_btn"
                               class="{{ round($balance_to_settle, 2) != 0 ? 'hide btn btn-flat close-shift-btn-close' : 'btn btn-flat close-shift-btn-close' }}"
                               href="{{ action('\Modules\PumperDashboard\Http\Controllers\ClosingShiftController@closeShift', $this_shift->id) }}">
                                <i class="fa fa-check"></i> @lang('pumperdashboard::lang.close_shift')
                            </a>
                        @elseif(($unconfirmed_pumps_count ?? 0) != 0)
                            <a onclick="alert_pending_receive()"
                               class="{{ round($balance_to_settle, 2) != 0 ? 'hide btn btn-flat close-shift-btn-close' : 'btn btn-flat close-shift-btn-close' }}"
                               href="#">
                                <i class="fa fa-check"></i> @lang('pumperdashboard::lang.close_shift')
                            </a>
                            <script>
                                function alert_pending_receive(){ toastr.error('Pending @lang('pumperdashboard::lang.receive_pump')'); }
                            </script>
                        @else
                            <a onclick="alert_pending_close()"
                               class="{{ round($balance_to_settle, 2) != 0 ? 'hide btn btn-flat close-shift-btn-close' : 'btn btn-flat close-shift-btn-close' }}"
                               href="#">
                                <i class="fa fa-check"></i> @lang('pumperdashboard::lang.close_shift')
                            </a>
                            <script>
                                function alert_pending_close(){ toastr.error('Please close the Meters first'); }
                            </script>
                        @endif
                    @endif

                    @if(($unconfirmed_pumps_count ?? 0) == 0 && ($unclosed_pumps_count ?? 0) == 0)
                        @php
                            /*
                             * S700: decide Shortage vs Excess on the ROUNDED balance.
                             *
                             * The buttons compared the raw float:
                             *     $balance_to_settle > 0  -> Shortage
                             *     $balance_to_settle < 0  -> Excess
                             *
                             * A settled shortage rarely lands on exactly zero. It
                             * ends a fraction below - -0.0001 and the like - from
                             * summing money as floats. That is still "< 0", so the
                             * page showed the EXCESS button on a shift with a
                             * shortage, while displaying "0.00 Excess" beside it.
                             * The reported screenshot shows exactly that: a
                             * shortage of 4,025.21 recorded, and a green Excess
                             * button.
                             *
                             * Rounding to 2 decimals first means anything that
                             * DISPLAYS as 0.00 is treated as balanced, so neither
                             * button is offered - which is correct, because there
                             * is nothing left to settle.
                             *
                             * The disabled attribute on both buttons already used
                             * round(..., 2); only the branch that CHOOSES which
                             * button to draw did not. That mismatch is why a
                             * disabled Excess button appeared at all.
                             */
                            $balance_rounded = round((float) $balance_to_settle, 2);
                        @endphp

                        @if ($balance_rounded > 0)
                            <button
                                @if($is_shift_closed || round($balance_to_settle, 2) == 0) disabled="disabled" @endif
                                type="button"
                                id="settleBalance"
                                {{-- MA-002: the amount, so the confirm dialog can name it. --}}
                                data-balance-amount="{{ number_format(abs($balance_to_settle), 2, '.', '') }}"
                                data-balance-kind="shortage"
                                {{-- MA-002: the shift number, so the dialog can be checked against the shift. --}}
                                data-shift-number="{{ $shift_number ?? '' }}"
                                class="btn btn-flat close-shift-btn-shortage">
                                <i class="fa fa-exclamation-triangle"></i> @lang('pumperdashboard::lang.shortage')
                            </button>
                        @endif

                        @if ($balance_rounded < 0)
                            <button
                                type="button"
                                @if($is_shift_closed || round($balance_to_settle, 2) == 0) disabled="disabled" @endif
                                id="settleBalance"
                                {{-- MA-002: the amount, so the confirm dialog can name it. --}}
                                data-balance-amount="{{ number_format(abs($balance_to_settle), 2, '.', '') }}"
                                data-balance-kind="excess"
                                {{-- MA-002: the shift number, so the dialog can be checked against the shift. --}}
                                data-shift-number="{{ $shift_number ?? '' }}"
                                class="btn btn-flat close-shift-btn-excess">
                                <i class="fa fa-plus-circle"></i> @lang('pumperdashboard::lang.excess')
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

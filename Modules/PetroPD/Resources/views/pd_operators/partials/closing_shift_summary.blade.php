@php
    /*
     * IS1800
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
     * IS1800 / preserve settled-balance behaviour:
     * Use total payments INCLUDING shortage/excess adjustments for settlement balance.
     * PumperDayEntryController sets total_paid without shortage/excess for display,
     * therefore Close Shift button stayed hidden after clicking OK on Shortage/Excess.
     */
    $normal_payment_total = $cash + $credit + $card + $cheque + $other;
    $settlement_payment_total = (float) ($payments->total ?? ($normal_payment_total + $shortage_excess));
    $total_paid = $settlement_payment_total;

    // Keep the Close Shift totals aligned with the Pumper Dashboard / Close Pumps source.
    $meter_sales_total = (float) ($meter_sales_total ?? $day_entries->unique('id')->sum('amount') ?? 0);
    $other_sale_total = (float) ($other_sale ?? 0);
    $total_sale = $meter_sales_total + $other_sale_total;
    $balance_to_settle = $total_sale - $settlement_payment_total;

    $is_shift_closed = !empty($this_shift) && (int) $this_shift->status === 2;
    // Petro shift statuses 0 and 1 are both open states; status 2 is closed.
    $is_shift_open = !empty($this_shift) && in_array((int) $this_shift->status, [0, 1], true);
    $resolved_pump_operator_name = trim((string) ($pump_operator_name ?? ($pump_operator->name ?? '')));
@endphp

<style>
/* IS1800: Professional PetroPD Close Shift summary panel */
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
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 3px;
    font-size: 13px;
    color: #64748b;
    font-weight: 600;
}
.close-shift-professional-subtitle .meta-divider {
    color: #94a3b8;
}
.close-shift-professional-subtitle .operator-name {
    color: #1e3a5f;
    font-weight: 800;
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
.close-shift-btn-print {
    background: linear-gradient(135deg, #2563eb, #38bdf8) !important;
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
                    <h3 class="close-shift-professional-title">@lang('petropd::lang.close_shift')</h3>
                    <div class="close-shift-professional-subtitle">
                        <span>
                            @if(!empty($shift_number))
                                @lang('petropd::lang.shift_number'): {{ $shift_number }}
                            @else
                                @lang('petropd::lang.shift')
                            @endif
                        </span>
                        @if($resolved_pump_operator_name !== '')
                            <span class="meta-divider">|</span>
                            <span class="operator-name">
                                @lang('petropd::lang.pump_operator'): {{ $resolved_pump_operator_name }}
                            </span>
                        @endif
                    </div>
                </div>

                @if($is_shift_closed)
                    <span class="close-shift-status-badge closed">
                        <i class="fa fa-check-circle"></i> @lang('petropd::lang.shift_closed')
                    </span>
                @elseif($is_shift_open)
                    <span class="close-shift-status-badge open">
                        <i class="fa fa-clock-o"></i> Shift Open
                    </span>
                @endif
            </div>

            <div class="close-shift-professional-body">
                <div class="close-shift-highlight-grid">
                    <div class="close-shift-highlight-box primary">
                        <div class="close-shift-highlight-label">Total Sale</div>
                        <div class="close-shift-highlight-value">{{ @num_format($total_sale) }}</div>
                    </div>

                    <div class="close-shift-highlight-box success">
                        <div class="close-shift-highlight-label">@lang('petropd::lang.total_payments')</div>
                        <div class="close-shift-highlight-value">{{ @num_format($total_paid) }}</div>
                    </div>

                    <div class="close-shift-highlight-box {{ $balance_to_settle > 0 ? 'danger' : ($balance_to_settle < 0 ? 'warning' : 'success') }}">
                        <div class="close-shift-highlight-label">@lang('petropd::lang.balance_to_settle')</div>
                        <div class="close-shift-highlight-value" id="balance_to_settle">
                            {{ @num_format($balance_to_settle) }}
                            @if($balance_to_settle < 0)
                                <small style="font-size:12px; font-weight:800;">@lang('petropd::lang.excess')</small>
                            @endif
                        </div>
                    </div>

                    <div class="close-shift-highlight-box warning">
                        <div class="close-shift-highlight-label">@lang('petropd::lang.current_balance_to_operator')</div>
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
                                <span class="close-shift-line-label">@lang('petropd::lang.shift_closed')</span>
                                <span class="close-shift-line-value">{{ $shift_number }}</span>
                            </div>
                        @endif

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('petropd::lang.no_of_closed_pumps')</span>
                            <span class="close-shift-line-value">{{ $today_pumps ?: '-' }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">Meter Sales</span>
                            <span class="close-shift-line-value">{{ @num_format($meter_sales_total) }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('petropd::lang.total_other_sales')</span>
                            <span class="close-shift-line-value">{{ @num_format($other_sale_total) }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">Total Sale</span>
                            <span class="close-shift-line-value">{{ @num_format($total_sale) }}</span>
                        </div>
                    </div>

                    <div class="close-shift-section">
                        <div class="close-shift-section-title">
                            <i class="fa fa-money"></i> Payment Summary
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('petropd::lang.cash')</span>
                            <span class="close-shift-line-value">{{ @num_format($cash) }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('petropd::lang.credit_sales')</span>
                            <span class="close-shift-line-value">{{ @num_format($credit) }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('petropd::lang.credit_cards')</span>
                            <span class="close-shift-line-value">{{ @num_format($card) }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('petropd::lang.cheque_sales')</span>
                            <span class="close-shift-line-value">{{ @num_format($cheque) }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('petropd::lang.other')</span>
                            <span class="close-shift-line-value">{{ @num_format($other) }}</span>
                        </div>

                        <div class="close-shift-line">
                            <span class="close-shift-line-label">@lang('petropd::lang.balance_to_settle')</span>
                            <span class="close-shift-line-value {{ $balance_to_settle > 0 ? 'positive' : ($balance_to_settle < 0 ? 'negative' : '') }}">
                                {{ @num_format($balance_to_settle) }}
                                @if($balance_to_settle < 0)
                                    <span>@lang('petropd::lang.excess')</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <div class="close-shift-action-footer">
                    @if(!empty(auth()->user()->pump_operator_id) && $is_shift_open)
                        <a class="btn btn-flat btn-modal close-shift-btn-other"
                           href="#" data-container=".view_modal"
                           data-href="{{ action('\Modules\\PetroPD\\Http\\Controllers\\PDPumpOperatorPaymentController@otherSales', $this_shift->id) }}">
                            <i class="fa fa-plus-circle"></i> @lang('petropd::lang.other_sales')
                        </a>
                    @endif

                    @if(!empty(auth()->user()->pump_operator_id) && $is_shift_open)
                        @if(($unconfirmed_pumps_count ?? 0) == 0 && ($unclosed_pumps_count ?? 0) == 0)
                            <a id="close_shift_btn"
                               class="{{ round($balance_to_settle, 2) != 0 ? 'hide btn btn-flat close-shift-btn-close' : 'btn btn-flat close-shift-btn-close' }}"
                               href="{{ action('\Modules\PetroPD\Http\Controllers\PDClosingShiftController@closeShift', $this_shift->id) }}">
                                <i class="fa fa-check"></i> @lang('petropd::lang.close_shift')
                            </a>
                        @elseif(($unconfirmed_pumps_count ?? 0) != 0)
                            <a onclick="alert_pending_receive()"
                               class="{{ round($balance_to_settle, 2) != 0 ? 'hide btn btn-flat close-shift-btn-close' : 'btn btn-flat close-shift-btn-close' }}"
                               href="#">
                                <i class="fa fa-check"></i> @lang('petropd::lang.close_shift')
                            </a>
                            <script>
                                function alert_pending_receive(){ toastr.error(@json('Pending ' . __('petropd::lang.receive_pump'))); }
                            </script>
                        @else
                            <a onclick="alert_pending_close()"
                               class="{{ round($balance_to_settle, 2) != 0 ? 'hide btn btn-flat close-shift-btn-close' : 'btn btn-flat close-shift-btn-close' }}"
                               href="#">
                                <i class="fa fa-check"></i> @lang('petropd::lang.close_shift')
                            </a>
                            <script>
                                function alert_pending_close(){ toastr.error('Please close the Meters first'); }
                            </script>
                        @endif
                    @endif

                    @if(!empty(auth()->user()->pump_operator_id) && $is_shift_open)
                        @if(($unconfirmed_pumps_count ?? 0) == 0 && ($unclosed_pumps_count ?? 0) == 0)
                            @if ($balance_to_settle > 0)
                                <button
                                    @if(round($balance_to_settle, 2) == 0) disabled="disabled" @endif
                                    type="button"
                                    id="settleBalance"
                                    {{-- MA-002: the amount, for the confirm dialog. --}}
                                    data-balance-amount="{{ number_format(abs($balance_to_settle), 2, '.', '') }}"
                                    data-balance-kind="shortage"
                                    data-shift-number="{{ $shift_number ?? '' }}"
                                    class="btn btn-flat close-shift-btn-shortage">
                                    <i class="fa fa-exclamation-triangle"></i> @lang('petropd::lang.shortage')
                                </button>
                            @endif

                            @if ($balance_to_settle < 0)
                                <button
                                    type="button"
                                    @if(round($balance_to_settle, 2) == 0) disabled="disabled" @endif
                                    id="settleBalance"
                                    {{-- MA-002: the amount, for the confirm dialog. --}}
                                    data-balance-amount="{{ number_format(abs($balance_to_settle), 2, '.', '') }}"
                                    data-balance-kind="excess"
                                    data-shift-number="{{ $shift_number ?? '' }}"
                                    class="btn btn-flat close-shift-btn-excess">
                                    <i class="fa fa-plus-circle"></i> @lang('petropd::lang.excess')
                                </button>
                            @endif
                        @endif
                    @endif

                    @if($is_shift_closed)
                        <a class="btn btn-flat close-shift-btn-print"
                           target="_blank"
                           rel="noopener"
                           href="{{ route('petropd.closing-shift.print', ['shift_id' => $this_shift->id]) }}">
                            <i class="fa fa-print"></i> Click here to get the print
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

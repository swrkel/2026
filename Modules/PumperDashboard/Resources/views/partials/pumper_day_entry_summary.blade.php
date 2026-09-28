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
<!-- MA-002 pumper_day_entry_summary BUILD=2026-08-04-P24 CASH_ROW=present -->
<script>try{console.log('MA-002 marker: pumper_day_entry_summary BUILD=2026-08-04-P24 (Cash row present)');}catch(e){}</script>
@php
    // IS1863: Cash is a permanent financial summary row. It must remain visible
    // even when the selected shift has no cash payments yet.
    $cash_total = (float) ($payments->cash ?? 0);
@endphp

<style>
    /* IS1865: This summary deliberately avoids the global .box/.row classes.
       Those shared classes were drawing a white strip over the first payment
       line and made the permanent Cash row appear to be missing. */
    .pd-day-summary-card {
        width: 100%;
        margin: 0 0 20px;
        border: 1px solid #e1e8ef;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .05);
        padding: 16px 20px 18px;
    }
    .pd-day-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px 48px;
        max-width: 1180px;
        margin: 0 auto;
    }
    .pd-day-summary-column {
        min-width: 0;
    }
    .pd-day-summary-line {
        display: grid !important;
        grid-template-columns: minmax(180px, 1fr) minmax(100px, auto);
        align-items: start;
        gap: 18px;
        padding: 4px 0;
        color: #0f172a;
        visibility: visible !important;
        opacity: 1 !important;
    }
    .pd-day-summary-label,
    .pd-day-summary-value {
        margin: 0;
        font-size: 20px;
        line-height: 1.3;
        font-weight: 700;
    }
    .pd-day-summary-label {
        color: #b91c1c;
    }
    .pd-day-summary-value {
        color: #111827;
        text-align: right;
        white-space: nowrap;
    }
    .pd-day-summary-block {
        display: block;
        width: 100%;
        margin: 0;
        padding: 0;
        float: none;
        clear: both;
    }
    #pumper_day_entry_cash_total_row {
        display: grid !important;
        visibility: visible !important;
        opacity: 1 !important;
        height: auto !important;
    }
    @media (max-width: 991px) {
        .pd-day-summary-grid {
            grid-template-columns: 1fr;
            gap: 4px;
        }
    }
    @media (max-width: 575px) {
        .pd-day-summary-card {
            padding: 14px;
        }
        .pd-day-summary-line {
            grid-template-columns: 1fr auto;
            gap: 10px;
        }
        .pd-day-summary-label,
        .pd-day-summary-value {
            font-size: 16px;
        }
    }
</style>

<div class="pd-day-summary-block">
    <div class="pd-day-summary-card">
        <div class="pd-day-summary-grid">
            <div class="pd-day-summary-column">
                <div class="pd-day-summary-line">
                    <span class="pd-day-summary-label">@lang('pumperdashboard::lang.total_sale_closed_pumps'):</span>
                    <span class="pd-day-summary-value">{{ @num_format($day_entries->sum('amount')) }}</span>
                </div>
                <div class="pd-day-summary-line">
                    <span class="pd-day-summary-label">@lang('pumperdashboard::lang.total_other_sales'):</span>
                    <span class="pd-day-summary-value">{{ @num_format($other_sale) }}</span>
                </div>
                <div class="pd-day-summary-line">
                    <span class="pd-day-summary-label">@lang('pumperdashboard::lang.total_payments'):</span>
                    <span class="pd-day-summary-value">{{ @num_format($payments->total) }}</span>
                </div>
                <div class="pd-day-summary-line">
                    <span class="pd-day-summary-label">@lang('pumperdashboard::lang.balance_to_settle'):</span>
                    <span class="pd-day-summary-value">
                        {{ @num_format($day_entries->sum('amount') + $other_sale - $payments->total) }}
                        @if(($day_entries->sum('amount') + $other_sale - $payments->total) < 0)
                            @lang('pumperdashboard::lang.excess')
                        @endif
                    </span>
                </div>
            </div>

            <div class="pd-day-summary-column">
                <div id="pumper_day_entry_cash_total_row" class="pd-day-summary-line" data-summary-row="cash">
                    <span class="pd-day-summary-label">@lang('pumperdashboard::lang.cash'):</span>
                    <span class="pd-day-summary-value">{{ @num_format($cash_total) }}</span>
                </div>
                <div class="pd-day-summary-line">
                    <span class="pd-day-summary-label">@lang('pumperdashboard::lang.credit_sales'):</span>
                    <span class="pd-day-summary-value">{{ @num_format($payments->credit) }}</span>
                </div>
                <div class="pd-day-summary-line">
                    <span class="pd-day-summary-label">@lang('pumperdashboard::lang.credit_cards'):</span>
                    <span class="pd-day-summary-value">{{ @num_format($payments->card) }}</span>
                </div>
                <div class="pd-day-summary-line">
                    <span class="pd-day-summary-label">@lang('pumperdashboard::lang.cheque_sales'):</span>
                    <span class="pd-day-summary-value">{{ @num_format($payments->cheque) }}</span>
                </div>
                <div class="pd-day-summary-line">
                    <span class="pd-day-summary-label">@lang('pumperdashboard::lang.other'):</span>
                    <span class="pd-day-summary-value">{{ @num_format($payments->other) }}</span>
                </div>
                <div class="pd-day-summary-line">
                    <span class="pd-day-summary-label">@lang('pumperdashboard::lang.current_balance_to_operator'):</span>
                    <span class="pd-day-summary-value">{{ @num_format(-1 * $payments->shortage_excess) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/*
 * MA-002 (S-609 #8): match the Close Shift summary.
 *
 * The Day Entries summary and the Close Shift summary have the SAME
 * structure - a two-column grid of label/value lines - but different class
 * names and different styling, so they looked like two different screens.
 *
 * These rules are copied verbatim from closing_shift_summary.blade.php and
 * applied to this partial's own class names. Deliberately styling rather
 * than renaming: the pd-day-summary-* names and the
 * #pumper_day_entry_cash_total_row id are used by the controller's
 * self-check and by scripts on this page, so renaming them would break
 * things that have nothing to do with how it looks.
 *
 * Declared after this partial's existing rules so it wins on equal
 * specificity, without deleting anything that was here before.
 */
.pd-day-summary-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}
.pd-day-summary-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 10px 15px;
    border-bottom: 1px solid #f1f5f9;
}
.pd-day-summary-label {
    color: #475569;
    font-size: 14px;
    font-weight: 700;
}
.pd-day-summary-value {
    color: #111827;
    font-size: 15px;
    font-weight: 800;
    text-align: right;
    word-break: break-word;
}
</style>

@php
    $business_name = session('business.name') ?? optional(optional($transactions->first())->business)->name ?? config('app.name');
    $printed_at = now()->format('Y-m-d H:i');
    $total_amount = $transactions->sum('final_total');
@endphp

<style>
    .credit-sales-screen-layout .table {
        margin-bottom: 0;
    }

    .credit-sales-print-layout {
        display: none;
        width: 72mm;
        max-width: 72mm;
        margin: 0 auto;
        color: #000;
        font-size: 11px;
        line-height: 1.35;
    }

    .credit-sales-print-layout,
    .credit-sales-print-layout * {
        box-sizing: border-box;
    }

    .credit-sales-print-layout .receipt-title {
        text-align: center;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .credit-sales-print-layout .receipt-meta {
        text-align: center;
        font-size: 10px;
        margin-bottom: 8px;
    }

    .credit-sales-print-layout .receipt-line {
        border-top: 1px dashed #000;
        padding: 6px 0;
    }

    .credit-sales-print-layout .receipt-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 8px;
    }

    .credit-sales-print-layout .receipt-label {
        font-weight: 700;
    }

    .credit-sales-print-layout .receipt-amount {
        text-align: right;
        white-space: nowrap;
    }

    .credit-sales-print-layout .receipt-subtext {
        margin-top: 2px;
        word-break: break-word;
    }

    .credit-sales-print-layout .receipt-total {
        border-top: 1px solid #000;
        border-bottom: 1px solid #000;
        margin-top: 8px;
        padding: 6px 0;
        font-weight: 700;
    }

    @media print {
        @page {
            margin: 4mm;
            size: auto;
        }

        .no-print,
        .credit-sales-screen-layout {
            display: none !important;
        }

        .credit-sales-print-layout {
            display: block !important;
        }

        .modal-dialog {
            width: auto !important;
            max-width: none !important;
            margin: 0 !important;
        }

        .modal-content {
            border: none !important;
            box-shadow: none !important;
        }

        .modal-body {
            padding: 0 !important;
        }
    }
</style>

<div class="modal-dialog" role="document" style="width: 70%">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title no-print">
                @lang('lang_v1.credit_sales') & @lang('report.pump_operator_shortage')
            </h4>
        </div>
        <div class="modal-body">
            <div class="row credit-sales-screen-layout">
                <div class="col-md-12">
                    <table class="table table-striped" id="view_credit_sales_table">
                        <thead>
                            <tr>
                                <th>@lang('messages.date')</th>
                                <th>@lang('sale.invoice_no')</th>
                                <th>@lang('sale.customer_name')</th>
                                <th>@lang('lang_v1.contact_no')</th>
                                <th>@lang('sale.location')</th>
                                <th>@lang('sale.type')</th>
                                <th>@lang('petro::lang.pump_operator')</th>
                                <th>@lang('sale.total_amount')</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td>
                                    {{ !empty($transaction->created_at) ? date('Y-m-d', strtotime($transaction->created_at)) : '' }}
                                </td>
                                <td>
                                    {{ $transaction->invoice_no }}
                                </td>
                                <td>
                                    {{ (!is_null($transaction->contact)) ? $transaction->contact->name : '' }}
                                </td>
                                <td>
                                    {{ (!is_null($transaction->contact)) ? $transaction->contact->mobile : '' }}
                                </td>
                                <td>
                                    {{ (!is_null($transaction->business)) ? $transaction->business->name : '' }}
                                </td>
                                <td>
                                    {{ str_replace('_', ' ', $transaction->sub_type) }}
                                </td>
                                <td>
                                    {{ $transaction->name ?? '' }}
                                </td>
                                <td>
                                    <span class="display_currency final-total" data-currency_symbol="true" data-orig-value="{{ $transaction->final_total }}">
                                        {{ number_format($transaction->final_total, 2) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="credit-sales-print-layout">
                <div class="receipt-title">
                    {{ $business_name }}<br>
                    @lang('lang_v1.credit_sales') & @lang('report.pump_operator_shortage')
                </div>

                <div class="receipt-meta">
                    @lang('messages.date'): {{ $printed_at }}<br>
                    @lang('lang_v1.total_items'): {{ $transactions->count() }}
                </div>

                @foreach ($transactions as $transaction)
                    <div class="receipt-line">
                        <div class="receipt-row">
                            <span class="receipt-label">{{ $transaction->invoice_no ?: __('sale.invoice_no') }}</span>
                            <span class="receipt-amount">
                                <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $transaction->final_total }}">
                                    {{ number_format($transaction->final_total, 2) }}
                                </span>
                            </span>
                        </div>
                        <div class="receipt-subtext">
                            {{ !empty($transaction->created_at) ? date('Y-m-d', strtotime($transaction->created_at)) : '' }}
                            | {{ ucwords(str_replace('_', ' ', $transaction->sub_type ?? '')) }}
                        </div>
                        @if (!empty(optional($transaction->contact)->name))
                            <div class="receipt-subtext">
                                @lang('sale.customer_name'): {{ $transaction->contact->name }}
                            </div>
                        @endif
                        @if (!empty(optional($transaction->contact)->mobile))
                            <div class="receipt-subtext">
                                @lang('lang_v1.contact_no'): {{ $transaction->contact->mobile }}
                            </div>
                        @endif
                        @if (!empty($transaction->name))
                            <div class="receipt-subtext">
                                @lang('petro::lang.pump_operator'): {{ $transaction->name }}
                            </div>
                        @endif
                    </div>
                @endforeach

                <div class="receipt-total">
                    <div class="receipt-row">
                        <span>@lang('sale.total_amount')</span>
                        <span class="receipt-amount">
                            <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $total_amount }}">
                                {{ number_format($total_amount, 2) }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-primary no-print print-credit-sales">
                <i class="fa fa-print"></i> @lang('messages.print')
            </button>
            <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close')</button>
        </div>
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
    $(document).ready(function () {
        if (typeof __currency_convert_recursively === 'function') {
            __currency_convert_recursively($('.payment_modal'));
        }

        $('.payment_modal')
            .off('click.creditSalesPrint', '.print-credit-sales')
            .on('click.creditSalesPrint', '.print-credit-sales', function (e) {
                e.preventDefault();

                var $modal = $(this).closest('div.modal');

                if ($.fn.printThis) {
                    $modal.printThis({
                        importCSS: true,
                        loadCSS: "",
                        removeInline: false,
                        printDelay: 333,
                        header: null,
                        footer: null,
                        formValues: true,
                        removeScripts: false
                    });
                } else {
                    window.print();
                }
            });
    });
</script>

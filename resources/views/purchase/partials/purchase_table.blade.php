<div class="table-responsive purchase-list-table-scroll">
    <table class="table table-bordered table-striped ajax_view purchase-list-table" id="purchase_table">
        <thead>
            <tr>
                <th class="notexport purchase-action-heading">@lang('messages.action')</th>
                <th class="purchase-date-heading">@lang('messages.date')</th>
                {{-- <th>@lang('purchase.invoice_date')</th> --}}
                <th>@if(!empty($is_purchase_order_list)) @lang('purchase.purchase_order_no') @else P. Invoice No @endif</th>
                @if (empty($is_purchase_order_list))
                    <th>@lang('purchase.ref_no')</th>
                @endif
                <th>@lang('purchase.location')</th>
                <th>@lang('purchase.supplier')</th>
                <th>@lang('purchase.purchase_status')</th>
                <th>@lang('purchase.payment_status')</th>
                <th class="purchase-amount-heading text-right">@lang('purchase.total_before_tax')</th>
                <th class="purchase-amount-heading text-right">@lang('purchase.purchase_tax')</th>
                <th class="purchase-amount-heading text-right">@lang('purchase.total_after_tax')</th>
                <th class="purchase-amount-heading text-right">@lang('purchase.grand_total')</th>
                @if (empty($is_purchase_order_list))
                    <th class="purchase-amount-heading text-right">@lang('purchase.payment_due') &nbsp;&nbsp;<i class="fa fa-info-circle text-info no-print" data-toggle="tooltip"
                            data-placement="bottom" data-html="true"
                            data-original-title="{{ __('messages.purchase_due_tooltip') }}" aria-hidden="true"></i></th>
                    <th>@lang('purchase.lot_number')</th>
                @endif
                <th>@lang('lang_v1.added_by')</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td colspan="@if (empty($is_purchase_order_list)) 5 @else 4 @endif"></td>
                <td><strong>@lang('sale.total'):</strong></td>
                <td id="footer_status_count"></td>
                <td id="footer_payment_status_count"></td>
                <td><span class="display_currency" id="footer_total_before_tax" data-currency_symbol ="true"></span></td>
                <td><span class="display_currency" id="footer_total_tax" data-currency_symbol ="true"></span></td>
                <td><span class="display_currency" id="footer_total_after_tax" data-currency_symbol ="true"></span></td>
                <td><span class="display_currency" id="footer_purchase_total" data-currency_symbol ="true"></span></td>
                @if (empty($is_purchase_order_list))
                    <td class="text-left"><small>@lang('report.purchase_due') - <span class="display_currency" id="footer_total_due"
                                data-currency_symbol ="true"></span><br>
                            @lang('lang_v1.purchase_return') - <span class="display_currency" id="footer_total_purchase_return_due"
                                data-currency_symbol ="true"></span>
                        </small></td>
                    <td></td>
                @endif
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

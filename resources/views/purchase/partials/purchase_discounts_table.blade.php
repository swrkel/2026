{{-- CENTER: Business + Report Name + Date --}}
<div class="row mb-3 align-items-center">
    <div class="col-12 text-center">
        <small style="font-size:12px; font-weight: bold;">
            Purchase from
            <span id="report_from_date" style="margin-left: 15px; margin-right: 5px;"></span>
            To
            <span id="report_to_date" style="margin-left: 10px"></span>
        </small>
    </div>
</div>


<div class="table-responsive">
    <table class="table table-bordered table-striped ajax_view" id="purchase_discounts_table" style="width: 100%;">
        <thead>
            <tr>
                <th class="notexport">@lang('messages.action')</th>
                <th>@lang('messages.date')</th>
                <th>P. Invoice No</th>
                <th>@lang('purchase.ref_no')</th>
                <th>@lang('purchase.location')</th>
                <th>@lang('purchase.supplier')</th>
                <th>@lang('purchase.product')</th>
                <th>@lang('purchase.discount_amount')</th>
                <th>@lang('purchase.total_after_tax')</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td colspan="7">
                    <strong>@lang('sale.total'):</strong>
                </td>
                <td>
                    <span class="display_currency" id="footer_discount_total" data-currency_symbol="true"></span>
                </td>
                <td>
                    <span class="display_currency" id="footer_total_after_tax" data-currency_symbol="true"></span>
                </td>
            </tr>
        </tfoot>

    </table>
</div>

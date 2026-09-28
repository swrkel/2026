@php
  $custom_labels = json_decode(session('business.custom_labels'), true);
  $product_custom_field1 = !empty($custom_labels['product']['custom_field_1']) ? $custom_labels['product']['custom_field_1'] : __('lang_v1.product_custom_field1');
  $product_custom_field2 = !empty($custom_labels['product']['custom_field_2']) ? $custom_labels['product']['custom_field_2'] : __('lang_v1.product_custom_field2');
  $product_custom_field3 = !empty($custom_labels['product']['custom_field_3']) ? $custom_labels['product']['custom_field_3'] : __('lang_v1.product_custom_field3');
  $product_custom_field4 = !empty($custom_labels['product']['custom_field_4']) ? $custom_labels['product']['custom_field_4'] : __('lang_v1.product_custom_field4');
@endphp
<style>
    #stock_summary_table th { white-space: nowrap; }
    #stock_summary_table th:nth-child(5),
    #stock_summary_table th:nth-child(8) { white-space: normal; width: 70px; }
</style>
<table class="table table-bordered table-striped nowrap" id="stock_summary_table" style="width: 100%;">
    <thead>
        <tr>
            <th>@lang('business.product')</th>
            <th>@lang('product.sku')</th>
            <th>@lang('product.category')</th>
            <th>@lang('sale.location')</th>
            <th>@lang('report.opening_stock')</th>
            <th>@lang('report.total_unit_purchased')</th>
            <th>@lang('report.total_unit_sold')</th>
            <th>@lang('report.current_stock')</th>
            <th>@lang('report.store_quantities')</th>
            <th>@lang('report.stock_adjustment')</th>
            <th>@lang('report.remarks')</th>
        </tr>
    </thead>
    <tbody></tbody>
    <tfoot>
        <tr class="bg-gray font-17 text-center footer-total">
            <td colspan="4"><strong>@lang('sale.total'):</strong></td>
            <td class="footer_opening_stock"></td>
            <td class="footer_total_purchased"></td>
            <td class="footer_total_sold"></td>
            <td class="footer_total_stock"></td>
            <td></td>
            <td class="footer_total_adjusted"></td>
            <td></td>
        </tr>
    </tfoot>
</table>

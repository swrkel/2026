@php
    $currencyDecimals = (int) data_get($meta, 'currency_decimals', 2);
    $rows = (array) data_get($data, 'rows', []);
    $grandTotal = (float) data_get($data, 'summary.grand_total', data_get($data, 'total', 0));
@endphp

<div class="mgmt-summary-row">
    <div>
        <small>Total Sold Quantity</small>
        <strong>{{ number_format((float) data_get($data, 'summary.sold_qty', 0), 3) }}</strong>
    </div>
    <div>
        <small>Total Discount</small>
        <strong>{{ number_format((float) data_get($data, 'summary.total_discount', 0), $currencyDecimals) }}</strong>
    </div>
    <div class="mgmt-summary-primary">
        <small>Total Sales</small>
        <strong>{{ number_format($grandTotal, $currencyDecimals) }}</strong>
    </div>
</div>

@if(!empty($rows))
    <div class="table-responsive">
        <table class="mgmt-report-table">
            <thead>
                <tr>
                    <th>Product Sub Category</th>
                    <th class="num">Sold Qty</th>
                    <th class="num">Unit Price</th>
                    <th class="num">Discount</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @php
                        // The fallbacks keep previously saved snapshots displayable.
                        $subCategory = data_get($row, 'sub_category', data_get($row, 'label', 'Uncategorized'));
                        $rowTotal = (float) data_get($row, 'total', data_get($row, 'amount', 0));
                    @endphp
                    <tr>
                        <td>{{ $subCategory ?: 'Uncategorized' }}</td>
                        <td class="num">{{ number_format((float) data_get($row, 'sold_qty', 0), 3) }}</td>
                        <td class="num">{{ number_format((float) data_get($row, 'unit_price', 0), $currencyDecimals) }}</td>
                        <td class="num">{{ number_format((float) data_get($row, 'discount', 0), $currencyDecimals) }}</td>
                        <td class="num"><strong>{{ number_format($rowTotal, $currencyDecimals) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="num">Grand Total</th>
                    <th class="num">{{ number_format($grandTotal, $currencyDecimals) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
@else
    <div class="mgmt-empty-state mgmt-empty-state-compact">
        <i class="fa fa-shopping-cart"></i>
        <h3>No sales found</h3>
        <p>No product sub-category sales were found for the selected report scope.</p>
    </div>
@endif

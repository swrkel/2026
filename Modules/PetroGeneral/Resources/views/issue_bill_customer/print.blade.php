<style>
    body.print-80 #invoice_table th:nth-child(1),
    body.print-80 #invoice_table td:nth-child(1) {
        width: 50%;
    }

    body.print-80 #invoice_table th:nth-child(2),
    body.print-80 #invoice_table td:nth-child(2) {
        width: 40%;
    }

    body.print-80 #invoice_table th:nth-child(3),
    body.print-80 #invoice_table td:nth-child(3) {
        width: 50%;
    }

    #invoice_footer {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        text-align: left;
        font-size: 12px;
        border-top: 1px solid #ccc;
        padding: 5px 0;
        background: #fff;
    }
</style>
<div class="col-md-12 class="{{ $issue_customer_bill_setting->print_option == 2 ? 'print-80' : 'print-a4' }}"">
<div class="title text-center">
    <h3>{{$business_location->name}}<br></h3>
    <h5>{{$business_location->landmark}}<br></h5>
    <h5>{{$business_location->alternate_number}}</h5>
</div>

<div class="row">
    <div class="text-center">
        <h3>@lang('petrogeneral::lang.customer_bill')</h3>
    </div>
    <div style="display: flex; justify-content: space-between">
        <div>
            <div>
                <label>@lang('petrogeneral::lang.date_time'): </label> {{$issue_customer_bill->date}}
            </div>
            <div><label>@lang('petrogeneral::lang.order_no'):</label> {{$issue_customer_bill->order_bill_no}}</div>
            <div><label>@lang('petrogeneral::lang.customer_name'):</label> {{$issue_customer_bill->customer_name}}</div>
            <div><label>@lang('petrogeneral::lang.vehicle_no'):</label> {{$issue_customer_bill->reference}}</div>
        </div>
        <div>
            <div>
                <label>@lang('petrogeneral::lang.bill_no'):</label> {{$issue_customer_bill->customer_bill_no}}
            </div>
            @if($issue_customer_bill_setting->show_pump)
                <div>
                    <label>@lang('petrogeneral::lang.pump'):</label> {{$issue_customer_bill->pump_name}}
                </div>
            @endif
            @if($issue_customer_bill_setting->show_pump_operator)
                <div>
                    <label>@lang('petrogeneral::lang.pump_operator'):</label> {{$issue_customer_bill->operator_name}}
                </div>
            @endif
        </div>
    </div>
</div>
<div class="row">
    <div>
        <table class="table-bordered table-striped table" id="invoice_table">
            <thead>
            <th>@lang('petrogeneral::lang.product')</th>
            <th>@lang('petrogeneral::lang.unit_price')</th>
            <th>@lang('petrogeneral::lang.qty')</th>
            <th>@lang('petrogeneral::lang.sub_total')</th>
            </thead>

            <tbody>
            @foreach ($bill_details as $item)
                <tr>
                    <td>
                        {{$item->product_name}}
                    </td>
                    <td style="text-align: right">
                        {{number_format($item->unit_price, $currency_precision, '.', ',')}}
                    </td>
                    <td style="text-align: right">
                        {{number_format($item->qty, $currency_precision, '.', ',')}}
                    </td>
                    <td style="text-align: right">
                        {{number_format($item->unit_price * $item->qty, $currency_precision, '.', ',')}}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3">@lang('petrogeneral::lang.total_before_discount')</td>
                <td style="text-align: right">{{$total_before_discount}}</td>
            </tr>
            <tr>
                <td colspan="3">@lang('petrogeneral::lang.discount')</td>
                <td style="text-align: right">{{$total_discount}}</td>
            </tr>
            <tr>
                <td colspan="3">@lang('petrogeneral::lang.total_after_discount')</td>
                <td style="text-align: right">{{$total_after_discount}}</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<div id="invoice_footer">
    <p>{{$admin_invoice_footer}}</p>
</div>
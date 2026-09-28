<div style="width: 50%; margin-left: 25%">
    <div>
        <div style="text-align: center;">
            {{$business_details->name}}
            <br>
            {{$pump_operator->name}}
            <br>
            {{\Carbon::now()}}
        </div>
    </div>
    <div>
        <br>
        <br>
        <table id="meter_sale_table">
            <tbody>
                <tr>
                    <td>@lang('petrogeneral::lang.current_amount' )</td>
                    <td>{{$daily_collection->current_amount}}</td>
                </tr>
                <tr>
                    <td>@lang('petrogeneral::lang.total_collection' )</td>
                    <td>{{$daily_collection->balance_collection}}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <br>
    <br>
    <div>
        @lang('petrogeneral::lang.cash_received_by')
    </div>
</div>
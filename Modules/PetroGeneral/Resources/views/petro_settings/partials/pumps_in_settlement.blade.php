@php
    $pumps_sold = array();
    $pump_settlement_nos = $pump_settlement_nos ?? [];
@endphp
@foreach($pumps_sold_list as $key => $pump)
    <div class="col-md-6">
        <h5><b>{{$pump}}</b></h5>
    </div>
    <div class="col-md-6">
        @php
            $pumps_sold[] = $key;
            // IS2326: settlement numbers are resolved in the controller (matches
            // meter_sales.settlement_no against both settlements.id and the code).
            $settlements = $pump_settlement_nos[(int) $key] ?? [];
        @endphp

        {{ implode(', ', $settlements) }}
    </div>
    <div class="clearfix"></div>
@endforeach
<input type="hidden" name="sold_pumps" value="{{json_encode($pumps_sold)}}">
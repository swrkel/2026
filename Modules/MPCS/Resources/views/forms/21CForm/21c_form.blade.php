<style>
   .rows {
    padding: 0 !important; 
    margin: 0 !important;
}
.full-width-input {
    width: 100% !important;
    min-width: 80px; /* reduced to allow auto-resize of columns */
    box-sizing: border-box;
    display: block;
    padding: 5px;
    margin: 0;
    border: 1px solid #fff;
    height: 100%;
    text-align: right;
}
.full-width-input-qty {
    width: 100% !important;
    min-width: 80px; /* reduced to allow auto-resize of columns */
    box-sizing: border-box;
    display: block;
    padding: 5px;
    margin: 0;
    border: 1px solid #fff;
    height: 100%;
    text-align: right;
}
.table tbody tr td.rows {
    padding: 0 !important;
    vertical-align: middle !important;
}
.skyblue-border {
        border-color: skyblue !important;
    }
    /* Table styling for sticky headers/columns */
    #form_21c_table {
        border-collapse: separate !important;
        border-spacing: 0;
    }

    /* 1. Horizontal Freeze: Description column (fixed when scrolling left/right) */
    #form_21c_table thead tr:first-child th:first-child,
    #form_21c_table tbody td:first-child {
        position: sticky;
        left: 0;
        z-index: 100;
        background-color: white !important;
        min-width: 200px;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        border-right: 1px solid lightgray !important;
    }

    /* 2. Vertical Freeze: Top header rows (fixed when scrolling up/down) */
    #form_21c_table thead tr:nth-child(1) th {
        position: sticky;
        top: 0;
        z-index: 110;
        background-color: #f7f7f7 !important;
        box-shadow: 0 2px 2px rgba(0,0,0,0.1);
    }
    
    /* Row 2 of header needs a 'top' offset equal to height of Row 1 */
    #form_21c_table thead tr:nth-child(2) th {
        position: sticky;
        top: 45px; 
        z-index: 110;
        background-color: #f7f7f7 !important;
        box-shadow: 0 2px 2px rgba(0,0,0,0.1);
    }

    /* The Corner: Top-left header cell needs the highest z-index */
    #form_21c_table thead tr:nth-child(1) th:first-child {
        z-index: 120;
    }

    /* Pump block: disable sticky first column so meter labels don’t stack/overlap when scrolling */
    #form_21c_table tbody tr.pump-row td:first-child,
    #form_21c_table tbody tr.pump-meter-data td:first-child {
        position: static;
        box-shadow: none;
        vertical-align: middle;
    }

    #form_21c_table tbody tr.pump-meter-data td:first-child {
        vertical-align: top;
        padding: 6px 8px;
        white-space: normal;
    }

    #form_21c_table tbody tr.pump-row td:first-child {
        padding: 6px 8px;
    }

    /* Footer under table — keep checkbox/signature block out of the column grid */
    .f21c-after-table {
        margin-top: 16px;
        clear: both;
    }

    .f21c-document-footer {
        clear: both;
        margin-top: 8px;
    }

    .f21c-form-meta {
        line-height: 1.4;
    }
    .f21c-form-meta #formno {
        white-space: nowrap;
    }

    /* IS1652: keep old 21C table layout, but place header details at the
       same locations shown in the working sample: Manager left, Date right,
       Form No far-right. */
    .f21c-meta-line {
        display: grid;
        grid-template-columns: 28% 34% 38%;
        align-items: center;
        width: 100%;
        margin: 0 0 2px 0;
        font-weight: bold;
        color: #1f2937;
    }
    .f21c-meta-manager {
        text-align: left;
        padding-left: 0;
        white-space: nowrap;
    }
    .f21c-meta-f16 {
        text-align: center;
        white-space: nowrap;
        min-height: 18px;
    }
    .f21c-meta-right {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 18px;
        white-space: nowrap;
        text-align: right;
    }
    .f21c-meta-right #openingdate,
    .f21c-meta-right #formno {
        white-space: nowrap;
    }
    @media print {
        .f21c-meta-line {
            grid-template-columns: 28% 34% 38%;
            font-size: 12px;
        }
        .f21c-meta-right {
            gap: 12px;
        }
    }



    /* IS1652 V17: Professional compact grid rows.
       The old table can place the small F16/F17 number span above the input, which
       makes the row look split/misaligned. Keep the actual value in the input and
       hide the helper spans inside the table so every cell in the row has one
       consistent height and baseline. */
    #form_21c_table {
        font-size: 14px;
    }
    #form_21c_table thead th {
        text-align: center !important;
        vertical-align: middle !important;
        height: 30px !important;
        padding: 3px 4px !important;
        line-height: 1.15 !important;
        font-weight: 700;
    }
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) {
        height: 44px !important;
    }
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) > td {
        height: 44px !important;
        padding: 0 4px !important;
        vertical-align: middle !important;
        line-height: 44px !important;
        border-bottom: 1px solid #edf2f7;
    }
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) > td:first-child {
        padding-left: 8px !important;
        line-height: 44px !important;
    }
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) td.rows {
        padding: 0 3px !important;
        vertical-align: middle !important;
        line-height: normal !important;
    }
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) td.rows > span[id$="_table"],
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) td.rows > span#today_f16_nos_table,
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) td.rows > span#previous_f16_nos_table,
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) td.rows > span#today_f17_nos_table,
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) td.rows > span#f16_total_purchase_nos_table,
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) td.rows > span#opening_stock_f22_nos_table {
        display: none !important;
    }
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) .full-width-input,
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) .full-width-input-qty {
        height: 36px !important;
        min-height: 36px !important;
        line-height: 36px !important;
        margin: 4px 0 !important;
        padding: 0 8px !important;
        border: 1px solid #d9ebfb !important;
        border-radius: 10px !important;
        background: #fff !important;
        box-shadow: none !important;
        text-align: right !important;
        font-weight: 600;
    }
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) td:first-child + td .full-width-input,
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) td:first-child + td .full-width-input-qty {
        text-align: right !important;
    }
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) input[readonly] {
        background: #fff !important;
        font-weight: 700;
    }
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) tr,
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) td {
        overflow: hidden;
    }

    #form_21c_table thead tr.f16-total-row,
    #form_21c_table thead tr.f16-total-row th,
    #form_21c_table thead tr.f16-total-row td {
        display: none !important;
        visibility: collapse !important;
        height: 0 !important;
        padding: 0 !important;
        border: 0 !important;
    }

    #form_21c_table tbody tr.pump-row td,
    #form_21c_table tbody tr.pump-meter-data td {
        min-height: 32px;
    }
    #form_21c_table tbody tr.pump-row .full-width-input,
    #form_21c_table tbody tr.pump-meter-data .full-width-input,
    #form_21c_table tbody tr.pump-row .full-width-input-qty,
    #form_21c_table tbody tr.pump-meter-data .full-width-input-qty {
        min-height: 26px;
        height: auto;
    }


    /* IS1652 V23: compact content-aware columns.
       Keep all headings on one line. Column pairs expand only when the
       sub-category caption requires more room; numeric cells stay compact. */
    .table-responsive,
    .f21c-table-scroll,
    #form_21c_table_wrapper {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: auto !important;
        overflow-y: visible !important;
        -webkit-overflow-scrolling: touch;
    }

    #form_21c_table {
        width: max-content !important;
        min-width: 100% !important;
        table-layout: auto !important;
        border-collapse: separate;
        border-spacing: 0;
    }

    #form_21c_table th,
    #form_21c_table td {
        white-space: nowrap !important;
        vertical-align: middle;
    }

    /* Compact fixed utility columns. */
    #form_21c_table thead tr:first-child th:first-child,
    #form_21c_table tbody td:first-child {
        width: 230px !important;
        min-width: 230px !important;
        max-width: 230px !important;
    }
    #form_21c_table thead tr:first-child th:nth-child(2),
    #form_21c_table tbody td:nth-child(2) {
        width: 92px !important;
        min-width: 92px !important;
        max-width: 92px !important;
    }

    /* IS1652 V24: product columns start at the smallest useful width and
       grow only when a real value needs more characters. This applies only
       to the fuel-product/sub-category area (column 3 onwards). */
    #form_21c_table thead tr:nth-child(2) th {
        width: 1% !important;
        min-width: 0 !important;
        padding-left: 7px !important;
        padding-right: 7px !important;
        white-space: nowrap !important;
    }

    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) > td:nth-child(n+3) {
        width: 1% !important;
        min-width: 0 !important;
        padding-left: 3px !important;
        padding-right: 3px !important;
    }

    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) > td:nth-child(n+3) .full-width-input-qty,
    #form_21c_table tbody tr:not(.pump-row):not(.pump-meter-data) > td:nth-child(n+3) .full-width-input {
        width: var(--f21c-value-width, 6ch) !important;
        min-width: 6ch !important;
        max-width: none !important;
        box-sizing: content-box !important;
        padding-left: 8px !important;
        padding-right: 8px !important;
    }

    /* Amount columns need enough room for their one-line heading, but not a
       fixed oversized width. Their values can still expand beyond this. */
    #form_21c_table thead tr:nth-child(2) th:nth-child(even) {
        min-width: 10.5ch !important;
    }

    #form_21c_table thead th[colspan="2"] {
        width: 1% !important;
        min-width: 0 !important;
        max-width: none !important;
        padding-left: 8px !important;
        padding-right: 8px !important;
        text-align: center !important;
        white-space: nowrap !important;
    }

    @media (max-width: 1200px) {
        #form_21c_table thead tr:first-child th:first-child,
        #form_21c_table tbody td:first-child {
            width: 210px !important;
            min-width: 210px !important;
            max-width: 210px !important;
        }
    }

</style>
<!-- Main content -->
<section class="content" style="padding: 10px;"> 
{!! Form::open(['id' => 'f21c_form']) !!}
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

            @php
                // Use controller-provided variables if available, otherwise compute inline (backward compat)
                if (!isset($location_options) || !isset($default_location_id)) {
                    $permitted_locations = auth()->user()->permitted_locations();
                    $locations_for_user = $business_locations;
                    if ($permitted_locations !== 'all' && is_array($permitted_locations)) {
                        $locations_for_user = collect($business_locations)->only($permitted_locations);
                    }
                    $location_keys = collect($locations_for_user)->keys()->values();
                    
                    if (count($locations_for_user) === 1) {
                        $default_location_id = $location_keys->first();
                        $location_options = collect($locations_for_user)->toArray();
                    } else {
                        $default_location_id = '';
                        $location_options = ['' => __('lang_v1.all')] + collect($locations_for_user)->toArray();
                    }
                }
            @endphp

            <div class="col-md-3" id="location_filter">
                <div class="form-group">
                    {!! Form::label('f21c_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select(
                        'f21c_location_id',
                        $location_options,
                        $default_location_id,
                        ['class' => 'form-control select2', 'id' => 'f21c_location_id', 'style' => 'width:100%']
                    ); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('form_21c_date_range', __('report.date') . ':') !!}
                    {!! Form::text('form_21c_date_range', @format_date(date('Y-m-d')), ['class' => 'form-control dropdown-toggle input_number customer_transaction_date', 'id' =>
                      'form_21c_date_range','required','readonly']); !!}
                </div>
            </div>



           

            @endcomponent
        </div>
    </div>
    <div class="row" >
                    <div class="row text-right" style="display: flex; justify-content: end;">
                     
                    {{--
                        IS-1936: this MUST be type="button", not type="submit".

                        It sits inside Form::open(['id' => 'f21c_form']) above, which
                        has no url/method, so it defaults to POST to the CURRENT url -
                        /mpcs/21CForm. That route is registered GET only
                        (routes.php: Route::get('/21CForm', 'F21FormController@index')),
                        so submitting produced
                            MethodNotAllowedHttpException
                            The POST method is not supported for route mpcs/21CForm.

                        The click handler opens the print window, so the submit was
                        pure collateral: the browser navigated the underlying page to
                        the 405 while the preview was on top, and the error was only
                        revealed once the preview was cancelled. Nothing consumes
                        submit_type=print server-side - there is no POST route at all -
                        so the value/name are dropped with it.
                    --}}
                     <button type="button" id="print_div"
                     class="btn btn-primary pull-right">@lang('mpcs::lang.print')</button>
                    </div>
                </div>
    <div class="row" id ="print_content">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            <div class="col-md-12">
                {{-- Row 1: Business Location Name + 21C label --}}
                <div class="row print-hide-header">
                    <div class="col-md-10">
                        <div class="text-center">
                            {{-- JS updateLocationName() sets this on every page load --}}
                            <h4 style="font-weight: bold;" id="print_location_name"></h4>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="row text-right" style="display: flex; justify-content: end;">
                            <h3 style="font-weight: bold;">21C</h3>
                        </div>
                    </div>
                </div>

                {{-- Row 2: Balance Stock For The Day (moved here, font +50%) --}}
                <div class="row" style="margin-bottom: 4px;">
                    <div class="col-md-12 text-center">
                        {{-- IS2109 #2: the trailing " :" removed. Nothing is ever appended after
                             it, so it rendered as a heading followed by a stray colon. --}}
                        <h4 id="balance_stock_label" style="font-weight: bold; font-size: 1.5em;">Balance Stock For The Day</h4>
                    </div>
                </div>

                {{-- Row 3: Details placed like the old working sample --}}
                <div class="row">
                    <div class="col-md-12">
                        <div class="f21c-form-meta f21c-meta-line">
                            <div class="f21c-meta-manager">
                                Manager Name: <span id="manager_name">{{ optional($settings)->manager_name }}</span>,
                            </div>
                            <div class="f21c-meta-f16">
                                F16 Form No: <span id="f16_form_nos_display">-</span>
                            </div>
                            <div class="f21c-meta-right">
                                <span id="openingdate"></span>
                                <span id="formno">Form No: {{ $F21c_from_no }}</span>
                            </div>
                        </div>
                        <input type="hidden" name="formnovalue" value="{{ $F21c_from_no }}">
                    </div>
                </div>
                       
<div class="table-responsive" style="max-height: 80vh; overflow: auto;">
    @php
    $columnsArray = array(
        'receipts' => 'Receipts',
        'today' => 'Today',
        'previous_day' => 'Previous Day',
        'total_receipts' => 'Total Receipts',
        'price_increment' => 'Price Increment',  // NEW section header
        'price_inc_today' => 'Today',             // row 12
        'price_inc_previous_day' => 'Previous Day', // row 13
        'price_inc_total' => 'Total Price Increment',    // row 14 = 12+13 (Image 20)
        'opening_stock' => 'Opening Stock',
        'total_receipts_today' => 'Total (Grand) Receipts Today',
        'issue' => 'Issue',
        'cash_for_today' => 'Cash for Today',
        'credit_for_today' => 'Credit for Today',
        'cooperative_section_for_today' => 'Cooperative Section for Today',
        'total_issues' => 'Total Issues',
        'issues_up_to_last_day' => 'Issues up to Last Day',
        'total_issues_one' => 'Total Issues (1)',
        'price_reduction' => 'Price Reduction',
        'price_discounts_for_today' => 'Price Reduction Today',
        'pre_date' => 'Price Reduction Previous Date',
        'total_discounts' => 'Total Discounts (2)',
        'total_for_today_one_plus_two' => 'Total for Today (1 + 2)',
        'balances' => 'Balances',
        
        'pump_meters' => 'Pump Meters'
    );

    $pumps = ($settings) ? json_decode($settings->pumps, true) : [];

    // Resolve business id inside the view to avoid undefined variable errors
    $business_id = session()->get('business.id') ?? session()->get('user.business_id');

    // MA-002: was `use Modules\Petro\Entities\Pump;` declared mid-view.
    // A use statement inside a @php block is fragile in a compiled Blade
    // template, and it made MPCS depend on the Petro module. Reference the
    // MPCS-owned read model for the shared `pumps` table by its full name
    // instead - same data, no cross-module dependency.
    $pumps_name = \Modules\MPCS\Entities\Pump::where('business_id', $business_id)->pluck('pump_name', 'id');
    $groupedPumps = [];
$maxPumpCount = 0;

foreach ($pump_operator as $pump) {
    $groupedPumps[$pump->category_id][] = $pump;
    $maxPumpCount = max($maxPumpCount, count($groupedPumps[$pump->category_id]));
}
   
    @endphp    

    <table style="border-left: 1px solid lightgray; table-layout:auto; width:100%;" id="form_21c_table">
    <thead>
        <tr >
            <th rowspan="2" style="border: 1px solid lightgray; text-align: center; ">@lang('mpcs::lang.description')</th>
            <th rowspan="2" style="border: 1px solid lightgray; ">@lang('mpcs::lang.no')</th>
            @foreach ($fuelCategory as $categoryName)
                <th colspan="2" style="border-top: 2px solid skyblue; border-left: 2px solid skyblue; border-right: 2px solid skyblue; border-bottom: none;" >{{ $categoryName }}</th>
            @endforeach
            <th colspan="2" style="border-top: 2px solid skyblue; border-left: 2px solid skyblue; border-right: 2px solid skyblue; border-bottom: none; text-align: center;">Total</th>
        </tr>
    
        <tr>
            @foreach ($fuelCategory as $categoryName)
                <th style="border-top: 2px solid skyblue; border-left: 2px solid skyblue;   border-bottom: none;" >Qty</th>
                <th style="border-top: 2px solid skyblue; border-left: 1px solid lightgray;  border-right: 2px solid skyblue; border-bottom: none;">Total Amount</th>
            @endforeach
            <th  style="border-top: 2px solid skyblue; border-left: 2px solid skyblue;   border-bottom: none;" >Qty</th>
            <th style="border-top: 2px solid skyblue; border-left: 1px solid lightgray;  border-right: 2px solid skyblue; border-bottom: 1px solid lightgray;">Total Amount</th>
        </tr>
        {{-- F16 purchase qty (No/item 6) and historical value (Value/item 7) per sub-category --}}
        <tr class="f16-total-row" hidden aria-hidden="true" style="display: none !important; visibility: collapse; height: 0; overflow: hidden; background-color: #f0f8ff;">
            <th style="border: 1px solid lightgray; font-size:0.8em; color:#555; white-space:nowrap;">F16 Total</th>
            <th style="border: 1px solid lightgray;"></th>
            @foreach ($fuelCategory as $categoryKey => $categoryName)
                {{-- Qty (item 6) --}}
                <th style="border: 1px solid lightgray; text-align:center; padding:2px;">
                    <input type="text"
                        id="f16_no_cat_{{ $categoryKey }}"
                        value="0"
                        readonly
                        style="width:100%; text-align:right; border:none; background:transparent; font-weight:bold; font-size:0.9em;">
                </th>
                {{-- Value = Qty × historical unit sale price inc. tax (item 7) --}}
                <th style="border: 1px solid lightgray; text-align:center; padding:2px;">
                    <input type="text"
                        id="f16_val_cat_{{ $categoryKey }}"
                        value="0"
                        readonly
                        style="width:100%; text-align:right; border:none; background:transparent; font-weight:bold; font-size:0.9em;">
                </th>
            @endforeach
            <th style="border: 1px solid lightgray;"></th>
            <th style="border: 1px solid lightgray;"></th>
        </tr>
    </thead>
    <tbody>
    @foreach ($columnsArray as $colKey => $column)
    @php
        $isHeaderRow = in_array($colKey, ['receipts', 'issue', 'price_increment', 'price_reduction', 'pump_meters']);
        $isAutoCalc  = in_array($colKey, ['price_inc_today', 'price_inc_previous_day', 'previous_day', 'total_receipts', 'total_receipts_today', 'total_issues', 'total_issues_one', 'total_discounts', 'total_for_today_one_plus_two', 'balances', 'price_inc_total']);
        $isPriceInc  = in_array($colKey, ['price_inc_today', 'price_inc_previous_day', 'price_inc_total']);
        $color = $isHeaderRow ? 'color: skyblue; font-weight: bold;' : '';
        $isFormno = in_array($colKey, ['opening_stock']);
    @endphp
    <tr>
        <td style="{{ $color }} white-space: nowrap;">{{ $column }}</td>

        @if ($isHeaderRow)
            {{-- No (1) + fuel Qty/Amt (2n) + Total Qty/Amt (2) = 2n+3 --}}
            <td colspan="{{ count($fuelCategory) * 2 + 3 }}"></td>
        @else
        @if($isFormno)
            <td class="rows" style="border-left: 1px solid lightgray;">
                <span id="opening_stock_f22_nos_table" style="font-size:0.8em; color:#555;"></span>
                <input type="text" name="{{ $colKey }}[no]" id="opening_stock_no" value="{{$formNo ?? ''}}" class="full-width-input-qty" readonly>
            </td>
        @else
            <td class="rows" style="border-left: 1px solid lightgray;">
                @if($colKey === 'today')
                    {{-- F16 form numbers shown in No column for Today row --}}
                    <span id="today_f16_nos_table" style="font-size:0.8em; color:#555;"></span>
                @elseif($colKey === 'previous_day')
                    {{-- F16 form numbers shown in No column for Previous Day row --}}
                    <span id="previous_f16_nos_table" style="font-size:0.8em; color:#555;"></span>
                @elseif($colKey === 'f16_total_purchase')
                    {{-- F16 form numbers also shown for Total Purchase Amount --}}
                    <span id="f16_total_purchase_nos_table" style="font-size:0.8em; color:#555;"></span>
                @elseif($colKey === 'price_inc_today')
                    {{-- F17 form numbers shown in No column for Price Increment Today row --}}
                    <span id="today_f17_nos_table" style="font-size:0.8em; color:#555;"></span>
                @endif
                <input type="text" name="{{ $colKey }}[no]" id="{{ $colKey }}_no" class="full-width-input-qty" readonly>
            </td>
        @endif

        @foreach ($fuelCategory as $categoryKey => $categoryName)
                <td class="rows" style="border-top: 2px solid skyblue; border-left: 2px solid skyblue;">
                    <input 
                        type="text"
                        name="{{ $colKey }}[{{ $categoryKey }}][qty]" 
                        class="full-width-input-qty qty-input" 
                        id="{{ $colKey }}_qty_{{ $categoryKey }}"
                        data-type="{{ $colKey }}" 
                        data-category="{{ $categoryKey }}"
                        @if ($isAutoCalc) readonly @endif
                    >
                </td>
                <td class="rows" style="border-top: 2px solid skyblue; border-left: 1px solid lightgray; border-right: 2px solid skyblue;">
                    <input 
                        type="text"
                        name="{{ $colKey }}[{{ $categoryKey }}][val]" 
                        class="full-width-input val-input" 
                        id="{{ $colKey }}_val_{{ $categoryKey }}"
                        data-type="{{ $colKey }}" 
                        data-category="{{ $categoryKey }}"
                        @if ($isAutoCalc) readonly @endif
                    >
                </td>
        @endforeach

            <td class="rows">
                <input type="text" id="{{ $colKey }}_qty_total" value="" readonly class="full-width-input-qty">
            </td>
            <td class="rows" style="border-top: 2px solid skyblue; border-left: 1px solid lightgray; border-right: 2px solid skyblue; border-bottom: 1px solid lightgray;">
                <input type="text" id="{{ $colKey }}_val_total" value="" readonly class="full-width-input">
            </td>
            @endif
        
    </tr>
    @if ($colKey == 'pump_meters')
                    @for ($i = 0; $i < $maxPumpCount; $i++)
                        <!-- Pump Row -->
                        <tr class="pump-row">
                            <td style="font-weight: bold; color: red;">Pump</td>
                            <td class="rows" style="border-left: 1px solid lightgray; border-bottom: 1px solid lightgray;">
                                <input type="number" step="0.01" name="pump_meters[no][]" class="full-width-input">
                            </td>
                            @foreach ($fuelCategory as $categoryKey => $categoryName)
                                @php
                                    $category = \App\Category::find($categoryKey);
                                    $pump = $groupedPumps[$category->id][$i] ?? null;
                                @endphp
                                @if ($pump)
                                    <td colspan="2" style="font-weight: bold; color: red; border-left: 2px solid skyblue; border-right: 2px solid skyblue;">
                                        {{ $pump->pump_no }}
                                        <input type="hidden" name="pump_ids[{{ $category->id }}][]" value="{{ $pump->pump_id }}">
                                    </td>
                                @else
                                    <td colspan="2" style="border-left: 2px solid skyblue; border-right: 2px solid skyblue;"></td>
                                @endif
                            @endforeach
                            <td colspan="2" style="border-left: 2px solid skyblue; border-right: 2px solid skyblue;"></td>
                        </tr>

                        <!-- Pump Meter Rows -->
                        @foreach (['pump_meter_opening', 'pump_meter_closing', 'issued_qty_for_today'] as $meterType)
                            <tr class="pump-meter-data">
                                <td style="border-left: 1px solid lightgray; border-bottom: {{ $loop->last ? '1px solid lightgray' : 'none' }}">
                                    {{ ucwords(str_replace('_', ' ', $meterType)) }}
                                </td>
                                <td class="rows" style="border-left: 1px solid lightgray; border-bottom: {{ $loop->last ? '1px solid lightgray' : 'none' }}">
                                    <input type="text" step="0.01" name="{{ $meterType }}[no][]" class="full-width-input">
                                </td>
                                @foreach ($fuelCategory as $categoryKey => $categoryName)
                                    @php
                                        $category = \App\Category::find($categoryKey);
                                        $pump = $groupedPumps[$category->id][$i] ?? null;
                                    @endphp
                                    <td colspan="2" style="font-weight: bold;  border-left: 2px solid skyblue; border-right: 2px solid skyblue; border-bottom: {{ $loop->parent->last ? '1px solid lightgray' : 'none' }}">
                                        @if ($pump)
                                            <input type="text" step="0.01" 
                                                name="{{ $meterType }}[{{ $categoryKey }}][val][]" 
                                                class="full-width-input pump-meter-input" 
                                                id="{{ $meterType }}_val_{{ $categoryKey }}_{{ $pump->pump_id }}"
                                                data-pump-id="{{ $pump->pump_id }}"
                                                data-category-id="{{ $categoryKey }}"
                                                data-meter-type="{{ $meterType }}"
                                                @if($meterType == 'issued_qty_for_today') readonly @endif>
                                        @endif
                                    </td>
                                @endforeach
                                <td colspan="2" style="font-weight: bold; color: red; border-left: 2px solid skyblue; border-right: 2px solid skyblue; border-bottom: {{ $loop->parent->last ? '1px solid lightgray' : 'none' }};"></td>
                            </tr>
                        @endforeach
                    @endfor
                @endif



@endforeach

  

    </tbody>
</table>

</div>

            {{-- Outside .table-responsive so layout flow isn’t tied to scroll box height; print CSS expands wrapper fully --}}
            <div id="f21c-document-footer" class="f21c-document-footer">
            <div class="row f21c-after-table">
                        <div class="checkbox">
                            <label>
                                {!! Form::checkbox('finalize', 1, false, ['class' => 'input-icheck', 'id' => 'finalize']); !!}
                                That all the details are entered correctly
                            </label>
                        </div>
                    </div>
                    <br>
                    <div class="row f21c-after-table">
                        <div class="col-md-6">
                            <p>@lang('mpcs::lang.checked_by')____________</p>  <br>
                            <p>@lang('mpcs::lang.date')____________</p> 
                        </div>
                        <div class="col-md-6 text-right">
                            <p>@lang('mpcs::lang.manage_signature')____________</p>  <br>
                            <p>@lang('mpcs::lang.date')____________</p> 
                        </div>
                    </div>
            </div>

            </div>

            {!! Form::close() !!}

            @endcomponent
        </div>
    </div>

</section>
<script>
(function () {
    'use strict';

    function visibleValueLength(input) {
        var value = (input.value || input.getAttribute('value') || '0.00').toString().trim();
        return Math.max(4, value.length);
    }

    function resizeF21CProductColumns() {
        var table = document.getElementById('form_21c_table');
        if (!table) return;

        var bodyRows = table.querySelectorAll('tbody tr:not(.pump-row):not(.pump-meter-data)');
        var maxByColumn = {};

        bodyRows.forEach(function (row) {
            Array.prototype.forEach.call(row.children, function (cell, index) {
                if (index < 2) return; // Description and No are intentionally unchanged.
                var input = cell.querySelector('input.full-width-input, input.full-width-input-qty');
                if (!input) return;
                maxByColumn[index] = Math.max(maxByColumn[index] || 4, visibleValueLength(input));
            });
        });

        bodyRows.forEach(function (row) {
            Array.prototype.forEach.call(row.children, function (cell, index) {
                if (index < 2 || !maxByColumn[index]) return;
                var input = cell.querySelector('input.full-width-input, input.full-width-input-qty');
                if (!input) return;
                var chars = Math.min(Math.max(maxByColumn[index] + 1, 5), 24);
                input.style.setProperty('--f21c-value-width', chars + 'ch');
            });
        });
    }

    function scheduleResize() {
        window.requestAnimationFrame(function () {
            resizeF21CProductColumns();
            setTimeout(resizeF21CProductColumns, 80);
        });
    }

    document.addEventListener('DOMContentLoaded', scheduleResize);
    document.addEventListener('input', function (event) {
        if (event.target.closest && event.target.closest('#form_21c_table')) scheduleResize();
    });
    document.addEventListener('change', function (event) {
        if (event.target.closest && (event.target.closest('#f21c_form') || event.target.closest('#form_21c_table'))) {
            scheduleResize();
        }
    });

    if (window.jQuery) {
        window.jQuery(document).ajaxComplete(scheduleResize);
    }

    var table = document.getElementById('form_21c_table');
    if (table && window.MutationObserver) {
        new MutationObserver(scheduleResize).observe(table, {childList: true, subtree: true});
    }
})();
</script>

<!-- /.content -->


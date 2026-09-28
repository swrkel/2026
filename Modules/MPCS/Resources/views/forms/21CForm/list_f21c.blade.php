<style>
.full-width-input {
    width: 100% !important;
    box-sizing: border-box;
    display: block;
    padding: 5px;
    margin: 0;
    border: 1px solid #fff;
    height: 100%;
    text-align: right; /* Right-align all numeric display values */
}
</style>
<!-- Main content -->
<section class="content" style="padding: 10px;"> <script>
$(document).ready(function() {
    $(document).on('click', '#add_21c_settings_btn', function() {
        let btn = $(this);
        btn.find('.fa-plus').addClass('hide');
        btn.find('.fa-spinner').removeClass('hide');
        btn.prop('disabled', true);
        
        // Failsafe: Reset button after 10 seconds if modal never opens
        setTimeout(function() {
            if (btn.prop('disabled')) {
                btn.find('.fa-plus').removeClass('hide');
                btn.find('.fa-spinner').addClass('hide');
                btn.prop('disabled', false);
            }
        }, 10000);
    });

    // Reset button state when the modal starts to show
    $(document).on('show.bs.modal', '.form_16_a_settings_modal', function () {
        let btn = $('#add_21c_settings_btn');
        btn.find('.fa-plus').removeClass('hide');
        btn.find('.fa-spinner').addClass('hide');
        btn.prop('disabled', false);
    });

    // Also reset if the modal fails to open or is closed
    $(document).on('hidden.bs.modal', '.form_16_a_settings_modal', function () {
        let btn = $('#add_21c_settings_btn');
        btn.find('.fa-plus').removeClass('hide');
        btn.find('.fa-spinner').addClass('hide');
        btn.prop('disabled', false);
    });
});
</script>
<div class="box-tools pull-right">
    {{-- Allow adding settings only once per day (no edit via UI) --}}
    @if (empty($has_today_21c_settings))
        <button type="button"
            class="btn btn-primary btn-modal"
            id="add_21c_settings_btn"
            data-href="{{ action('\Modules\MPCS\Http\Controllers\F21FormController@get21CFormSettings') }}"
            data-container=".form_16_a_settings_modal">
            <i class="fa fa-plus"></i> <span class="btn-text">Add Form 21 C Settings</span>
            <i class="fa fa-spinner fa-spin hide" style="margin-left:5px;"></i>
        </button>
    @endif
</div>
   
<div class="row">
                    <div class="col-md-3 text-red">
                        <h5 style="font-weight: bold;" class="text-red">Starting Form No: {{ $latestForm->starting_number ?? '' }}</h5>                        
                    </div>
                     
                    <div class="col-md-3">
                        <div class="text-center">
                        @if ($latestForm)
                            <h5 style="font-weight: bold;">Opening Date :{{ $latestForm->date }} </h5>
                            @else
                            <h5 style="font-weight: bold;">Opening Date : </h5>
                            @endif    
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center pull-left">
                        @if ($latestForm)
                            <h5 style="font-weight: bold;" class="text-red">Time: {{ $latestForm->time }}</h5>
                            @else
                            <h5 style="font-weight: bold;" class="text-red">Time:</h5>
                        @endif
                        </div>
                    </div>
                </div>

     <div class="row">
       
            @component('components.widget', ['class' => 'box-primary'])
            <div class="col-md-12">
                <div class="box-body" style="margin-top: 20px;">
                    <div class="row">
                        <div class="col-md-12">
                            
                            <div id="msg"></div>
                            @php
    $columnsArray = [
        'receipts' => 'Receipts Section',
        'previous_day' => 'Previous Day Amount',
        'opening_stock' => 'Opening Stock Amount',
        'issue' => 'Issue Section',
        'total_issues' => 'Total Issues Amount'
    ];
@endphp

<table class="table table-bordered" id="form_21c_settings">
    <thead>
        <tr>
            <th class="no-wrap align-middle" rowspan="2">@lang('mpcs::lang.product')</th>
            @foreach ($fuelCategory as $categoryName)
                <th colspan="2" class="text-center">{{ $categoryName }}</th>
            @endforeach
        </tr>
        <tr>
            @foreach ($fuelCategory as $categoryId => $categoryName)
                <th class="text-center">Qty</th>
                <th class="text-center">Val</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($columnsArray as $colKey => $column)
            <tr>
                @php
                    $highlight = in_array($colKey, ['receipts', 'issue']) ? 'color: skyblue; font-weight: bold;' : '';
                @endphp
                <td style="{{ $highlight }} white-space: nowrap; width: auto; max-width: none;">{{ $column }}</td>

                @if (in_array($colKey, ['receipts', 'issue']))
                    <td colspan="{{ count($fuelCategory) * 2 }}"></td>
                @else
                    @foreach ($fuelCategory as $categoryId => $categoryName)
                        @php
                            $qty = $categoriesData[$categoryId][$colKey]['qty'] ?? '';
                            $val = $categoriesData[$categoryId][$colKey]['val'] ?? '';
                            
                            // Get precision values from passed variables or fetch from business settings
                            if (!isset($currency_precision) || !isset($qty_precision)) {
                                $business_id = session()->get('business.id') ?? session()->get('user.business_id');
                                $business_details = \App\Business::find($business_id);
                                if (!isset($currency_precision)) {
                                    $currency_precision = ($business_details && $business_details->currency_precision) ? (int)$business_details->currency_precision : 2;
                                } else {
                                    $currency_precision = (int)$currency_precision;
                                }
                                if (!isset($qty_precision)) {
                                    $qty_precision = ($business_details && $business_details->quantity_precision) ? (int)$business_details->quantity_precision : 2;
                                } else {
                                    $qty_precision = (int)$qty_precision;
                                }
                            } else {
                                $currency_precision = (int)$currency_precision;
                                $qty_precision = (int)$qty_precision;
                            }
                            
                            // Format quantity with quantity precision and comma separators
                            $formatted_qty = '';
                            if ($qty !== '' && $qty !== null) {
                                $formatted_qty = number_format((float)$qty, $qty_precision, session('currency')['decimal_separator'] ?? '.', session('currency')['thousand_separator'] ?? ',');
                            }
                            
                            // Format value with currency precision and comma separators
                            $formatted_val = '';
                            if ($val !== '' && $val !== null) {
                                $formatted_val = number_format((float)$val, $currency_precision, session('currency')['decimal_separator'] ?? '.', session('currency')['thousand_separator'] ?? ',');
                            }
                        @endphp
                        <td>
                            <input type="text"
                                   name="{{ $colKey }}[{{ $categoryId }}][qty]"
                                   value="{{ $formatted_qty }}"
                                   readonly class="full-width-input">
                        </td>
                        <td>
                            <input type="text"
                                   name="{{ $colKey }}[{{ $categoryId }}][val]"
                                   value="{{ $formatted_val }}"
                                   readonly class="full-width-input">
                        </td>
                    @endforeach
                @endif
            </tr>
        @endforeach
    </tbody>
</table>

    
                </div>
            </div>
        </div>

    </div>
            @endcomponent
         
</div>

  
    <div class="modal fade form_16_a_settings_modal"   tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade update_form_16_a_settings_modal"   tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>


</section>
<!-- /.content -->

 

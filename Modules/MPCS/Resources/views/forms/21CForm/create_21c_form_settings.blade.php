@php
    $is_saved = !empty($latestForm);
    $readonly = $is_saved ? 'readonly' : '';
@endphp

<style>
.full-width-input {
    width: 100% !important;
    min-width: 120px; /* increased to allow larger numbers to be visible */
    box-sizing: border-box;
    display: block;
    padding: 5px;
    margin: 0;
    border: 1px solid #ddd;
    height: 100%;
    text-align: right;
    border-radius: 3px;
    overflow: visible;
}
.full-width-input:focus {
    outline: none;
    border-color: #66afe9;
    box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102,175,233,.6);
}
.section-header-row td {
    background-color: #1a3a5c;
    color: skyblue;
    font-weight: bold;
    padding: 6px 8px !important;
}
/* Loading overlay */
#form21c_loading_overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.45);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}
#form21c_loading_overlay .loading-box {
    background: #fff;
    border-radius: 8px;
    padding: 30px 40px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
#form21c_loading_overlay .loading-box i {
    font-size: 36px;
    color: #337ab7;
    margin-bottom: 12px;
    display: block;
}
#form21c_loading_overlay .loading-box p {
    margin: 0;
    font-size: 15px;
    color: #333;
    font-weight: 600;
}
</style>

{{-- Loading Overlay --}}
<div id="form21c_loading_overlay">
    <div class="loading-box">
        <i class="fa fa-spinner fa-spin"></i>
        <p>Saving, please wait...</p>
    </div>
</div>

<div class="modal-dialog" role="document" style="width: 85%;">
    <div class="modal-content">
        @if(!$is_saved)
        {!! Form::open(['url' => action('\Modules\MPCS\Http\Controllers\F21FormController@store21cFormSettings'), 'method' => 'post', 'id' => 'add_21c_form_settings']) !!}
        @endif

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{{ $is_saved ? 'View F 21C Form Settings' : __('mpcs::lang.add_21_c_form_settings') }}</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                {{-- Date --}}
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.opening_date')</label>
                        <div class="input-group">
                            <input type="text" class="form-control @if(!$is_saved) create_21c_datepicker_init @endif" id="create_21c_datepicker" name="datepicker" data-date-format="yyyy/mm/dd" value="{{ $latestForm->date ?? '' }}" {{ $readonly }} required />
                            <div class="input-group-addon">
                                <i class="fa fa-calendar-o"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Time --}}
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.time')</label>
                        <div class="input-group">
                            <input class="form-control @if(!$is_saved) timepicker @endif" id="create_21c_time" name="time" type="{{ $is_saved ? 'text' : 'time' }}" value="{{ $is_saved ? \Carbon\Carbon::parse($latestForm->time)->format('h:i A') : '12:00' }}" {{ $readonly }} required>
                            <div class="input-group-addon">
                                <i class="fa fa-clock-o" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Starting Number --}}
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.form_starting_number') <span class="required" aria-required="true">*</span></label>
                        <input type="text" name="starting_number" class="form-control" value="{{ $starting_number }}" {{ $readonly }} required>
                    </div>
                </div>

                {{-- Manager Name --}}
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.manager_name') <span class="required" aria-required="true">*</span></label>
                        <input type="text" id="create_21c_manager_name" name="manager_name" class="form-control" value="{{ $latestForm->manager_name ?? '' }}" {{ $readonly }} required>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="row">
                <div class="col-md-12">
                    <h4 class="text-center" style="margin: 10px 0 15px;">Receipts & Issues — Previous Day Amounts</h4>
                    <div class="table-responsive">
                        @php
                            $sections = [
                                'receipts_header' => [
                                    'label' => 'Receipts Section',
                                    'is_header' => true,
                                    'color'  => 'skyblue',
                                ],
                                'previous_day' => [
                                    'label' => 'Previous Day Amount',
                                    'is_header' => false,
                                    'type'   => 'both', // qty + val
                                ],
                                'opening_stock' => [
                                    'label' => 'Opening Stock Amount',
                                    'is_header' => false,
                                    'type'   => 'both',
                                ],
                                'issue_header' => [
                                    'label' => 'Issue Section',
                                    'is_header' => true,
                                    'color'  => 'skyblue',
                                ],
                                'total_issues' => [
                                    'label' => 'Previous Day Amount',
                                    'is_header' => false,
                                    'type'   => 'both',
                                ],
                            ];
                        @endphp

                        <table class="table table-bordered table-condensed" id="form_21c_create_table" style="table-layout: auto; width: 100%;">
                            <thead>
                                <tr>
                                    <th rowspan="2" style="vertical-align: middle; white-space: nowrap;">Section / Item</th>
                                    @foreach ($fuelCategory as $categoryName)
                                        <th colspan="2" class="text-center" style="background:#2c3e50; color:#ecf0f1;">{{ $categoryName }}</th>
                                    @endforeach
                                </tr>
                                <tr>
                                    @foreach ($fuelCategory as $categoryName)
                                        <th class="text-center" style="background:#34495e; color:#ecf0f1;">Balance Qty</th>
                                        <th class="text-center" style="background:#34495e; color:#ecf0f1;">@lang('mpcs::lang.value')</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sections as $colKey => $section)
                                    @if ($section['is_header'])
                                        <tr class="section-header-row">
                                            <td colspan="{{ count($fuelCategory) * 2 + 1 }}" style="color:{{ $section['color'] ?? 'skyblue' }}; font-weight:bold; background:#1a3a5c;">
                                                {{ $section['label'] }}
                                            </td>
                                        </tr>
                                    @else
                                        @php
                                            // Map header keys back to actual form field keys
                                            $formKey = $colKey; // previous_day, opening_stock, total_issues
                                        @endphp
                                        <tr>
                                            <td style="white-space: nowrap; font-weight: 500;">{{ $section['label'] }}</td>
                                            @foreach ($fuelCategory as $categoryKey => $categoryName)
                                                @php
                                                    $qty = $latestData[$categoryKey][$formKey]['qty'] ?? '';
                                                    $val = $latestData[$categoryKey][$formKey]['val'] ?? '';
                                                    
                                                    // Format with correct precision when displaying saved values (readonly mode)
                                                    if ($is_saved && !empty($qty)) {
                                                        $qty = number_format((float)$qty, $quantity_precision, '.', ',');
                                                    }
                                                    if ($is_saved && !empty($val)) {
                                                        $val = number_format((float)$val, $currency_precision, '.', ',');
                                                    }
                                                @endphp
                                                <td style="padding:2px;">
                                                    <input
                                                        type="text"
                                                        name="{{ $formKey }}[{{ $categoryKey }}][qty]"
                                                        class="full-width-input qty-input"
                                                        id="{{ $formKey }}_qty_{{ $categoryKey }}"
                                                        value="{{ $qty }}"
                                                        {{ $readonly }}
                                                        placeholder="0.00"
                                                    >
                                                </td>
                                                <td style="padding:2px;">
                                                    <input
                                                        type="text"
                                                        name="{{ $formKey }}[{{ $categoryKey }}][val]"
                                                        class="full-width-input val-input"
                                                        id="{{ $formKey }}_val_{{ $categoryKey }}"
                                                        value="{{ $val }}"
                                                        {{ $readonly }}
                                                        placeholder="0.00"
                                                    >
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>{{-- end modal-body --}}

        <div class="modal-footer">
            @if(!$is_saved)
            <button type="submit" class="btn btn-primary" id="save_21c_settings_btn">
                <i class="fa fa-save"></i> @lang('messages.save')
            </button>
            @endif
            <button type="button" class="btn btn-default" id="close_21c_modal" data-dismiss="modal">
                <i class="fa fa-times"></i> @lang('messages.close')
            </button>
        </div>

        @if(!$is_saved)
        {!! Form::close() !!}
        @endif
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
$(document).ready(function() {

    @if(!$is_saved)
    // Set today's date
    $('#create_21c_datepicker').datepicker({
        autoclose: true,
        format: 'yyyy/mm/dd'
    }).datepicker('setDate', new Date());

    // Set current time
    let now = new Date();
    let hours   = String(now.getHours()).padStart(2, '0');
    let minutes = String(now.getMinutes()).padStart(2, '0');
    document.getElementById('create_21c_time').value = hours + ':' + minutes;
    @endif

    const currencyPrecision = {{ $currency_precision ?? 2 }};
    const qtyPrecision = {{ $quantity_precision ?? 2 }};

    // ── Format existing values if any ───────────────────────────────────────
    // Format currency/value inputs with currency precision
    $('#form_21c_create_table .val-input').each(function() {
        let raw = parseFloat($(this).val().replace(/,/g, ''));
        if (!isNaN(raw)) {
            $(this).val(__number_f(raw, false, false, currencyPrecision));
        }
    });
    
    // Format quantity inputs with quantity precision
    $('#form_21c_create_table .qty-input').each(function() {
        let raw = parseFloat($(this).val().replace(/,/g, ''));
        if (!isNaN(raw)) {
            $(this).val(__number_f(raw, false, false, qtyPrecision));
        }
    });

    // ── Format on blur ──────────────────────────────────────────────────────
    // Format currency/value inputs with currency precision on blur
    $(document).on('blur', '#form_21c_create_table .val-input', function() {
        let raw = parseFloat($(this).val().replace(/,/g, ''));
        if (!isNaN(raw)) {
            $(this).val(__number_f(raw, false, false, currencyPrecision));
        }
    });

    // Format quantity inputs with quantity precision on blur
    $(document).on('blur', '#form_21c_create_table .qty-input', function() {
        let raw = parseFloat($(this).val().replace(/,/g, ''));
        if (!isNaN(raw)) {
            $(this).val(__number_f(raw, false, false, qtyPrecision));
        }
    });

    // ── Submit: strip commas → show loader ──────────────────────────────────
    $('#add_21c_form_settings').on('submit', function() {
        // Strip commas so the server gets plain numbers
        $(this).find('.val-input, .qty-input').each(function() {
            let v = $(this).val();
            if (v) $(this).val(v.replace(/,/g, ''));
        });

        // Show loading overlay
        $('#form21c_loading_overlay').css('display', 'flex');
        $('#save_21c_settings_btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
    });
});
</script>

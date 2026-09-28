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
/* Loading overlay */
#form21c_edit_loading_overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.45);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}
#form21c_edit_loading_overlay .loading-box {
    background: #fff;
    border-radius: 8px;
    padding: 30px 40px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
#form21c_edit_loading_overlay .loading-box i {
    font-size: 36px;
    color: #337ab7;
    margin-bottom: 12px;
    display: block;
}
#form21c_edit_loading_overlay .loading-box p {
    margin: 0;
    font-size: 15px;
    color: #333;
    font-weight: 600;
}
</style>

{{-- Loading Overlay --}}
<div id="form21c_edit_loading_overlay">
    <div class="loading-box">
        <i class="fa fa-spinner fa-spin"></i>
        <p>Saving, please wait...</p>
    </div>
</div>

<div class="modal-dialog" role="document" style="width: 85%;">
    <div class="modal-content">
    {!! Form::open(['url' => action([\Modules\MPCS\Http\Controllers\F21FormController::class, 'mpcs21Update'], [$latestForm->id]), 'method' => 'post', 'id' => 'update_21c_form_settings']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">Edit 21 C Form Settings</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                {{-- Date --}}
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.opening_date')</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="edit_21c_datepicker" name="datepicker" data-date-format="yyyy/mm/dd" value="{{ $latestForm->date }}" required />
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
                            <input class="form-control timepicker" id="edit_21c_time" name="time" type="time" value="{{ $latestForm->time }}" required>
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
                        <input type="text" name="starting_number" class="form-control" value="{{ $latestForm->starting_number }}" required>
                    </div>
                </div>

                {{-- Manager Name --}}
                <div class="col-md-3">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.manager_name') <span class="required" aria-required="true">*</span></label>
                        <input type="text" id="edit_21c_manager_name" name="manager_name" class="form-control" value="{{ $latestForm->manager_name }}" required>
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
                                    'label'     => 'Receipts Section',
                                    'is_header' => true,
                                ],
                                'previous_day' => [
                                    'label'     => 'Previous Day Amount',
                                    'is_header' => false,
                                ],
                                'opening_stock' => [
                                    'label'     => 'Opening Stock Amount',
                                    'is_header' => false,
                                ],
                                'issue_header' => [
                                    'label'     => 'Issue Section',
                                    'is_header' => true,
                                ],
                                'total_issues' => [
                                    'label'     => 'Previous Day Amount',
                                    'is_header' => false,
                                ],
                            ];
                        @endphp

                        <table class="table table-bordered table-condensed" id="form_21c_edit_table" style="table-layout: auto; width: 100%;">
                            <thead>
                                <tr>
                                    <th rowspan="2" style="vertical-align: middle; white-space: nowrap;">Section / Item</th>
                                    @foreach ($fuelCategory as $categoryName)
                                        <th colspan="2" class="text-center" style="background:#2c3e50; color:#ecf0f1;">{{ $categoryName }}</th>
                                    @endforeach
                                </tr>
                                <tr>
                                    @foreach ($fuelCategory as $categoryName)
                                        <th class="text-center" style="background:#34495e; color:#ecf0f1;">Qty</th>
                                        <th class="text-center" style="background:#34495e; color:#ecf0f1;">Val</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sections as $colKey => $section)
                                    @if ($section['is_header'])
                                        <tr>
                                            <td colspan="{{ count($fuelCategory) * 2 + 1 }}"
                                                style="color: skyblue; font-weight: bold; background: #1a3a5c;">
                                                {{ $section['label'] }}
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td style="white-space: nowrap; font-weight: 500;">{{ $section['label'] }}</td>
                                            @foreach ($fuelCategory as $categoryId => $categoryName)
                                                @php
                                                    $qty = $categoriesData[$categoryId][$colKey]['qty'] ?? '';
                                                    $val = $categoriesData[$categoryId][$colKey]['val'] ?? '';
                                                @endphp
                                                <td style="padding:2px;">
                                                    <input type="text"
                                                           name="{{ $colKey }}[{{ $categoryId }}][qty]"
                                                           class="full-width-input qty-input"
                                                           placeholder="Qty"
                                                           value="{{ $qty }}">
                                                </td>
                                                <td style="padding:2px;">
                                                    <input type="text"
                                                           name="{{ $colKey }}[{{ $categoryId }}][val]"
                                                           class="full-width-input val-input"
                                                           placeholder="Val"
                                                           value="{{ $val }}">
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
            <button type="submit" class="btn btn-primary" id="update_21c_settings_btn">
                <i class="fa fa-save"></i> @lang('messages.save')
            </button>
            <button type="button" class="btn btn-default" id="close_21c_edit_modal" data-dismiss="modal">
                <i class="fa fa-times"></i> @lang('messages.close')
            </button>
        </div>

    {!! Form::close() !!}
    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
$(document).ready(function() {

    const currencyPrecision = {{ $currency_precision ?? 2 }};
    const qtyPrecision      = {{ $quantity_precision ?? 2 }};

    // ── Format existing values on load ───────────────────────────────────────
    $('#form_21c_edit_table .val-input').each(function() {
        let raw = parseFloat($(this).val());
        if (!isNaN(raw) && raw !== 0) {
            $(this).val(__number_f(raw, false, false, currencyPrecision));
        }
    });

    $('#form_21c_edit_table .qty-input').each(function() {
        let raw = parseFloat($(this).val());
        if (!isNaN(raw) && raw !== 0) {
            $(this).val(__number_f(raw, false, false, qtyPrecision));
        }
    });

    // ── Format on blur ───────────────────────────────────────────────────────
    $(document).on('blur', '#update_21c_form_settings .val-input', function() {
        let raw = parseFloat($(this).val().replace(/,/g, ''));
        if (!isNaN(raw)) {
            $(this).val(__number_f(raw, false, false, currencyPrecision));
        }
    });

    $(document).on('blur', '#update_21c_form_settings .qty-input', function() {
        let raw = parseFloat($(this).val().replace(/,/g, ''));
        if (!isNaN(raw)) {
            $(this).val(__number_f(raw, false, false, qtyPrecision));
        }
    });

    // ── Submit: strip commas → show loader ───────────────────────────────────
    $('#update_21c_form_settings').on('submit', function() {
        $(this).find('.val-input, .qty-input').each(function() {
            let v = $(this).val();
            if (v) $(this).val(v.replace(/,/g, ''));
        });

        // Show loading overlay
        $('#form21c_edit_loading_overlay').css('display', 'flex');
        $('#update_21c_settings_btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
    });
});
</script>

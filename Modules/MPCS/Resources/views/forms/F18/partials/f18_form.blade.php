@php
    $loc_keys = $business_locations->keys()->toArray();
    $is_single_loc = count($loc_keys) === 1;
    $auto_from = $is_single_loc ? $loc_keys[0] : $default_location_id;
    $qp = $qty_precision ?? 2;
    $qty_step = 1 / pow(10, $qp);
    $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
@endphp

{{-- F18 Form Styles --}}
<style>
    /* Remove section outer spacing */
    #f18_form_tab .box,
    #f18_form_tab .widget-container,
    #f18_form_tab .box-body {
        padding-top: 6px !important;
        padding-bottom: 6px !important;
    }

    .f18-section { padding: 0 12px; }

    /* ── Header row ── */
    .f18-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 6px 12px 4px;
        gap: 12px;
    }
    .f18-header-center { text-align: center; flex: 1; line-height: 1.2; }
    .f18-header-center h4 { margin: 0 0 2px; font-weight: 700; font-size: 16px; }
    .f18-header-center small { font-size: 12px; color: #555; }
    .f18-header-no { text-align: right; min-width: 180px; }
    .f18-header-no label { margin-bottom: 2px; font-size: 12px; }
    .f18-header-no input { height: 30px; font-size: 13px; }

    /* ── Location row ── */
    .f18-loc-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 12px;
        flex-wrap: nowrap;
    }
    .f18-loc-row .f18-loc-item {
        display: flex;
        align-items: center;
        gap: 5px;
        white-space: nowrap;
    }
    .f18-loc-row label { margin: 0; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .f18-loc-row .form-control,
    .f18-loc-row .select2-container--default .select2-selection--single { height: 30px !important; font-size: 12px; padding: 2px 8px; }
    .f18-loc-row .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 28px !important; }
    .f18-loc-spacer { flex: 1; }

    /* ── Filter row ── */
    .f18-filter-row {
        display: flex;
        align-items: flex-end;
        gap: 6px;
        padding: 4px 12px;
        flex-wrap: nowrap;
    }
    .f18-filter-row .f18-filter-item { display: flex; flex-direction: column; }
    .f18-filter-row label { margin-bottom: 2px; font-size: 11px; font-weight: 600; white-space: nowrap; }
    
    .f18-filter-row .form-control,
    .f18-filter-row .select2-container--default .select2-selection--single,
    .f18-filter-row .select2-container--default .select2-selection--multiple { 
        height: 30px !important; 
        min-height: 30px !important;
        font-size: 12px; 
    }
    .f18-filter-row .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 28px !important;
    }
    
    /* Common Select2 Refinements */
    .select2-selection__choice__remove { display: none !important; }
    .select2-selection__choice {
        margin-top: 2px !important;
        padding: 0 5px !important;
        font-size: 11px;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        display: flex !important;
        align-items: center;
        overflow-x: auto;
        white-space: nowrap;
        padding: 0 4px !important;
    }
    .f18-filter-item-cat    { min-width: 130px; max-width: 155px; flex: 0 0 145px; }
    .f18-filter-item-subcat { min-width: 140px; max-width: 180px; flex: 0 0 155px; }
    .f18-filter-item-prod   { min-width: 140px; max-width: 180px; flex: 0 0 155px; }
    .f18-filter-item-qty    { min-width: 70px;  max-width: 90px;  flex: 0 0 80px; }
    .btn-add-f18 {
        height: 30px;
        padding: 0 16px;
        font-size: 13px;
        align-self: flex-end;
        margin-bottom: 0;
    }

    /* ── Table ── */
    .f18-table-wrap { padding: 4px 12px; }
    #f18_table { margin-bottom: 0; border-color: #999 !important; }
    #f18_table th, #f18_table td { border-color: #999 !important; padding: 4px 6px; font-size: 12px; }
    #f18_table thead th { background: #f5f5f5; text-align: center; vertical-align: middle; }
    #f18_table tfoot th { background: #f9f9f9; }
    td.f18-amt, th.f18-amt { text-align: right !important; }
    th.f18-amt-hd { text-align: right !important; }

    /* ── Signatures ── */
    .f18-sigs {
        display: flex;
        justify-content: space-between;
        padding: 20px 12px 8px;
        gap: 10px;
    }
    .f18-sig-block { flex: 1; text-align: center; min-width: 80px; }
    .f18-sig-line { border-bottom: 1px dashed #777; height: 22px; margin-bottom: 3px; }
    .f18-sig-block span { font-size: 11px; }

    /* ── Print: hide controls, remove borders from inputs/selects ── */
    @media print {
        .no-print, .no-print * { display: none !important; }
        .f18-filter-row { display: none !important; }
        input.form-control, select.form-control,
        .select2-container { border: none !important; box-shadow: none !important; background: transparent !important; }
        .select2-container.no-print { display: none !important; }
        #f18_table th, #f18_table td { font-size: 10px; padding: 2px 4px; }
        .f18-sigs { padding-top: 30px; }
    }
</style>

{{-- ── Header: Business Name | Save & Print | F18 No ── --}}
<div class="f18-header">
    {{-- Left: Save actions --}}
    <div class="no-print" style="min-width:220px; display:flex; gap:8px; align-items:center;">
        <button type="button" id="f18_save_btn" class="btn btn-success btn-sm">
            <i class="fa fa-save"></i> Save
        </button>
        <button type="button" id="f18_save_print_btn" class="btn btn-primary btn-sm">
            <i class="fa fa-print"></i> Save and Print
        </button>
    </div>

    {{-- Center: business name + subtitle + date --}}
    <div class="f18-header-center">
        <h4>{{ request()->session()->get('business.name') }}</h4>
        <small>Goods Exchange Note</small>
        <div style="font-size:12px; color:#555; margin-top:2px;" id="f18_form_date_display">{{ now()->format('Y-m-d') }}</div>
    </div>

    {{-- Right: F 18 No --}}
    <div class="f18-header-no">
    <div style="display:flex; gap:8px; flex-direction: row; align-items: center; justify-content: flex-end;">
        <label style="margin-bottom: 0; white-space: nowrap;">F 18 No:</label>
        <input type="text" id="f18_form_no" name="f18_form_no"
               class="form-control" style="max-width:160px;"
               value="{{ $F18_form_no }}" readonly>
    </div>
</div>
</div>

{{-- ── Location | Transferred to | Date — single row ── --}}
<div class="f18-loc-row">
    {{-- From Location --}}
    <div class="f18-loc-item">
        <label>From:</label>
        @if($is_single_loc)
            <strong>{{ $business_locations[$auto_from] ?? '' }}</strong>
            <input type="hidden" id="f18_from_location_id" name="f18_from_location_id" value="{{ $auto_from }}">
        @else
            {!! Form::select('f18_from_location_id', $business_locations, $auto_from, [
                'class'       => 'form-control select2',
                'id'          => 'f18_from_location_id',
                'style'       => 'min-width:140px; max-width:200px;',
                'placeholder' => __('messages.please_select'),
            ]) !!}
        @endif
    </div>

    <div class="f18-loc-spacer"></div>

    {{-- Transferred to --}}
    <div class="f18-loc-item">
        <label>Transferred to:</label>
        <select name="f18_to_location_id" id="f18_to_location_id"
                class="form-control select2"
                style="min-width:140px; max-width:200px;">
            @unless($auto_to)
                <option value="">{{ __('messages.please_select') }}</option>
            @endunless
            @foreach($f18_to_locations as $id => $name)
                <option value="{{ $id }}" @selected($auto_to == $id)>{{ $name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Date --}}
    <div class="f18-loc-item">
        <label>Date:</label>
        <input type="text" id="f18_date" name="f18_date"
               class="form-control" style="max-width:130px;"
               value="{{ now()->format('Y-m-d') }}" readonly>
    </div>
</div>

{{-- ── Filters: Category | Sub Category | Product | Qty | Add — single row ── --}}
<div class="f18-filter-row no-print">
    <div class="f18-filter-item f18-filter-item-cat">
        <label>@lang('mpcs::lang.product_category')</label>
        <select id="f18_category_id" name="f18_category_id" class="form-control select2" style="width:100%;">
            <option value="">@lang('lang_v1.all')</option>
            @foreach($categories as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="f18-filter-item f18-filter-item-subcat">
        <label>@lang('mpcs::lang.product_sub_category')</label>
        <select id="f18_sub_category_ids" name="f18_sub_category_id"
                class="form-control select2" style="width:100%;">
            <option value="">@lang('lang_v1.all')</option>
            @foreach($sub_categories as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="f18-filter-item f18-filter-item-prod">
        <label>@lang('mpcs::lang.product')</label>
        <select id="f18_product_id" name="f18_product_id" class="form-control select2" style="width:100%;">
            <option value="">@lang('lang_v1.all')</option>
            @foreach($products as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </select>
    </div>

    <div class="f18-filter-item f18-filter-item-qty">
        <label>@lang('mpcs::lang.qty')</label>
        <input type="number" id="f18_qty" name="f18_qty"
               class="form-control" min="{{ $qty_step }}" step="{{ $qty_step }}">
    </div>

    <div style="align-self:flex-end;">
        <button type="button" id="f18_add_row" class="btn btn-primary btn-add-f18">
            @lang('mpcs::lang.add')
        </button>
    </div>
</div>

{{-- ── Table ── --}}
<div class="f18-table-wrap">
    <table class="table table-bordered table-condensed" id="f18_table">
        <thead>
            <tr>
                <th rowspan="3" style="vertical-align:middle;text-align:center;width:40px;">@lang('mpcs::lang.index')</th>
                <th rowspan="3" style="vertical-align:middle;text-align:center;">@lang('mpcs::lang.description')</th>
                <th rowspan="3" style="vertical-align:middle;text-align:center;width:60px;">@lang('mpcs::lang.qty')</th>
                <th colspan="4" class="text-center">@lang('mpcs::lang.issued_location')</th>
                <th colspan="4" class="text-center">@lang('mpcs::lang.received_location')</th>
                <th rowspan="3" style="vertical-align:middle;text-align:center;">@lang('mpcs::lang.office_use')</th>
                <th rowspan="3" class="no-print" style="vertical-align:middle;text-align:center;width:40px;">×</th>
            </tr>
            <tr>
                <th colspan="2" class="text-center">@lang('mpcs::lang.purchase_price')</th>
                <th colspan="2" class="text-center">@lang('mpcs::lang.sale_price')</th>
                <th colspan="2" class="text-center">@lang('mpcs::lang.purchase_price')</th>
                <th colspan="2" class="text-center">@lang('mpcs::lang.sale_price')</th>
            </tr>
            <tr>
                <th class="text-center">@lang('mpcs::lang.unit')</th>
                <th class="text-center">@lang('mpcs::lang.total')</th>
                <th class="text-center">@lang('mpcs::lang.unit')</th>
                <th class="text-center">@lang('mpcs::lang.total')</th>
                <th class="text-center">@lang('mpcs::lang.unit')</th>
                <th class="text-center">@lang('mpcs::lang.total')</th>
                <th class="text-center">@lang('mpcs::lang.unit')</th>
                <th class="text-center">@lang('mpcs::lang.total')</th>
            </tr>
        </thead>
        <tbody></tbody>
        <tfoot>
            <tr style="background: #f9f9f9;">
                <th colspan="3" class="text-right" style="font-size:12px;">Total Amount</th>
                <th class="text-center">—</th>
                <th class="f18-amt" id="f18_total_issued_purchase">0.00</th>
                <th class="text-center">—</th>
                <th class="f18-amt" id="f18_total_issued_sale">0.00</th>
                <th class="text-center">—</th>
                <th class="f18-amt" id="f18_total_received_purchase">0.00</th>
                <th class="text-center">—</th>
                <th class="f18-amt" id="f18_total_received_sale">0.00</th>
                <th></th>
                <th class="no-print"></th>
            </tr>
        </tfoot>
    </table>
</div>

{{-- ── Signatures ── --}}
<div class="f18-sigs">
    @foreach(['Prepared By','Approved By','Handed Over By','Received By','Date'] as $sig)
        <div class="f18-sig-block">
            <div class="f18-sig-line"></div>
            <span>{{ $sig }}</span>
        </div>
    @endforeach
</div>

{{-- ── System standard report footer ── --}}
@if (!empty($reports_footer) && !empty($reports_footer->value))
    <div style="margin-top:30px; width:100%; text-align:left; font-size:12px; color:#333; padding:10px 0 0 10px; border-top:1px solid #eee;">
        {!! $reports_footer->value !!}
    </div>
@endif

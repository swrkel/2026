@extends('layouts.app')
@section('title', 'POS')
@section('content')
@include('layouts.partials.header-pos')
@inject('request', 'Illuminate\Http\Request')
<script></script>
<style>
    .btn-darkbrown {
        background-color: rgb(135 0 22) !important;
    }

    .box-header {
        padding-bottom: 0px !important;
        display: inline-block;
    }

    .w-100 {
        width: 100% !important;
    }

    .box-body {
        padding-top: 5px !important;
        margin-left: 15px !important;
    }

    /* Force customer dropdown search input to always show (like category dropdown) */
    .customer-select2-dropdown .select2-search--dropdown,
    .customer-select2-dropdown .select2-search--dropdown.select2-search--hide {
        display: block !important;
    }
    .min-height-50hv {
        min-height: 50vh !important;
    }

    .bg-orange {
        background-color: #ff7f33 !important;
        color: #fff !important;
        overflow: hidden;
    }
</style>
<!-- Content Header (Page header) -->
<!-- Main content -->
@php
    $enable_line_discount = !empty($pos_settings['enable_line_discount']) ? 1 : 0;
    $show_purchase_price = !empty($pos_settings['show_purchase_price']) ? 1 : 0;
    request()->session()->put('is_sales_page', $is_sales_page);
@endphp
<section class="content no-print">
    <input type="hidden" name="enable_code" id="enable_code"
        value="{{ !empty($search_product_settings['enable_code']) ? 1 : '' }}">
    <input type="hidden" name="enable_rack_number" id="enable_rack_number"
        value="{{ !empty($search_product_settings['enable_rack_number']) ? 1 : '' }}">
    <input type="hidden" name="enable_qty" id="enable_qty"
        value="{{ !empty($search_product_settings['enable_qty']) ? 1 : '' }}">
    <input type="hidden" name="enable_product_cost" id="enable_product_cost"
        value="{{ !empty($search_product_settings['enable_product_cost']) ? 1 : '' }}">
    <input type="hidden" name="enable_product_supplier" id="enable_product_supplier"
        value="{{ !empty($search_product_settings['enable_product_supplier']) ? 1 : '' }}">
    <input type="hidden" id="module" value="sales_pos">
    @if (!empty($pos_settings['allow_overselling']) || (isset($_GET['type']) && $_GET['type'] == 'quotation'))
        <input type="hidden" id="is_overselling_allowed">
    @endif
    @if (session('business.enable_rp') == 1)
        <input type="hidden" id="reward_point_enabled">
    @endif

    @if (!empty($walk_in_customer))
        <input type="hidden" id="default_customer_id" value="{{ $walk_in_customer['id'] }}">
        <input type="hidden" id="default_customer_name" value="{{ $walk_in_customer['name'] ?? 'Walk-in Customer' }}">
        <input type="hidden" id="default_customer_balance" value="{{ $walk_in_customer['opening_balance'] ?? 0 }}">
        <input type="hidden" id="default_customer_address"
            value="{{ $walk_in_customer['landmark'] ?? ($walk_in_customer['address'] ?? '') }}">
    @endif
    {!! Form::open(['url' => action('SellPosController@store'), 'method' => 'post', 'id' => 'add_pos_sell_form']) !!}
    <input type="hidden" name="offline_mode" id="offline_mode" value="0">

    <div class="row">
        <div
            class="left_div @if (!empty($pos_settings['hide_product_suggestion']) && !empty($pos_settings['hide_recent_trans'])) col-md-10 col-md-offset-1 @else col-md-7 @endif col-sm-12">
            @component('components.widget', ['class' => 'box-success'])
            @slot('header')
            <div class="col-md-3">
                <div class="col-md-12">
                    <p class="text-right  pull-left"><strong>@lang('sale.location'):</strong>
                        {{ $default_location->name }}
                    </p>
                </div>
                <div class="col-md-12">
                    <h4 class="invoice_no" style="margin: 0; width: 150px;">
                        {{ $creation_type == 'quotation' ? __('lang_v1.quotation_no') : __('lang_v1.invoice_no') }}:
                        <span class="invoice_no_span"></span>
                    </h4>
                    <input type="hidden" name="invoice_no">
                </div>
            </div>
            <div class="col-md-9">
                <div class="col-md-12"></div>
                <div class="col-md-6 text-red" style="font-size: 16px;">
                    <input type="hidden" id="customer_id_param" value="{{ request('customer_id') ?? '' }}">
                    <b>@lang('lang_v1.customer'):</b> <span class="customer_name"></span>
                </div>
                <div class="col-md-6 text-red" style="font-size: 16px; display: none;" id="due_amount_container">
                    <b>@lang('lang_v1.due_amount'):</b> <span class="customer_due_amount"> </span>
                </div>
            </div>
            <input type="hidden" id="item_addition_method" value="{{ $business_details->item_addition_method }}">
            <input type="hidden" id="service_addition_method" value="{{ $business_details->service_addition_method }}">
            @endslot
            <div class="clearfix"></div>
            <input type="hidden" name="price_later" id="price_later" value="0">
            {!! Form::hidden('location_id', $default_location->id, [
    'id' => 'location_id',
    'data-receipt_printer_type' => !empty($default_location->receipt_printer_type)
        ? $default_location->receipt_printer_type
        : 'browser',
    'data-default_accounts' => $default_location->default_payment_accounts,
]) !!}
            <style>
                .select2-drop-active {
                    margin-top: -25px;
                }
            </style>
            <!-- /.box-header -->
            <div class="box-body w-100">
                <div class="row">
                    @php
                        $addRow = 0;
                    @endphp
                    @if (!empty($pos_settings['enable_transaction_date']))
                        <div class="col-md-4 col-sm-6">
                            <div class="form-group">
                                {!! Form::label('transaction_date', __('sale.sale_date') . ':*') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-calendar"></i>
                                    </span>

                                    <input type="datetime-local"
                                        value="{{ !empty($temp_data->transaction_date) ? \Carbon::parse($temp_data->transaction_date)->format('Y-m-d\TH:i:s') : \Carbon::parse($default_datetime)->format('Y-m-d\TH:i:s') }}"
                                        name="transaction_date" class="form-control" id="datetimepicker_pos" required>

                                </div>
                            </div>
                        </div>
                        @php
                            $addRow++;
                        @endphp
                    @endif
                    <div class="col-md-6 col-sm-6">
                        <div class="form-group">
                            {!! Form::label('ref_no', __('lang_v1.ref_no') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-link"></i>
                                </span>
                                {!! Form::text('ref_no', null, ['class' => 'form-control', 'id' => 'ref_no']) !!}
                            </div>
                        </div>
                        @php
                            $addRow++;
                        @endphp
                    </div>
                    @if ($addRow == 2)
                            @php
                                $addRow = 0;
                            @endphp
                        </div>
                        <div class="row">
                    @endif
                    @if (request()->session()->get('business.is_pharmacy') || request()->session()->get('business.is_hospital'))
                                        <div class="col-md-6 col-sm-6">
                                            <div class="form-group">
                                                @if (request()->session()->get('business.is_pharmacy'))
                                                    {!! Form::label('patients', __('patient.patients') . ':*') !!}
                                                @endif
                                                @if (request()->session()->get('business.is_hospital'))
                                                    {!! Form::label('patients', __('patient.patient_cusotmer') . ':*') !!}
                                                @endif
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-frown-o"></i>
                                                    </span>
                                                    {!! Form::select('patient', [], null, [
                            'placeholder' => 'Select patient',
                            'class' => 'form-control
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        select2',
                            'id' => 'pos_patients',
                        ]) !!}
                                                </div>
                                            </div>
                                        </div>
                                        @php
                                            $addRow++;
                                        @endphp
                    @endif
                    @if ($addRow == 2)
                            @php
                                $addRow = 0;
                            @endphp
                        </div>
                        <div class="row">
                    @endif
                    @if (config('constants.enable_sell_in_diff_currency') == true)
                                        <div class="col-md-4 col-sm-6 pt--20">
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-exchange"></i>
                                                    </span>
                                                    {!! Form::text(
                            'exchange_rate',
                            !empty($temp_data->exchange_rate) ? $temp_data->exchange_rate : config('constants.currency_exchange_rate'),
                            [
                                'class' => 'form-control input-sm input_number',
                                'placeholder' => __('lang_v1.currency_exchange_rate'),
                                'id' => 'exchange_rate',
                            ],
                        ) !!}
                                                </div>
                                            </div>
                                        </div>
                                        @php
                                            $addRow++;
                                        @endphp
                    @endif
                    @if ($addRow == 2)
                            @php
                                $addRow = 0;
                            @endphp
                        </div>
                        <div class="row">
                    @endif
                    @if (!empty($price_groups) && count($price_groups) > 1)
                                        <div class="col-md-4 col-sm-6 pt--20">
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-money"></i>
                                                    </span>
                                                    @php
                                                        reset($price_groups);
                                                        $selected_price_group =
                                                            !empty($default_price_group_id) &&
                                                            array_key_exists($default_price_group_id, $price_groups)
                                                            ? $default_price_group_id
                                                            : null;
                                                    @endphp
                                                    {!! Form::hidden(
                            'hidden_price_group',
                            !empty($temp_data->hidden_price_group) ? $temp_data->hidden_price_group : key($price_groups),
                            ['id' => 'hidden_price_group'],
                        ) !!}
                                                    {!! Form::select(
                            'price_group',
                            $price_groups,
                            !empty($temp_data->price_group) ? $temp_data->price_group : $selected_price_group,
                            ['class' => 'form-control select2', 'id' => 'price_group', 'style' => 'width: 100%;'],
                        ) !!}
                                                    <span class="input-group-addon">
                                                        @show_tooltip(__('lang_v1.price_group_help_text'))
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        @php
                                            $addRow++;
                                        @endphp
                    @else
                                        @php
                                            reset($price_groups);
                                        @endphp
                                        {!! Form::hidden('price_group', !empty($temp_data->price_group) ? $temp_data->price_group : key($price_groups), [
                            'id' => 'price_group',
                        ]) !!}
                    @endif
                    @if (!empty($default_price_group_id))
                                        {!! Form::hidden(
                            'default_price_group',
                            !empty($temp_data->default_price_group) ? $temp_data->default_price_group : $default_price_group_id,
                            ['id' => 'default_price_group'],
                        ) !!}
                    @endif
                    @if ($addRow == 2)
                            @php
                                $addRow = 0;
                            @endphp
                        </div>
                        <div class="row">
                    @endif
                    @if (in_array('types_of_service', $enabled_modules) && !empty($types_of_service))
                                        <div class="col-md-4 col-sm-6 pt--20">
                                            <div class="form-group">
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fa fa-external-link text-primary service_modal_btn"></i>
                                                    </span>
                                                    {!! Form::select(
                            'types_of_service_id',
                            $types_of_service,
                            !empty($temp_data->types_of_service_id) ? $temp_data->types_of_service_id : null,
                            [
                                'class' => 'form-control',
                                'id' => 'types_of_service_id',
                                'style' => 'width: 100%;',
                                'placeholder' => __('lang_v1.select_types_of_service'),
                            ],
                        ) !!}
                                                    {!! Form::hidden(
                            'types_of_service_price_group',
                            !empty($temp_data->types_of_service_price_group) ? $temp_data->types_of_service_price_group : null,
                            ['id' => 'types_of_service_price_group'],
                        ) !!}
                                                    <span class="input-group-addon">
                                                        @show_tooltip(__('lang_v1.types_of_service_help'))
                                                    </span>
                                                </div>
                                                <small>
                                                    <p class="help-block hide" id="price_group_text">@lang('lang_v1.price_group'):
                                                        <span></span>
                                                    </p>
                                                </small>
                                            </div>
                                        </div>
                                        @php
                                            $addRow++;
                                        @endphp
                                        @if ($addRow == 2)
                                                @php
                                                    $addRow = 0;
                                                @endphp
                                            </div>
                                            <div class="row">
                                        @endif
                                        <div class="modal fade types_of_service_modal" tabindex="-1" role="dialog"
                                            aria-labelledby="gridSystemModalLabel"></div>
                    @endif
                    @if (in_array('subscription', $enabled_modules))
                        <div class="col-md-4 pull-right col-sm-6">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('is_recurring', 1, false, ['class' => 'input-icheck', 'id' => 'is_recurring']) !!}
                                    @lang('lang_v1.subscribe')?
                                </label><button type="button" data-toggle="modal" data-target="#recurringInvoiceModal"
                                    class="btn btn-link"><i
                                        class="fa fa-external-link"></i></button>@show_tooltip(__('lang_v1.recurring_invoice_help'))
                            </div>
                        </div>
                        @php
                            $addRow++;
                        @endphp
                    @endif

                    @if ($addRow == 2)
                            @php
                                $addRow = 0;
                            @endphp
                        </div>
                        <div class="row">
                    @endif
                    <div class="@if (!empty($commission_agent)) col-sm-4 pt--20 @else col-sm-6 pt--20 @endif">
                        @php
                            $addRow++;
                        @endphp
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>

                            <!-- Hidden field for sub_type -->
                            <input class="form-control" id="autoservice" name="autoservice" type="hidden"
                                value="{{ request()->query('sub_type') }}" />

                            @if (request()->query('sub_type') == 'repair')
                                <!-- Repair-specific dropdown (disabled) -->
                                <select class="form-control no-select2" id="customer_id">
                                    @if (isset($job_sheet->customer) && $job_sheet->customer->id)
                                        <option value="{{ $job_sheet->customer->id }}" selected>
                                            {{ $job_sheet->customer->first_name }}
                                        </option>
                                    @else
                                        <option value="" selected>Customer Not Found</option>
                                    @endif
                                </select>

                                <input type="hidden" name="contact_id" id="contact_id"
                                    value="{{ $job_sheet->customer->id ?? '' }}">
                            @else
                                <!-- Normal POS: selectable customer dropdown -->
                                <select class="form-control mousetrap" name="contact_id" id="customer_id"
                                    required>
                                    <option value="">Select Customer</option>
                                    @foreach ($customers as $customer_id => $customer_name)
                                        <option value="{{ $customer_id }}" {{ !empty($temp_data->contact_id) && $temp_data->contact_id == $customer_id ? 'selected' : '' }}>
                                            {{ $customer_name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default bg-white btn-flat add_new_customer"
                                    data-name="" @if (!auth()->user()->can('customer.create')) disabled @endif>
                                    <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                </button>
                            </span>
                        </div>
                    </div>
                    @if ($addRow == 2)
                            @php
                                $addRow = 0;
                            @endphp
                        </div>
                        <div class="row">
                    @endif
                    <input type="hidden" name="pay_term_number" id="pay_term_number"
                        value="{{ !empty($temp_data->pay_term_number) ? $temp_data->pay_term_number : $walk_in_customer['pay_term_number'] }}">
                    <input type="hidden" name="pay_term_type" id="pay_term_type"
                        value="{{ !empty($temp_data->pay_term_type) ? $temp_data->pay_term_type : $walk_in_customer['pay_term_type'] }}">
                    @if (!empty($commission_agent))
                                        <div class="col-sm-4 pt--20">
                                            @php
                                                $addRow++;
                                            @endphp
                                            <div class="form-group">
                                                {!! Form::select(
                            'commission_agent',
                            $commission_agent,
                            !empty($temp_data->commission_agent) ? $temp_data->commission_agent : null,
                            ['class' => 'form-control select2', 'placeholder' => __('lang_v1.commission_agent')],
                        ) !!}
                                            </div>
                                        </div>
                    @endif
                    @if ($addRow == 2)
                            @php
                                $addRow = 0;
                            @endphp
                        </div>
                        <div class="row">
                    @endif
                    <div class="@if (!empty($commission_agent)) col-sm-4 pt--20 @else col-sm-6 pt--20 @endif">
                        @php
                            $addRow++;
                        @endphp
                        <div class="form-group">
                            @include('sale_pos.partials.enhanced_search_input')
                        </div>
                    </div>

                    <!-- Call restaurant module if defined -->
                    @if (in_array('tables', $enabled_modules) || in_array('service_staff', $enabled_modules))
                        <span id="restaurant_module_span">
                            <div class="col-md-3"></div>
                        </span>
                    @endif
                    @if ($addRow == 2)
                            @php
                                $addRow = 0;
                            @endphp
                        </div>
                        <div class="row">
                    @endif
                    <div class=" col-sm-6 pt--20">
                        @php
                            $addRow++;
                        @endphp
                        <div class="form-group" style="width: 100% !important">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-list-ol"></i>
                                </span>
                                <input class="form-control" id="job_sheet_id" name="job_sheet_id" type="hidden"
                                    value="{{ $job_sheet ? $job_sheet->id : '' }}" readonly>
                                <input class="form-control valid" id="job_sheet_no" name="job_sheet_no" type="text"
                                    value="{{ $job_sheet ? $job_sheet->job_sheet_no : '' }}" readonly=""
                                    aria-invalid="false">
                            </div>
                        </div>
                    </div>
                    @if ($addRow == 2)
                            @php
                                $addRow = 0;
                            @endphp
                        </div>
                        <div class="row">
                    @endif
                    @if ($job_sheet && $job_sheet->reportStatus == '1')
                        <div class=" col-sm-6 pt--20">
                            @php
                                $addRow++;
                            @endphp
                            <div class="form-group" style="width: 100% !important">
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-car"></i>
                                    </span>
                                    <input class="form-control valid" id="vehicle_no" name="vehicle_no" type="text"
                                        value="{{ $vehicle->vehicle_no }}" readonly="" aria-invalid="false">
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="row">
                    <div class="col-sm-12 pos_product_div">
                        <input type="hidden" name="sell_price_tax" id="sell_price_tax"
                            value="{{ !empty($temp_data->sell_price_tax) ? $temp_data->sell_price_tax : $business_details->sell_price_tax }}">
                        <!-- Keeps count of product rows -->
                        <input type="hidden" id="product_row_count"
                            value="{{ !empty($temp_data->product_row_count) ? $temp_data->product_row_count : 0 }}">
                        @php
                            $hide_tax = 'hide';
                            if (session()->get('business.enable_inline_tax') == 1) {
                                $hide_tax = '';
                            }
                        @endphp
                        <div class="table-responsive">
                            <table class="table table-condensed table-bordered table-striped" id="pos_table">
                                <thead>
                                    <tr>
                                        <th class="text-center">@lang('sale.product')</th>
                                        <th class="text-center" width="12%">@lang('sale.qty')</th>
                                        <th class="text-center" width="12%">@lang('sale.unit_price')</th>
                                        <th class="text-center" width="12%">@lang('sale.discount_type')</th>
                                        <th class="text-center" width="10%">@lang('sale.discount')</th>
                                        <th class="text-center" width="12%">
                                            @lang('sale.price_inc_tax')
                                        </th>
                                        <th class="text-center" width="12%">@lang('sale.subtotal')</th>
                                        <th class="text-center"><i class="fa fa-close" aria-hidden="true"></i></th>
                                    </tr>
                                </thead>
                                @php
                                    $qty = $job_sheet
                                        ? DB::table('repair_job_sheets')
                                            ->where('id', $job_sheet->id)
                                            ->value('parts')
                                        : 0;
                                    $product_totals = 0;
                                    $product_item = 0;
                                @endphp
                                <tbody id="saleBody">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            <!--  temp cat id and brand id if there is any temp data  -->
            <input type="hidden" id="cat_id_suggestion" name="cat_id_suggestion"
                value="{{ !empty($temp_data->cat_id_suggestion) ? $temp_data->cat_id_suggestion : 0 }}">
            <input type="hidden" id="brand_id_suggestion" name="brand_id_suggestion"
                value="{{ !empty($temp_data->brand_id_suggestion) ? $temp_data->brand_id_suggestion : 0 }}">
            <input type="hidden" name="is_pos" value="1" id="is_pos">
            <input type="hidden" name="is_duplicate" value="0" id="is_duplicate">
            <input type="hidden" name="was_customer_wallet" id="was_customer_wallet" value=0>
            <input type="hidden" name="in_customer_wallet" id="in_customer_wallet" value=0>

            @endcomponent
        </div>
        <div class="col-md-5 col-sm-12 right_div">
            @include('sale_pos.partials.right_div')
        </div>
    </div>
    @include('sale_pos.partials.pos_details')
    @include('sale_pos.partials.payment_modal')
    @if (empty($pos_settings['disable_suspend']))
        @include('sale_pos.partials.suspend_note_modal')
    @endif
    @if (empty($pos_settings['disable_recurring_invoice']))
        @include('sale_pos.partials.recurring_invoice_modal')
    @endif

    <!-- /.box-body -->
    {!! Form::close() !!}
</section>

<!-- This will be printed -->
<section class="invoice print_section" id="receipt_section">
</section>
<div class="modal fade pos_recent_trans_model" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>

<div class="modal fade register_details_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade close_register_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade quick_return_modal" id="quick_return_modal" role="dialog"></div>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>
<div class="modal fade patient_prescriptions_modal" role="dialog" aria-labelledby="modalTitle"></div>

<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    @include('contact.create', ['quick_add' => true])
</div>


<!-- /.content -->
@include('sale_pos.partials.configure_search_modal')
@stop
@section('javascript')
    @if(auth()->user()->can('offline.access') && ($can_offline_access ?? false))
        <script>
            window.APP_CAN_OFFLINE_SYNC_MANAGE = {{ ($can_offline_sync_manage ?? false) ? 'true' : 'false' }};
        </script>
        <script src="{{ asset('js/offline-queue.js?v=' . ($asset_v ?? 1)) }}"></script>
        <script src="{{ asset('js/offline-wrapper.js?v=' . ($asset_v ?? 1)) }}"></script>
    @endif
    {{-- Bump version to force latest POS JS (discount only on subtotal) --}}
    <script src="{{ asset('js/pos-v2.js?v=6' . ($asset_v ?? 1)) }}"></script>
    <script src="{{ asset('js/printer.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/opening_stock.js?v=' . $asset_v) }}"></script>
    @include('sale_pos.partials.keyboard_shortcuts')
    <!-- Call restaurant module if defined -->
    @if (
            in_array('tables', $enabled_modules) ||
            in_array('modifiers', $enabled_modules) ||
            in_array('service_staff', $enabled_modules)
        )
        <script src="{{ asset('js/restaurant.js?v=' . $asset_v) }}"></script>
    @endif
    <script src="{{ asset('js/sell_return.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/pos-row-calc.js?v=3' . ($asset_v ?? 1)) }}"></script>
        <script>
            // Helper function to update due amount and toggle visibility
            function updateDueAmount(dueAmount) {
                // Parse the due amount - handle string or number
                var due = 0;
                
                if (dueAmount === null || dueAmount === undefined || dueAmount === '') {
                    due = 0;
                } else if (typeof dueAmount === 'string') {
                    // Remove any currency formatting, commas, and parse
                    due = parseFloat(dueAmount.replace(/[^\d.-]/g, '')) || 0;
                } else {
                    due = parseFloat(dueAmount) || 0;
                }
                
                // Round to 2 decimal places to handle floating point precision issues
                due = Math.round(due * 100) / 100;
                
                // Update the text (use original format if it's a formatted string, otherwise format it)
                if (typeof dueAmount === 'string' && dueAmount !== '' && dueAmount !== '0' && dueAmount !== '0.00') {
                    $('.customer_due_amount').text(dueAmount);
                } else {
                    $('.customer_due_amount').text(due.toFixed(2));
                }
                
                // Show/hide the container based on whether due amount > 0
                if (due > 0) {
                    $('#due_amount_container').show();
                } else {
                    $('#due_amount_container').hide();
                }
            }
            
            $(document).ready(function() {
                // Remove exact duplicate options by id while preserving selected values.
                (function dedupeCustomerOptions() {
                    var seen = {};
                    var $select = $('#customer_id');
                    $select.find('option').each(function() {
                        var val = $(this).val();
                        if (!val) {
                            return;
                        }
                        if (seen[val]) {
                            $(this).remove();
                        } else {
                            seen[val] = true;
                        }
                    });
                })();
                
                // Initialize customer name display
                var customerId = $('#customer_id').val();
                var customerName = $('#customer_id option:selected').text();
                
                if (customerId && customerName && customerName !== 'Select Customer') {
                    $('.customer_name').text(customerName);
                    console.log('Initial customer set:', customerId, customerName);
                } else {
                    // Set default customer if available
                    var defaultCustomerId = $('#default_customer_id').val();
                    var defaultCustomerName = $('#default_customer_name').val();
                    if (defaultCustomerId && defaultCustomerName) {
                        $('.customer_name').text(defaultCustomerName);
                        console.log('Default customer set:', defaultCustomerId, defaultCustomerName);
                    }
                }
                
                // Initialize customer information for repair invoices
                if ($('input[name="autoservice"]').val() === 'repair') {
                    var customerId = $('input[name="contact_id"]').val();
                    var customerName = $('select#customer_id option:selected').text();
                    console.log('Repair invoice - Customer ID:', customerId, 'Customer Name:', customerName);

                    if (customerId && customerName && customerName !== 'Customer Not Found') {
                        $('.customer_name').text(customerName);
                        // Fetch customer details for due amount
                        $.ajax({
                            method: 'post',
                            url: '/get-customer-details',
                            data: {
                                contact_id: customerId
                            },
                            success: function(result) {
                                try {
                                    result = JSON.parse(result);
                                    $('.customer_due_amount').text(result['due'] || '0.00');
                                    console.log('Customer details loaded successfully');
                                } catch (e) {
                                    console.error('Error parsing customer details:', e);
                                    $('.customer_due_amount').text('0.00');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error fetching customer details:', error);
                                $('.customer_due_amount').text('0.00');
                            }
                        });
                    } else {
                        console.error('Customer information not found for repair invoice');
                        $('.customer_name').text('Customer Not Found');
                        updateDueAmount('0.00');

                        // Try to get customer info from the URL parameters
                        var urlParams = new URLSearchParams(window.location.search);
                        var jobSheetId = urlParams.get('job_sheet_id');
                        var customerId = urlParams.get('customer_id');

                        if (customerId) {
                            console.log('Trying to fetch customer info from URL parameter:', customerId);
                            $.ajax({
                                method: 'post',
                                url: '/get-customer-details',
                                data: {
                                    contact_id: customerId
                                },
                                success: function(result) {
                                    try {
                                        result = JSON.parse(result);
                                        $('.customer_name').text(result['name'] || 'Customer Not Found');
                                        updateDueAmount(result['due'] || '0.00');
                                        console.log('Customer details loaded from URL parameter');
                                    } catch (e) {
                                        console.error('Error parsing customer details from URL:', e);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('Error fetching customer details from URL:', error);
                                }
                            });
                        }
                    }
                } else {
                    console.log('Regular invoice - triggering customer change');
                    // Check if customer dropdown exists and has options
                    var customerDropdown = $('#customer_id');
                    if (customerDropdown.length && customerDropdown.find('option').length > 0) {
                        console.log('Customer dropdown found with options, triggering change');
                        customerDropdown.trigger('change');
                    }
                }
            });



            // Assuming you have the parts data available as a JavaScript variable
            // If not, you'll need to convert your PHP $parts to JavaScript first


            $('#total_new').hide();

            $(document).ready(function() {
                var show = $("#show").val();
                var total_new = $("#total_new").val();

                $('#show').hide();
            });
            $(document).ready(function() {

                setTimeout(() => {
                    $(".payment_method").val($(".payment_method option:eq(1)").val());
                    $(".payment_method").selectmenu().selectmenu("refresh");
                    @can('is_service_staff')
                        $("#res_waiter_id").val("{{ auth()->user()->id }}");
                        $("#res_waiter_id").trigger('change.select2');
                    @endcan
                }, 2000);
            });
        </script>
        <script>
            $('#toggle_popup').click(function() {
                $.ajax({
                    url: '/toggle_popup',
                    type: 'get',
                    dataType: 'json',
                    success: function(result) {}
                });
            });
            var show = $("#show").val();
            $('#show').hide();
            if (show != null) {
                $('#show').show();
                $('#hide').hide();
            }
        </script>
        <script>
            $('.right_div').show();
            $('.left_div').show();
            $("#hide_show_products").click(function() {
                $(".right_div").toggle();
                $('.left_div').toggleClass('col-md-7');
                $('.left_div').toggleClass('col-md-12');
            });
            $('document').ready(function() {
                reset_pos_form();
                $('.payment_types_dropdown').val('cash');
                $('.payment_types_dropdown').trigger('change');
            });
        </script>
        <script>
            var product_row = $('input#product_row_count').val();
            var location_id = $('input#location_id').val();
            var customer_id = $('select#customer_id').val();
            var is_direct_sell = false;
            if (
                $('input[name="is_direct_sale"]').length > 0 &&
                $('input[name="is_direct_sale"]').val() == 1
            ) {
                is_direct_sell = true;
            }
            var price_group = '';
            if ($('#price_group').length > 0) {
                price_group = parseInt($('#price_group').val());
            }
            //If default price group present
            if ($('#default_price_group').length > 0 &&
                !price_group) {
                price_group = $('#default_price_group').val();
            }
            //If types of service selected give more priority
            if ($('#types_of_service_price_group').length > 0 &&
                $('#types_of_service_price_group').val()) {
                price_group = $('#types_of_service_price_group').val();
            }
        </script>
        @if (!empty($temp_data->products))
            @php $i = -1; @endphp
            @foreach ($temp_data->products as $product)
                <script>
                    $(document).ready(function() {
                        // base_url = '{{ URL::to('/') }}';
                        var qty = parseInt({{ $product->quantity }});
                        var variation_id = parseInt({{ $product->variation_id }});
                        add_pos_product_row(qty, variation_id, location_id);
                    })
                </script>
                @php $i++; @endphp
            @endforeach
        @endif
        <script>
            $('#request_approval').click(function() {
                let customer_id = $('#customer_id').val();
                $.ajax({
                    method: 'get',
                    url: '/customer-limit-approval/send-reuqest-for-approval/' + customer_id,
                    data: {},
                    success: function(result) {
                        if (result.success === 1) {
                            toastr.success(result.msg)
                        }
                    },
                });
            });
            //Update values for each row
            $('#is_duplicate').change(function() {
                getInvoice();
            });

            function getInvoice() {
                $.ajax({
                    method: 'get',
                    url: '{{ action('SellController@getInvoiveNo') }}',
                    data: {
                        location_id: $('#location_id').val(),
                        @if ($creation_type == 'quotation')
                            creation_type: 'quotation'
                        @endif
                    },
                    success: function(result) {
                        if (parseInt($('#is_duplicate').val()) == 1) {
                            $('.invoice_no_span').text(result.duplicate_invoice_no);
                            $('input[name="invoice_no"]').val(result.duplicate_invoice_no);
                        } else {
                            $('.invoice_no_span').text(result.orignal_invoice_no);
                            $('input[name="invoice_no"]').val(result.orignal_invoice_no);
                        }
                    },
                });
            }
            getInvoice();
            @if (auth()->user()->can('unfinished_form.pos'))
                @if (!empty($temp_data))
                    swal({
                        title: "Do you want to load unsaved data?",
                        icon: "info",
                        buttons: {
                            confirm: {
                                text: "Yes",
                                value: false,
                                visible: true,
                                className: "",
                                closeModal: true
                            },
                            cancel: {
                                text: "No",
                                value: true,
                                visible: true,
                                className: "",
                                closeModal: true,
                            }
                        },
                        dangerMode: false,
                    }).then((sure) => {
                        if (sure) {
                            window.location.href =
                                "{{ action('TempController@clearData', ['type' => 'add_pos_data']) }}";
                        }
                    });
                @endif
            @endif

            $('#customer_id').change(async function() {
                var customerId = $(this).val();
                var selectedCustomerName = $.trim($(this).find('option:selected').text());
                
                // If no customer selected, clear and hide due amount
                if (!customerId) {
                    $('.customer_name').text('');
                    updateDueAmount('0.00');
                    return;
                }

                // Reflect selected customer immediately even before async response returns.
                if (selectedCustomerName && selectedCustomerName !== 'Select Customer') {
                    $('.customer_name').text(selectedCustomerName);
                }
                
                if (window.APP_IS_OFFLINE_MODE || !navigator.onLine) {
                    const customer = await window.OfflineCache.getCustomerDetailByKey(customerId);
                    if (customer) {
                        $('.customer_name').text(customer.customer);
                        updateDueAmount(customer.due || 0);
                    } else {
                        toastr.warning("Customer info not available offline.");
                        updateDueAmount('0.00');
                    }

                    return;
                }
                $.ajax({
                    method: 'post',
                    url: '/get-customer-details',
                    data: {
                        contact_id: customerId
                    },
                    success: function(result) {
                        try {
                            if (typeof result === 'string') {
                                result = JSON.parse(result);
                            }
                            $('.customer_name').text(result['name'] || selectedCustomerName);
                            updateDueAmount(result['due'] || '0.00');
                        } catch (e) {
                            $('.customer_name').text(selectedCustomerName);
                            updateDueAmount('0.00');
                        }
                    },
                    error: function() {
                        updateDueAmount('0.00');
                    }
                });
            });

            $('#add_to_customer_wallet').click(function() {
                var change_return = parseFloat($('input#change_return').val().replace(',', ''));
                let was_customer_wallet = parseFloat($('#was_customer_wallet').val());
                $('input#in_customer_wallet').val(parseFloat(was_customer_wallet + change_return));
                $('span.customer_wallet').text(__currency_trans_from_en(parseFloat(was_customer_wallet + change_return),
                    true));
            })
            $(document).on('click', '#verify_password_btn', function() {
                $.ajax({
                    method: 'post',
                    url: '/check_user_password',
                    data: {
                        password: $('#verify_password').val()
                    },
                    success: function(result) {
                        if (result.success == 1) {
                            $('#verify_password_modal').find('.modal-title').empty().text('Enter Invoice');
                            $('#verify_password_modal').find('.modal-body').empty().append(`
                    <input type="text" id="return_invoice" name="return_invoice" placeholder="@lang('lang_v1.enter_invoice')"
                        style="margin-auto;" class="form-control">
                    `);
                            $('#verify_password_modal').find('.modal-footer').empty().append(`
                    <button type="button" id="return_invoice_btn" class="btn btn-primary">Submit</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    `);
                        } else {
                            toastr.error('Password does not match');
                        }
                    },
                });
            });
            $(document).on('click', '#return_invoice_btn', function() {
                let return_invoice = $('#return_invoice').val();
                $.ajax({
                    method: 'get',
                    url: '/sell-return/add/' + return_invoice,
                    data: {},
                    success: function(result) {
                        if (result.success == 0) {
                            $('#verify_password_modal').modal('hide')
                            toastr.error(result.msg);
                            return false;
                        } else {
                            $('#verify_password_modal').modal('hide');
                            resetVerifyPasswordModal();
                            $('.quick_return_modal').empty().append(result);
                            $('.quick_return_modal').modal('show');
                            $('#pos_invoice_return').val($('.invoice_no_span').text());
                        }
                    },
                });
            });

            function resetVerifyPasswordModal() {
                $('#verify_password_modal').find('.modal-title').empty().text('Enter Password');
                $('#verify_password_modal').find('.modal-body').empty().append(`
            <input type="password" id="verify_password" name="verify_password" placeholder="@lang('lang_v1.enter_password')"
            style="margin-auto;" class="form-control">
            `);
                $('#verify_password_modal').find('.modal-footer').empty().append(`
            <button type="button" id="verify_password_btn" class="btn btn-primary">@lang('lang_v1.verify')</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        `);
            }
            $(document).on('keyup', '.cash_denomination', function(e) {
                e.preventDefault();
                var subtotal_element = $(this).closest('tr').find('.denomination_subtotal');
                var denomination = $(this).data('denomination');


                var subtotal = denomination * $(this).val();
                subtotal_element.data('total', subtotal);
                subtotal_element.html(__currency_trans_from_en(subtotal, true));
                var grand_total = 0;
                var row_denomination = $(this).closest('tbody').find('.cash_denomination');
                row_denomination.each(function() {
                    grand_total += $(this).val() * $(this).data('denomination');
                })
                var total_element = $(this).closest('table').find('.denomination_total');
                total_element.data('total', grand_total);
                total_element.html(__currency_trans_from_en(grand_total, true));


            });
        </script>
        <script type="text/javascript">
            $(document).ready(function() {

                $('form#sell_return_form').validate();
                update_sell_return_total();
            });
            $(document).on('click', '#sell_return_submit', function(e) {
                e.preventDefault();
                var data = $('form#sell_return_form').serialize();
                $.ajax({
                    method: 'POST',
                    url: "{{ action('SellReturnController@savePosReturn') }}",
                    dataType: 'json',
                    data: data,
                    success: function(result) {
                        var location_id = $('input#location_id').val();
                        if (result.success == true) {
                            $('.quick_return_modal').modal('hide');
                            jQuery.each(result.returns, function(id, obj) {
                                id = Object.keys(obj);
                                qty = Object.values(obj);
                                add_pos_product_row(qty * -1, id, location_id);
                                $('input#product_row_count').val(parseInt($(
                                    'input#product_row_count').val()) + 1);
                            })
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });
            $(document).on('change', 'input.return_qty, #discount_amount, #discount_type', function() {
                update_sell_return_total()
            });

            function update_sell_return_total() {
                var net_return = 0;
                $('table#sell_return_table tbody tr').each(function() {
                    var quantity = __read_number($(this).find('input.return_qty'));
                    var unit_price = __read_number($(this).find('input.unit_price'));
                    var subtotal = quantity * unit_price;
                    $(this).find('.return_subtotal').text(__currency_trans_from_en(subtotal, true));
                    net_return += subtotal;
                });
                var discount = 0;
                if ($('#discount_type').val() == 'fixed') {
                    discount = __read_number($("#discount_amount"));
                } else if ($('#discount_type').val() == 'percentage') {
                    var discount_percent = __read_number($("#discount_amount"));
                    discount = __calculate_amount('percentage', discount_percent, net_return);
                }
                discounted_net_return = net_return - discount;
                var tax_percent = $('input#tax_percent').val();
                var total_tax = __calculate_amount('percentage', tax_percent, discounted_net_return);
                var net_return_inc_tax = total_tax + discounted_net_return;
                $('input#tax_amount').val(total_tax);
                $('span#total_return_discount').text(__currency_trans_from_en(discount, true));
                $('span#total_return_tax').text(__currency_trans_from_en(total_tax, true));
                $('span#net_return').text(__currency_trans_from_en(net_return_inc_tax, true));
            }

            function add_pos_product_row(qty, variation_id, location_id) {
                $.ajax({
                    method: 'GET',
                    url: '/sells/pos/get_product_row_temp/' + variation_id + '/' + location_id + '/' + qty,
                    data: {
                        product_row: $('input#product_row_count').val(),
                        customer_id: customer_id,
                        is_direct_sell: is_direct_sell,
                        price_group: price_group,
                        purchase_line_id: null
                    },
                    dataType: 'json',
                    success: function(result) {
                        console.log('temps...')
                        if (result.success) {
                            $('table#pos_table tbody')
                                .append(result.html_content)
                                .find('input.pos_quantity');
                            //increment row count
                            var this_row = $('table#pos_table tbody')
                                .find('tr')
                                .last();
                            pos_each_row(this_row);
                            //For initial discount if present
                            var line_total = __read_number(this_row.find('input.pos_line_total'));
                            this_row.find('span.pos_line_total_text').text(line_total);
                            pos_total_row();
                            //Check if multipler is present then multiply it when a new row is added.
                            if (__getUnitMultiplier(this_row) > 1) {
                                this_row.find('select.sub_unit').trigger('change');
                            }
                            if (result.enable_sr_no == '1') {
                                var new_row = $('table#pos_table tbody')
                                    .find('tr')
                                    .last();
                                new_row.find('.add-pos-row-description').trigger('click');
                            }
                            round_row_to_iraqi_dinnar(this_row);
                            __currency_convert_recursively(this_row);
                            $('input#search_product')
                                .focus()
                                .select();
                            //Used in restaurant module
                            if (result.html_modifier) {
                                $('table#pos_table tbody')
                                    .find('tr')
                                    .last()
                                    .find('td:first')
                                    .append(result.html_modifier);
                            }
                            //scroll bottom of items list
                            $(".pos_product_div").animate({
                                scrollTop: $('.pos_product_div').prop("scrollHeight")
                            }, 1000);
                        } else {
                            toastr.error(result.msg);
                            $('input#search_product')
                                .focus()
                                .select();
                        }
                    }
                });
            }

            $('#show').hide();

            // Call the function to load parts when the page is ready
            $(document).ready(function() {
                try {
                    var parts = @json($parts ?? []); // Fallback to empty array if null

                    // Ensure parts is an array
                    if (!Array.isArray(parts)) {
                        console.warn("Parts is not an array, converting...");
                        if (parts && typeof parts === 'object') {
                            parts = Object.values(parts);
                        } else {
                            parts = [];
                        }
                    }
                    loadPartsToPosTable(parts);
                } catch (e) {
                    console.error("Error loading parts:", e);
                }
            });

            function loadPartsToPosTable(parts) {
                console.log("Loading parts to POS table...", parts);

                if (!parts || !Array.isArray(parts)) {
                    console.warn("No parts data or invalid format");
                    return;
                }

                if (parts.length === 0) {
                    console.log("Parts array is empty");
                    return;
                }

                parts.forEach(function(part, index) {
                    console.log(`Processing part ${index}:`, part);

                    if (!part || !part.variation_id) {
                        console.warn(`Invalid part at index ${index}`, part);
                        return;
                    }

                    try {
                        console.log(`Adding part ${part.variation_id} with quantity ${part.quantity || 1}`);
                        pos_product_row(
                            part.variation_id,
                            null,
                            null,
                            part.quantity || 1
                        );
                    } catch (e) {
                        console.error(`Error adding part ${part.variation_id}:`, e);
                    }
                });
            }

            // ============================================================
            // Row Edit Product Price Modal Handlers (defined once globally)
            // ============================================================
            
            // Handle click on product name to open edit modal
            $(document).on('click', '.row_edit_product_price_btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $btn = $(this);
                var $tableRow = $btn.closest('tr.product_row');
                var rowCount = $tableRow.data('row_index');
                
                // Find the modal inside this row's td and move it to body temporarily
                var $modal = $tableRow.find('.row_edit_product_price_model');
                
                if ($modal.length) {
                    // Clone the modal to body if not already there
                    var bodyModalId = 'body_row_edit_product_price_modal_' + rowCount;
                    var $bodyModal = $('#' + bodyModalId);
                    
                    if (!$bodyModal.length) {
                        // Clone and append to body
                        $bodyModal = $modal.clone();
                        $bodyModal.attr('id', bodyModalId);
                        $bodyModal.appendTo('body');
                    }
                    
                    // Sync table values to modal before showing
                    var discountAmount = $tableRow.find('.row_discount_amount_table').val();
                    var discountType = $tableRow.find('.row_discount_type_table').val();
                    var unitPrice = $tableRow.find('.pos_unit_price').val();
                    
                    $bodyModal.find('.row_discount_amount').val(discountAmount);
                    $bodyModal.find('.row_discount_type').val(discountType);
                    $bodyModal.find('.pos_unit_price').val(unitPrice);
                    
                    // Store reference to table row
                    $bodyModal.data('table_row', $tableRow);
                    $bodyModal.data('row_count', rowCount);
                    
                    // Show modal
                    $bodyModal.modal('show');
                }
            });

            // Handle modal discount changes - sync with table row
            $(document).on('input change', '[id^="body_row_edit_product_price_modal_"] .row_discount_amount, [id^="body_row_edit_product_price_modal_"] .row_discount_type', function() {
                var $modal = $(this).closest('.modal');
                var $tableRow = $modal.data('table_row');

                if ($tableRow && $tableRow.length) {
                    var discountAmount = $modal.find('.row_discount_amount').val();
                    var discountType = $modal.find('.row_discount_type').val();

                    $tableRow.find('.row_discount_amount_table').val(discountAmount);
                    $tableRow.find('.row_discount_type_table').val(discountType);

                    if (typeof pos_each_row === 'function') {
                        pos_each_row($tableRow);
                        if (typeof pos_total_row === 'function') {
                            pos_total_row();
                        }
                    }
                }
            });

            // Handle modal unit price changes
            $(document).on('input', '[id^="body_row_edit_product_price_modal_"] .pos_unit_price', function() {
                var $modal = $(this).closest('.modal');
                var $tableRow = $modal.data('table_row');

                if ($tableRow && $tableRow.length) {
                    var newUnitPrice = parseFloat($(this).val()) || 0;
                    
                    // Update table row unit price
                    $tableRow.find('.pos_unit_price').val($(this).val());
                    
                    // Update the hidden base price field
                    var multiplier = parseFloat($tableRow.find('.base_unit_multiplier').val()) || 1;
                    var basePrice = newUnitPrice / multiplier;
                    $tableRow.find('.hidden_base_unit_sell_price').val(basePrice.toFixed(2));

                    if (typeof pos_each_row === 'function') {
                        pos_each_row($tableRow);
                        if (typeof pos_total_row === 'function') {
                            pos_total_row();
                        }
                    }
                }
            });
            
            // Clean up modal from body when row is removed
            $(document).on('click', '.pos_remove_row', function() {
                var $tableRow = $(this).closest('tr.product_row');
                var rowCount = $tableRow.data('row_index');
                $('#body_row_edit_product_price_modal_' + rowCount).remove();
            });
        </script>
@endsection

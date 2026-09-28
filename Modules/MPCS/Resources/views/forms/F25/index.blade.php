@extends('layouts.app')
@section('title', __('mpcs::lang.F25_form'))

@section('content')
<style>
/*
 * IS2162: the Action menu must escape the table's scroll container.
 *
 * .table-responsive scrolls horizontally, and a scrolling box clips anything
 * that overflows it - including an open dropdown, which then appears cut off at
 * the table edge. The same fault has been corrected on the MPCS, Supplier and
 * Pump lists.
 *
 * position: static on the group lets the menu position against the table
 * wrapper instead, and the raised z-index keeps it above the rows.
 */
#f25_list_table .btn-group { position: static; }

#f25_list_table .dropdown-menu {
    position: absolute;
    z-index: 1060;
}

/* The wrapper must not crop it on the way out. */
.f25-list-wrapper .table-responsive { overflow: visible !important; }

/* IS2286: keep the complete Goods Issued entry grid on one desktop screen. */
.f25-entry-table {
    width: 100%;
    max-width: 100%;
    overflow-x: visible !important;
}

#f25_rows_table {
    width: 100% !important;
    max-width: 100% !important;
    table-layout: fixed !important;
    margin-bottom: 8px;
}

#f25_rows_table th,
#f25_rows_table td {
    min-width: 0 !important;
    padding: 3px 2px !important;
    font-size: 10px;
    line-height: 1.15;
    white-space: normal !important;
    overflow-wrap: anywhere;
    vertical-align: middle !important;
}

#f25_rows_table th:nth-child(1) { width: 3%; }
#f25_rows_table th:nth-child(2) { width: 7%; }
#f25_rows_table th:nth-child(3) { width: 14%; }
#f25_rows_table th:nth-child(4),
#f25_rows_table th:nth-child(5) { width: 5%; }
#f25_rows_table th:nth-child(8) { width: 7%; }
#f25_rows_table th:nth-child(13) { width: 8%; }
#f25_rows_table th:nth-child(14) { width: 4%; }

#f25_rows_table .form-control,
#f25_rows_table .select2-container,
#f25_rows_table .select2-selection {
    width: 100% !important;
    min-width: 0 !important;
    max-width: 100% !important;
    height: 28px !important;
    padding: 3px 2px;
    font-size: 10px;
}

#f25_rows_table .select2-selection__rendered {
    padding-left: 3px !important;
    padding-right: 14px !important;
    line-height: 26px !important;
}

    .modal-backdrop {
        opacity: 0.5 !important;
    }

    /* Memaksa scrollbar vertikal Select2 selalu aktif dan terlihat (meskipun isi sedikit) */
    .select2-results__options {
        max-height: 100px !important;
        overflow-y: scroll !important; /* Paksa memunculkan scrollbar */
        scrollbar-width: thin !important; /* Firefox */
        scrollbar-color: #a8a8a8 #f1f1f1 !important; /* Firefox */
    }

    /* Webkit (Chrome, Safari, Edge): Pastikan scrollbar kustom tidak disembunyikan browser */
    .select2-results__options::-webkit-scrollbar {
        -webkit-appearance: none !important;
        width: 8px !important;
    }

    .select2-results__options::-webkit-scrollbar-track {
        background: #f1f1f1 !important;
        border-radius: 4px !important;
    }

    .select2-results__options::-webkit-scrollbar-thumb {
        background: #c1c1c1 !important;
        border-radius: 4px !important;
        border: 1px solid #f1f1f1 !important;
        min-height: 30px !important; /* Beri ukuran minimum agar gagang scrollbar tetap terlihat jelas */
    }

    .select2-results__options::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8 !important;
    }
</style>
@php
    $initial_location_id = $default_location_id;
    if (empty($initial_location_id) && count($business_locations) > 0) {
        $initial_location_id = collect($business_locations)->keys()->first();
    }
@endphp

<section class="content" style="padding-top:0;">
    <div class="row">
        <div class="col-md-12">
            <div class="settlement_tabs" data-mpcs-tabs>
                <ul class="nav nav-tabs no-print">
                    <li class="active">
                        <a href="#f25_form_tab" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('mpcs::lang.F25_form')</strong>
                        </a>
                    </li>
                    <li>
                        <a href="#f25_settings_tab" data-toggle="tab">
                            <i class="fa fa-cog"></i> <strong>@lang('mpcs::lang.f25_settings')</strong>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="f25_form_tab">
                        <div class="box box-primary">
                            <div class="box-body">
                                @if (session('status'))
                                    @if (session('status.success'))
                                        <div class="alert alert-success alert-dismissible">
                                            <button type="button" class="close" data-dismiss="close" aria-hidden="true">&times;</button>
                                            <h5><i class="icon fa fa-check"></i> {{ session('status.msg') }}</h5>
                                        </div>
                                    @else
                                        <div class="alert alert-danger alert-dismissible">
                                            <button type="button" class="close" data-dismiss="close" aria-hidden="true">&times;</button>
                                            <h5><i class="icon fa fa-ban"></i> {{ session('status.msg') }}</h5>
                                        </div>
                                    @endif
                                @endif

                                @if(empty($settings) || $settings->isEmpty())
                                    <div class="alert alert-warning">
                                        @lang('mpcs::lang.f25_no_settings')
                                    </div>
                                @else
                                    <div class="alert alert-info bg-aqua-active" style="border: none; margin-bottom: 20px;">
                                        <h4 style="margin-top: 0; font-weight: bold; font-size: 16px;"><i class="icon fa fa-info-circle"></i> @lang('mpcs::lang.f25_settings') / @lang('mpcs::lang.starting_number')</h4>
                                        <div style="margin-top: 10px;">
                                            @foreach($settings as $setting)
                                                <span class="label label-primary" style="font-size: 13px; margin-right: 10px; margin-bottom: 5px; display: inline-block; padding: 6px 10px; border-radius: 4px; background-color: #3c8dbc !important;">
                                                    <strong>@lang('mpcs::lang.opening_date'):</strong> {{ $setting->opening_date_display }} &nbsp;|&nbsp;
                                                    <strong>@lang('mpcs::lang.starting_number'):</strong> {{ $setting->starting_number }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <form id="f25_form" method="POST" action="{{ url('mpcs/F25/store') }}">
                                    @csrf
                                    <div class="row" style="margin-bottom: 20px;">
                                        <!-- Spacing to keep Business Location centered -->
                                        <div class="col-md-3"></div>
                                        
                                        <!-- Center Title & Business Location -->
                                        <div class="col-md-6 text-center">
                                            <div class="form-group" style="max-width: 320px; margin: 0 auto 10px auto;">
                                                <label style="font-size: 14px; font-weight: bold; color: #333;">Business Location</label>
                                                <select class="form-control select2" name="location_id" id="f25_location_id" required style="width: 100%;">
                                                    @foreach($business_locations as $id => $name)
                                                        <option value="{{ $id }}" {{ (string) $initial_location_id === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <h3 style="margin-top: 10px; margin-bottom: 0; font-weight: bold; color: #222; letter-spacing: 0.5px;">Goods Issued Form</h3>
                                        </div>

                                        <!-- Right "F 25" Title & Form No -->
                                        <div class="col-md-3 text-right">
                                            <div class="f25-header-title" style="font-size: 24px; font-weight: 900; color: #333; margin-bottom: 5px; padding-right: 5px;">F 25</div>
                                            <div class="form-group" style="max-width: 180px; float: right; text-align: left;">
                                                <label>@lang('mpcs::lang.form_no') *</label>
                                                <input type="text" class="form-control text-right" name="form_no" id="f25_form_no" value="{{ $form_no }}" readonly required style="font-weight: bold; background-color: #f5f5f5; color: #222;">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row" style="margin-bottom: 15px;">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>@lang('mpcs::lang.date') *</label>
                                                <input type="date" class="form-control" name="form_date" id="f25_form_date" value="{{ $today }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>@lang('mpcs::lang.supplier')</label>
                                                <select class="form-control select2" name="supplier_id" id="f25_supplier_id">
                                                    <option value="">@lang('lang_v1.please_select')</option>
                                                    @foreach($suppliers as $id => $name)
                                                        <option value="{{ $id }}">{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>@lang('mpcs::lang.bill_no')</label>
                                                <input type="text" class="form-control" name="bill_no" id="f25_bill_no">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>@lang('mpcs::lang.f25_delivery_location')</label>
                                                <select class="form-control select2" name="delivery_location_id" id="f25_delivery_location_id">
                                                    <option value="">@lang('lang_v1.please_select')</option>
                                                    @foreach($delivery_locations as $delivery_location)
                                                        <option value="{{ $delivery_location->id }}">
                                                            {{ str_pad((string) $delivery_location->location_code, 4, '0', STR_PAD_LEFT) }} - {{ $delivery_location->location_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive f25-entry-table">
                                        <table class="table table-bordered table-condensed" id="f25_rows_table" style="background-color: #fff;">
                                            <thead style="background-color: #3c8dbc !important; color: #ffffff !important;">
                                                <tr style="background-color: #3c8dbc !important; color: #ffffff !important;">
                                                    <th rowspan="2" class="text-center" style="vertical-align: middle; min-width:50px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">No</th>
                                                    <th rowspan="2" class="text-center" style="vertical-align: middle; min-width:95px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.bill_no')</th>
                                                    <th rowspan="2" class="text-center" style="vertical-align: middle; min-width:170px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.description_products')</th>
                                                    <th rowspan="2" class="text-center" style="vertical-align: middle; min-width:80px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.pcs')</th>
                                                    <th rowspan="2" class="text-center" style="vertical-align: middle; min-width:90px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.qty')</th>
                                                    <th colspan="2" class="text-center" style="vertical-align: middle; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.purchase_price')</th>
                                                    <th rowspan="2" class="text-center" style="vertical-align: middle; min-width:100px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.received_qty')</th>
                                                    <th colspan="2" class="text-center" style="vertical-align: middle; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.difference')</th>
                                                    <th colspan="2" class="text-center" style="vertical-align: middle; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.difference_in_cost')</th>
                                                    <th rowspan="2" class="text-center" style="vertical-align: middle; min-width:120px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.short_signature')</th>
                                                    <th rowspan="2" class="text-center" style="vertical-align: middle; min-width:70px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.action')</th>
                                                </tr>
                                                <tr style="background-color: #3c8dbc !important; color: #ffffff !important;">
                                                    <th class="text-center" style="min-width:110px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.unit_price')</th>
                                                    <th class="text-center" style="min-width:110px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.total')</th>
                                                    <th class="text-center" style="min-width:90px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.short')</th>
                                                    <th class="text-center" style="min-width:90px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.excess')</th>
                                                    <th class="text-center" style="min-width:120px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.short')</th>
                                                    <th class="text-center" style="min-width:120px; background-color: #3c8dbc !important; color: #ffffff !important; border: 1px solid #3c8dbc;">@lang('mpcs::lang.excess')</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>

                                    <div class="f25-signature-container row" style="margin-top: 20px; background-color: #fcfcfc; padding: 15px; border-radius: 6px; border: 1px solid #ddd; margin-left: 0; margin-right: 0; margin-bottom: 20px;">
                                        <div class="col-md-12">
                                            <h4 style="margin-top: 0; color: #222; border-bottom: 2px solid #3c8dbc; padding-bottom: 8px;"><strong>Received the above delivered Goods.</strong></h4>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group" style="margin-bottom: 0;">
                                                {{-- IS2110: the (19)-(22) field numbers were removed. They were
                                                     internal ordering references, not part of the form. --}}
                                                <label>Received Date</label>
                                                <input type="date" class="form-control" id="f25_footer_received_date" value="{{ $today }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group" style="margin-bottom: 0;">
                                                <label>Time</label>
                                                <input type="time" class="form-control" id="f25_footer_received_time" value="{{ $current_time }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group" style="margin-bottom: 0;">
                                                <label>Officer (Dispatch)</label>
                                                <input type="text" class="form-control" id="f25_footer_field_21" placeholder="Officer Name">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group" style="margin-bottom: 0;">
                                                <label>Store Keeper</label>
                                                <input type="text" class="form-control" id="f25_footer_field_22" placeholder="Store Keeper Name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <button type="button" class="btn btn-default" id="f25_add_row">
                                                <i class="fa fa-plus"></i> @lang('mpcs::lang.add')
                                            </button>
                                            <button type="submit" class="btn btn-primary pull-right" id="f25_save_btn">
                                                <i class="fa fa-save"></i> @lang('mpcs::lang.save')
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">@lang('mpcs::lang.F25_form') @lang('mpcs::lang.list')</h3>
                            </div>
                            <div class="box-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped" id="f25_list_table">
                                        <thead>
                                            <tr>
                                                <th>@lang('mpcs::lang.form_no')</th>
                                                <th>@lang('mpcs::lang.date')</th>
                                                <th>@lang('mpcs::lang.location')</th>
                                                <th>@lang('mpcs::lang.supplier')</th>
                                                <th>@lang('mpcs::lang.f25_delivery_location')</th>
                                                <th>@lang('mpcs::lang.action')</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="f25_settings_tab">
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">@lang('mpcs::lang.f25_settings')</h3>
                                <div class="box-tools pull-right">
                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#f25_settings_modal">
                                        <i class="fa fa-plus"></i> @lang('mpcs::lang.add')
                                    </button>
                                </div>
                            </div>
                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible" style="margin: 15px;">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h5><i class="icon fa fa-ban"></i> Error!</h5>
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if (session('status'))
                                <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible" style="margin: 15px;">
                                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                    <h5>
                                        <i class="icon fa fa-{{ session('status.success') ? 'check' : 'ban' }}"></i>
                                        {{ session('status.msg') }}
                                    </h5>
                                </div>
                            @endif
                            <div class="box-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>@lang('mpcs::lang.opening_date')</th>
                                                <th>@lang('mpcs::lang.starting_number')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($settings as $setting)
                                                <tr>
                                                    <td>{{ $setting->opening_date_display }}</td>
                                                    <td>{{ $setting->starting_number }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center">@lang('lang_v1.no_data')</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <h3 class="box-title">@lang('mpcs::lang.f25_delivery_locations')</h3>
                                <div class="box-tools pull-right">
                                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#f25_delivery_modal">
                                        <i class="fa fa-plus"></i> @lang('mpcs::lang.add')
                                    </button>
                                </div>
                            </div>
                            <div class="box-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>@lang('mpcs::lang.code')</th>
                                                <th>@lang('lang_v1.name')</th>
                                                <th>@lang('mpcs::lang.status')</th>
                                                <th>@lang('mpcs::lang.action')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($all_delivery_locations as $delivery_location)
                                                <tr>
                                                    <td>{{ str_pad((string) $delivery_location->location_code, 4, '0', STR_PAD_LEFT) }}</td>
                                                    <td>
                                                        <form method="POST" action="{{ url('mpcs/F25/delivery-locations/' . $delivery_location->id) }}" class="form-inline">
                                                            @csrf
                                                            <input type="text" class="form-control input-sm" name="name" value="{{ $delivery_location->location_name }}" required>
                                                            <input type="hidden" name="is_active" value="{{ $delivery_location->status === 'active' ? 1 : 0 }}">
                                                            <button type="submit" class="btn btn-xs btn-default">
                                                                <i class="fa fa-save"></i> @lang('mpcs::lang.edit')
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        <button type="button"
                                                                class="btn btn-xs {{ $delivery_location->status === 'active' ? 'btn-success' : 'btn-default' }} f25-open-status-modal"
                                                                data-id="{{ $delivery_location->id }}"
                                                                data-name="{{ $delivery_location->location_name }}"
                                                                data-status="{{ $delivery_location->status === 'active' ? 1 : 0 }}">
                                                            {{ $delivery_location->status === 'active' ? __('mpcs::lang.active') : __('mpcs::lang.inactive') }}
                                                        </button>
                                                    </td>
                                                    <td>
                                                        <button type="button"
                                                                class="btn btn-xs btn-primary f25-open-status-modal"
                                                                data-id="{{ $delivery_location->id }}"
                                                                data-name="{{ $delivery_location->location_name }}"
                                                                data-status="{{ $delivery_location->status === 'active' ? 1 : 0 }}">
                                                            Edit
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center">@lang('lang_v1.no_data')</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal" id="f25_settings_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">@lang('mpcs::lang.f25_settings')</h4>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ url('mpcs/F25/settings') }}" class="row">
                    @csrf
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.opening_date') *</label>
                            <input type="date" class="form-control" name="opening_date" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.starting_number') *</label>
                            <input type="number" min="1" class="form-control" name="starting_number" required>
                        </div>
                    </div>
                    <div class="col-md-12 text-right">
                        <button type="submit" class="btn btn-primary">@lang('mpcs::lang.save')</button>
                    </div>
                </form>

                <hr>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('mpcs::lang.opening_date')</th>
                                <th>@lang('mpcs::lang.starting_number')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($settings as $setting)
                                <tr>
                                    <td>{{ $setting->opening_date_display }}</td>
                                    <td>{{ $setting->starting_number }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center">@lang('lang_v1.no_data')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="f25_delivery_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">@lang('mpcs::lang.f25_delivery_locations')</h4>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ url('mpcs/F25/delivery-locations') }}" class="row">
                    @csrf
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>@lang('lang_v1.name') *</label>
                            <textarea class="form-control" name="names" rows="3" required></textarea>
                            <small class="text-muted">@lang('mpcs::lang.f25_delivery_locations_hint')</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.status')</label>
                            <select class="form-control" name="is_active">
                                <option value="1">@lang('mpcs::lang.active')</option>
                                <option value="0">@lang('mpcs::lang.inactive')</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary form-control">@lang('mpcs::lang.add')</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>


<div class="modal" id="f25_status_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title">Edit @lang('mpcs::lang.f25_delivery_locations')</h4>
                <!-- <h4 class="modal-title">@lang('mpcs::lang.status')</h4> -->
            </div>
            <div class="modal-body">
                <form id="f25_status_form" method="POST" action="" class="row">
                    @csrf
                    
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>@lang('lang_v1.name') *</label>
                            <textarea class="form-control" name="name" id="f25_edit_location_name" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.status')</label>
                            <select class="form-control" name="is_active" id="f25_edit_is_active">
                                <option value="1">@lang('mpcs::lang.active')</option>
                                <option value="0">@lang('mpcs::lang.inactive')</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 text-right">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary form-control">@lang('mpcs::lang.save')</button>
                    </div>

                    <!-- Commented old status update code
                    <input type="hidden" name="name" id="f25_status_location_name">
                    <input type="hidden" name="is_active" id="f25_status_is_active" value="1">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.f25_current_status')</label>
                        <input type="text" class="form-control" id="f25_current_status_text" readonly>
                    </div>
                    <div class="form-group">
                        <label>@lang('mpcs::lang.f25_change_to')</label>
                        <div>
                            <label class="checkbox-inline">
                                <input type="checkbox" class="f25-status-check" value="1"> @lang('mpcs::lang.active')
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" class="f25-status-check" value="0"> @lang('mpcs::lang.inactive')
                            </label>
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">@lang('mpcs::lang.update')</button>
                    </div>
                    -->
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
    @include('mpcs::partials.safe_tabs')
<script type="text/javascript">
    $(document).ready(function() {
        var f25RowIndex = 0;
        var currencyPrecision = {{ (int) ($currency_precision ?? 2) }};
        var quantityPrecision = 2;
        var defaultLineDate = @json($today);
        var defaultLineTime = @json($current_time);
        var pleaseSelectText = @json(__('lang_v1.please_select'));

        if ($.fn.select2) {
            // Initialize location dropdown independently (not via global .select2 class)
            $('.select2').select2({
                width: '100%',
                minimumResultsForSearch: 0
            });
            // Initialize Supplier and Delivery Location select2 elements
            // $('.select2').select2({ width: '100%' });
        }

        function parseNumeric(value) {
            var text = String(value || '').replace(/,/g, '').trim();
            var num = parseFloat(text);
            return isNaN(num) ? 0 : num;
        }

        function formatNumeric(value, precision) {
            var effectivePrecision = typeof precision === 'number' ? precision : currencyPrecision;
            return parseNumeric(value).toLocaleString('en-US', {
                minimumFractionDigits: effectivePrecision,
                maximumFractionDigits: effectivePrecision
            });
        }

        function normalizeNumericInputsForSubmit() {
            $('#f25_rows_table').find('.f25-numeric').each(function() {
                $(this).val(parseNumeric($(this).val()));
            });
        }

        function productSelectHtml(index) {
            var html = '<select class="form-control select2 f25-product-select" name="rows[' + index + '][product_id]">';
            html += '<option value="">' + pleaseSelectText + '</option>';
            html += '</select>';
            return html;
        }

        function replaceProductOptions($select, products, selected) {
            var html = '<option value="">' + pleaseSelectText + '</option>';
            $.each(products, function(_, product) {
                html += '<option value="' + product.id + '">' + product.name + '</option>';
            });
            $select.html(html);
            if (selected) {
                $select.val(String(selected));
            }
            $select.trigger('change.select2');
        }

        function loadProductsByLocation() {
            var locationId = $('#f25_location_id').val() || '';
            $.ajax({
                method: 'GET',
                url: @json(url('mpcs/F25/products')),
                data: { location_id: locationId },
                success: function(resp) {
                    if (!resp || !resp.success) {
                        return;
                    }
                    var products = resp.products || [];
                    $('#f25_rows_table').find('.f25-product-select').each(function() {
                        var selected = $(this).val();
                        replaceProductOptions($(this), products, selected);
                    });
                }
            });
        }

        function updateFormNoByDate() {
            $.ajax({
                method: 'GET',
                url: @json(url('mpcs/F25/form-no')),
                data: { 
                    date: $('#f25_form_date').val(),
                    location_id: $('#f25_location_id').val()
                },
                success: function(resp) {
                    $('#f25_form_no').val(resp.form_no || '');
                }
            });
        }

        function recalcRow($row) {
            var qty = parseNumeric($row.find('.f25-qty').val());
            var unitPrice = parseNumeric($row.find('.f25-unit-price').val());
            var receivedQty = parseNumeric($row.find('.f25-received-qty').val());
            var total = qty * unitPrice;
            var shortQty = qty > receivedQty ? (qty - receivedQty) : 0;
            var excessQty = receivedQty > qty ? (receivedQty - qty) : 0;
            var shortAmount = shortQty * unitPrice;
            var excessAmount = excessQty * unitPrice;

            $row.find('.f25-total').val(formatNumeric(total, currencyPrecision));
            $row.find('.f25-short-qty').val(formatNumeric(shortQty, quantityPrecision));
            $row.find('.f25-excess-qty').val(formatNumeric(excessQty, quantityPrecision));
            $row.find('.f25-short-amount').val(formatNumeric(shortAmount, currencyPrecision));
            $row.find('.f25-excess-amount').val(formatNumeric(excessAmount, currencyPrecision));
        }

        function formatPcsValue(value) {
            var raw = String(value || '').trim();
            if (!raw) {
                return '';
            }
            var numericCandidate = raw.replace(/,/g, '');
            if (/^-?\d+(\.\d+)?$/.test(numericCandidate)) {
                return formatNumeric(numericCandidate, quantityPrecision);
            }
            return raw;
        }

        function addRow() {
            var idx = f25RowIndex++;
            var row = '';
            row += '<tr>';
            row += '<td class="f25-row-no">' + (idx + 1) + '</td>';
            row += '<td><input type="text" class="form-control input-sm" name="rows[' + idx + '][line_bill_no]"></td>';
            row += '<td>' + productSelectHtml(idx) + '<input type="hidden" name="rows[' + idx + '][product_name]" class="f25-product-name"></td>';
            row += '<td><input type="text" class="form-control input-sm text-right f25-pcs" name="rows[' + idx + '][pcs]" value=""></td>';
            row += '<td><input type="text" class="form-control input-sm text-right f25-numeric f25-qty" name="rows[' + idx + '][qty]" value="' + formatNumeric(0, quantityPrecision) + '"></td>';
            row += '<td><input type="text" class="form-control input-sm text-right f25-numeric f25-unit-price" name="rows[' + idx + '][unit_price]" value="' + formatNumeric(0, currencyPrecision) + '"></td>';
            row += '<td><input type="text" class="form-control input-sm text-right f25-numeric f25-total" name="rows[' + idx + '][total_amount]" value="' + formatNumeric(0, currencyPrecision) + '" readonly></td>';
            row += '<td><input type="text" class="form-control input-sm text-right f25-numeric f25-received-qty" name="rows[' + idx + '][received_qty]" value="' + formatNumeric(0, quantityPrecision) + '"></td>';
            row += '<td><input type="text" class="form-control input-sm text-right f25-numeric f25-short-qty" name="rows[' + idx + '][short_qty]" value="' + formatNumeric(0, quantityPrecision) + '" readonly></td>';
            row += '<td><input type="text" class="form-control input-sm text-right f25-numeric f25-excess-qty" name="rows[' + idx + '][excess_qty]" value="' + formatNumeric(0, quantityPrecision) + '" readonly></td>';
            row += '<td><input type="text" class="form-control input-sm text-right f25-numeric f25-short-amount" name="rows[' + idx + '][short_amount]" value="' + formatNumeric(0, currencyPrecision) + '" readonly></td>';
            row += '<td><input type="text" class="form-control input-sm text-right f25-numeric f25-excess-amount" name="rows[' + idx + '][excess_amount]" value="' + formatNumeric(0, currencyPrecision) + '" readonly></td>';
            row += '<td><input type="text" class="form-control input-sm" name="rows[' + idx + '][short_signature]"></td>';
            row += '<input type="hidden" class="f25-line-date" name="rows[' + idx + '][line_date]" value="' + defaultLineDate + '">';
            row += '<input type="hidden" class="f25-line-time" name="rows[' + idx + '][line_time]" value="' + defaultLineTime + '">';
            row += '<input type="hidden" class="f25-field-21" name="rows[' + idx + '][field_21]">';
            row += '<input type="hidden" class="f25-field-22" name="rows[' + idx + '][field_22]">';
            row += '<td><button type="button" class="btn btn-xs btn-danger f25-remove-row"><i class="fa fa-times"></i></button></td>';
            row += '</tr>';

            $('#f25_rows_table tbody').append(row);
            var $last = $('#f25_rows_table tbody tr:last');
            if ($.fn.select2) {
                $last.find('.select2').select2({ width: '100%' });
            }
            loadProductsByLocation();
            recalcRow($last);
            syncFooterToHiddenInputs();
        }

        function renumberRows() {
            $('#f25_rows_table tbody tr').each(function(i) {
                $(this).find('.f25-row-no').text(i + 1);
            });
        }

        $('#f25_form_date').on('change', updateFormNoByDate);
        $('#f25_location_id').on('change', loadProductsByLocation);
        $('#f25_add_row').on('click', addRow);

        $('.f25-open-status-modal').on('click', function() {
            var id = $(this).data('id');
            var locationName = $(this).data('name');
            var status = parseInt($(this).data('status')) === 1 ? 1 : 0;
            var deliveryBaseUrl = @json(url('mpcs/F25/delivery-locations'));

            // Populate the new inputs
            $('#f25_edit_location_name').val(locationName);
            $('#f25_edit_is_active').val(String(status));
            $('#f25_status_form').attr('action', deliveryBaseUrl + '/' + id);

            /* Commented old status modal logic
            var nextStatus = status === 1 ? 0 : 1;
            $('#f25_status_location_name').val(locationName);
            $('#f25_current_status_text').val(status === 1 ? '@lang("mpcs::lang.active")' : '@lang("mpcs::lang.inactive")');
            $('#f25_status_is_active').val(String(status));

            $('.f25-status-check')
                .prop('checked', false)
                .prop('disabled', false);
            $('.f25-status-check[value="' + status + '"]')
                .prop('checked', true)
                .prop('disabled', true);
            $('.f25-status-check[value="' + nextStatus + '"]')
                .prop('checked', false)
                .prop('disabled', false);
            */
            $('#f25_status_modal').modal('show');
        });


        $(document).on('click', '[data-target="#f25_settings_modal"]', function(e) {
            e.preventDefault();
            $('#f25_settings_modal').modal('show');
        });

        $(document).on('click', '[data-target="#f25_delivery_modal"]', function(e) {
            e.preventDefault();
            $('#f25_delivery_modal').modal('show');
        });


        $(document).on('change', '.f25-status-check', function() {
            var selectedValue = $(this).val();
            if ($(this).prop('disabled')) {
                return;
            }
            if (!$(this).prop('checked')) {
                $(this).prop('checked', true);
            }
            $('.f25-status-check').not(this).each(function() {
                if (!$(this).prop('disabled')) {
                    $(this).prop('checked', false);
                }
            });
            $('#f25_status_is_active').val(String(selectedValue));
        });

        $(document).on('blur', '.f25-numeric', function() {
            if ($(this).hasClass('f25-qty') || $(this).hasClass('f25-received-qty') || $(this).hasClass('f25-short-qty') || $(this).hasClass('f25-excess-qty')) {
                $(this).val(formatNumeric($(this).val(), quantityPrecision));
                return;
            }
            $(this).val(formatNumeric($(this).val(), currencyPrecision));
        });

        $(document).on('blur', '.f25-pcs', function() {
            $(this).val(formatPcsValue($(this).val()));
        });

        $(document).on('keyup change', '.f25-qty, .f25-unit-price, .f25-received-qty', function() {
            recalcRow($(this).closest('tr'));
        });

        $(document).on('change', '.f25-product-select', function() {
            var $row = $(this).closest('tr');
            var productId = $(this).val();
            var productName = $(this).find('option:selected').text() || '';
            $row.find('.f25-product-name').val(productName);

            if (!productId) {
                $row.find('.f25-unit-price').val(formatNumeric(0, currencyPrecision));
                recalcRow($row);
                return;
            }

            $.ajax({
                method: 'GET',
                url: @json(url('mpcs/F25/product-price')),
                data: { product_id: productId },
                success: function(resp) {
                    if (resp && resp.success) {
                        $row.find('.f25-unit-price').val(formatNumeric(resp.unit_price, currencyPrecision));
                        recalcRow($row);
                    }
                }
            });
        });

        $(document).on('click', '.f25-remove-row', function() {
            $(this).closest('tr').remove();
            renumberRows();
        });

        function syncFooterToHiddenInputs() {
            var dateVal = $('#f25_footer_received_date').val();
            var timeVal = $('#f25_footer_received_time').val();
            var f21Val = $('#f25_footer_field_21').val();
            var f22Val = $('#f25_footer_field_22').val();

            $('#f25_rows_table tbody tr').each(function() {
                var $row = $(this);
                $row.find('.f25-line-date').val(dateVal);
                $row.find('.f25-line-time').val(timeVal);
                $row.find('.f25-field-21').val(f21Val);
                $row.find('.f25-field-22').val(f22Val);
            });
        }

        $(document).on('change input', '#f25_footer_received_date, #f25_footer_received_time, #f25_footer_field_21, #f25_footer_field_22', function() {
            syncFooterToHiddenInputs();
        });

        $('#f25_form').on('submit', function(e) {
            syncFooterToHiddenInputs();
            if ($('#f25_rows_table tbody tr').length < 1) {
                e.preventDefault();
                toastr.warning('@lang("mpcs::lang.please_add_at_least_one_row")');
                return false;
            }
            if (!$('#f25_form_no').val()) {
                e.preventDefault();
                toastr.warning('@lang("mpcs::lang.f25_form_no_required")');
                return false;
            }
            normalizeNumericInputsForSubmit();
        });

        addRow();
        loadProductsByLocation();
        if (!$('#f25_form_no').val()) {
            updateFormNoByDate();
        }

        $('#f25_list_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: @json(url('mpcs/F25/list')),
            columns: [
                { data: 'form_no', name: 'form_no' },
                { data: 'form_date', name: 'form_date' },
                { data: 'location_name', name: 'location_name' },
                { data: 'supplier_name', name: 'supplier_name' },
                { data: 'delivery_code', name: 'delivery_code' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[1, 'desc']]
        });

    });
</script>
@endsection

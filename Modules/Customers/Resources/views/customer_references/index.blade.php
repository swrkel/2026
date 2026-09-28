@extends('layouts.app')

@section('title', 'List Customer Reference')

@section('content')

{{--
    Task 8046 - List Customer Reference.

    Every dropdown on this page carries the `select2` class, which is what gives
    the "Type & Auto Filter" behaviour the spec asks for at the top of the
    document. That applies to the filter bar here and to the dropdowns inside
    the Add popup, which are initialised in customer-reference.js after the
    modal is shown.
--}}

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">List Customer Reference</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="{{ route('customers.index') }}">@lang('customers::lang.customers')</a></li>
                    <li><span></span> List Customer Reference</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<section class="content main-content-inner">

    @if(! $installed)
        {{--
            The page still opens when the table is missing so the user gets an
            instruction instead of a 500, matching how the workflow and
            master-data pages behave on a partially migrated tenant.
        --}}
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i>
            The required table <strong>customer_qr_references</strong> is not installed in this database yet.
            Run <strong>00_MASTER_CUSTOMER_QR_REFERENCE.sql</strong> (or the module migrations) in this tenant
            database, then refresh this page.
        </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('cus_ref_date_range', 'Date Range:') !!}
                        <input type="text" name="cus_ref_date_range" id="cus_ref_date_range"
                               class="form-control" style="width: 100%;" readonly
                               placeholder="Select a date range" />
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('cus_ref_filter_customer_id', 'Customer:') !!}
                        {!! Form::select('cus_ref_filter_customer_id', $customers, null, [
                            'class' => 'form-control select2',
                            'id' => 'cus_ref_filter_customer_id',
                            'style' => 'width: 100%;',
                            'placeholder' => 'All',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('cus_ref_filter_status', 'Status:') !!}
                        {!! Form::select('cus_ref_filter_status', ['1' => 'Active', '0' => 'Inactive'], null, [
                            'class' => 'form-control select2',
                            'id' => 'cus_ref_filter_status',
                            'style' => 'width: 100%;',
                            'placeholder' => 'All',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('cus_ref_filter_is_vehicle', 'Is a Vehicle:') !!}
                        {!! Form::select('cus_ref_filter_is_vehicle', ['1' => 'Yes', '0' => 'No'], null, [
                            'class' => 'form-control select2',
                            'id' => 'cus_ref_filter_is_vehicle',
                            'style' => 'width: 100%;',
                            'placeholder' => 'All',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('cus_ref_filter_reference_no', 'Reference No:') !!}
                        {!! Form::text('cus_ref_filter_reference_no', null, [
                            'class' => 'form-control',
                            'id' => 'cus_ref_filter_reference_no',
                            'style' => 'width: 100%;',
                            'placeholder' => 'Search reference no',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('cus_ref_filter_fuel_type', 'Fuel Type:') !!}
                        {!! Form::select('cus_ref_filter_fuel_type', $fuelTypes, null, [
                            'class' => 'form-control select2',
                            'id' => 'cus_ref_filter_fuel_type',
                            'style' => 'width: 100%;',
                            'placeholder' => 'All',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('cus_ref_filter_added_by', 'Added By:') !!}
                        {!! Form::select('cus_ref_filter_added_by', $addedByUsers, null, [
                            'class' => 'form-control select2',
                            'id' => 'cus_ref_filter_added_by',
                            'style' => 'width: 100%;',
                            'placeholder' => 'All',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group" style="margin-top: 25px;">
                        <button type="button" class="btn btn-primary" id="cus_ref_apply_filters">
                            <i class="fa fa-filter"></i> Apply
                        </button>
                        <button type="button" class="btn btn-default" id="cus_ref_reset_filters">
                            <i class="fa fa-refresh"></i> Reset
                        </button>
                    </div>
                </div>

            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])

                <div class="row" style="margin-bottom: 12px;">
                    <div class="col-md-12 text-right">
                        <button type="button" class="btn btn-primary" id="cus_ref_add_button"
                                {{ $installed ? '' : 'disabled' }}>
                            <i class="fa fa-plus"></i> Add Customer Reference
                        </button>
                    </div>
                </div>

                @unless($fuelTypeConfigured)
                    {{--
                        Without a Fuel product category the Fuel Type dropdown
                        contains only the system default. Saying so here is far
                        less confusing than a one-item dropdown with no
                        explanation.
                    --}}
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        No <strong>Fuel</strong> product category with sub-categories was found for this business,
                        so the Fuel Type dropdown currently offers only <strong>Not Known</strong>.
                        Add sub-categories under the Fuel product category, or set
                        <code>customers.fuel_category_id</code>, to populate this list.
                    </div>
                @endunless


                <div class="table-responsive customer-references-scroll"
                     style="overflow-x: auto !important; overflow-y: visible !important; width: 100%; -webkit-overflow-scrolling: touch;">
                    <table class="table table-bordered table-striped" id="customer_references_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th class="notexport">@lang('messages.action')</th>
                                <th>Date &amp; Time</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Is a Vehicle</th>
                                <th>Reference No</th>
                                <th>Fuel Type</th>
                                <th>Added By</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

            @endcomponent
        </div>
    </div>

</section>

@include('customers::customer_references.partials.add_modal')
@include('customers::customer_references.partials.generic_modal')

@endsection

@section('javascript')
    {{--
        Configuration is handed to the runtime script through a JSON island
        rather than by inlining route strings into the JS file, so the script
        itself stays static and cacheable.

        The array is assembled in the controller and arrives here as one ready
        variable, deliberately not built inline with a blade directive. Blade
        parses a directive argument by counting brackets, so a multi-line array
        that itself contains array access makes that parser terminate early,
        producing a truncated and syntactically invalid compiled view.
    --}}
    <script type="application/json" id="cus_ref_config">{!! $jsConfigJson !!}</script>

    <script src="{{ route('customers.customer_references.runtime') }}?v=20260828-8046-v4"></script>
@endsection

@section('css')
<style>
    /*
     * The table has eight columns and is wider than the box on a normal
     * screen, so it gets its own scroll context. The theme's .box carries
     * overflow:hidden and would otherwise clip the right-hand columns with no
     * way to reach them - the same problem fixed on Customer Payments in
     * LA-1185.
     */
    .customer-references-scroll { min-height: 220px; }
    .customer-references-scroll table { min-width: 900px; }

    /*
     * The Action dropdown is moved to <body> while open, because the scroll
     * wrapper above clips anything that overflows it and no z-index can undo
     * clipping. These rules style it in its detached position.
     */
    .cus-ref-floating-menu {
        z-index: 100000 !important;
        display: block !important;
        min-width: 170px;
    }

    /* Staged rows in the Add popup, before Save is pressed. */
    .cus-ref-staged-table tbody tr.cus-ref-editing { background: #fff8e1; }

    .cus-ref-qr-holder { text-align: center; padding: 10px; }
    .cus-ref-qr-holder svg,
    .cus-ref-qr-holder canvas,
    .cus-ref-qr-holder img { max-width: 260px; height: auto; }

    .cus-ref-qr-payload {
        white-space: pre-wrap;
        font-family: monospace;
        background: #f7f7f9;
        border: 1px solid #e1e1e8;
        border-radius: 4px;
        padding: 10px;
        margin-top: 10px;
    }
</style>
@endsection

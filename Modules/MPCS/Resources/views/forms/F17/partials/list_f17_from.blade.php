<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __(
        'mpcs::lang.list_f17_from')])
    
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('list_f17_date_range', __('mpcs::lang.date') . ':') !!}
                {!! Form::text('list_f17_date_range', null, ['class' => 'form-control list_f17_filter', 'id' => 'list_f17_date_range', 'readonly']) !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('from_no_filter', __('mpcs::lang.from_no') . ':') !!}
                {!! Form::select('from_no_filter', $forms_nos, null, ['class' => 'form-control list_f17_filter select2', 'style' => 'width: 100%', 'id' => 'from_no_filter', 'placeholder' => __('lang_v1.all')]) !!}
            </div>
        </div>
        <div class="col-md-3" id="location_filter">
            <div class="form-group">
                {!! Form::label('list_form_f17_location_id', __('purchase.business_location') . ':') !!}
                {!! Form::select('list_form_f17_location_id', $business_locations, $default_location_id ?? null, ['class' => 'form-control list_f17_filter select2',
                'id' => 'list_form_f17_location_id',
                'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
    
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('list_store_id', __('lang_v1.store_id').':') !!}
                <select name="store_id" id="list_store_id" class="form-control list_f17_filter select2" style="width: 100%;" data-placeholder="@lang('lang_v1.all')">
                    <option value="" selected>@lang('lang_v1.all')</option>
                    @foreach($stores as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('list_f17_category_id', __('mpcs::lang.product_category') . ':') !!}
                <select name="category_id" id="list_f17_category_id" class="form-control list_f17_filter select2" style="width: 100%">
                    <option value="">@lang('lang_v1.all')</option>
                    @foreach($categories as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('list_f17_sub_category_id', __('mpcs::lang.product_sub_category') . ':') !!}
                <select name="sub_category_id" id="list_f17_sub_category_id" class="form-control list_f17_filter select2" style="width: 100%">
                    <option value="">@lang('lang_v1.all')</option>
                </select>
            </div>
        </div>
    
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('list_f17_brand_id', __('product.brand') . ':') !!}
                {!! Form::select('brand_id', $brands, null, ['class' => 'form-control list_f17_filter select2', 'style' =>
                'width:100%', 'id' => 'list_f17_brand_id', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('list_f17_unit_id', __('product.unit') . ':') !!}
                {!! Form::select('unit_id', $units, null, ['class' => 'form-control list_f17_filter select2', 'style' =>
                'width:100%', 'id' => 'list_f17_unit_id', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
    
        @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __(
    'mpcs::lang.list_f17_from')])
    {{-- IS2200 #4: fit all F17 list columns inside the table section. --}}
    <style>
        #list_form_f17_table_wrapper {
            width: 100% !important;
            max-width: 100% !important;
        }
        #list_form_f17_table {
            width: 100% !important;
            table-layout: fixed !important;
        }
        #list_form_f17_table thead th {
            white-space: normal !important;
            overflow-wrap: anywhere !important;
            word-break: normal !important;
            font-size: 10px !important;
            line-height: 1.15 !important;
            padding: 6px 3px !important;
            vertical-align: middle !important;
            text-align: center !important;
        }
        #list_form_f17_table tbody td {
            white-space: normal !important;
            overflow-wrap: anywhere !important;
            word-break: normal !important;
            font-size: 11px !important;
            line-height: 1.18 !important;
            padding: 6px 4px !important;
            vertical-align: middle !important;
        }
        #list_form_f17_table th:nth-child(1)  { width: 8%; }
        #list_form_f17_table th:nth-child(2)  { width: 9%; }
        #list_form_f17_table th:nth-child(3)  { width: 6%; }
        #list_form_f17_table th:nth-child(4)  { width: 11%; }
        #list_form_f17_table th:nth-child(5)  { width: 8%; }
        #list_form_f17_table th:nth-child(6)  { width: 9%; }
        #list_form_f17_table th:nth-child(7)  { width: 7%; }
        #list_form_f17_table th:nth-child(8)  { width: 8%; }
        #list_form_f17_table th:nth-child(9)  { width: 10%; }
        #list_form_f17_table th:nth-child(10) { width: 10%; }
        #list_form_f17_table th:nth-child(11) { width: 7%; }
        #list_form_f17_table th:nth-child(12) { width: 7%; }
        #list_form_f17_table .btn-group {
            position: relative;
        }
        #list_form_f17_table .dropdown-menu {
            z-index: 5000;
        }
        .f17-list-table-wrap {
            width: 100%;
            max-width: 100%;
            overflow: visible !important;
        }
    </style>
    <div class="table-responsive f17-list-table-wrap">
        <table class="table table-bordered table-striped" id="list_form_f17_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('mpcs::lang.action')</th>
                    <th>@lang('mpcs::lang.date_and_time')</th>
                    <th>@lang('mpcs::lang.form_no')</th>
                    <th>@lang('mpcs::lang.location')</th>
                    <th>@lang('mpcs::lang.category')</th>
                    <th>@lang('mpcs::lang.sub_category')</th>
                    <th>@lang('mpcs::lang.store')</th>
                    <th>@lang('mpcs::lang.select_mode')</th>
                    <th>@lang('mpcs::lang.total_price_change_loss')</th>
                    <th>@lang('mpcs::lang.total_price_change_gain')</th>
                    <th>@lang('mpcs::lang.user')</th>
                    <th>@lang('mpcs::lang.page_no')</th>

                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

    <div class="modal fade fuel_tank_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->
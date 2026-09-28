<!-- Main content -->
{!! Form::open([
    'url' => url('/mpcs/F17'),
    'method' => 'post',
    'id' => 'mpcs_f17_form',
    'data-mpcs-form' => 'f17',
    'autocomplete' => 'off',
]) !!}
<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __(
    'mpcs::lang.f17_from')])
    <div class="row">
        <!-- 1. F 17 Form No - First -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('F17_from_no', __('mpcs::lang.F17_from_no') . ':') !!}
                {!! Form::text('F17_from_no', $F17_from_no, ['class' => 'form-control', 'id' => 'F17_from_no', 'readonly',  'style' => 'height:28px']) !!}
            </div>
        </div>

        <!-- 2. Date -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('f17_date', __('mpcs::lang.date') . ':') !!}
                {!! Form::text('f17_date', null, ['class' => 'form-control', 'id' => 'f17_date', 'placeholder' => 'Select Date', 'style' => 'height:28px']) !!}
            </div>
        </div>

        <!-- 3. Business Location -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                {!! Form::select('location_id', $business_locations, $default_location_id ?? null, ['class' => 'form-control f17_filter select2',
                'id' => 'location_id',
                'style' => 'width:100%', 'placeholder' => __('messages.please_select')]); !!}
            </div>
        </div>

        <!-- 4. Store -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('store_id', __('lang_v1.store_id').':') !!}
                <select name="store_id" id="store_id" class="form-control f17_filter select2" style="width: 100%" data-placeholder="@lang('lang_v1.all')">
                    <option value="" selected>@lang('lang_v1.all')</option>
                    @foreach($stores as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- 5. Product Category -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('category_id', __('mpcs::lang.product_category') . ':') !!}
                <select name="category_id" id="product_list_filter_category_id" class="form-control f17_filter select2" style="width: 100%">
                    <option value="">@lang('lang_v1.all')</option>
                    @foreach($categories as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- 6. Product Sub Category -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sub_category_id', __('mpcs::lang.product_sub_category') . ':') !!}
                <select name="sub_category_id" id="product_list_filter_sub_category_id" class="form-control f17_filter select2" style="width: 100%" data-placeholder="@lang('lang_v1.all')">
                    <option value="" selected>@lang('lang_v1.all')</option>
                </select>
            </div>
        </div>

        <!-- 7. Brand -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('brand_id', __('product.brand') . ':') !!}
                <select name="brand_id" id="product_list_filter_brand_id" class="form-control f17_filter select2" style="width: 100%">
                    <option value="">@lang('lang_v1.all')</option>
                </select>
            </div>
        </div>

        <!-- 8. Product -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('product_id', __('mpcs::lang.product') . ':') !!}
                <select name="product_id" id="product_list_filter_product_id" class="form-control f17_filter select2" style="width: 100%">
                    <option value="">@lang('lang_v1.all')</option>
                </select>
            </div>
        </div>

        <!-- 9. Unit -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('unit_id', __('product.unit') . ':') !!}
                <select name="unit_id" id="product_list_filter_unit_id" class="form-control f17_filter select2" style="width: 100%" disabled>
                    <option value="">@lang('lang_v1.all')</option>
                </select>
            </div>
        </div>
    </div>

    @endcomponent
    @component('components.widget', ['class' => 'box-primary'])
    @slot('tool')
    <div class="col-md-3 pull-right mb-12">
        <button type="submit" name="submit_type" id="f17_save" value="save" form="mpcs_f17_form" data-mpcs-action="f17-save" class="btn btn-primary pull-right"
            style="margin-left: 20px">@lang('mpcs::lang.save')</button>
    </div>
    @endslot
    <!-- MPCS module f17 form should be full width -->
    <div class="">
        <table class="table table-bordered table-striped" id="form_17_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('mpcs::lang.index')</th>
                    <th>@lang('mpcs::lang.product_code')</th>
                    <th>@lang('mpcs::lang.product')</th>
                    <th>@lang('mpcs::lang.current_stock')</th>
                    <th>@lang('mpcs::lang.unit_price')</th>
                    <th>@lang('mpcs::lang.select_mode')</th>
                    <th>@lang('mpcs::lang.new_price')</th>
                    <th>@lang('mpcs::lang.unit_price_difference')</th>
                    <th>@lang('mpcs::lang.price_changed_loss')</th>
                    <th>@lang('mpcs::lang.price_changed_gain')</th>
                    <th>@lang('mpcs::lang.signature')</th>
                    <th>@lang('mpcs::lang.page_no')</th>

                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

    {!! Form::close() !!}

    <div class="modal fade fuel_tank_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->
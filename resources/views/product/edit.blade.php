@extends('layouts.app')
@section('title', __('product.edit_product'))

@section('content')

@php
$is_image_required = !empty($common_settings['is_product_image_required']) && empty($product->image);

@endphp
<style>
  .select2 {
    width: 100% !important;
  }
</style>

<!-- Content Header (Page header) -->
<section class="content-header">
  <h1>@lang('product.edit_product')</h1>
  <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
  {!! Form::open(['url' => action([\App\Http\Controllers\ProductController::class, 'update'] , [$product->id] ), 'method' => 'PUT', 'id' => 'product_add_form',
  'class' => 'product_form', 'files' => true ]) !!}
  <input type="hidden" id="product_id" value="{{ $product->id }}">

  @component('components.widget', ['class' => 'box-primary'])
  <div class="row">
    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('name', __('product.product_name') . ':*') !!}
        {!! Form::text('name', $product->name, ['class' => 'form-control', 'required', 'id' => 'product_name',
        'placeholder' => __('product.product_name')]); !!}
      </div>
    </div>

    <div class="col-sm-3">
      <div class="form-group">
        @php
            $can_edit_sku = false; // S290: SKU is locked for all users once product is created
            $sku_attributes = ['class' => 'form-control', 'placeholder' => __('product.sku'), 'required' => 'required'];
            if (true) {
                $sku_attributes['readonly'] = 'readonly';
            }
        @endphp
        {!! Form::label('sku', __('product.sku') . ':*') !!} @show_tooltip(__('tooltip.sku'))
        {!! Form::text('sku', $product->sku, $sku_attributes); !!}
        @if (!$can_edit_sku)
            <small class="text-muted"><i class="fa fa-lock"></i> {{ __('lang_v1.sku_edit_not_permitted') }}</small>
        @endif
      </div>
    </div>

    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('barcode_type', __('product.barcode_type') . ':*') !!}
        {!! Form::select('barcode_type', $barcode_types, $product->barcode_type, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'required']); !!}
      </div>
    </div>

    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('semi_finished', __( 'unit.semi_finished' ) . ':*') !!}
        {!! Form::select('semi_finished', ['1' => __('messages.yes'), '0' => __('messages.no')], $product->semi_finished, ['placeholder'
        => __( 'messages.please_select' ), 'required', 'class' => 'form-control']); !!}
      </div>
    </div>

  </div>
  <div class="row">

    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('unit_id', __('product.unit') . ':*') !!}
        <div class="input-group">
          {!! Form::select('unit_id', $units, $product->unit_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2', 'required']); !!}
          <span class="input-group-btn">
            <button type="button" @if(!auth()->user()->can('unit.create')) disabled @endif class="btn btn-default bg-white btn-flat quick_add_unit btn-modal" data-href="{{action([\App\Http\Controllers\UnitController::class, 'create'], ['quick_add' => true])}}" title="@lang('unit.add_unit')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
          </span>
        </div>
      </div>
    </div>

    <div class="col-sm-3 @if(!session('business.enable_sub_units')) hide @endif">
      <div class="form-group">
        {!! Form::label('sub_unit_ids', __('lang_v1.related_sub_units') . ':') !!} @show_tooltip(__('lang_v1.sub_units_tooltip'))

        <select name="sub_unit_ids[]" class="form-control select2" multiple id="sub_unit_ids">
          @foreach($sub_units as $sub_unit_id => $sub_unit_value)
          <option value="{{$sub_unit_id}}"
            @if(is_array($product->sub_unit_ids) &&in_array($sub_unit_id, $product->sub_unit_ids)) selected
            @endif>{{$sub_unit_value['name']}}</option>
          @endforeach
        </select>
      </div>
    </div>

    @if(!empty($common_settings['enable_secondary_unit']))
    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('secondary_unit_id', __('lang_v1.secondary_unit') . ':') !!} @show_tooltip(__('lang_v1.secondary_unit_help'))
        {!! Form::select('secondary_unit_id', $units, $product->secondary_unit_id, ['class' => 'form-control select2']); !!}
      </div>
    </div>
    @endif

    <div class="col-sm-3 @if(!session('business.enable_brand')) hide @endif">
      <div class="form-group">
        {!! Form::label('brand_id', __('product.brand') . ':') !!}
        <div class="input-group">
          {!! Form::select('brand_id', $brands, $product->brand_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
          <span class="input-group-btn">
            <button type="button" @if(!auth()->user()->can('brand.create')) disabled @endif class="btn btn-default bg-white btn-flat btn-modal" data-href="{{action([\App\Http\Controllers\BrandController::class, 'create'], ['quick_add' => true])}}" title="@lang('brand.add_brand')" data-container=".view_modal"><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
          </span>
        </div>
      </div>
    </div>
    <div class="col-sm-3 @if(!session('business.enable_category')) hide @endif">
      <div class="form-group">
        {!! Form::label('category_id', __('product.category') . ':') !!}
        {!! Form::select('category_id', $categories, $product->category_id, ['placeholder' => __('messages.please_select'), 'id' => 'category_id',  'class' => 'form-control select2']); !!}
      </div>
    </div>

    <div class="col-sm-3 @if(!(session('business.enable_category') && session('business.enable_sub_category'))) hide @endif">
      <div class="form-group">
        {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
        {!! Form::select('sub_category_id', $sub_categories, $product->sub_category_id, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2']); !!}
      </div>
    </div>
  </div>
{{-- @if($is_pumper_dashboard_enabled)
  <div class="row">
        <div class="col-sm-3">
            <div class="form-group">
                <label for="show_in_pumper_dashboard">@lang('Show the product in the pumper dashboard')</label>
                <select name="show_in_pumper_dashboard" id="show_in_pumper_dashboard" class="form-control">
                    <option value="0" {{ isset($product) && !$product->show_in_pumper_dashboard ? 'selected' : '' }}>No</option>
                    <option value="1" {{ isset($product) && $product->show_in_pumper_dashboard ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
        </div>
    </div>
    @endif --}}


@if($is_pumper_dashboard_enabled)
  <div class="row">
    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('show_in_pumper_dashboard', __('Show the product in the pumper dashboard')) !!}

        @php
          $selected_pumper = old(
            'show_in_pumper_dashboard',
            isset($product)
              ? (int) $product->show_in_pumper_dashboard
              : (int) (($business_default_pumper ?? 1) ? 1 : 0)
          );
        @endphp

        {!! Form::select(
            'show_in_pumper_dashboard',
            [0 => __('lang_v1.no'), 1 => __('lang_v1.yes')],
            $selected_pumper,
            ['class' => 'form-control', 'id' => 'show_in_pumper_dashboard']
        ) !!}
      </div>
    </div>
  </div>
@endif


  <div class="row">
    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('product_locations', __('business.business_locations') . ':') !!} @show_tooltip(__('lang_v1.product_location_help'))
        {!! Form::select('product_locations[]', $business_locations, $product->product_locations->pluck('id'), ['class' => 'form-control select2', 'multiple', 'id' => 'product_locations']); !!}
      </div>
    </div>


    <div class="col-sm-3">
      <div class="form-group">
        <br>
        <label>
          {!! Form::checkbox('enable_stock', 1, $product->enable_stock, ['class' => 'input-icheck', 'id' => 'enable_stock']); !!} <strong>@lang('product.manage_stock')</strong>
        </label>@show_tooltip(__('tooltip.enable_stock')) <p class="help-block"><i>@lang('product.enable_stock_help')</i></p>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="form-group">
        <br>
        <label>
          {!! Form::checkbox('vat_claimed', 1, !empty($product) ? $product->vat_claimed : true, ['class' => 'input-icheck', 'id' => 'vat_claimed']); !!} <strong>@lang('product.vat_input_claimed')</strong>

      </div>
    </div>
    <div class="col-sm-3" id="alert_quantity_div" @if(!$product->enable_stock) style="display:none" @endif>
      <div class="form-group">
        {!! Form::label('alert_quantity', __('product.alert_quantity') . ':') !!} @show_tooltip(__('tooltip.alert_quantity'))
        {!! Form::text('alert_quantity', $alert_quantity, ['class' => 'form-control input_number',
        'placeholder' => __('product.alert_quantity') , 'min' => '0']); !!}
      </div>
    </div>
    @if(!empty($common_settings['enable_product_warranty']))
    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('warranty_id', __('lang_v1.warranty') . ':') !!}
        {!! Form::select('warranty_id', $warranties, $product->warranty_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
      </div>
    </div>
    @endif
    <!-- include module fields -->
    @if(!empty($pos_module_data))
    @foreach($pos_module_data as $key => $value)
    @if(!empty($value['view_path']))
    @includeIf($value['view_path'], ['view_data' => $value['view_data']])
    @endif
    @endforeach
    @endif
  </div>
  <div class="row">
    <div class="col-sm-3 equal-column @if($product->enable_stock == 0) hide @endif" id="raw_material_div">
      <div class="form-group">
        <br />
        {!! Form::label('stock_type', __('product.stock_type'), []) !!} {!! Form::select('stock_type', $accounts, $product->stock_type, ['class' => 'form-control select2', 'id' => 'stock_type', 'required', 'placeholder' =>
        __('product.please_select')]) !!}
      </div>
    </div>

    <div class="clearfix"></div>
    <div class="col-sm-6">
      <div class="form-group">
        {!! Form::label('product_description', __('lang_v1.product_description') . ':') !!}
        {!! Form::textarea('product_description', $product->product_description, ['class' => 'form-control']); !!}
      </div>
    </div>
    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('added_date', __('lang_v1.product_added_date') . ':*') !!}
        <div class="input-group">
          <span class="input-group-addon">
            <i class="fa fa-calendar"></i>
          </span>
          {!! Form::text('date', date('m/d/Y', strtotime($product->date)), ['class' => 'form-control required input_number', 'id' => 'product_added_date']); !!}
        </div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('image', __('lang_v1.product_image') . ':') !!}
        {!! Form::file('image', ['id' => 'upload_image', 'accept' => 'image/*', 'required' => $is_image_required]); !!}
        <small>
          <p class="help-block">@lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)]). @lang('lang_v1.aspect_ratio_should_be_1_1') @if(!empty($product->image)) <br> @lang('lang_v1.previous_image_will_be_replaced') @endif</p>
        </small>
      </div>
    </div>


  </div>
  <div class="col-sm-4">
    <div class="form-group">
      {!! Form::label('product_brochure', __('lang_v1.product_brochure') . ':') !!}
      {!! Form::file('product_brochure', ['id' => 'product_brochure', 'accept' => implode(',', array_keys(config('constants.document_upload_mimes_types')))]); !!}
      <small>
        <p class="help-block">
          @lang('lang_v1.previous_file_will_be_replaced')<br>
          @lang('purchase.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])
          @includeIf('components.document_help_text')
        </p>
      </small>
    </div>
  </div>
  @endcomponent

  @component('components.widget', ['class' => 'box-primary'])
  <div class="row">
    @if(session('business.enable_product_expiry'))

    @if(session('business.expiry_type') == 'add_expiry')
    @php
    $expiry_period = 12;
    $hide = true;
    @endphp
    @else
    @php
    $expiry_period = null;
    $hide = false;
    @endphp
    @endif
    <div class="col-sm-4 @if($hide) hide @endif">
      <div class="form-group">
        <div class="multi-input">
          @php
          $disabled = false;
          $disabled_period = false;
          if( empty($product->expiry_period_type) || empty($product->enable_stock) ){
          $disabled = true;
          }
          if( empty($product->enable_stock) ){
          $disabled_period = true;
          }
          @endphp
          {!! Form::label('expiry_period', __('product.expires_in') . ':') !!}<br>
          {!! Form::text('expiry_period', @num_format($product->expiry_period), ['class' => 'form-control pull-left input_number',
          'placeholder' => __('product.expiry_period'), 'style' => 'width:60%;', 'disabled' => $disabled]); !!}
          {!! Form::select('expiry_period_type', ['months'=>__('product.months'), 'days'=>__('product.days'), '' =>__('product.not_applicable') ], $product->expiry_period_type, ['class' => 'form-control select2 pull-left', 'style' => 'width:40%;', 'id' => 'expiry_period_type', 'disabled' => $disabled_period]); !!}
        </div>
      </div>
    </div>
    @endif
    <div class="col-sm-4">
      <div class="checkbox">
        <label>
          {!! Form::checkbox('enable_sr_no', 1, $product->enable_sr_no, ['class' => 'input-icheck']); !!} <strong>@lang('lang_v1.enable_imei_or_sr_no')</strong>
        </label>
        @show_tooltip(__('lang_v1.tooltip_sr_no'))
      </div>
    </div>

    <div class="col-sm-4">
      <div class="form-group">
        <br>
        <label>
          {!! Form::checkbox('not_for_selling', 1, $product->not_for_selling, ['class' => 'input-icheck']); !!} <strong>@lang('lang_v1.not_for_selling')</strong>
        </label> @show_tooltip(__('lang_v1.tooltip_not_for_selling'))
      </div>
    </div>
    <div class="col-sm-3">
      <div class="form-group">
        <br>
        <label>
          {!! Form::checkbox('do_not_update_purchase_price', 1, !empty($product->do_not_update_purchase_price), ['class' => 'input-icheck']); !!} <strong>@lang('lang_v1.do_not_update_purchase_price')</strong>
        </label> @show_tooltip(__('lang_v1.tooltip_do_not_update_purchase_price'))
      </div>
    </div>

    <div class="clearfix"></div>

    <!-- Rack, Row & position number -->
    @if(session('business.enable_racks') || session('business.enable_row') || session('business.enable_position'))
    <div class="col-md-12">
      <h4>@lang('lang_v1.rack_details'):
        @show_tooltip(__('lang_v1.tooltip_rack_details'))
      </h4>
    </div>
    @foreach($business_locations as $id => $location)
    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('rack_' . $id, $location . ':') !!}


        @if(!empty($rack_details[$id]))
        @if(session('business.enable_racks'))
        {!! Form::text('product_racks_update[' . $id . '][rack]', $rack_details[$id]['rack'], ['class' => 'form-control', 'id' => 'rack_' . $id]); !!}
        @endif

        @if(session('business.enable_row'))
        {!! Form::text('product_racks_update[' . $id . '][row]', $rack_details[$id]['row'], ['class' => 'form-control']); !!}
        @endif

        @if(session('business.enable_position'))
        {!! Form::text('product_racks_update[' . $id . '][position]', $rack_details[$id]['position'], ['class' => 'form-control']); !!}
        @endif
        @else
        {!! Form::text('product_racks[' . $id . '][rack]', null, ['class' => 'form-control', 'id' => 'rack_' . $id, 'placeholder' => __('lang_v1.rack')]); !!}

        {!! Form::text('product_racks[' . $id . '][row]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.row')]); !!}

        {!! Form::text('product_racks[' . $id . '][position]', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.position')]); !!}
        @endif

      </div>
    </div>
    @endforeach
    @endif


    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('weight', __('lang_v1.weight') . ':') !!}
        {!! Form::text('weight', $product->weight, ['class' => 'form-control', 'placeholder' => __('lang_v1.weight')]); !!}
      </div>
    </div>
    <div class="clearfix"></div>

    @php
    $custom_labels = json_decode(session('business.custom_labels'), true);
    $product_custom_fields = !empty($custom_labels['product']) ? $custom_labels['product'] : [];
    $product_cf_details = !empty($custom_labels['product_cf_details']) ? $custom_labels['product_cf_details'] : [];
    @endphp
    <!--custom fields-->

    @foreach($product_custom_fields as $index => $cf)
    @if(!empty($cf))
    @php
    $db_field_name = 'product_custom_field' . $loop->iteration;
    $cf_type = !empty($product_cf_details[$loop->iteration]['type']) ? $product_cf_details[$loop->iteration]['type'] : 'text';
    $dropdown = !empty($product_cf_details[$loop->iteration]['dropdown_options']) ? explode(PHP_EOL, $product_cf_details[$loop->iteration]['dropdown_options']) : [];
    @endphp

    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label($db_field_name, $cf . ':') !!}
        @if(in_array($cf_type, ['text', 'date']))
        <input type="{{$cf_type}}" name="{{$db_field_name}}" id="{{$db_field_name}}"
          value="{{$product->$db_field_name}}" class="form-control" placeholder="{{$cf}}">
        @elseif($cf_type == 'dropdown')
        {!! Form::select($db_field_name, $dropdown, $product->$db_field_name, ['placeholder' => $cf, 'class' => 'form-control select2']); !!}
        @endif
      </div>
    </div>
    @endif
    @endforeach

    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('preparation_time_in_minutes', __('lang_v1.preparation_time_in_minutes') . ':') !!}
        {!! Form::number('preparation_time_in_minutes', $product->preparation_time_in_minutes, ['class' => 'form-control', 'placeholder' => __('lang_v1.preparation_time_in_minutes')]); !!}
      </div>
    </div>
    <!--custom fields-->
    @include('layouts.partials.module_form_part')
  </div>
  @endcomponent

  @component('components.widget', ['class' => 'box-primary', 'title' => 'Price Information'])
  <div class="row">
    <div class="col-sm-4 @if(!session('business.enable_price_tax')) hide @endif">
      <div class="form-group">
        {!! Form::label('tax', __('product.applicable_tax') . ':') !!}
        {!! Form::select('tax', $taxes, $product->tax, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2'], $tax_attributes); !!}
      </div>
    </div>

    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('tax_type', 'VAT Inclusive / Exclusive:*') !!}
        {!! Form::select('tax_type',['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')], $product->tax_type,
        ['class' => 'form-control select2', 'required']); !!}
      </div>
    </div>

    <div class="col-sm-4 @if(!session('business.enable_price_tax')) hide @endif">
      <div class="form-group">
        {!! Form::label('sale_tax', __('product.sale_tax') . ':') !!}
        {!! Form::select('sale_tax', $taxes, $product->sale_tax, ['placeholder' => __('messages.please_select'), 'class' => 'form-control select2'], $tax_attributes); !!}
      </div>
    </div>

    <div class="clearfix"></div>
    <div class="col-sm-4">
      <div class="form-group">
        {!! Form::label('type', __('product.product_type') . ':*') !!} @show_tooltip(__('tooltip.product_type'))
        {!! Form::select('type', $product_types, $product->type, ['class' => 'form-control select2',
        'required','disabled', 'data-action' => 'edit', 'data-product_id' => $product->id ]); !!}
        <input type="hidden" value="{{ $product->id }}" id="$product_id">
      </div>
    </div>
    <div class="col-sm-3" id="fuel-checkbox" style="display: none;">
        <div class="form-group">
            <br>
            <input type="checkbox" id="auto_calculate_purchase_cost" class="input-icheck" />
            <strong>@lang('product.auto_calculate_purchase_cost')</strong>
        </div>
    </div>
    <input type="hidden" value="{{ $tax_rate->amount ?? null }}" id="tax_rate">

    
  <div class="modal fade" id="purchaseCostModal" tabindex="-1" role="document" aria-labelledby="purchaseCostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
      <div class="modal-content pad border border-primary">
        <h5 class="modal-title mar-bottom">Product: <strong><span id="modal_product_name">xxxxxxxx</span></strong></h5>

        <table class="table align-middle text-center">
          <thead>
            <tr>
              <th>Qty</th>
              <th>{{ __('product.unit_sale_price') }}</th>
              <th>{{ __('product.amount') }}</th>
              <th>{{ __('product.discount_mount') }}</th>
              <th>{{ __('product.evaporation_amount') }}</th>
              <th>{{ __('product.unit_price_with_tax') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><input type="number" id="modal_qty" class="form-control"></td>
              <td><input type="number" id="modal_unit_sale_price" class="form-control"></td>
              <td><input type="number" id="modal_amount" class="form-control" readonly></td>
              <td><input type="number" id="modal_discount" class="form-control"></td>
              <td><input type="number" id="modal_evaporation" class="form-control"></td>
              <td><input type="number" id="modal_price_with_tax" class="form-control" readonly></td>
            </tr>
          </tbody>
        </table>

        <div class="mt-3 row align-right">
          <button type="button" id="modal_save" class="btn btn-primary">Save</button>
          <button type="button" id="modal_close" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </div>
    </div>
  </div>

    <div class="form-group col-sm-12" id="product_form_part"></div>
    <input type="hidden" id="variation_counter" value="0">
    <input type="hidden" id="default_profit_percent" value="{{ $default_profit_percent }}">
  </div>
  @endcomponent

  <div class="row">
    <input type="hidden" name="submit_type" id="submit_type">
    <div class="col-sm-12">
      <div class="text-center">
        <div class="btn-group">
          @if($selling_price_group_count)
          <button type="submit" value="submit_n_add_selling_prices" class="btn btn-warning btn-big submit_product_form">@lang('lang_v1.save_n_add_selling_price_group_prices')</button>
          @endif

          @can('product.opening_stock')
          <button type="submit" @if(empty($product->enable_stock)) disabled="true" @endif id="opening_stock_button" value="update_n_edit_opening_stock" class="btn bg-purple submit_product_form btn-big">@lang('lang_v1.update_n_edit_opening_stock')</button>
          @endif

          <button type="submit" value="save_n_add_another" class="btn bg-maroon submit_product_form btn-big">@lang('lang_v1.update_n_add_another')</button>

          <button type="submit" value="submit" class="btn btn-primary submit_product_form btn-big">@lang('messages.update')</button>
        </div>
      </div>
    </div>
  </div>
  {!! Form::close() !!}
</section>
<!-- /.content -->

@endsection

<style>
  .pad{
    padding: 10px;
  }
  .mar-bottom{
    margin: 5px;
  }
  .align-right{
    padding-right: 15px;
    text-align: right;
  }
</style>

@section('javascript')
<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
  $(document).ready(function() {
    $("#product_added_date").datepicker({
      format: "mm/dd/yyyy",
    });

    __page_leave_confirmation('#product_add_form');
  });

  $(document).ready(function() {
    $('#auto_calculate_purchase_cost').on('ifChanged',function() {
      if ($(this).is(':checked')) {
        // Charger le nom du produit automatiquement ici
        $('#modal_product_name').text($('#product_name').val() ?? 'xxxxxxxx');
        $('#purchaseCostModal').modal('show');
      }
    });

    $('#modal_close').click(function(){
      $('#purchaseCostModal').modal('hide');
    });

    function recalculateAmountAndPrice() {
      const qty = parseFloat($('#modal_qty').val()) || 0;
      const unit_price = parseFloat($('#modal_unit_sale_price').val()) || 0;
      const discount = parseFloat($('#modal_discount').val()) || 0;
      const evaporation = parseFloat($('#modal_evaporation').val()) || 0;

      const amount = qty * unit_price;
      $('#modal_amount').val((amount).toFixed(2));

      const unit_price_with_tax = (amount - discount - evaporation) / (qty || 1);
      $('#modal_price_with_tax').val(unit_price_with_tax);

      originalPriceWithTax = unit_price_with_tax
    }

    $('#modal_qty, #modal_unit_sale_price, #modal_discount, #modal_evaporation').on('input', recalculateAmountAndPrice);
    let currencyPrecision = {{ session('business.currency_precision', 2) }};
    originalPriceWithTax = 0; 

    $('#modal_save').click(function() {
      const unitPriceWithTax = parseFloat($('#modal_price_with_tax').val()) || 0;
      const unitSalePriceWithTax = parseFloat($('#modal_unit_sale_price').val()) || 0;
      const tax_rate = parseFloat($('#tax_rate').val()) || 0;

      // Calcul du prix HT (hors taxe)
      const unitPrice = originalPriceWithTax / (1 + tax_rate / 100);
      const unitSalePrice = unitSalePriceWithTax / (1 + tax_rate / 100);

      const marginPercent = ((unitSalePrice - unitPrice) / unitPrice) * 100;

      if (!unitPriceWithTax || originalPriceWithTax <= 0) {
        toastr.warning('Please fill in the informations');
        return;
      }

      $.ajax({
        url: '/products/update-variation',
        method: 'POST',
        data: {
            product_id: $('#product_id').val(),
            default_sell_price: unitSalePrice,
            sell_price_with_tax: unitSalePriceWithTax,
            dpp_inc_tax: originalPriceWithTax,
            dpp: unitPrice,
            profit_percent: marginPercent,
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.msg);

                $('.dpp_inc_tax').val(originalPriceWithTax).prop('readonly', true);
                $('.dpp').val(unitPrice).prop('readonly', true);

                $('#single_dsp_inc_tax').val(unitSalePriceWithTax).prop('readonly', true);
                $('#single_dsp').val(unitSalePrice).prop('readonly', true);
                $('#profit_percent').val(marginPercent).prop('readonly', true);
                // Fermer la modal
                $('#purchaseCostModal').modal('hide');
            } else {
                toastr.error('Erreur lors de la mise à jour du produit');
            }
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            toastr.error('Une erreur est survenue.');
        }
      });
    });
  });

  // Ensure disabled selects still submit their current value to preserve selections
  $(document).ready(function() {
    $('.product_form').on('submit', function() {
      var form = this;
      var fields = ['category_id','sub_category_id','brand_id','unit_id'];
      fields.forEach(function(name) {
        var $el = $('[name="' + name + '"]');
        if ($el.length && $el.is(':disabled')) {
          $('<input>').attr({ type: 'hidden', name: name, value: $el.val() }).appendTo(form);
        }
      });
    });
  });

  $(document).ready(function() {
    function toggleFuelCheckbox() {
      const selectedText = $('#category_id option:selected').text().trim().toLowerCase();
      if (selectedText === 'fuel') {
        $('#fuel-checkbox').show();
        $('.dpp_inc_tax').prop('readonly', true);
        $('.dpp').prop('readonly', true);

        $('#single_dsp_inc_tax').prop('readonly', true);
        $('#single_dsp').prop('readonly', true);
      } else {
        $('#fuel-checkbox').hide();
        $('#auto_calculate_purchase_cost').prop('checked', false); 

        $('.dpp_inc_tax').prop('readonly', false);
        $('.dpp').prop('readonly', false);

        $('#single_dsp_inc_tax').prop('readonly', false);
        $('#single_dsp').prop('readonly', false);
      }
    }

    // Exécution au chargement
    toggleFuelCheckbox();

    // Exécution lors d’un changement
    $('#category_id').on('change', toggleFuelCheckbox);
  });
</script>
@endsection
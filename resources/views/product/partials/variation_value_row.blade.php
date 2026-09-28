@php
    $variation_name = !empty($variation_name) ? $variation_name : null;
    $variation_value_id = !empty($variation_value_id) ? $variation_value_id : null;

    $name = (empty($row_type) || $row_type == 'add') ? 'product_variation' : 'product_variation_edit';

    $readonly = !empty($variation_value_id) ? 'readonly' : '';
    $can_edit_sku = false; // S290: SKU/sub-SKU locked for all users after creation
    $sub_sku_attributes = ['class' => 'form-control input-sm input_sub_sku'];

    if ($row_type == 'edit' && !$can_edit_sku) {
        $sub_sku_attributes['readonly'] = 'readonly';
    }
@endphp

@php
    // Price information must always be visible. Tax controls may be hidden by business settings,
    // but purchase price, profit percent and selling price are mandatory for saving products.
    $default = '';
    $class = '';
@endphp

@php
    $is_variation_value_hidden = !empty($variation_value_id) ? 1 : 0;
@endphp

<tr @if(!empty($variation_value_id)) 
        data-variation_value_id="{{$variation_value_id}}" 
        class="variation_value_row hide" 
    @endif>
    <td>
        {!! Form::text($name . '[' . $variation_index . '][variations][' . $value_index . '][sub_sku]', null, $sub_sku_attributes); !!}
        {!! Form::hidden($name . '[' . $variation_index . '][variations][' . $value_index . '][is_hidden]', 
            $is_variation_value_hidden , ['class' => 'is_variation_value_hidden']) !!}

        {!! Form::hidden($name . '[' . $variation_index . '][variations][' . $value_index . '][variation_value_id]', $variation_value_id) !!}
    </td>
    <td>
        {!! Form::text($name . '[' . $variation_index . '][variations][' . $value_index . '][value]', $variation_name, ['class' => 'form-control input-sm variation_value_name', 'required', $readonly]); !!}
    </td>
    <td class="{{$class}}">
        <div class="width-50 f-left">
            {!! Form::text($name . '[' . $variation_index . '][variations][' . $value_index . '][default_purchase_price]', $default, ['class' => 'form-control input-sm variable_dpp input_number', 'placeholder' => __('product.exc_of_tax'), 'required']); !!}
        </div>

        <div class="width-50 f-left">
            <div class="input-group">
                {!! Form::text($name . '[' . $variation_index . '][variations][' . $value_index . '][dpp_inc_tax]', $default, ['class' => 'form-control input-sm variable_dpp_inc_tax input_number', 'placeholder' => __('product.inc_of_tax'), 'required']); !!}
                @if($value_index == 0)
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-default bg-white btn-flat apply-all btn-sm p-5-5" data-toggle="tooltip" title="@lang('lang_v1.apply_all')" data-target-class=".variable_dpp_inc_tax"><i class="fas fa-check-double"></i></button>
                    </span>
                @endif
            </div>
        </div>
    </td>
    <td class="{{$class}}">
        <div class="input-group">
            {!! Form::text($name . '[' . $variation_index . '][variations][' . $value_index . '][profit_percent]', '', ['class' => 'form-control input-sm variable_profit_percent input_number']); !!}
            @if($value_index == 0)
                <span class="input-group-btn">
                    <button type="button" class="btn btn-default bg-white btn-flat apply-all btn-sm p-5-5" data-toggle="tooltip" title="@lang('lang_v1.apply_all')" data-target-class=".variable_profit_percent"><i class="fas fa-check-double"></i></button>
                </span>
            @endif
        </div>
    </td>
    <td class="{{$class}}">
        {!! Form::text($name . '[' . $variation_index . '][variations][' . $value_index . '][default_sell_price]', $default, ['class' => 'form-control input-sm variable_dsp input_number', 'placeholder' => __('product.exc_of_tax'), 'required']); !!}

        {!! Form::text($name . '[' . $variation_index . '][variations][' . $value_index . '][sell_price_inc_tax]', $default, ['class' => 'form-control input-sm variable_dsp_inc_tax input_number', 'placeholder' => __('product.inc_of_tax'), 'required']); !!}
    </td>
    <td>{!! Form::file('variation_images_' . $variation_index . '_' . $value_index . '[]', ['class' => 
        'variation_images', 'accept' => 'image/*', 'multiple']); !!}</td>
    <td>
        <button type="button" class="btn btn-danger btn-xs remove_variation_value_row">-</button>
        <input type="hidden" class="variation_row_index" value="{{$value_index}}">
    </td>
</tr>

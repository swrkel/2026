<div class="tab-pane" id="product_custom_fields_tab">
    <div class="row">
        @for($i = 1; $i <= 4; $i++)
            <div class="col-md-3"><div class="form-group">{!! Form::label('product_custom_field' . $i, __('product::product.custom_field') . ' ' . $i . ':') !!}{!! Form::text('product_custom_field' . $i, null, ['class' => 'form-control']) !!}</div></div>
        @endfor
    </div>
</div>

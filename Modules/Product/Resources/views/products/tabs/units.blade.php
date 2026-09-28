<div class="tab-pane" id="product_units_tab">
    <div class="row">
        <div class="col-md-4"><div class="form-group">{!! Form::label('unit_id', __('product::units.unit') . ':*') !!}{!! Form::select('unit_id', $units ?? [], null, ['class' => 'form-control select2', 'required', 'placeholder' => __('product::common.please_select')]) !!}</div></div>
        <div class="col-md-4"><div class="form-group">{!! Form::label('sub_unit_ids', __('product::units.sub_units') . ':') !!}{!! Form::select('sub_unit_ids[]', $units ?? [], null, ['class' => 'form-control select2', 'multiple']) !!}</div></div>
    </div>
</div>

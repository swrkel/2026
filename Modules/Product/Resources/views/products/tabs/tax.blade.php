<div class="tab-pane" id="product_tax_tab">
    <div class="row">
        <div class="col-md-4"><div class="form-group">{!! Form::label('tax', __('product::tax.tax') . ':') !!}{!! Form::select('tax', $taxes ?? [], null, ['class' => 'form-control select2', 'placeholder' => __('product::common.please_select')]) !!}</div></div>
        <div class="col-md-4"><div class="form-group">{!! Form::label('tax_type', __('product::tax.tax_type') . ':') !!}{!! Form::select('tax_type', ['exclusive' => __('product::tax.exclusive'), 'inclusive' => __('product::tax.inclusive')], null, ['class' => 'form-control']) !!}</div></div>
    </div>
</div>

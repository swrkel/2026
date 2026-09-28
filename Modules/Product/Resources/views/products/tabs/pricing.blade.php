<div class="tab-pane" id="product_pricing_tab">
    <div class="row">
        <div class="col-md-4"><div class="form-group">{!! Form::label('default_purchase_price', __('product::pricing.purchase_price') . ':') !!}{!! Form::text('default_purchase_price', null, ['class' => 'form-control input_number']) !!}</div></div>
        <div class="col-md-4"><div class="form-group">{!! Form::label('profit_percent', __('product::pricing.profit_percent') . ':') !!}{!! Form::text('profit_percent', null, ['class' => 'form-control input_number']) !!}</div></div>
        <div class="col-md-4"><div class="form-group">{!! Form::label('sell_price_inc_tax', __('product::pricing.selling_price') . ':') !!}{!! Form::text('sell_price_inc_tax', null, ['class' => 'form-control input_number']) !!}</div></div>
    </div>
</div>

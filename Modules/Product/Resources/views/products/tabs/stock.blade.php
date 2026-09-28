<div class="tab-pane" id="product_stock_tab">
    <div class="row">
        <div class="col-md-3"><label>{!! Form::checkbox('enable_stock', 1, null, ['id' => 'enable_stock']) !!} @lang('product::product.enable_stock')</label></div>
        <div class="col-md-3"><div class="form-group">{!! Form::label('alert_quantity', __('product::stock.alert_quantity') . ':') !!}{!! Form::text('alert_quantity', null, ['class' => 'form-control input_number']) !!}</div></div>
        <div class="col-md-3"><div class="form-group">{!! Form::label('expiry_period', __('product::stock.expiry_period') . ':') !!}{!! Form::text('expiry_period', null, ['class' => 'form-control input_number']) !!}</div></div>
        <div class="col-md-3"><div class="form-group">{!! Form::label('expiry_period_type', __('product::stock.expiry_period_type') . ':') !!}{!! Form::select('expiry_period_type', ['days' => __('product::stock.days'), 'months' => __('product::stock.months')], null, ['class' => 'form-control']) !!}</div></div>
    </div>
</div>

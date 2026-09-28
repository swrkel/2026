<div class="row">
  <div class="col-md-4"><div class="form-group">{!! Form::label('name', __('product::common.name').':') !!}{!! Form::text('name', null, ['class'=>'form-control','required']) !!}</div></div>
  <div class="col-md-4"><div class="form-group">{!! Form::label('sku', __('product::product.sku').':') !!}{!! Form::text('sku', null, ['class'=>'form-control']) !!}</div></div>
  <div class="col-md-4"><div class="form-group">{!! Form::label('type', __('product::product.type').':') !!}{!! Form::select('type', ['single'=>'Single','variable'=>'Variable'], null, ['class'=>'form-control select2','style'=>'width:100%']) !!}</div></div>
</div>
<div class="row">
  <div class="col-md-3"><label>{!! Form::checkbox('enable_stock', 1, null) !!} @lang('product::product.enable_stock')</label></div>
  <div class="col-md-3"><label>{!! Form::checkbox('not_for_selling', 1, null) !!} @lang('product::product.not_for_selling')</label></div>
  <div class="col-md-3"><label>{!! Form::checkbox('is_inactive', 1, null) !!} @lang('product::product.inactive')</label></div>
</div>

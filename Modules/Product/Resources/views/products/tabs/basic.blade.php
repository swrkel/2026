<div class="tab-pane active" id="product_basic_tab">
    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('name', __('product::product.product_name') . ':*') !!}
                {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('product::product.product_name')]) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('sku', __('product::product.sku') . ':') !!}
                {!! Form::text('sku', null, ['class' => 'form-control', 'placeholder' => __('product::product.auto_generate_if_empty')]) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('product_type', __('product::product.product_type') . ':*') !!}
                {!! Form::select('product_type', ['single' => __('product::product.single'), 'variable' => __('product::product.variable')], null, ['class' => 'form-control', 'required', 'id' => 'product_type']) !!}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('category_id', __('product::categories.category') . ':') !!}
                {!! Form::select('category_id', $categories ?? [], null, ['class' => 'form-control select2', 'placeholder' => __('product::common.please_select')]) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('brand_id', __('product::brands.brand') . ':') !!}
                {!! Form::select('brand_id', $brands ?? [], null, ['class' => 'form-control select2', 'placeholder' => __('product::common.please_select')]) !!}
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('description', __('product::product.description') . ':') !!}
                {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 2]) !!}
            </div>
        </div>
    </div>
</div>

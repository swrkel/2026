 
 

@component('components.filters', ['title' => __('report.filters'), 'id' => 'stock_report_filters'])
    <div class="row">
        <div class="col-md-3">
    <div class="form-group">
        {!! Form::label('date_range', __('report.date_range') . ':') !!}
        <div class="input-group">
            <span class="input-group-addon">
                <i class="fa fa-calendar"></i>
            </span>
            <input type="text" id="sa_date_range" class="form-control" readonly
                   value="{{ now()->startOfMonth()->format(session('business.date_format', 'm/d/Y')) }} - {{ now()->endOfMonth()->format(session('business.date_format', 'm/d/Y')) }}">
        </div>
    </div>
</div>
        
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('location_id',  __('purchase.business_location') . ':*') !!}
                {!! Form::select('location_id', $business_locations, null, [
                    'class' => 'form-control select2 location-filter',
                    'style' => 'width:100%',
                    'required',
                    'placeholder' => __('lang_v1.please_select')
                ]) !!}
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('product_type', __('product.product_type') . ':') !!}
                {!! Form::select('product_type', 
                    [
                        'single' => __('lang_v1.single'), 
                        'variable' => __('lang_v1.variable'), 
                        'combo' => __('lang_v1.combo')
                    ], 
                    null, [
                        'class' => 'form-control select2 product-type-filter',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all')
                    ]) 
                !!}
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('category_id', __('product.category') . ':') !!}
                {!! Form::select('category_id', $categories, null, [
                    'class' => 'form-control select2 category-filter',
                    'style' => 'width:100%',
                    'placeholder' => __('lang_v1.all'),
                    'data-sub-category-target' => '#sub_category_id'
                ]) !!}
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sub_category_id', __('product.sub_category') . ':') !!}
                {!! Form::select('sub_category_id', $sub_categories, null, [
                    'class' => 'form-control select2 sub-category-filter',
                    'style' => 'width:100%',
                    'placeholder' => __('lang_v1.all'),
                    'disabled' => empty($sub_categories)
                ]) !!}
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('brand_id', __('product.brand') . ':') !!}
                {!! Form::select('brand_id', $brands, null, [
                    'class' => 'form-control select2 brand-filter',
                    'style' => 'width:100%',
                    'placeholder' => __('lang_v1.all')
                ]) !!}
            </div>
        </div>
           <div class="col-md-3">
            <div class="form-group">
               {!! Form::label('store_id', __('lang_v1.store_id') . ':') !!}
                {!! Form::select('store_id', $stores, null, ['class' => 'form-control select2 store_id', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('product_id', __('lang_v1.products') . ':') !!}
                {!! Form::select('product_id', $products, null, [
                    'class' => 'form-control select2 product-filter',
                    'style' => 'width:100%',
                    'placeholder' => __('lang_v1.all')
                    
                ]) !!}
            </div>
        </div>
         <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('unit_id', __('product.unit') . ':') !!}
                {!! Form::select('unit_id', $units, null, ['class' => 'form-control select2 unit_id', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
     
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sku', __('product.sku') . ':') !!}
                {!! Form::text('sku', null, [
                    'class' => 'form-control sku-filter',
                    'placeholder' => __('Sku')
                ]) !!}
            </div>
        </div>
        
         
        
         
    </div>
@endcomponent

@component('components.widget', ['class' => 'box-primary', 'title' => __('All Stock Reports')])
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dis_stock_transfer_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Date</th>
                    <th>Product</th>
                    {{-- Kept as an internal searchable DataTables field only.
                         It is never displayed, exported or exposed in Column Visibility. --}}
                    <th class="noVis stock-internal-sku-column">SKU</th>
                    <th>Store</th>
                    <th>Description</th>
                    <th class="stock-starting-qty-col">
                        <span class="stock-heading-lines"><span>Starting</span><span>Qty</span></span>
                    </th>
                    <th class="stock-purchase-qty-col">
                        <span class="stock-heading-lines"><span>Purchase Qty</span><span>Bonus Qty</span></span>
                    </th>
                    <th class="stock-purchase-return-col">
                        <span class="stock-heading-lines"><span>Purchase</span><span>Return</span><span>Qty</span></span>
                    </th>
                    <th class="stock-adjustment-return-col">
                        <span class="stock-heading-lines"><span>Stock Adjustment</span><span>Return</span><span>Qty</span></span>
                    </th>
                    <th>Sold Qty </th>
                    <th>Sales Return Qty </th>                   
                    <th>Balance </th>
                </tr>
            </thead>
        </table>
            </div>
        </div>
    </div>
@endcomponent
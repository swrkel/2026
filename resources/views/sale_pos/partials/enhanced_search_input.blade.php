<div class="enhanced-search-container">
    <div class="input-group">
        <div class="input-group-btn">
            <button type="button" class="btn btn-default bg-white btn-flat" data-toggle="modal"
                data-target="#configure_search_modal"
                title="{{ __('lang_v1.configure_product_search') }}">
                <i class="fa fa-barcode"></i>
            </button>
        </div>
        
        <div class="search-input-wrapper">
            {!! Form::text('search_product', null, [
                'class' => 'form-control mousetrap enhanced-search-input',
                'id' => 'search_product',
                'placeholder' => __('lang_v1.search_product_placeholder'),
                'disabled' => is_null($default_location ?? null) ? true : false,
                'autofocus' => is_null($default_location ?? null) ? false : true,
            ]) !!}
        </div>
        
        <span class="input-group-btn">
            <button type="button" class="btn btn-default bg-white btn-flat pos_add_quick_product"
                data-href="{{ action('ProductController@quickAdd') }}"
                data-container=".quick_add_product_modal">
                <i class="fa fa-plus-circle text-primary fa-lg"></i>
            </button>
        </span>
    </div>
</div>

<style>
.enhanced-search-container {
    position: relative;
}

.search-input-wrapper {
    position: relative;
    display: flex;
    flex: 1;
}

.enhanced-search-input {
    border-radius: 0;
    flex: 1;
}

.enhanced-search-container .input-group-btn:first-child .btn {
    border-radius: 4px 0 0 4px;
    border-right: none;
}

.enhanced-search-container .input-group-btn:last-child .btn {
    border-radius: 0 4px 4px 0;
}

</style>
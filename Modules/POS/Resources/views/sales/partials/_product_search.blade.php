<div class="box box-primary pos-panel">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('pos::page_003.product_search') }}</h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-5">
                <input type="text" id="pos_product_search" class="form-control" placeholder="{{ __('pos::page_003.scan_or_search_product') }}" autocomplete="off">
            </div>
            <div class="col-md-3">
                <select id="pos_category_filter" class="form-control pos-typeahead">
                    <option value="">{{ __('pos::page_003.all_categories') }}</option>
                    @foreach($product_filters['categories'] ?? [] as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select id="pos_brand_filter" class="form-control pos-typeahead">
                    <option value="">{{ __('pos::page_003.all_brands') }}</option>
                    @foreach($product_filters['brands'] ?? [] as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" id="pos_price_check_btn" class="btn btn-primary btn-block"><i class="fa fa-search"></i></button>
            </div>
        </div>
    </div>
</div>

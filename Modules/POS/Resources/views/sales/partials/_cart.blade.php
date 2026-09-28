<div class="box box-success pos-panel">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-shopping-cart"></i> {{ __('pos::page_003.cart') }}</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-xs btn-warning pos-cart-hold"><i class="fa fa-pause"></i> {{ __('pos::page_003.hold') }}</button>
            <button type="button" class="btn btn-xs btn-danger pos-cart-clear"><i class="fa fa-trash"></i> {{ __('pos::page_003.clear') }}</button>
        </div>
    </div>
    <div class="box-body no-padding">
        <div class="table-responsive">
            <table class="table table-condensed table-striped pos-cart-table">
                <thead>
                    <tr>
                        <th>{{ __('pos::page_003.product') }}</th>
                        <th class="text-right">{{ __('pos::page_003.qty') }}</th>
                        <th class="text-right">{{ __('pos::page_003.price') }}</th>
                        <th class="text-right">{{ __('pos::page_003.total') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="pos_cart_lines">
                    @forelse($cart['lines'] ?? [] as $line)
                        <tr data-line-id="{{ $line->id }}"><td>{{ $line->product_id }}</td><td class="text-right">{{ number_format($line->quantity, 4) }}</td><td class="text-right">{{ number_format($line->unit_price, 4) }}</td><td class="text-right">{{ number_format($line->line_total, 4) }}</td><td><button class="btn btn-xs btn-danger pos-remove-line"><i class="fa fa-times"></i></button></td></tr>
                    @empty
                        <tr class="pos-empty-cart"><td colspan="5" class="text-center">{{ __('pos::page_003.cart_is_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pos-note-box">
            <label>{{ __('pos::page_003.note') }}</label>
            <textarea id="pos_sale_note" class="form-control" rows="2" placeholder="{{ __('pos::page_003.enter_note') }}"></textarea>
        </div>
    </div>
</div>

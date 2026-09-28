<div class="col-md-12 rn-repeat-card">
    <h4>@lang('restaurantnew::lang.variants')</h4>
    @php($variants = old('variants', $row->variants ?? collect()))
    @for($i = 0; $i < max(3, count($variants)); $i++)
        @php($variant = $variants[$i] ?? null)
        <div class="row rn-repeat-row">
            <div class="form-group col-md-3"><input name="variants[{{ $i }}][name]" class="form-control" placeholder="@lang('restaurantnew::lang.variant_name')" value="{{ data_get($variant,'name') }}"></div>
            <div class="form-group col-md-2"><input name="variants[{{ $i }}][sku]" class="form-control" placeholder="@lang('restaurantnew::lang.sku')" value="{{ data_get($variant,'sku') }}"></div>
            <div class="form-group col-md-2"><input type="number" step="0.0001" name="variants[{{ $i }}][price]" class="form-control" placeholder="@lang('restaurantnew::lang.price')" value="{{ data_get($variant,'price') }}"></div>
            <div class="form-group col-md-2"><input type="number" step="0.0001" name="variants[{{ $i }}][cost_price]" class="form-control" placeholder="@lang('restaurantnew::lang.cost_price')" value="{{ data_get($variant,'cost_price') }}"></div>
            <div class="form-group col-md-3 rn-checkbox-line"><label><input type="checkbox" name="variants[{{ $i }}][is_default]" value="1" {{ data_get($variant,'is_default') ? 'checked' : '' }}> @lang('restaurantnew::lang.default')</label><label><input type="checkbox" name="variants[{{ $i }}][is_active]" value="1" {{ data_get($variant,'is_active', true) ? 'checked' : '' }}> @lang('restaurantnew::lang.active')</label></div>
        </div>
    @endfor
</div>

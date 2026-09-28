<div class="col-md-12 rn-repeat-card">
    <h4>@lang('restaurantnew::lang.modifiers_addons')</h4>
    @php($modifiers = old('modifiers', $row->modifiers ?? collect()))
    @for($i = 0; $i < max(5, count($modifiers)); $i++)
        @php($modifier = $modifiers[$i] ?? null)
        <div class="row rn-repeat-row">
            <div class="form-group col-md-4"><input name="modifiers[{{ $i }}][name]" class="form-control" placeholder="@lang('restaurantnew::lang.modifier_name')" value="{{ data_get($modifier,'name') }}"></div>
            <div class="form-group col-md-3"><input name="modifiers[{{ $i }}][group_name]" class="form-control" placeholder="@lang('restaurantnew::lang.group_name')" value="{{ data_get($modifier,'group_name') }}"></div>
            <div class="form-group col-md-2"><input type="number" step="0.0001" name="modifiers[{{ $i }}][price]" class="form-control" placeholder="@lang('restaurantnew::lang.price')" value="{{ data_get($modifier,'price') }}"></div>
            <div class="form-group col-md-3 rn-checkbox-line"><label><input type="checkbox" name="modifiers[{{ $i }}][is_active]" value="1" {{ data_get($modifier,'is_active', true) ? 'checked' : '' }}> @lang('restaurantnew::lang.active')</label></div>
        </div>
    @endfor
</div>

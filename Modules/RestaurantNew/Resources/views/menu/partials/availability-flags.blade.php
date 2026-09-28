<div class="form-group col-md-12 rn-checkbox-line">
    <label><input type="checkbox" name="is_active" value="1" {{ old('is_active', $row->is_active ?? true) ? 'checked' : '' }}> @lang('restaurantnew::lang.active')</label>
    <label><input type="checkbox" name="available_for_dine_in" value="1" {{ old('available_for_dine_in', $row->available_for_dine_in ?? true) ? 'checked' : '' }}> @lang('restaurantnew::lang.dine_in')</label>
    <label><input type="checkbox" name="available_for_takeaway" value="1" {{ old('available_for_takeaway', $row->available_for_takeaway ?? true) ? 'checked' : '' }}> @lang('restaurantnew::lang.takeaway')</label>
    <label><input type="checkbox" name="available_for_delivery" value="1" {{ old('available_for_delivery', $row->available_for_delivery ?? true) ? 'checked' : '' }}> @lang('restaurantnew::lang.delivery')</label>
</div>

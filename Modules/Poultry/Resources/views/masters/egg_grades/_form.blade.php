<div class="row">
    <div class="col-md-6 form-group">
        <label>@lang('poultry::lang.name') *</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $row->name ?? '') }}">
    </div>
    <div class="col-md-3 form-group">
        <label>@lang('poultry::lang.code')</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $row->code ?? '') }}">
    </div>
    <div class="col-md-3 form-group">
        <label>@lang('poultry::lang.sort_order')</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $row->sort_order ?? 0) }}">
    </div>
</div>
<div class="row">
    <div class="col-md-6 form-group">
        <label>@lang('poultry::lang.min_weight_g')</label>
        <input type="number" step="0.01" name="min_weight_g" class="form-control"
               value="{{ old('min_weight_g', $row->min_weight_g ?? '') }}">
    </div>
    <div class="col-md-6 form-group">
        <label>@lang('poultry::lang.max_weight_g')</label>
        <input type="number" step="0.01" name="max_weight_g" class="form-control"
               value="{{ old('max_weight_g', $row->max_weight_g ?? '') }}">
    </div>
</div>
<div class="form-group">
    <label>@lang('poultry::lang.sell_as')</label>
    <select name="variation_id" class="form-control">
        <option value="">@lang('poultry::lang.not_stocked')</option>
        @foreach ($items as $id => $label)
            <option value="{{ $id }}" {{ old('variation_id', $row->variation_id ?? '') == $id ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <span class="help-block">@lang('poultry::lang.sell_as_help')</span>
</div>
<div class="checkbox">
    <label><input type="checkbox" name="is_saleable" value="1"
        {{ old('is_saleable', $row->is_saleable ?? true) ? 'checked' : '' }}> @lang('poultry::lang.saleable')</label>
</div>

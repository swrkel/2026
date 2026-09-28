<div class="row">
    <div class="col-md-6 form-group">
        <label>@lang('poultry::lang.farm') *</label>
        <select name="farm_id" class="form-control" required>
            @foreach ($farms as $id => $name)
                <option value="{{ $id }}" {{ old('farm_id', $row->farm_id ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 form-group">
        <label>@lang('poultry::lang.name') *</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $row->name ?? '') }}">
    </div>
</div>
<div class="row">
    <div class="col-md-4 form-group">
        <label>@lang('poultry::lang.code')</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $row->code ?? '') }}">
    </div>
    <div class="col-md-4 form-group">
        <label>@lang('poultry::lang.housing_type') *</label>
        <select name="housing_type" class="form-control" required>
            @foreach ($types as $key => $label)
                <option value="{{ $key }}" {{ old('housing_type', $row->housing_type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 form-group">
        <label>@lang('poultry::lang.capacity') *</label>
        <input type="number" name="capacity" class="form-control" min="0" required
               value="{{ old('capacity', $row->capacity ?? 0) }}">
    </div>
</div>
<div class="form-group">
    <label>@lang('poultry::lang.floor_area_sqm')</label>
    <input type="number" step="0.01" name="floor_area_sqm" class="form-control"
           value="{{ old('floor_area_sqm', $row->floor_area_sqm ?? '') }}">
    <span class="help-block">@lang('poultry::lang.floor_area_help')</span>
</div>
<div class="checkbox">
    <label><input type="checkbox" name="is_active" value="1"
        {{ old('is_active', $row->is_active ?? true) ? 'checked' : '' }}> @lang('poultry::lang.active')</label>
</div>

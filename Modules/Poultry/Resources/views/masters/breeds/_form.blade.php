<div class="row">
    <div class="col-md-6 form-group">
        <label>@lang('poultry::lang.name') *</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $row->name ?? '') }}">
    </div>
    <div class="col-md-6 form-group">
        <label>@lang('poultry::lang.bird_type') *</label>
        <select name="bird_type" class="form-control" required>
            @foreach ($types as $key => $label)
                <option value="{{ $key }}" {{ old('bird_type', $row->bird_type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="form-group">
    <label>@lang('poultry::lang.standard_curve')</label>
    <textarea name="standard_curve" class="form-control" rows="6"
              placeholder='{"weights":{"7":180,"14":450,"35":2000},"hen_day":{"20":45,"30":94},"target_fcr":1.55}'>{{ old('standard_curve', $row->standard_curve ?? '') }}</textarea>
    <span class="help-block">@lang('poultry::lang.standard_curve_help')</span>
</div>
<div class="checkbox">
    <label><input type="checkbox" name="is_active" value="1"
        {{ old('is_active', $row->is_active ?? true) ? 'checked' : '' }}> @lang('poultry::lang.active')</label>
</div>

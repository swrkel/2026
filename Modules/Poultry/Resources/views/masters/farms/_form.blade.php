<div class="row">
    <div class="col-md-6 form-group">
        <label>@lang('poultry::lang.name') *</label>
        <input type="text" name="name" class="form-control" required
               value="{{ old('name', $row->name ?? '') }}">
    </div>
    <div class="col-md-6 form-group">
        <label>@lang('poultry::lang.code')</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $row->code ?? '') }}">
    </div>
</div>
<div class="form-group">
    <label>@lang('poultry::lang.business_location')</label>
    <select name="location_id" class="form-control">
        <option value="">--</option>
        @foreach ($locations as $id => $name)
            <option value="{{ $id }}" {{ old('location_id', $row->location_id ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
    <span class="help-block">@lang('poultry::lang.farm_location_help')</span>
</div>
<div class="row">
    <div class="col-md-8 form-group">
        <label>@lang('poultry::lang.address')</label>
        <textarea name="address" class="form-control" rows="2">{{ old('address', $row->address ?? '') }}</textarea>
    </div>
    <div class="col-md-4 form-group">
        <label>@lang('poultry::lang.city')</label>
        <input type="text" name="city" class="form-control" value="{{ old('city', $row->city ?? '') }}">
    </div>
</div>
<div class="checkbox">
    <label><input type="checkbox" name="is_active" value="1"
        {{ old('is_active', $row->is_active ?? true) ? 'checked' : '' }}> @lang('poultry::lang.active')</label>
</div>

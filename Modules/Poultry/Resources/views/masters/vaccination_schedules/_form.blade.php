<div class="row">
    <div class="col-md-6 form-group">
        <label>@lang('poultry::lang.name') *</label>
        <input type="text" name="name" class="form-control" required value="{{ old('name', $row->name ?? '') }}">
    </div>
    <div class="col-md-6 form-group">
        <label>@lang('poultry::lang.disease')</label>
        <input type="text" name="disease" class="form-control" value="{{ old('disease', $row->disease ?? '') }}">
    </div>
</div>
<div class="row">
    <div class="col-md-4 form-group">
        <label>@lang('poultry::lang.bird_type') *</label>
        <select name="bird_type" class="form-control" required>
            @foreach ($types as $key => $label)
                <option value="{{ $key }}" {{ old('bird_type', $row->bird_type ?? 'all') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 form-group">
        <label>@lang('poultry::lang.breed')</label>
        <select name="breed_id" class="form-control">
            <option value="">@lang('poultry::lang.all_breeds')</option>
            @foreach ($breeds as $id => $name)
                <option value="{{ $id }}" {{ old('breed_id', $row->breed_id ?? '') == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 form-group">
        <label>@lang('poultry::lang.age_days') *</label>
        <input type="number" name="age_days" class="form-control" min="0" required
               value="{{ old('age_days', $row->age_days ?? 0) }}">
    </div>
</div>
<div class="row">
    <div class="col-md-6 form-group">
        <label>@lang('poultry::lang.route') *</label>
        <select name="route" class="form-control" required>
            @foreach ($routes as $key => $label)
                <option value="{{ $key }}" {{ old('route', $row->route ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 form-group">
        <label>@lang('poultry::lang.dose')</label>
        <input type="text" name="dose" class="form-control" value="{{ old('dose', $row->dose ?? '') }}">
    </div>
</div>
<div class="checkbox">
    <label><input type="checkbox" name="is_mandatory" value="1"
        {{ old('is_mandatory', $row->is_mandatory ?? true) ? 'checked' : '' }}> @lang('poultry::lang.mandatory')</label>
</div>
<div class="checkbox">
    <label><input type="checkbox" name="is_active" value="1"
        {{ old('is_active', $row->is_active ?? true) ? 'checked' : '' }}> @lang('poultry::lang.active')</label>
</div>

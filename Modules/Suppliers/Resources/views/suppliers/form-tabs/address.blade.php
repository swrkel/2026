<div class="row">
    <div class="col-md-6 col-sm-12 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.address_line_1')</label>
            <input type="text" name="address_line_1" class="form-control" value="{{ old('address_line_1', $isEdit ? $supplier->address_line_1 : '') }}">
        </div>
    </div>
    <div class="col-md-6 col-sm-12 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.address_line_2')</label>
            <input type="text" name="address_line_2" class="form-control" value="{{ old('address_line_2', $isEdit ? $supplier->address_line_2 : '') }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.city')</label>
            <input type="text" name="city" class="form-control" value="{{ old('city', $isEdit ? $supplier->city : '') }}">
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.state')</label>
            <input type="text" name="state" class="form-control" value="{{ old('state', $isEdit ? $supplier->state : '') }}">
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.country')</label>
            <input type="text" name="country" class="form-control" value="{{ old('country', $isEdit ? $supplier->country : '') }}">
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.zip_code')</label>
            <input type="text" name="zip_code" class="form-control" value="{{ old('zip_code', $isEdit ? $supplier->zip_code : '') }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.landmark')</label>
            <input type="text" name="landmark" class="form-control" value="{{ old('landmark', $isEdit ? $supplier->landmark : '') }}">
        </div>
    </div>
</div>

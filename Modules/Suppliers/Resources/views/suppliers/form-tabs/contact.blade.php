<div class="row">
    <div class="col-md-4 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.mobile')</label>
            <input type="text" name="mobile" class="form-control" value="{{ old('mobile', $isEdit ? $supplier->mobile : '') }}">
        </div>
    </div>
    <div class="col-md-4 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.alternate_number')</label>
            <input type="text" name="alternate_number" class="form-control" value="{{ old('alternate_number', $isEdit ? $supplier->alternate_number : '') }}">
        </div>
    </div>
    <div class="col-md-4 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.landline')</label>
            <input type="text" name="landline" class="form-control" value="{{ old('landline', $isEdit ? $supplier->landline : '') }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.email')</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $isEdit ? $supplier->email : '') }}">
        </div>
    </div>
    <div class="col-md-4 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.tax_number')</label>
            <input type="text" name="tax_number" class="form-control" value="{{ old('tax_number', $isEdit ? $supplier->tax_number : '') }}">
        </div>
    </div>
</div>

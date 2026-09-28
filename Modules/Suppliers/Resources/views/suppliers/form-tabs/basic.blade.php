<div class="row">
    <div class="{{ $isEdit ? 'col-md-4' : 'col-md-6' }} col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.supplier_no')</label>
            <input type="text" name="contact_id" class="form-control" value="{{ old('contact_id', $isEdit ? $supplier->contact_id : $supplierNumber) }}" {{ $isEdit ? '' : 'readonly' }}>
        </div>
    </div>
    <div class="{{ $isEdit ? 'col-md-4' : 'col-md-6' }} col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.supplier_name') <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $isEdit ? $supplier->name : '') }}" required>
        </div>
    </div>

    {{-- Business Name is intentionally retained only on Edit Supplier so older
         records can still be maintained without showing it on Add Supplier. --}}
    @if($isEdit)
        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="form-group">
                <label>@lang('suppliers::lang.business_name')</label>
                <input type="text" name="supplier_business_name" class="form-control" value="{{ old('supplier_business_name', $supplier->supplier_business_name) }}">
            </div>
        </div>
    @endif
</div>

<div class="row">
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.prefix')</label>
            <input type="text" name="prefix" class="form-control" value="{{ old('prefix', $isEdit ? $supplier->prefix : '') }}">
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.first_name')</label>
            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $isEdit ? $supplier->first_name : '') }}">
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.middle_name')</label>
            <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $isEdit ? $supplier->middle_name : '') }}">
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.last_name')</label>
            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $isEdit ? $supplier->last_name : '') }}">
        </div>
    </div>
</div>

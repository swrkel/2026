<div class="row">
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.pay_term_number')</label>
            <input type="number" min="0" name="pay_term_number" class="form-control" value="{{ old('pay_term_number', $isEdit ? $supplier->pay_term_number : '') }}">
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.pay_term_type')</label>
            <select name="pay_term_type" class="form-control">
                <option value="">@lang('suppliers::lang.please_select')</option>
                @foreach($payTermTypes ?? [] as $key => $label)
                    <option value="{{ $key }}" {{ old('pay_term_type', $isEdit ? $supplier->pay_term_type : '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.credit_limit')</label>
            <input type="text" name="credit_limit" class="form-control input_number" value="{{ old('credit_limit', $isEdit ? $supplier->credit_limit : '') }}">
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.opening_balance')</label>
            <input type="text" name="opening_balance" class="form-control input_number" value="{{ old('opening_balance', $isEdit ? $supplier->opening_balance : '') }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 col-sm-6 col-xs-12">
        <div class="form-group">
            <label>@lang('suppliers::lang.business_location')</label>
            <select name="location_id" class="form-control">
                <option value="">@lang('suppliers::lang.all_locations')</option>
                @foreach($locations ?? [] as $id => $name)
                    <option value="{{ $id }}" {{ old('location_id', $isEdit ? $supplier->location_id : '') == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if(!$isEdit)
        <div class="col-md-4 col-sm-6 col-xs-12">
            <div class="form-group">
                <label>Transaction Date</label>
                <div class="input-group supplier-transaction-date-group">
                    <input
                        type="date"
                        name="transaction_date"
                        id="supplier_transaction_date"
                        class="form-control"
                        data-supplier-date
                        value="{{ old('transaction_date', date('Y-m-d')) }}"
                        autocomplete="off"
                    >
                    <span class="input-group-btn">
                        <button
                            type="button"
                            class="btn btn-default supplier-date-trigger"
                            data-target="#supplier_transaction_date"
                            aria-label="Select transaction date"
                            title="Select transaction date"
                        ><i class="fa fa-calendar"></i></button>
                    </span>
                </div>
            </div>
        </div>
    @endif
</div>

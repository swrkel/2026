@include('loan::loan_products._style')

@php
    $is_edit = isset($product);
    $selected_locations = $is_edit ? $product->locations->pluck('id')->toArray() : (!empty($default_location) ? [$default_location] : []);
    $fee_rows = old('fees', $is_edit ? $product->fees->map(function($fee){ return ['fee_id' => $fee->id, 'type' => $fee->pivot->fee_type, 'value' => $fee->pivot->fee_value, 'based_on' => $fee->pivot->calculation_based_on, 'applied_when' => $fee->pivot->applied_when]; })->toArray() : []);
    $penalty_rows = old('penalties', $is_edit ? $product->penalties->map(function($penalty){ return ['penalty_id' => $penalty->id, 'type' => $penalty->pivot->penalty_type, 'value' => $penalty->pivot->penalty_value, 'grace_period' => $penalty->pivot->grace_period, 'grace_period_cycle' => $penalty->pivot->grace_period_cycle]; })->toArray() : []);
    if (empty($fee_rows)) { $fee_rows = [['fee_id' => '', 'type' => 'fixed', 'value' => '', 'based_on' => 'principal', 'applied_when' => 'application']]; }
    if (empty($penalty_rows)) { $penalty_rows = [['penalty_id' => '', 'type' => 'fixed', 'value' => '', 'grace_period' => '', 'grace_period_cycle' => 'days']]; }
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Please check the form.</strong>
        <ul style="margin-bottom:0;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $is_edit ? url('/loan/loan-products/'.$product->id.'/update') : url('/loan/loan-products/store') }}">
    @csrf

    <div class="loan-erp-card">
        <div class="loan-erp-card-title"><i class="fa fa-briefcase"></i> Product Information</div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Product Code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $product->code ?? '') }}" placeholder="Example: LN-001">
                </div>
            </div>
            <div class="col-md-5">
                <div class="form-group">
                    <label>Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $product->name ?? '') }}" required>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Product Category</label>
                    <select name="category_id" class="form-control select2">
                        <option value="">Please Select</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $product->category_id ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="active" {{ old('status', $product->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $product->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Currency</label>
                    <select name="currency_id" class="form-control select2">
                        @foreach($currencies as $currency)
                            <option value="{{ $currency->id }}" {{ old('currency_id', $product->currency_id ?? $default_currency) == $currency->id ? 'selected' : '' }}>{{ $currency->code }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-9">
                <div class="form-group">
                    <label>Available Business Locations</label>
                    <select name="location_ids[]" class="form-control select2" multiple>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ in_array($location->id, old('location_ids', $selected_locations)) ? 'selected' : '' }}>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" placeholder="Brief product description">{{ old('description', $product->description ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="loan-erp-card">
                <div class="loan-erp-card-title"><i class="fa fa-money"></i> Loan Amount & Tenure</div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>Minimum Loan Amount</label><input type="number" step="0.01" name="minimum_amount" class="form-control text-right" value="{{ old('minimum_amount', $product->minimum_amount ?? '') }}"></div></div>
                    <div class="col-md-6"><div class="form-group"><label>Maximum Loan Amount</label><input type="number" step="0.01" name="maximum_amount" class="form-control text-right" value="{{ old('maximum_amount', $product->maximum_amount ?? '') }}"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Minimum Tenure</label><input type="number" name="minimum_loan_term" class="form-control text-right" value="{{ old('minimum_loan_term', $product->minimum_loan_term ?? '') }}"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Maximum Tenure</label><input type="number" name="maximum_loan_term" class="form-control text-right" value="{{ old('maximum_loan_term', $product->maximum_loan_term ?? '') }}"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Tenure Cycle</label><select name="duration_type" class="form-control">@foreach($loan_term_cycles as $key => $label)<option value="{{ $key }}" {{ old('duration_type', $product->duration_type ?? 'months') == $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="loan-erp-card">
                <div class="loan-erp-card-title"><i class="fa fa-percent"></i> Interest & Charges</div>
                <div class="row">
                    <div class="col-md-4"><div class="form-group"><label>Interest Rate %</label><input type="number" step="0.01" name="interest_rate" class="form-control text-right" value="{{ old('interest_rate', $product->interest_rate ?? '') }}"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Min Rate %</label><input type="number" step="0.01" name="minimum_interest_rate" class="form-control text-right" value="{{ old('minimum_interest_rate', $product->minimum_interest_rate ?? '') }}"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Max Rate %</label><input type="number" step="0.01" name="maximum_interest_rate" class="form-control text-right" value="{{ old('maximum_interest_rate', $product->maximum_interest_rate ?? '') }}"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Default Rate %</label><input type="number" step="0.01" name="default_interest_rate" class="form-control text-right" value="{{ old('default_interest_rate', $product->default_interest_rate ?? '') }}"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Processing Fee</label><input type="number" step="0.01" name="processing_fee" class="form-control text-right" value="{{ old('processing_fee', $product->processing_fee ?? '') }}"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Late Payment Charge</label><input type="number" step="0.01" name="late_payment_charge" class="form-control text-right" value="{{ old('late_payment_charge', $product->late_payment_charge ?? '') }}"></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Interest Method</label><select name="interest_method" class="form-control">@foreach($interest_types as $key => $label)<option value="{{ $key }}" {{ old('interest_method', $product->interest_method ?? 'reducing_balance') == $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Frequency</label><select name="interest_frequency" class="form-control">@foreach($interest_frequencies as $key => $label)<option value="{{ $key }}" {{ old('interest_frequency', $product->interest_frequency ?? 'monthly') == $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
                    <div class="col-md-4"><div class="form-group"><label>Calculation</label><select name="calculation_method" class="form-control">@foreach($calculation_methods as $key => $label)<option value="{{ $key }}" {{ old('calculation_method', $product->calculation_method ?? 'fixed') == $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="loan-erp-card">
        <div class="loan-erp-card-title"><i class="fa fa-calendar-check-o"></i> Repayment Rules</div>
        <div class="row">
            <div class="col-md-3"><div class="form-group"><label>Repayment Frequency</label><select name="repayment_frequency" class="form-control">@foreach($installment_frequencies as $key => $label)<option value="{{ $key }}" {{ old('repayment_frequency', $product->repayment_frequency ?? 'monthly') == $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
            <div class="col-md-3"><div class="form-group"><label>No. of Installments</label><input type="number" name="number_of_installments" class="form-control text-right" value="{{ old('number_of_installments', $product->number_of_installments ?? '') }}"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Grace Period</label><input type="number" name="grace_period" class="form-control text-right" value="{{ old('grace_period', $product->grace_period ?? '') }}"></div></div>
            <div class="col-md-2"><div class="form-group"><label>Grace Cycle</label><select name="grace_period_cycle" class="form-control">@foreach($loan_term_cycles as $key => $label)<option value="{{ $key }}" {{ old('grace_period_cycle', $product->grace_period_cycle ?? 'days') == $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
            <div class="col-md-2"><div class="form-group"><label>Compounding</label><select name="compounding_period" class="form-control">@foreach($compounding_periods as $key => $label)<option value="{{ $key }}" {{ old('compounding_period', $product->compounding_period ?? 'none') == $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div></div>
            <div class="col-md-12 loan-inline-checks">
                <label><input type="checkbox" name="allow_partial_payments" value="1" {{ old('allow_partial_payments', $product->allow_partial_payments ?? true) ? 'checked' : '' }}> Allow Partial Payments</label>
                <label><input type="checkbox" name="allow_early_settlement" value="1" {{ old('allow_early_settlement', $product->allow_early_settlement ?? true) ? 'checked' : '' }}> Allow Early Settlement</label>
                <label><input type="checkbox" name="auto_generate_schedule" value="1" {{ old('auto_generate_schedule', $product->auto_generate_schedule ?? true) ? 'checked' : '' }}> Auto Generate Schedule</label>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="loan-erp-card">
                <div class="loan-erp-card-title"><i class="fa fa-tags"></i> Linked Fees</div>
                <div id="loan-fee-rows">
                    @foreach($fee_rows as $i => $row)
                        <div class="loan-repeat-row">
                            <div class="row">
                                <div class="col-md-4"><select name="fees[{{ $i }}][fee_id]" class="form-control"><option value="">Fee</option>@foreach($fees as $fee)<option value="{{ $fee->id }}" {{ ($row['fee_id'] ?? '') == $fee->id ? 'selected' : '' }}>{{ $fee->name }}</option>@endforeach</select></div>
                                <div class="col-md-2"><select name="fees[{{ $i }}][type]" class="form-control"><option value="fixed" {{ ($row['type'] ?? '') == 'fixed' ? 'selected' : '' }}>Fixed</option><option value="percentage" {{ ($row['type'] ?? '') == 'percentage' ? 'selected' : '' }}>%</option></select></div>
                                <div class="col-md-2"><input name="fees[{{ $i }}][value]" class="form-control text-right" placeholder="Value" value="{{ $row['value'] ?? '' }}"></div>
                                <div class="col-md-2"><input name="fees[{{ $i }}][based_on]" class="form-control" placeholder="Based On" value="{{ $row['based_on'] ?? 'principal' }}"></div>
                                <div class="col-md-2"><input name="fees[{{ $i }}][applied_when]" class="form-control" placeholder="When" value="{{ $row['applied_when'] ?? 'application' }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-default btn-sm" id="add-fee-row"><i class="fa fa-plus"></i> Add Fee Row</button>
            </div>
        </div>
        <div class="col-md-6">
            <div class="loan-erp-card">
                <div class="loan-erp-card-title"><i class="fa fa-warning"></i> Linked Penalties</div>
                <div id="loan-penalty-rows">
                    @foreach($penalty_rows as $i => $row)
                        <div class="loan-repeat-row">
                            <div class="row">
                                <div class="col-md-4"><select name="penalties[{{ $i }}][penalty_id]" class="form-control"><option value="">Penalty</option>@foreach($penalties as $penalty)<option value="{{ $penalty->id }}" {{ ($row['penalty_id'] ?? '') == $penalty->id ? 'selected' : '' }}>{{ $penalty->name }}</option>@endforeach</select></div>
                                <div class="col-md-2"><select name="penalties[{{ $i }}][type]" class="form-control"><option value="fixed" {{ ($row['type'] ?? '') == 'fixed' ? 'selected' : '' }}>Fixed</option><option value="percentage" {{ ($row['type'] ?? '') == 'percentage' ? 'selected' : '' }}>%</option></select></div>
                                <div class="col-md-2"><input name="penalties[{{ $i }}][value]" class="form-control text-right" placeholder="Value" value="{{ $row['value'] ?? '' }}"></div>
                                <div class="col-md-2"><input name="penalties[{{ $i }}][grace_period]" class="form-control text-right" placeholder="Grace" value="{{ $row['grace_period'] ?? '' }}"></div>
                                <div class="col-md-2"><select name="penalties[{{ $i }}][grace_period_cycle]" class="form-control">@foreach($loan_term_cycles as $key => $label)<option value="{{ $key }}" {{ ($row['grace_period_cycle'] ?? 'days') == $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-default btn-sm" id="add-penalty-row"><i class="fa fa-plus"></i> Add Penalty Row</button>
            </div>
        </div>
    </div>

    <div class="loan-erp-card">
        <div class="loan-erp-card-title"><i class="fa fa-sticky-note-o"></i> Notes</div>
        <textarea name="notes" class="form-control" placeholder="Internal notes about this loan product">{{ old('notes', $product->notes ?? '') }}</textarea>
    </div>

    <div class="loan-action-footer">
        <a href="{{ url('/loan/loan-products') }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Back</a>
        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> {{ $is_edit ? 'Update Loan Product' : 'Save Loan Product' }}</button>
    </div>
</form>

<script>
(function(){
    var feeIndex = {{ count($fee_rows) }};
    var penaltyIndex = {{ count($penalty_rows) }};
    var feeOptions = `{!! '<option value="">Fee</option>' . $fees->map(function($fee){ return '<option value="'.$fee->id.'">'.e($fee->name).'</option>'; })->implode('') !!}`;
    var penaltyOptions = `{!! '<option value="">Penalty</option>' . $penalties->map(function($penalty){ return '<option value="'.$penalty->id.'">'.e($penalty->name).'</option>'; })->implode('') !!}`;
    document.addEventListener('click', function(e){
        if (e.target.closest('#add-fee-row')) {
            var html = '<div class="loan-repeat-row"><div class="row"><div class="col-md-4"><select name="fees['+feeIndex+'][fee_id]" class="form-control">'+feeOptions+'</select></div><div class="col-md-2"><select name="fees['+feeIndex+'][type]" class="form-control"><option value="fixed">Fixed</option><option value="percentage">%</option></select></div><div class="col-md-2"><input name="fees['+feeIndex+'][value]" class="form-control text-right" placeholder="Value"></div><div class="col-md-2"><input name="fees['+feeIndex+'][based_on]" class="form-control" value="principal" placeholder="Based On"></div><div class="col-md-2"><input name="fees['+feeIndex+'][applied_when]" class="form-control" value="application" placeholder="When"></div></div></div>';
            document.getElementById('loan-fee-rows').insertAdjacentHTML('beforeend', html); feeIndex++;
        }
        if (e.target.closest('#add-penalty-row')) {
            var html = '<div class="loan-repeat-row"><div class="row"><div class="col-md-4"><select name="penalties['+penaltyIndex+'][penalty_id]" class="form-control">'+penaltyOptions+'</select></div><div class="col-md-2"><select name="penalties['+penaltyIndex+'][type]" class="form-control"><option value="fixed">Fixed</option><option value="percentage">%</option></select></div><div class="col-md-2"><input name="penalties['+penaltyIndex+'][value]" class="form-control text-right" placeholder="Value"></div><div class="col-md-2"><input name="penalties['+penaltyIndex+'][grace_period]" class="form-control text-right" placeholder="Grace"></div><div class="col-md-2"><select name="penalties['+penaltyIndex+'][grace_period_cycle]" class="form-control"><option value="days">Days</option><option value="weeks">Weeks</option><option value="months">Months</option><option value="years">Years</option></select></div></div></div>';
            document.getElementById('loan-penalty-rows').insertAdjacentHTML('beforeend', html); penaltyIndex++;
        }
    });
})();
</script>

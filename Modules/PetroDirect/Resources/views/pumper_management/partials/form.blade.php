@php
    $isEdit = !empty($pump_operator);
    $operator = $pump_operator ?? null;
@endphp

@component('components.widget', ['class' => 'box-primary petrodirect-pumper-form-card', 'title' => $isEdit ? __('petrodirect::lang.edit_pumper') : __('petrodirect::lang.add_pumper')])
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('name', __('petrodirect::lang.name') . ':*') !!}
                {!! Form::text('name', old('name', $operator->name ?? null), ['class' => 'form-control', 'required', 'placeholder' => __('petrodirect::lang.name')]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('address', __('petrodirect::lang.address') . ':*') !!}
                {!! Form::text('address', old('address', $operator->address ?? null), ['class' => 'form-control', 'required', 'placeholder' => __('petrodirect::lang.address')]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('mobile', __('petrodirect::lang.mobile') . ':*') !!}
                {!! Form::text('mobile', old('mobile', $operator->mobile ?? null), ['class' => 'form-control input_number', 'required', 'placeholder' => __('petrodirect::lang.mobile')]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('landline', __('petrodirect::lang.landline')) !!}
                {!! Form::text('landline', old('landline', $operator->landline ?? null), ['class' => 'form-control input_number', 'placeholder' => __('petrodirect::lang.landline')]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('dob', __('petrodirect::lang.dob')) !!}
                {!! Form::text('dob', old('dob', $operator->dob ?? null), ['class' => 'form-control datepicker', 'placeholder' => 'YYYY-MM-DD']) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('cnic', __('petrodirect::lang.cnic')) !!}
                {!! Form::text('cnic', old('cnic', $operator->cnic ?? null), ['class' => 'form-control', 'placeholder' => __('petrodirect::lang.cnic')]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('email', __('petrodirect::lang.email')) !!}
                {!! Form::email('email', old('email', $user->email ?? $operator->email ?? null), ['class' => 'form-control', 'placeholder' => __('petrodirect::lang.email')]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('username', __('petrodirect::lang.username')) !!}
                {!! Form::text('username', old('username', $user->username ?? $operator->username ?? null), ['class' => 'form-control', 'placeholder' => __('petrodirect::lang.username')]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('password', __('petrodirect::lang.passcode')) !!}
                {!! Form::text('password', old('password', $isEdit ? null : ($generate_passcode ?? null)), ['class' => 'form-control', 'placeholder' => __('petrodirect::lang.passcode')]) !!}
                @if($isEdit)<small class="help-block">@lang('petrodirect::lang.leave_blank_to_keep_existing')</small>@endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('opening_balance', __('petrodirect::lang.opening_balance')) !!}
                {!! Form::text('opening_balance', old('opening_balance', $operator->opening_balance ?? 0), ['class' => 'form-control input_number', 'placeholder' => __('petrodirect::lang.opening_balance')]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('location_id', __('petrodirect::lang.location') . ':*') !!}
                {!! Form::select('location_id', $locations, old('location_id', $operator->location_id ?? null), ['class' => 'form-control select2', 'required', 'placeholder' => __('petrodirect::lang.please_select'), 'style' => 'width:100%;']) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('commission_type', __('petrodirect::lang.commission_type')) !!}
                {!! Form::select('commission_type', ['none' => 'None', 'fixed' => 'Fixed', 'percentage' => 'Percentage'], old('commission_type', $operator->commission_type ?? 'none'), ['class' => 'form-control select2 commission_type', 'style' => 'width:100%;']) !!}
            </div>
        </div>
        <div class="col-md-6 commission_ap_div">
            <div class="form-group">
                {!! Form::label('commission_ap', __('petrodirect::lang.commission_value')) !!}
                {!! Form::text('commission_ap', old('commission_ap', $operator->commission_ap ?? null), ['class' => 'form-control input_number', 'placeholder' => __('petrodirect::lang.commission_value')]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('transaction_date', __('petrodirect::lang.transaction_date')) !!}
                {!! Form::text('transaction_date', old('transaction_date', !empty($operator->transaction_date) ? date('Y-m-d', strtotime($operator->transaction_date)) : date('Y-m-d')), ['class' => 'form-control datepicker', 'placeholder' => 'YYYY-MM-DD']) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('is_default', __('petrodirect::lang.use_for_admin_operator_dashboard_login')) !!}
                {!! Form::select('is_default', ['0' => __('messages.no'), '1' => __('messages.yes')], old('is_default', $operator->is_default ?? 0), ['class' => 'form-control select2', 'style' => 'width:100%;']) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('can_fullscreen', __('petrodirect::lang.can_minimize_full_screen')) !!}
                {!! Form::select('can_fullscreen', ['0' => __('messages.no'), '1' => __('messages.yes')], old('can_fullscreen', $operator->can_fullscreen ?? 0), ['class' => 'form-control select2', 'style' => 'width:100%;']) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>&nbsp;</label>
                <div class="checkbox">
                    <label>{!! Form::checkbox('active', 1, old('active', !isset($operator) || (int) ($operator->active ?? 1) === 1)) !!} @lang('petrodirect::lang.active')</label>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>&nbsp;</label>
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('is_petro_pd_only', 1, old('is_petro_pd_only', (bool) ($operator->is_petro_pd_only ?? false))) !!}
                        <strong>Petro PD only</strong>
                    </label>
                    <p class="help-block">Exclude this operator and their data from Petro Direct, Petro and Settlement SW settlements.</p>
                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="checkbox">
                <label>
                    {!! Form::hidden('hide_in_direct_settlement_if_pending_shifts', 0) !!}
                    {!! Form::checkbox('hide_in_direct_settlement_if_pending_shifts', 1, old('hide_in_direct_settlement_if_pending_shifts', !empty($operator->hide_in_direct_settlement_if_pending_shifts))) !!}
                    @lang('petrodirect::lang.hide_in_direct_settlement_if_pending_shifts')
                </label>
            </div>
        </div>
    </div>
@endcomponent

<div class="box box-solid">
    <div class="box-body text-right">
        <button type="submit" class="btn btn-primary">{{ $submit_text }}</button>
        <a href="{{ route('petrodirect.pumper-management.index') }}" class="btn btn-default">@lang('messages.close')</a>
    </div>
</div>

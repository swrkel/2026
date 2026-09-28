{{-- Edit Sales Agent Modal Content --}}
<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
    <h4 class="modal-title">@lang('lang_v1.edit_sales_agent')</h4>
</div>
{!! Form::open(['url' => route('salesagent.management.update', $sales_agent->id), 'method' => 'PUT', 'id' => 'sales_agent_edit_form']) !!}
<div class="modal-body">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('name', __('lang_v1.sales_agent_name') . ':*') !!}
                {!! Form::text('name', $sales_agent->name, ['class' => 'form-control', 'required', 'placeholder' => __('lang_v1.sales_agent_name')]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('joined_date', __('lang_v1.joined_date') . ':*') !!}
                {!! Form::date('joined_date', optional($sales_agent->joined_date)->format('Y-m-d'), ['class' => 'form-control', 'required']) !!}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                {!! Form::select('location_id', $business_locations, $default_location_id ?? $sales_agent->location_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('employment_grade', __('lang_v1.employment_grade') . ':') !!}
                {!! Form::text('employment_grade', $sales_agent->employment_grade, ['class' => 'form-control', 'placeholder' => __('lang_v1.employment_grade')]) !!}
            </div>
        </div>
    </div>

    @php
        $commission_entitled = $sales_agent->commission_entitled ?? ((float) $sales_agent->commission > 0 ? 'yes' : 'no');
        $commission_type = $sales_agent->commission_type ?? 'percentage';
    @endphp

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('salary', __('lang_v1.salary') . ':') !!}
                {!! Form::text('salary', number_format((float) $sales_agent->salary, $currency_precision ?? 2), [
                    'class' => 'form-control input_number sales-agent-decimal',
                    'placeholder' => number_format(0, $currency_precision ?? 2),
                    'data-decimal-precision' => $currency_precision ?? 2,
                ]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('commission_entitled', 'Commission Entitled:') !!}
                {!! Form::select('commission_entitled', ['yes' => __('messages.yes'), 'no' => __('messages.no')], $commission_entitled, [
                    'class' => 'form-control select2 commission-entitled-edit',
                    'id' => 'commission_entitled_edit',
                ]) !!}
            </div>
        </div>
    </div>

    <div class="row commission-fields-edit">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('commission_type', 'Commission Type:') !!}
                {!! Form::select('commission_type', ['percentage' => 'Percentage', 'fixed' => 'Fixed'], $commission_type, [
                    'class' => 'form-control select2',
                    'id' => 'commission_type_edit',
                ]) !!}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('commission', 'Commission Value:') !!}
                {!! Form::text('commission', number_format((float) $sales_agent->commission, $currency_precision ?? 2), [
                    'class' => 'form-control input_number sales-agent-decimal commission-value',
                    'placeholder' => number_format(0, $currency_precision ?? 2),
                    'data-decimal-precision' => $currency_precision ?? 2,
                    'id' => 'commission_value_edit',
                ]) !!}
                <small class="help-block commission-percentage-help">Do not type % sign. System will treat the value as percentage.</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('user_id', __('lang_v1.link_to_user') . ':') !!}
                {!! Form::select('user_id', $users, $sales_agent->user_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]) !!}
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
    <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
</div>
{!! Form::close() !!}

<script>
    window.initSalesAgentEditForm = function() {
        function cleanDecimalInput($input, applyPrecision) {
            var currency_precision = parseInt("{{ $currency_precision ?? 2 }}", 10);
            var value = $input.val();

            if (typeof value === 'string') {
                value = value.replace(/%/g, '').replace(/,/g, '').trim();
            }

            if (value === '' || isNaN(value)) {
                $input.val(value === '' ? '' : Number(0).toFixed(currency_precision));
                return;
            }

            if (applyPrecision === true) {
                $input.val(parseFloat(value).toFixed(currency_precision));
                return;
            }

            var parts = value.split('.');
            if (parts.length > 1 && parts[1].length > currency_precision) {
                parts[1] = parts[1].substring(0, currency_precision);
                $input.val(parts.join('.'));
            } else {
                $input.val(value);
            }
        }

        function toggleEditCommissionFields() {
            if ($('#commission_entitled_edit').val() === 'yes') {
                $('.commission-fields-edit').show();
                if (!$('#commission_type_edit').val()) {
                    $('#commission_type_edit').val('percentage').trigger('change.select2');
                }
            } else {
                $('.commission-fields-edit').hide();
                $('#commission_type_edit').val('percentage').trigger('change.select2');
                $('#commission_value_edit').val('');
            }
        }

        $('#commission_entitled_edit').off('change.salesAgentEdit').on('change.salesAgentEdit', toggleEditCommissionFields);

        $('#sales_agent_edit_form').find('.sales-agent-decimal, .commission-value')
            .off('input.salesAgentEdit keyup.salesAgentEdit change.salesAgentEdit blur.salesAgentEdit')
            .on('input.salesAgentEdit keyup.salesAgentEdit change.salesAgentEdit', function() {
                cleanDecimalInput($(this), false);
            })
            .on('blur.salesAgentEdit', function() {
                cleanDecimalInput($(this), true);
            });

        toggleEditCommissionFields();
    };

    window.initSalesAgentEditForm();
</script>

{{--
    Add / edit a pump operator, shown in a modal.

    Writes to the BUSINESS's pump_operators table, shared with every module that
    reads operators. short_amount, excess_amount and settlement_no are
    deliberately absent: those are set by settlement, not by hand.
--}}

<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => $operator
                ? route('sw.operators.update', $operator->id)
                : route('sw.operators.store'),
            'method' => $operator ? 'put' : 'post',
            'id' => 'sw_operator_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">
                {{ $operator ? __('sw::lang.edit_pump_operator') : __('sw::lang.add_pump_operator') }}
            </h4>
        </div>

        <div class="modal-body">
            <div class="row">

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('name', __('sw::lang.name') . ':*') !!}
                        {!! Form::text('name', $operator->name ?? null, [
                            'class' => 'form-control', 'required', 'maxlength' => 100,
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        {!! Form::select('location_id', $business_locations, $operator->location_id ?? null, [
                            'class' => 'form-control select2', 'required', 'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('mobile', __('sw::lang.mobile') . ':') !!}
                        {!! Form::text('mobile', $operator->mobile ?? null, [
                            'class' => 'form-control', 'maxlength' => 20,
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('landline', __('sw::lang.landline') . ':') !!}
                        {!! Form::text('landline', $operator->landline ?? null, [
                            'class' => 'form-control', 'maxlength' => 20,
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('cnic', __('sw::lang.nic') . ':') !!}
                        {!! Form::text('cnic', $operator->cnic ?? null, [
                            'class' => 'form-control', 'maxlength' => 100,
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('dob', __('sw::lang.date_of_birth') . ':') !!}
                        {!! Form::date('dob', $operator->dob ?? null, ['class' => 'form-control']) !!}
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('address', __('sw::lang.address') . ':') !!}
                        {!! Form::textarea('address', $operator->address ?? null, [
                            'class' => 'form-control', 'rows' => 2,
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('assigned_pump_id', __('sw::lang.assigned_pump') . ':') !!}
                        {!! Form::select('assigned_pump_id', $pumps, $operator->assigned_pump_id ?? null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('messages.please_select'),
                            'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('commission_type', __('sw::lang.commission_type') . ':*') !!}
                        {!! Form::select('commission_type', [
                            'none' => __('sw::lang.none'),
                            'fixed' => __('sw::lang.fixed'),
                            'percentage' => __('sw::lang.percentage'),
                        ], $operator->commission_type ?? 'none', [
                            'class' => 'form-control select2', 'required', 'style' => 'width:100%',
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('commission_ap', __('sw::lang.commission_rate') . ':') !!}
                        {!! Form::number('commission_ap', $operator->commission_ap ?? 0, [
                            'class' => 'form-control', 'step' => '0.01', 'min' => 0,
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('active', 1, $operator ? (bool) $operator->active : true) !!}
                            @lang('sw::lang.active')
                        </label>
                    </div>
                </div>

            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div>
</div>

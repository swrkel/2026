<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\Superadmin\Http\Controllers\AgentController@update', $agent->id), 'method' => 'put', 'id' => 'edit_agent', 'files' => true
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'superadmin::lang.edit_agent' )</h4>
        </div>

        <div class="modal-body">
            <!-- Row 1: Full Name, Address, Country -->
            <div class="row">
                <div class="form-group col-md-4">
                    {!! Form::label('name', __('business.name') . ':*') !!}
                    {!! Form::text('name', $agent->name, ['class' => 'form-control', 'placeholder' => __('business.name'), 'required']) !!}
                </div>
                <div class="form-group col-md-4">
                    {!! Form::label('address', __('customer.address') . ':') !!}
                    {!! Form::text('address', $agent->address, ['class' => 'form-control', 'placeholder' => __('customer.address')]) !!}
                </div>
                <div class="form-group col-md-4">
                    {!! Form::label('country_id', __('superadmin::lang.country') . ':*') !!}
                    {!! Form::select('country_id', $countries, $agent->country_id, ['class' => 'form-control select2', 'id' => 'agent_country_id', 'style' => 'width:100%', 'required']) !!}
                </div>
            </div>

            <!-- Row 2: District, City, Mobile No 1 -->
            <div class="row">
                <div class="form-group col-md-4">
                    {!! Form::label('district_id', __('superadmin::lang.district') . ':*') !!}
                    {!! Form::select('district_id', $districts, $agent->district_id, ['class' => 'form-control select2', 'id' => 'agent_district_id', 'style' => 'width:100%', 'required']) !!}
                </div>
                <div class="form-group col-md-4">
                    {!! Form::label('city', __('superadmin::lang.city') . ':*') !!}
                    {!! Form::select('city', $cities, $agent->city, ['class' => 'form-control select2', 'id' => 'agent_city', 'style' => 'width:100%', 'required']) !!}
                </div>
                <div class="form-group col-md-4">
                    {!! Form::label('mobile_number', __('customer.mobile') . ':*') !!}
                    {!! Form::text('mobile_number', $agent->mobile_number, ['class' => 'form-control mobile-no', 'placeholder' => __('customer.mobile_number'), 'required']) !!}
                </div>
            </div>

            <!-- Row 3: Mobile No 2, Mobile No 3, Land Number -->
            <div class="row">
                <div class="form-group col-md-4">
                    {!! Form::label('mobile_no_2', __('superadmin::lang.mobile_no_2') . ':') !!}
                    {!! Form::text('mobile_no_2', $agent->mobile_no_2, ['class' => 'form-control mobile-no', 'placeholder' => __('superadmin::lang.mobile_no_2')]) !!}
                </div>
                <div class="form-group col-md-4">
                    {!! Form::label('mobile_no_3', __('superadmin::lang.mobile_no_3') . ':') !!}
                    {!! Form::text('mobile_no_3', $agent->mobile_no_3, ['class' => 'form-control mobile-no', 'placeholder' => __('superadmin::lang.mobile_no_3')]) !!}
                </div>
                <div class="form-group col-md-4">
                    {!! Form::label('land_number', __('superadmin::lang.land_number') . ':') !!}
                    {!! Form::text('land_number', $agent->land_number, ['class' => 'form-control', 'placeholder' => __('superadmin::lang.land_number')]) !!}
                </div>
            </div>

            <!-- Row 4: Email, Username, NIC Number -->
            <div class="row">
                <div class="form-group col-md-4">
                    {!! Form::label('email', __('business.email') . ':') !!}
                    {!! Form::email('email', $agent->email, ['class' => 'form-control', 'placeholder' => __('business.email')]) !!}
                </div>
                <div class="form-group col-md-4">
                    {!! Form::label('username', __('superadmin::lang.username') . ':*') !!}
                    {!! Form::text('username', $agent->username, ['class' => 'form-control', 'placeholder' => __('superadmin::lang.username'), 'required']) !!}
                </div>
                <div class="form-group col-md-4">
                    {!! Form::label('nic_number', __('superadmin::lang.nic_number') . ':*') !!}
                    {!! Form::text('nic_number', $agent->nic_number, ['class' => 'form-control', 'placeholder' => __('superadmin::lang.nic_number'), 'required']) !!}
                </div>
            </div>

            <!-- Row 5: Referral Code, Bank Name, Account Number -->
            <div class="row">
                <div class="form-group col-md-4">
                    {!! Form::label('referral_code', __('superadmin::lang.referral_code') . ':*') !!}
                    {!! Form::text('referral_code', $agent->referral_code, ['class' => 'form-control', 'placeholder' => __('superadmin::lang.referral_code'), 'required']) !!}
                </div>
                <div class="form-group col-md-4">
                    {!! Form::label('bank_name', __('superadmin::lang.bank_name') . ':*') !!}
                    {!! Form::text('bank_name', $agent->bank_name, ['class' => 'form-control', 'placeholder' => __('superadmin::lang.bank_name'), 'required']) !!}
                </div>
                <div class="form-group col-md-4">
                    {!! Form::label('account_number', __('superadmin::lang.account_number') . ':*') !!}
                    {!! Form::text('account_number', $agent->account_number, ['class' => 'form-control', 'placeholder' => __('superadmin::lang.account_number'), 'required']) !!}
                </div>
            </div>

            <!-- Row 6: Branch, NIC Copy, Agent Photo -->
            <div class="row">
                <div class="form-group col-md-4">
                    {!! Form::label('branch', __('superadmin::lang.branch') . ':*') !!}
                    {!! Form::text('branch', $agent->branch, ['class' => 'form-control', 'placeholder' => __('superadmin::lang.branch'), 'required']) !!}
                </div>
                <div class="form-group col-md-4">
                    {!! Form::label('nic_copy', __('superadmin::lang.upload_nic_image') . ':') !!}
                    {!! Form::file('nic_copy', ['class' => 'form-control']) !!}
                </div>
                <div class="form-group col-md-4">
                    {!! Form::label('agent_photo', __('superadmin::lang.upload_your_photo') . ':') !!}
                    {!! Form::file('agent_photo', ['class' => 'form-control']) !!}
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
    $('.select2').select2({width: '100%'});
</script>

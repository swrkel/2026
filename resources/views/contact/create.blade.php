<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        @php
            $form_id = 'contact_add_form';

            if (!isset($businessLocations)) {
                $businessLocations = bussionLocation();
            }
            if (!isset($module)) {
                $module = 'other';
            }

            if (isset($quick_add)) {
                $form_id = 'quick_add_contact';
            }

            if (isset(request()->is_credit)) {
                $form_id = 'cc_contact_add_form';
            }

            $custom_labels = !empty(session('business.custom_labels'))
                ? json_decode(session('business.custom_labels'), true)
                : [];
            $contact_fields = !empty(session('business.contact_fields')) ? session('business.contact_fields') : [];
            $business_id = request()->session()->get('user.business_id');
            $type_a = !empty($type) ? $type : 'customer';
            $type = !empty($type) ? $type : 'customer';
            if (!isset($types) || !is_array($types)) {
                $types = [];
                if (auth()->check() && auth()->user()->can('supplier.create')) {
                    $types['supplier'] = __('report.supplier');
                }
                if (auth()->check() && auth()->user()->can('customer.create') && $type != 'supplier') {
                    $types['customer'] = __('report.customer');
                }
                if (auth()->check() && auth()->user()->can('supplier.create') && auth()->user()->can('customer.create') && $type != 'supplier') {
                    $types['both'] = __('lang_v1.both_supplier_customer');
                }
            }
            $supplier_groups = App\ContactGroup::where('business_id', $business_id)
                ->where(function ($query) use ($type) {
                    $query->where('contact_groups.type', 'supplier')->orWhere('contact_groups.type', 'both');
                })
                ->pluck('name', 'id');

            $customer_groups = App\ContactGroup::where('business_id', $business_id)
                ->where(function ($query) use ($type) {
                    $query->where('contact_groups.type', 'customer')->orWhere('contact_groups.type', 'both');
                })
                ->pluck('name', 'id');

            $both_groups = App\ContactGroup::where('business_id', $business_id)
                ->where(function ($query) use ($type) {
                    $query->where('contact_groups.type', 'both');
                })
                ->pluck('name', 'id');

            $is_property = false;
            if (isset($is_property_customer)) {
                $is_property = true;
            }
            use App\NotificationTemplate;

            if ($type == 'customer') {
                //add by sakhi
                if (empty($contact_fields) || !isset($contact_fields['location'])) {
                    $contact_fields = defaultCustomerForm();
                } elseif (
                    $contact_fields['location'] == array_key_first($businessLocations->toArray()) ||
                    $contact_fields['location'] != 0
                ) {
                    $contact_fields = defaultCustomerForm();
                }

                $notifications = NotificationTemplate::customerNotifications();
            } else {
                $notifications = NotificationTemplate::supplierNotifications();
            }
            $customers = \App\Contact::customersDropdown($business_id, false);

            function requiredLabel($name, $label)
            {
                return Form::label($name, $label . ' <span class="text-danger">*</span>', [], false);
            }
        @endphp
        {!! Form::open([
            'url' => isset($is_bank_customer) && $is_bank_customer ? action('CustomersBankController@store') : action('ContactController@store'),
            'method' => 'post',
            'id' => !empty(request()->form_id) ? request()->form_id : $form_id,
            'enctype' => 'multipart/form-data',
            'files' => true,
        ]) !!}
        @if(isset($is_bank_customer) && $is_bank_customer)
            {!! Form::hidden('module', 'bank') !!}
        @else
            {!! Form::hidden('module', $module) !!}
        @endif
        <div class="modal-header">
            <button type="button" class="close closing_contact_modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('contact.add_contact')</h4>
        </div>

        <div class="modal-body">
            <div class="row">
                @if ($type == 'customer' && isset($customerSettings->location) && $customerSettings->location == 1)
                    <div class="col-md-4">

                        <div class="form-group">
                            {!! Form::label('location', __('contact.location') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-map-marker"></i>
                                </span>
                                {!! Form::select('location', $businessLocations, null, [
                                    'id' => 'location',
                                    'class' => 'form-control',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @if(!isset($is_bank_customer) || !$is_bank_customer)
                <div class="col-md-4 fi-gr contact_type_div">
                    <div class="form-group">
                        {{-- {!! Form::label('type', __('contact.contact_type') . ':*', ['style' => 'color: red;']) !!} --}}
                        {!! requiredLabel('name', __('contact.contact_type')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>

                            {!! Form::select('type', $types, !empty($type) ? $type : null, [
                                'class' => 'form-control',
                                'id' => 'contact_type',
                                'placeholder' => __('messages.please_select'),
                                'required',
                            ]) !!}
                        </div>
                    </div>
                </div>
                @else
                    <div style="display: none;">
                        {!! Form::select('type', $types, 'customer', ['id' => 'contact_type']) !!}
                    </div>
                @endif
                @php
                    //$type = 'property_customer';
                @endphp
                <div class="col-md-3 fi-gr">
                    <div class="form-group">
                        {{-- {!! Form::label('name', __('contact.name') . ':*', ['style' => 'color: red;']) !!} --}}
                        {!! requiredLabel('name', __('contact.name')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-user"></i>
                            </span>
                            {!! Form::text('name', null, [
                                'class' => 'form-control customer_name',
                                'placeholder' => __('contact.name'),
                                'required',
                            ]) !!}
                        </div>
                    </div>
                </div>
                @if ($type != 'supplier' && isset($customerSettings->need_to_send_sms) && $customerSettings->need_to_send_sms == 1)
                    <div class="col-md-4 fi-gr @if ($type == 'customer' && !array_key_exists('need_to_send_sms', $contact_fields)) hide @endif ">
                        <div class="form-group">
                            {{-- {!! Form::label('name', __('contact.should_notify') . ':*') !!} --}}
                            {!! requiredLabel('name', __('contact.should_notify')) !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-envelope"></i>
                                </span>
                                {!! Form::select('should_notify', ['1' => __('messages.yes'), '0' => __('messages.no')], null, [
                                    'placeholder' => __('messages.please_select'),
                                    'required',
                                    'class' => 'form-control need_to_send_sms',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    @if (isset($customerSettings->credit_notification_type) && $customerSettings->credit_notification_type == 1 && (!isset($is_bank_customer) || !$is_bank_customer))
                        <div class="col-md-4 fi-gr @if ($type == 'customer' && !array_key_exists('credit_notification_type', $contact_fields)) hide @endif">
                            <div class="form-group">
                                {!! Form::label('name', __('contact.credit_notification') . ':*') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-envelope"></i>
                                    </span>
                                    {!! Form::select(
                                        'credit_notification',
                                        [
                                            'settlement' => __('contact.settlement'),
                                            'customer_bill' => __('contact.bill_to_customer'),
                                            'pumper_dashboard' => __('contact.pumper_dashboard'),
                                        ],
                                        null,
                                        ['placeholder' => __('contact.none'), 'class' => 'form-control credit_notification_type'],
                                    ) !!}
                                </div>
                            </div>
                        </div>
                    @endif

                    @if(!isset($is_bank_customer) || !$is_bank_customer)
                    <div class="col-md-4 fi-gr manual_bill_div">
                        <div class="form-group">
                            {{-- {!! Form::label('manual_bill_settlement', 'Manual Bill to Settlement:') !!} --}}
                            {!! requiredLabel('manual_bill_settlement', 'Manual Bill to Settlement') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-file-text-o"></i>
                                </span>
                                {!! Form::select('manual_bill_settlement', [0 => 'No', 1 => 'Yes'], null, [
                                    'class' => 'form-control',
                                    'id' => 'manual_bill_settlement',
                                    'placeholder' => __('messages.please_select'),
                                    'required',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    @endif


                @endif
                <div class="clearfix"></div>
                @if (isset($customerSettings->sub_customer) && $customerSettings->sub_customer == 1)
                    <div class="col-md-4 fi-gr @if ($type == 'customer' && !array_key_exists('sub_customer', $contact_fields)) hide @endif">
                        {!! Form::checkbox('sub_customer', 1, !empty($contact->sub_customer) ? true : false, [
                            'class' => 'sub_customer',
                        ]) !!} {{ __('contact.sub_customer') }}
                    </div>


                    <div class="col-md-9 fi-gr sub_customer_field hide">
                        <div class="form-group">
                            {!! Form::label('name', __('contact.sub_customers') . ':*') !!}
                            {!! Form::select('sub_customers[]', $customers, null, ['class' => 'form-control select2', 'multiple']) !!}
                        </div>
                    </div>
                @endif

                <div class="clearfix"></div>

                @if($type == 'customer' || $type == 'both')
                    <div class="col-md-4 fi-gr">
                        <div class="form-group">
                            {!! Form::label('customer_passcode', 'Customer Passcode:*') !!}
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                {!! Form::text('customer_passcode', old('customer_passcode', $generated_customer_passcode ?? null), [
                                    'class' => 'form-control',
                                    'maxlength' => 4,
                                    'pattern' => '[0-9]{4}',
                                    'required',
                                    'placeholder' => '4 digit passcode'
                                ]) !!}
                            </div>
                            <small class="text-muted">Auto-generated unique passcode for Distribution Dealer login.</small>
                        </div>
                    </div>
                @endif

                <div class="col-md-4 fi-gr @if ($type == 'customer') hide @endif">
                    <div class="form-group">
                        {!! Form::label('contact_id', __('lang_v1.contact_id') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-id-badge"></i>
                            </span>
                            {!! Form::text('contact_id', !empty($contact_id) ? $contact_id : null, [
                                'class' => 'form-control',
                                'placeholder' => __('lang_v1.contact_id'),
                                'readonly',
                            ]) !!}
                        </div>
                    </div>
                </div>
                @if ($type != 'supplier')
                    @if (isset($customerSettings->tax_number) && $customerSettings->tax_number == 1)
                        <div
                            class="col-md-4 fi-gr @if ($is_property && !array_key_exists('property_customer_tax_number', $contact_fields)) hide @endif  @if ($type == 'customer' && !array_key_exists('customer_tax_number', $contact_fields)) hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_tax_number', $contact_fields)) hide @endif">
                            <div class="form-group">
                                {!! Form::label('tax_number', __('contact.tax_no') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-info"></i>
                                    </span>
                                    {!! Form::text('tax_number', null, [
                                        'class' => 'form-control property_customer_tax_number',
                                        'placeholder' => __('contact.tax_no'),
                                    ]) !!}
                                </div>
                            </div>
                        </div>
                    @endif
                    @if (isset($customerSettings->vat_no) && $customerSettings->vat_no == 1)
                        <div class="col-md-4 fi-gr @if ($type == 'customer' && !array_key_exists('vat_no', $contact_fields)) hide @endif">
                            <div class="form-group">
                                {!! Form::label('vat_number', __('contact.vat_number') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-info"></i>
                                    </span>
                                    {!! Form::text('vat_number', null, [
                                        'class' => 'form-control vat_no',
                                        'placeholder' => __('contact.vat_number'),
                                    ]) !!}
                                </div>
                            </div>
                        </div>
                    @endif

                @endif
                @if (!isset($mode))
                    <div class="col-md-3 fi-gr">
                        <div class="form-group">
                            {!! Form::label('opening_balance', __('lang_v1.opening_balance') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-money"></i>
                                </span>
                                {!! Form::text('opening_balance', 0, ['class' => 'form-control input_number customer_opening_balance']) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @if ($type != 'supplier' && isset($customerSettings->pay_term) && $customerSettings->pay_term == 1)
                    <div
                        class="col-md-4 fi-gr @if ($is_property && !array_key_exists('property_customer_pay_term', $contact_fields)) hide @endif   @if ($type == 'customer' && !array_key_exists('customer_pay_term', $contact_fields)) hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_pay_term', $contact_fields)) hide @endif">
                        <div class="form-group">
                            <div class="multi-input">
                                {!! Form::label('pay_term_number', __('contact.pay_term') . ':') !!} @show_tooltip(__('tooltip.pay_term'))
                                <br />
                                {!! Form::number('pay_term_number', null, [
                                    'class' => 'form-control width-40 pull-left input_number customer_pay_term',
                                    'placeholder' => __('contact.pay_term'),
                                ]) !!}

                                {!! Form::select('pay_term_type', ['months' => __('lang_v1.months'), 'days' => __('lang_v1.days')], '', [
                                    'class' => 'form-control width-60 pull-left',
                                    'placeholder' => __('messages.please_select'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @php
                    $show_customer_group = false;
                    if ($is_property) {
                        if (array_key_exists('property_customer_customer_group', $contact_fields)) {
                            $show_customer_group = true;
                        }
                    } else {
                        if ((isset($customerSettings->customer_group) && $customerSettings->customer_group == 1) || array_key_exists('customer_customer_group', $contact_fields)) {
                            $show_customer_group = true;
                        }
                    }
                @endphp
                <div
                    class="col-md-4 fi-gr customer_fields @if (!$show_customer_group) hide backend_hide @endif">
                        <div class="form-group">
                            {!! Form::label('customer_group_id', __('lang_v1.customer_group') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-users"></i>
                                </span>
                                {!! Form::select('customer_group_id', $customer_groups, '', ['class' => 'form-control customer_customer_group']) !!}
                            </div>
                        </div>
                    </div>
                @if ($type != 'supplier')
                    <div class="col-md-4 fi-gr supplier_fields">
                        <div class="form-group">
                            {!! Form::label('supplier_group_id', __('lang_v1.supplier_group') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-users"></i>
                                </span>
                                {!! Form::select('supplier_group_id', $type_a == 'supplier' ? $supplier_groups : $customer_groups, '', [
                                    'class' => 'form-control',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @if (isset($customerSettings->credit_limit) && $customerSettings->credit_limit == 1 && (!isset($is_bank_customer) || !$is_bank_customer))
                    <div
                        class="col-md-4 fi-gr customer_fields  @if ($type == 'customer' && !array_key_exists('customer_credit_limit', $contact_fields)) hide backend_hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_credit_limit', $contact_fields)) hide backend_hide @endif">
                        <div class="form-group">
                            {!! Form::label('credit_limit', __('lang_v1.credit_limit') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-money"></i>
                                </span>
                                {!! Form::text('credit_limit', null, ['class' => 'form-control input_number customer_credit_limit']) !!}
                            </div>
                            <p class="help-block">@lang('lang_v1.credit_limit_help')</p>
                        </div>
                    </div>
                @endif

                @if (
                    $type != 'customer' &&
                        !array_key_exists('customer_password', $contact_fields) &&
                        isset($customerSettings->password) &&
                        $customerSettings->password == 1)
                    <div class="col-md-4 fi-gr customer_fields ">
                        <div class="form-group">
                            {!! Form::label('password', __('business.password') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-key"></i>
                                </span>

                                {!! Form::password('password', [
                                    'class' => 'form-control customer_password',
                                    'id' => 'password',
                                    'placeholder' => __('business.password'),
                                ]) !!}
                            </div>
                            <p class="help-block">At least 6 character.</p>
                        </div>
                    </div>
                @endif
                @if (
                    $type != 'customer' &&
                        !array_key_exists('customer_confirm_password', $contact_fields) &&
                        isset($customerSettings->confirm_password) &&
                        $customerSettings->confirm_password == 1)
                    <div class="col-md-4 fi-gr customer_fields ">
                        <div class="form-group">
                            {!! Form::label('confirm_password', __('business.confirm_password') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-key"></i>,
                                </span>
                                {!! Form::password('confirm_password', [
                                    'class' => 'form-control customer_confirm_password',
                                    'id' => 'confirm_password',
                                    'placeholder' => __('business.confirm_password'),
                                ]) !!}
                            </div>
                            <p class="help-block">At least 6 character.</p>
                        </div>
                    </div>
                @endif
                <div class="col-md-4 fi-gr">
                    <div class="form-group">
                        {{-- {!! Form::label('transaction_date', __('lang_v1.transaction_date') . ':') !!} --}}
                        {!! requiredLabel('transaction_date', __('lang_v1.transaction_date')) !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="fa fa-calendar"></i>
                            </span>
                            {!! Form::text('transaction_date', @format_date(date('Y-m-d')), [
                                'class' => 'form-control input_number customer_transaction_date',
                                'id' => 'transaction_date_contact',
                                'required',
                                'readonly',
                            ]) !!}
                        </div>

                    </div>
                </div>
                <input type="hidden" name="property_id_value" value="<?php
                if (isset($_GET['property_id'])) {
                    echo $_GET['property_id'];
                } else {
                    echo 1;
                }
                ?>">

                @if(isset($is_bank_customer) && $is_bank_customer)
                <div class="clearfix"></div>
                <hr>
                <div class="row" style="margin-left: 0px; margin-right: 0px;">
                    <!-- NIC Number -->
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('nic_number', __('contact.nic_number') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-id-card"></i></span>
                                {!! Form::text('nic_number', null, ['class' => 'form-control', 'placeholder' => __('contact.nic_number'), 'id' => 'nic_number']) !!}
                            </div>
                        </div>
                    </div>
                    <!-- NIC Photo -->
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('nic_image', __('contact.nic_image') . ':') !!}
                            {!! Form::file('nic_image', ['id' => 'nic_image', 'accept' => 'image/*']) !!}
                            <div class="mt-2 text-center">
                                <img id="nic_image_preview" src="" style="max-height: 100px; display: none; margin-top: 10px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row" style="margin-left: 0px; margin-right: 0px;">
                    <!-- Passport Number -->
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('passport_number', __('contact.passport_number') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-id-badge"></i></span>
                                {!! Form::text('passport_number', null, ['class' => 'form-control', 'placeholder' => __('contact.passport_number'), 'id' => 'passport_number']) !!}
                            </div>
                        </div>
                    </div>
                    <!-- Passport Photo -->
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('passport_image', __('contact.passport_image') . ':') !!}
                            {!! Form::file('passport_image', ['id' => 'passport_image', 'accept' => 'image/*']) !!}
                            <div class="mt-2 text-center">
                                <img id="passport_image_preview" src="" style="max-height: 100px; display: none; margin-top: 10px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row" style="margin-left: 0px; margin-right: 0px;">
                    <!-- Customer Photo -->
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('image', __('Customer Photo') . ':') !!}
                            {!! Form::file('image', ['id' => 'image', 'accept' => 'image/*']) !!}
                            <div class="mt-2 text-center">
                                <img id="image_preview" src="" style="max-height: 100px; display: none; margin-top: 10px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;" />
                            </div>
                        </div>
                    </div>
                    <!-- Signature Photo -->
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('signature', __('lang_v1.signature') . ':') !!}
                            {!! Form::file('signature', ['id' => 'signature', 'accept' => 'image/*']) !!}
                            <div class="mt-2 text-center">
                                <img id="signature_preview" src="" style="max-height: 100px; display: none; margin-top: 10px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;" />
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="clearfix"></div>
                @endif
                @if (
                    $type != 'supplier' &&
                        isset($customerSettings->add_more_mobile_numbers) &&
                        $customerSettings->add_more_mobile_numbers == 1 &&
                        (!isset($is_bank_customer) || !$is_bank_customer))
                    <div class="col-md-12 fi-gr @if ($type == 'customer' && !array_key_exists('add_more_mobile', $contact_fields)) hide @endif">
                        <hr />
                        {!! Form::checkbox('add_more_nos', 1, false, ['class' => 'add_more_nos add_more_mobile']) !!} {{ __('lang_v1.add_more_nos_for_sms') }} <br><br>
                        <table class="table table-bordered table-striped" style="width: 100%" id="numbers_table">
                            <thead>
                                <tr>
                                    <th>@lang('lang_v1.name')</th>
                                    <th>@lang('lang_v1.phone')</th>
                                    @foreach ($notifications as $key => $notification)
                                        <th>{{ $notification['name'] }}</th>
                                    @endforeach

                                    <th>*</th>
                                </tr>
                            </thead>

                            <tbody>

                                <tr>
                                    <td>
                                        {!! Form::text('phone_name[]', null, ['class' => 'form-control notification_fields']) !!}
                                    </td>
                                    <td>
                                        {!! Form::text('phone_number[]', null, ['class' => 'form-control notification_fields']) !!}
                                    </td>
                                    @foreach ($notifications as $key => $notification)
                                        <td class="text-center">
                                            {!! Form::checkbox($key . '[]', 1, false, ['class' => 'toggler', 'data-toggle_id' => 'base_unit_div']) !!}
                                        </td>
                                    @endforeach

                                    <td>
                                        <button type="button" id="add_number_row" class="btn btn-success">+</button>
                                    </td>
                                </tr>


                            </tbody>

                        </table>

                        <input type="hidden" id="notification_parameters" name="notification_parameters">

                    </div>
                @endif
                @if (isset($customerSettings->email) && $customerSettings->email == 1)
                    <div
                        class="col-md-3 fi-gr @if ($type == 'customer' && !array_key_exists('customer_email', $contact_fields)) hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_email', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('email', __('business.email') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-envelope"></i>
                                </span>
                                {!! Form::email('email', null, [
                                    'class' => 'form-control customer_email',
                                    'placeholder' => __('business.email'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endif
                <input type="hidden" name="is_modal" value="1" id="is_modal">

                @if (isset($customerSettings->mobile) && $customerSettings->mobile == 1)
                    @php
                        $mobileAttributes = [
                            'class' => 'form-control input_number customer_mobile',
                            'placeholder' => __('contact.mobile'),
                        ];

                        if ($type === 'customer') {
                            $mobileAttributes['required'] = true;
                        }
                    @endphp

                    <div
                        class="col-md-3 fi-gr 
            @if ($type == 'customer' && !array_key_exists('customer_mobile', $contact_fields)) hide @endif 
            @if ($type == 'supplier' && !array_key_exists('supplier_mobile', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('mobile', __('contact.mobile') . (isset($is_bank_customer) && $is_bank_customer ? ' (Enter with the country code):*' : ':*')) !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-mobile"></i>
                                </span>
                                {!! Form::text('mobile', null, $mobileAttributes) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @if ($type == 'supplier')
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('whatsapp_number', __('contact.whatsapp') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-phone"></i>
                                </span>
                                {!! Form::text('whatsapp_number', null, [
                                    'class' => 'form-control input_number',
                                    'placeholder' => __('contact.whatsapp'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @if (isset($customerSettings->alternate_contact_number) && $customerSettings->alternate_contact_number == 1)
                    <div
                        class="col-md-3 fi-gr @if ($type == 'customer' && !array_key_exists('customer_alternate_contact_number', $contact_fields)) hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_alternate_contact_number', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('alternate_number', __('contact.alternate_contact_number') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-phone"></i>
                                </span>
                                {!! Form::text('alternate_number', null, [
                                    'class' => 'form-control input_number customer_alternate_contact_number',
                                    'placeholder' => __('contact.alternate_contact_number'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @if (isset($customerSettings->landline) && $customerSettings->landline == 1)
                    <div
                        class="col-md-3 fi-gr @if ($type == 'customer' && !array_key_exists('customer_landline', $contact_fields)) hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_landline', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('landline', __('contact.landline') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-phone"></i>
                                </span>
                                {!! Form::text('landline', null, [
                                    'class' => 'form-control input_number customer_landline',
                                    'placeholder' => __('contact.landline'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @if (isset($customerSettings->assigned_to) && $customerSettings->assigned_to)
                    <div class="col-md-6 fi-gr @if ($type == 'customer' && !array_key_exists('assigned_to', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('assigned_to', __('lang_v1.assigned_to')) !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-user"></i>
                                </span>
                                {!! Form::select('assigned_to', $user_groups ?? [], null, ['class' => 'form-control select2 assigned_to']) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @if ($type != 'supplier' && isset($customerSettings->vehicle_no) && $customerSettings->vehicle_no == 1)
                    <div class="col-md-6 fi-gr @if ($type == 'customer' && !array_key_exists('vehicle_no', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('vehicle_no', 'Vehicle No') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-user"></i>
                                </span>
                                {!! Form::text('vehicle_no', null, [
                                    'class' => 'form-control vehicle_no',
                                    'placeholder' => 'Enter Vehicle Number',
                                ]) !!}
                            </div>
                        </div>
                    </div>

                    {{-- Modified by Engr. Alex -- task 7882: Issue 1 - Sub Customer dropdown with type & auto filter and plus icon --}}
                    <div class="col-md-6 fi-gr @if ($type == 'customer' && !array_key_exists('vehicle_no', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('sub_customer_id', 'Sub Customer') !!}
                            <div class="input-group">
                                <select name="sub_customer_id" id="sub_customer_id" class="form-control" style="width:100%;">
                                    @if(!empty($contact->sub_customer_id))
                                        <option value="{{ $contact->sub_customer_id }}" selected>
                                            {{ optional(\App\Contact::find($contact->sub_customer_id))->name }}
                                        </option>
                                    @endif
                                </select>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default btn-flat" id="add_sub_customer_btn" title="Add new sub-customer">
                                        <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Quick-add Sub Customer Modal --}}
                    <div class="modal fade" id="quick_add_sub_customer_modal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                    <h4 class="modal-title">Add New Sub Customer</h4>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Name <span class="text-danger">*</span></label>
                                        <input type="text" id="new_sub_customer_name" class="form-control" placeholder="Customer Name">
                                    </div>
                                    <div class="form-group">
                                        <label>Mobile</label>
                                        <input type="text" id="new_sub_customer_mobile" class="form-control" placeholder="Mobile Number">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" id="save_sub_customer_btn">Save & Select</button>
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @if (isset($customerSettings->address) && $customerSettings->address == 1)
                    <div class="col-md-12 fi-gr @if ($type == 'customer' && !array_key_exists('customer_address', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('address', __('contact.address') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-phone"></i>
                                </span>
                                {!! Form::text('address', null, [
                                    'class' => 'form-control customer_address',
                                    'placeholder' => __('contact.address'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endif

                @if (isset($customerSettings->address_line_2) && $customerSettings->address_line_2 == 1)
                    <div class="col-md-12 fi-gr @if ($type == 'customer' && !array_key_exists('address_line_2', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('address', __('contact.address_2') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-phone"></i>
                                </span>
                                {!! Form::text('address_2', null, [
                                    'class' => 'form-control address_line_2',
                                    'placeholder' => __('contact.address_2'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @if (isset($customerSettings->address_line_3) && $customerSettings->address_line_3 == 1)
                    <div class="col-md-12 fi-gr @if ($type == 'customer' && !array_key_exists('address_line_3', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('address_3', __('contact.address_3') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-phone"></i>
                                </span>
                                {!! Form::text('address_3', null, [
                                    'class' => 'form-control address_line_3',
                                    'placeholder' => __('contact.address_3'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @if (isset($customerSettings->city) && $customerSettings->city == 1)
                    <div
                        class="col-md-3 fi-gr  @if ($type == 'customer' && !array_key_exists('customer_city', $contact_fields)) hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_city', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('city', __('business.city') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-map-marker"></i>
                                </span>
                                {!! Form::text('city', null, ['class' => 'form-control customer_city', 'placeholder' => __('business.city')]) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @if (isset($customerSettings->state) && $customerSettings->state == 1)
                    <div
                        class="col-md-3 fi-gr  @if ($type == 'customer' && !array_key_exists('customer_state', $contact_fields)) hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_state', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('state', __('business.state') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-map-marker"></i>
                                </span>
                                {!! Form::text('state', null, ['class' => 'form-control customer_state', 'placeholder' => __('business.state')]) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @if (isset($customerSettings->country) && $customerSettings->country == 1)
                    <div
                        class="col-md-3 fi-gr @if ($type == 'customer' && !array_key_exists('customer_country', $contact_fields)) hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_country', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('country', __('business.country') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-globe"></i>
                                </span>
                                @php
                                    $countries = \DB::table('countries')->orderBy('country')->pluck('country', 'country')->toArray();
                                @endphp
                                {!! Form::select('country', $countries, null, [
                                    'class' => 'form-control select2 customer_country',
                                    'placeholder' => __('business.country'),
                                    'style' => 'width: 100%;',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endif
                @if ($type != 'supplier' && isset($customerSettings->landmark) && $customerSettings->landmark == 1)
                    <div
                        class="col-md-3 fi-gr @if ($type == 'customer' && !array_key_exists('customer_landmark', $contact_fields)) hide @endif @if ($type == 'supplier' && !array_key_exists('supplier_landmark', $contact_fields)) hide @endif">
                        <div class="form-group">
                            {!! Form::label('landmark', __('business.landmark') . ':') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-map-marker"></i>
                                </span>
                                {!! Form::text('landmark', null, [
                                    'class' => 'form-control customer_landmark',
                                    'placeholder' => __('business.landmark'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endif
                <div>

                    <div class="col-md-12 customer_fields">
                        @if (isset($customerSettings->passport_nic_no) && $customerSettings->passport_nic_no == 1)
                            <div class="col-md-4 fi-gr @if ($type == 'customer' && !array_key_exists('passport_nic_no', $contact_fields)) hide @endif">
                                <div class="form-group">
                                    {!! Form::label('nic_number', __('contact.passport_no')) !!}
                                    <div class="input-group">
                                        <span class="input-group-addon">
                                            <i class="fa fa-user"></i>
                                        </span>
                                        {!! Form::text('nic_number', null, [
                                            'class' => 'form-control nic_number passport_nic_no',
                                            'placeholder' => __('contact.passport_no'),
                                        ]) !!}
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if (isset($customerSettings->passport_nic_image) && $customerSettings->passport_nic_image == 1)
                            <div class="col-md-4 fi-gr @if ($type == 'customer' && !array_key_exists('passport_nic_image', $contact_fields)) hide @endif">
                                <div class="form-group">
                                    {!! Form::label('image', __('contact.passport_image')) !!}
                                    {!! Form::file('image', ['id' => 'image', 'accept' => 'image/*', 'class' => 'passport_nic_image']) !!}
                                </div>
                            </div>
                        @endif
                        @if (isset($customerSettings->signature) && $customerSettings->signature == 1)
                            <div class="col-md-4 fi-gr @if ($type == 'customer' && !array_key_exists('signature', $contact_fields)) hide @endif">
                                <div class="form-group">
                                    {!! Form::label('signature', __('contact.signature')) !!}
                                    {!! Form::file('signature', ['id' => 'signature', 'accept' => 'image/*', 'class' => 'signature']) !!}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            <input type="hidden" name="is_modal" value="1">
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                <button type="button" class="btn btn-default closing_contact_modal">@lang('messages.close')</button>
            </div>

            {!! Form::close() !!}

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
    <script>
        var LANG = {
            contact_id_already_exists: "{{ __('Contact ID already exists') }}",
        };
        $(document).ready(function() {
            $("#numbers_table").hide();

            // Modified by Engr. Alex -- task 7882: Issue 1 - Sub Customer select2 AJAX + quick-add
            $('#sub_customer_id').select2({
                placeholder: 'Search or select sub customer...',
                allowClear: true,
                ajax: {
                    url: '{{ route("contacts.get-sub-customers") }}',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { return { q: params.term }; },
                    processResults: function(data) { return { results: data.results }; },
                    cache: true
                },
                minimumInputLength: 0
            });

            // Open quick-add modal
            $('#add_sub_customer_btn').on('click', function() {
                $('#new_sub_customer_name').val('');
                $('#new_sub_customer_mobile').val('');
                $('#quick_add_sub_customer_modal').modal('show');
            });

            // Save new sub-customer and add to select2
            $('#save_sub_customer_btn').on('click', function() {
                var name = $('#new_sub_customer_name').val().trim();
                if (!name) { alert('Name is required.'); return; }

                $.ajax({
                    url: '{{ route("contacts.quick-add-sub-customer") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        name: name,
                        mobile: $('#new_sub_customer_mobile').val()
                    },
                    success: function(res) {
                        if (res.success) {
                            var option = new Option(res.text, res.id, true, true);
                            $('#sub_customer_id').append(option).trigger('change');
                            $('#quick_add_sub_customer_modal').modal('hide');
                            toastr.success('Sub customer added successfully.');
                        }
                    },
                    error: function() { toastr.error('Failed to add sub customer.'); }
                });
            });


            // Add row when add_number_row button is clicked

            $('#numbers_table').on('click', '#add_number_row', function() {
                var lastRow = $('#numbers_table tbody tr:last');
                var newRow = lastRow.clone(); // Clone the last row
                newRow.find('input[type="text"]').val(''); // Clear the input value
                lastRow.after(newRow); // Append the new row after the last row

                // Add remove button to the new row
                var removeButton = $('<button>', {
                    'type': 'button',
                    'class': 'btn btn-danger remove-number-row',
                    'text': '-'
                });
                newRow.find('td:last').html(removeButton); // Add the remove button to the last cell
            });

            // Remove row when remove-number-row button is clicked
            $('#numbers_table').on('click', '.remove-number-row', function() {
                $(this).closest('tr').remove(); // Remove the row
            });
            $(document).on('click', '.closing_contact_modal', function() {
                $('.contact_modal_recipient').modal('hide');
                $('.contact_modal').modal('hide');
            })
            $(document).on('change', '.add_more_nos', function() {
                if ($(this).is(':checked')) {

                    $("#numbers_table").show();
                    $("#notification_fields").attr('required', true);
                } else {

                    $("#numbers_table").hide();
                    $("#notification_fields").attr('required', false);
                }
            });

            $(document).on('change', '.sub_customer', function() {
                if ($(this).is(':checked')) {
                    $(".sub_customer_field").removeClass('hide');
                } else {
                    $(".sub_customer_field").addClass('hide');
                }
            });

            $(document).on('change', '.toggler', function() {
                var formData = [];

                $('#numbers_table tbody tr').each(function() {
                    var phoneNumber = $(this).find('input[name="phone_number[]"]')
                        .val(); // Get phone number value
                    var phoneName = $(this).find('input[name="phone_name[]"]')
                        .val(); // Get phone number value
                    var checkboxes = $(this).find('.toggler'); // Get checkboxes
                    var rowValues = {
                        'phone_number': phoneNumber,
                        'notifications': {},
                        'phone_name': phoneName
                    };

                    checkboxes.each(function() {
                        var checkboxName = $(this).attr('name').replace('[]',
                            ''); // Get the checkbox name
                        var checkboxValue = $(this).is(':checked') ? 1 :
                            0; // Determine checkbox value (1 if checked, 0 if not)
                        rowValues['notifications'][checkboxName] =
                            checkboxValue; // Add checkbox name and value to rowValues object
                    });

                    formData.push(rowValues); // Add rowValues to formData array
                });

                $("#notification_parameters").val(JSON.stringify(formData));
            });

            $('#transaction_date_contact').datepicker({
                autoclose: true,
                format: datepicker_date_format,
                todayHighlight: true
            });
            $(document).on('change', '#location', function() {
                event.preventDefault();
                $('body').find('.fi-gr').hide();
                var id = $(this).val();

                $.ajax({
                    method: 'GET',
                    url: '{{ url('location/customer-form') }}/' + id,
                    dataType: 'json',
                    success: function(result) {
                        $.each(result.data, function(index, value) {
                            $('body').find('.' + index).closest('.fi-gr').show();
                        })


                    },
                    error: function(xhr, status, error) {
                        toastr.error('An error occurred. Please try again.');
                    }
                });

            })
            // $('#cc_contact_add_form').submit(function (e) {
            //   e.preventDefault();
            //   var data = $(this).serialize();
            //   $(this).find('button[type="submit"]').attr('disabled', true);
            //   $.ajax({
            //       method: 'POST',
            //       url: $(this).attr('action'),
            //       dataType: 'json',
            //       data: data,
            //       success: function (result) {
            //           if (result.success == true) {
            //               var customerId = result.data.id; // Corrected from response to result
            //               var customerName = result.data.name; // Corrected from response to result
            //               $('#credit_sale_customer_id').append($('<option>', {
            //                   value: customerId,
            //                   text: customerName,
            //                   selected: true
            //               }));

            //               $('#credit_sale_customer_id').val(result.data.id).trigger('change');

            //               $('div.contact_modal').modal('hide');
            //               toastr.success(result.msg);

            //           } else {
            //               toastr.error(result.msg);
            //           }
            //       },
            //       error: function (xhr, status, error) {
            //           toastr.error('An error occurred. Please try again.');
            //       }
            //   });

            function reloadContactCustomerTablesAfterAdd() {
                // CONTACT_SUPPLIER_001: Reload both customer and supplier list DataTables after modal save.
                var table_ids = ['#contact_table', '#bank_contact_table', '#supplier_table', '#suppliers_table'];

                table_ids.forEach(function(table_id) {
                    if ($(table_id).length && $.fn.DataTable && $.fn.DataTable.isDataTable(table_id)) {
                        $(table_id).DataTable().ajax.reload(null, false);
                    }
                });

                var table_objects = [
                    window.contact_table,
                    window.supplier_table,
                    window.suppliers_table
                ];

                table_objects.forEach(function(table_obj) {
                    if (table_obj && table_obj.ajax && typeof table_obj.ajax.reload === 'function') {
                        table_obj.ajax.reload(null, false);
                    }
                });
            }

            $('form[id^="contact_add_form"], form[id^="quick_add_contact"], form[id^="cc_contact_add_form"]').each(
                function() {
                    $(this).validate({
                        rules: {
                            contact_id: {
                                remote: {
                                    url: '/contacts/check-contact-id',
                                    type: 'post',
                                    data: {
                                        contact_id: function() {
                                            return $('#contact_id').val();
                                        },
                                        ignore_current: function() {
                                            return $('#contact_id').data('saved') || '';
                                        }
                                    }
                                }
                            },
                            nic_number: {
                                required: function(element) {
                                    return $('#passport_number').length > 0 && $('#passport_number').val().trim() === '';
                                },
                                nicFormat: true
                            },
                            passport_number: {
                                required: function(element) {
                                    return $('#nic_number').length > 0 && $('#nic_number').val().trim() === '';
                                },
                                passportFormat: true
                            }
                        },
                        messages: {
                            contact_id: {
                                remote: LANG.contact_id_already_exists
                            }
                        },
                        submitHandler: function(form) {
                            var $form = $(form);
                            var $submit_button = $form.find('button[type="submit"], input[type="submit"]');
                            $submit_button.prop('disabled', true);

                            $.ajax({
                                method: 'POST',
                                url: $form.attr('action'),
                                data: new FormData(form),
                                contentType: false,
                                processData: false,
                                cache: false,
                                dataType: 'json',
                                success: function(result) {
                                    if (result && result.success) {
                                        toastr.success(result.msg || 'Contact created successfully');

                                        var handled = false;
                                        $(document).trigger('contact.quick_add.success', [result, function() {
                                            handled = true;
                                        }]);

                                        // Hide all contact modals used by Customers/Contacts/Add Customer.
                                        $form.closest('.modal').modal('hide');
                                        $('div.contact_modal, div.customer_modal, div.view_modal').modal('hide');

                                        form.reset();
                                        $form.validate().resetForm();

                                        if (result.data && $('select#customer_id').length) {
                                            var customerOption = new Option(result.data.name, result.data.id, true, true);
                                            $('select#customer_id').append(customerOption).trigger('change');
                                            handled = true;
                                        }

                                        if (result.data && $('select#supplier_id').length) {
                                            var supplierOption = new Option(result.data.name, result.data.id, true, true);
                                            $('select#supplier_id').append(supplierOption).trigger('change');
                                            handled = true;
                                        }

                                        // CUS-010: when adding from Contact/Customers list modal, reload the list
                                        // so the newly-added customer appears immediately without manual refresh.
                                        reloadContactCustomerTablesAfterAdd();

                                        if (!handled && result.redirect_url) {
                                            window.location.href = result.redirect_url;
                                        }
                                    } else {
                                        toastr.error((result && result.msg) ? result.msg : 'Contact ID already exists');
                                    }
                                },
                                error: function(xhr) {
                                    var msg = 'Something went wrong';
                                    if (xhr.responseJSON) {
                                        if (xhr.responseJSON.msg) {
                                            msg = xhr.responseJSON.msg;
                                        } else if (xhr.responseJSON.message) {
                                            msg = xhr.responseJSON.message;
                                        }
                                    }
                                    toastr.error(msg);
                                    console.error(xhr.responseText);
                                },
                                complete: function() {
                                    $submit_button.prop('disabled', false);
                                }
                            });
                        }

                    });
                });
        });

        // $('#contact_add_form').submit(function(e) {
        //     e.preventDefault();
        //     var data = $(this).serialize();
        //     $(this).find('button[type="submit"]').attr('disabled', true);
        //     $.ajax({
        //         method: 'POST',
        //         url: $(this).attr('action'),
        //         dataType: 'json',
        //         contentType: false,
        //         processData: false,
        //         cache: false,
        //         data: new FormData(this),
        //         success: function(result) {
        //             if (result.success) {
        //                 toastr.success(result.msg);
        //                 $('.passenger_contact_modal').modal('hide');
        //                 if (result.data) {
        //                     const select = $('#passenger_name_select');
        //                     select.append($('<option>', {
        //                         value: result.data.id,
        //                         text: result.data.name,
        //                         selected: true
        //                     }));
        //                     select.val(result.data.id).trigger('change');
        //                 }
        //             } else {
        //                 toastr.error(result.msg || 'Something went wrong');
        //                 console.error(result.error); // log detailed error in console
        //             }
        //         },
        //         error: function(xhr) {
        //             let msg = 'Something went wrong';
        //             if (xhr.responseJSON) {
        //                 if (xhr.responseJSON.msg) msg = xhr.responseJSON.msg;
        //                 else if (xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors)
        //                     .join('\n');
        //             }
        //             toastr.error(msg);
        //             console.error(xhr.responseText);
        //         }
        //     });

        // });

        // Use class or any container that wraps all modal forms

        // FileReader and Custom Validation methods for bank customers
        if (typeof $.validator !== 'undefined') {
            $.validator.addMethod("nicFormat", function(value, element) {
                return this.optional(element) || /^[A-Za-z0-9\-\s]{9,20}$/.test(value);
            }, "Please enter a valid NIC number (9-20 alphanumeric characters, hyphens, or spaces).");

            $.validator.addMethod("passportFormat", function(value, element) {
                return this.optional(element) || /^[A-Za-z0-9]{6,15}$/.test(value);
            }, "Please enter a valid Passport number (6-15 alphanumeric characters).");
        }

        function readURL(input, previewId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#' + previewId).attr('src', e.target.result).show();
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $(document).on('change', '#nic_image', function() {
            readURL(this, 'nic_image_preview');
        });
        $(document).on('change', '#passport_image', function() {
            readURL(this, 'passport_image_preview');
        });
        $(document).on('change', '#image', function() {
            readURL(this, 'image_preview');
        });
        $(document).on('change', '#signature', function() {
            readURL(this, 'signature_preview');
        });
    </script>

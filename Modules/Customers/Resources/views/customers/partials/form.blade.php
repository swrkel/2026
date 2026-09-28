@php
    $customer = $customer ?? null;
    $typeOptions = $typeOptions ?? ['customer' => 'Customer', 'both' => 'Both Supplier & Customer'];
    $statusOptions = $statusOptions ?? ['1' => 'Active', '0' => 'Inactive'];
    $groupOptions = $groupOptions ?? [];
    $businessLocations = $businessLocations ?? [];
    $customers = $customers ?? [];
    $userGroups = $userGroups ?? [];
    $payTermTypeOptions = $payTermTypeOptions ?? ['days' => 'Days', 'months' => 'Months'];
    $generatedPasscode = $generatedPasscode ?? old('customer_passcode', data_get($customer, 'customer_passcode'));
    $transactionDate = old('transaction_date', !empty(data_get($customer, 'transaction_date')) ? @format_date(data_get($customer, 'transaction_date')) : date('m/d/Y'));
    $notificationContactsRaw = data_get($customer, 'notification_contacts');
    if (is_string($notificationContactsRaw)) {
        $decodedNotificationContacts = json_decode($notificationContactsRaw, true);
        $notificationContactsDisplay = is_array($decodedNotificationContacts) ? implode(',', $decodedNotificationContacts) : (($notificationContactsRaw === 'null') ? '' : $notificationContactsRaw);
    } elseif (is_array($notificationContactsRaw)) {
        $notificationContactsDisplay = implode(',', $notificationContactsRaw);
    } else {
        $notificationContactsDisplay = '';
    }
@endphp

{{--
    MA-002 (S-622): the select2 dropdown opens BEHIND the modal.

    Your inspected element settled this. Its parent carried

        select2-container--below      select2 had positioned the panel
        select2-container--focus      and given it focus

    Those classes are added BY SELECT2 WHEN IT OPENS. So select2 was
    working the whole time and the panel was opening - you simply could not
    see it.

    select2 renders its panel on <body>. A Bootstrap modal sits at
    z-index 1050 with its backdrop at 1040, while the select2 panel
    defaults to about 1 - so it opens behind the modal every time.

    This is a CSS STACKING problem, not a JavaScript one, which is why
    every JavaScript change I made had no effect.

    CSS ONLY - there is no script here, so nothing can loop, freeze, or
    run at the wrong moment.
--}}
<style>
    /* Above the modal (1050) and its backdrop (1040). */
    .select2-container--open,
    .select2-container {
        z-index: 10060 !important;
    }

    /* The panel itself, wherever select2 attaches it. */
    .select2-container--open .select2-dropdown,
    .select2-dropdown {
        z-index: 10061 !important;
    }

    /* Keep it usable on a long list inside a scrolling modal. */
    .select2-results__options {
        max-height: 260px !important;
        overflow-y: auto !important;
    }
</style>

<div class="customers-contact-parity-form">
    <ul class="nav nav-tabs customers-form-tabs" role="tablist" aria-label="Customer form sections">
        <li class="active" role="presentation">
            <a href="#customers_basic_tab" role="tab" aria-controls="customers_basic_tab" aria-selected="true" data-customers-form-tab="customers_basic_tab"><i class="fa fa-user"></i> Basic Details</a>
        </li>
        <li role="presentation">
            <a href="#customers_address_tab" role="tab" aria-controls="customers_address_tab" aria-selected="false" data-customers-form-tab="customers_address_tab"><i class="fa fa-map-marker"></i> Address</a>
        </li>
        <li role="presentation">
            <a href="#customers_credit_tab" role="tab" aria-controls="customers_credit_tab" aria-selected="false" data-customers-form-tab="customers_credit_tab"><i class="fa fa-money"></i> Credit / Opening Balance</a>
        </li>
        <li role="presentation">
            <a href="#customers_notify_tab" role="tab" aria-controls="customers_notify_tab" aria-selected="false" data-customers-form-tab="customers_notify_tab"><i class="fa fa-bell"></i> Notifications</a>
        </li>
    </ul>

    <div class="tab-content customers-tab-content">
        <div class="tab-pane active" id="customers_basic_tab">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('type', 'Contact Type:*') !!}
                        <div class="input-group"><span class="input-group-addon"><i class="fa fa-user"></i></span>
                            {!! Form::select('type', $typeOptions, old('type', data_get($customer, 'type', 'customer')), ['class' => 'form-control select2', 'required']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('name', 'Customer Name:*') !!}
                        <div class="input-group"><span class="input-group-addon"><i class="fa fa-user"></i></span>
                            {!! Form::text('name', old('name', data_get($customer, 'name')), ['class' => 'form-control', 'required', 'placeholder' => 'Customer name']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('supplier_business_name', 'Business Name:') !!}
                        <div class="input-group"><span class="input-group-addon"><i class="fa fa-building"></i></span>
                            {!! Form::text('supplier_business_name', old('supplier_business_name', data_get($customer, 'supplier_business_name')), ['class' => 'form-control', 'placeholder' => 'Business name']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('contact_id', 'Customer Code:') !!}
                        <div class="input-group"><span class="input-group-addon"><i class="fa fa-id-badge"></i></span>
                            {!! Form::text('contact_id', old('contact_id', data_get($customer, 'contact_id')), ['class' => 'form-control', 'placeholder' => 'Auto if blank']) !!}
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('mobile', 'Mobile:') !!}
                        <div class="input-group"><span class="input-group-addon"><i class="fa fa-mobile"></i></span>
                            {!! Form::text('mobile', old('mobile', data_get($customer, 'mobile')), ['class' => 'form-control', 'placeholder' => 'Mobile']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('whatsapp_number', 'WhatsApp Number:') !!}
                        <div class="input-group"><span class="input-group-addon"><i class="fa fa-whatsapp"></i></span>
                            {!! Form::text('whatsapp_number', old('whatsapp_number', data_get($customer, 'whatsapp_number')), ['class' => 'form-control', 'placeholder' => 'WhatsApp number']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('alternate_number', 'Alternate Number:') !!}
                        <div class="input-group"><span class="input-group-addon"><i class="fa fa-phone"></i></span>
                            {!! Form::text('alternate_number', old('alternate_number', data_get($customer, 'alternate_number')), ['class' => 'form-control']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('landline', 'Landline:') !!}
                        <div class="input-group"><span class="input-group-addon"><i class="fa fa-phone-square"></i></span>
                            {!! Form::text('landline', old('landline', data_get($customer, 'landline')), ['class' => 'form-control']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('email', 'Email:') !!}
                        <div class="input-group"><span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                            {!! Form::email('email', old('email', data_get($customer, 'email')), ['class' => 'form-control']) !!}
                        </div>
                    </div>
                </div>

                {{--
                    Customer registration fields.

                    Date of birth is captured as separate Day and Month selects,
                    with the Year OPTIONAL. Most customers will not give a birth
                    year, but will give the day and month - which is all a
                    birthday greeting needs. Three fields let "not given" be
                    recorded as exactly that, rather than a made-up year that
                    later produces a wrong age.

                    A single date picker cannot express this: it always demands a
                    year, so the year ends up invented.
                --}}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('dob_day', 'Birthday - Day:') !!}
                        <select name="dob_day" id="dob_day" class="form-control">
                            <option value="">--</option>
                            @for($d = 1; $d <= 31; $d++)
                                <option value="{{ $d }}" {{ (string) old('dob_day', data_get($customer, 'dob_day')) === (string) $d ? 'selected' : '' }}>{{ $d }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('dob_month', 'Birthday - Month:') !!}
                        <select name="dob_month" id="dob_month" class="form-control">
                            <option value="">--</option>
                            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $monthName)
                                <option value="{{ $i + 1 }}" {{ (string) old('dob_month', data_get($customer, 'dob_month')) === (string) ($i + 1) ? 'selected' : '' }}>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('dob_year', 'Birth Year:') !!}
                        {!! Form::number('dob_year', old('dob_year', data_get($customer, 'dob_year')), [
                            'class' => 'form-control',
                            'min' => 1900,
                            'max' => date('Y'),
                            'placeholder' => 'Optional',
                        ]) !!}
                        <small class="text-muted">Leave blank if the customer prefers not to say.</small>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('customer_group_id', 'Customer Group:') !!}
                        {!! Form::select('customer_group_id', ['' => 'Please Select'] + $groupOptions, old('customer_group_id', data_get($customer, 'customer_group_id')), ['class' => 'form-control select2']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('business_location_id', 'Business Location:') !!}
                        {!! Form::select('business_location_id', ['' => 'All / Head Office'] + $businessLocations, old('business_location_id', data_get($customer, 'business_location_id')), ['class' => 'form-control select2']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('assigned_to', 'Assigned To:') !!}
                        {!! Form::select('assigned_to', ['' => 'Please Select'] + $userGroups, old('assigned_to', data_get($customer, 'assigned_to')), ['class' => 'form-control select2']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('vehicle_no', 'Vehicle No:') !!}
                        <div class="input-group"><span class="input-group-addon"><i class="fa fa-car"></i></span>
                            {!! Form::text('vehicle_no', old('vehicle_no', data_get($customer, 'vehicle_no')), ['class' => 'form-control', 'placeholder' => 'Vehicle no']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('customer_passcode', 'Customer Passcode:*') !!}
                        <div class="input-group"><span class="input-group-addon"><i class="fa fa-key"></i></span>
                            {!! Form::text('customer_passcode', old('customer_passcode', $generatedPasscode), ['class' => 'form-control', 'required', 'maxlength' => 4, 'pattern' => '[0-9]{4}', 'placeholder' => '4 digits']) !!}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('active', 'Status:*') !!}
                        {!! Form::select('active', $statusOptions, old('active', data_get($customer, 'active', 1)), ['class' => 'form-control select2', 'required']) !!}
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane" id="customers_address_tab">
            <div class="row">
                <div class="col-md-6"><div class="form-group">{!! Form::label('address_line_1', 'Address Line 1:') !!}{!! Form::text('address_line_1', old('address_line_1', data_get($customer, 'address_line_1')), ['class' => 'form-control']) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('address_line_2', 'Address Line 2:') !!}{!! Form::text('address_line_2', old('address_line_2', data_get($customer, 'address_line_2', data_get($customer, 'address_2'))), ['class' => 'form-control']) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('address_3', 'Address Line 3:') !!}{!! Form::text('address_3', old('address_3', data_get($customer, 'address_3')), ['class' => 'form-control']) !!}</div></div>
                <div class="col-md-6"><div class="form-group">{!! Form::label('landmark', 'Landmark:') !!}{!! Form::text('landmark', old('landmark', data_get($customer, 'landmark')), ['class' => 'form-control']) !!}</div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('city', 'City:') !!}{!! Form::text('city', old('city', data_get($customer, 'city')), ['class' => 'form-control']) !!}</div></div>

                {{-- Living District and Town. The table already has `city`, but
                     district is the larger administrative area with the town
                     inside it, so one cannot stand in for the other. --}}
                <div class="col-md-3"><div class="form-group">{!! Form::label('living_district', 'Living District:') !!}{!! Form::text('living_district', old('living_district', data_get($customer, 'living_district')), ['class' => 'form-control', 'placeholder' => 'District']) !!}</div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('town', 'Town:') !!}{!! Form::text('town', old('town', data_get($customer, 'town')), ['class' => 'form-control', 'placeholder' => 'Town']) !!}</div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('state', 'State:') !!}{!! Form::text('state', old('state', data_get($customer, 'state')), ['class' => 'form-control']) !!}</div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('country', 'Country:') !!}{!! Form::text('country', old('country', data_get($customer, 'country')), ['class' => 'form-control']) !!}</div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('zip_code', 'Zip Code:') !!}{!! Form::text('zip_code', old('zip_code', data_get($customer, 'zip_code')), ['class' => 'form-control']) !!}</div></div>
            </div>
        </div>

        <div class="tab-pane" id="customers_credit_tab">
            <div class="row">
                <div class="col-md-3"><div class="form-group">{!! Form::label('tax_number', 'Tax No:') !!}{!! Form::text('tax_number', old('tax_number', data_get($customer, 'tax_number')), ['class' => 'form-control']) !!}</div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('vat_number', 'VAT No:') !!}{!! Form::text('vat_number', old('vat_number', data_get($customer, 'vat_number')), ['class' => 'form-control']) !!}</div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('credit_limit', 'Credit Limit:') !!}{!! Form::text('credit_limit', old('credit_limit', data_get($customer, 'credit_limit')), ['class' => 'form-control input_number customers-amount-field']) !!}</div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('pay_term_number', 'Pay Term:') !!}{!! Form::number('pay_term_number', old('pay_term_number', data_get($customer, 'pay_term_number')), ['class' => 'form-control', 'min' => 0]) !!}</div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('pay_term_type', 'Pay Term Type:') !!}{!! Form::select('pay_term_type', ['' => 'Please Select'] + $payTermTypeOptions, old('pay_term_type', data_get($customer, 'pay_term_type')), ['class' => 'form-control select2']) !!}</div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('opening_balance', 'Opening Balance:') !!}{!! Form::text('opening_balance', old('opening_balance', data_get($customer, 'opening_balance')), ['class' => 'form-control input_number customers-amount-field']) !!}</div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('transaction_date', 'Transaction Date:*') !!}<div class="input-group"><span class="input-group-addon customers-open-datepicker" role="button" tabindex="0" aria-label="Open transaction date calendar"><i class="fa fa-calendar"></i></span>{!! Form::text('transaction_date', $transactionDate, ['class' => 'form-control customers-datepicker datepicker', 'required', 'autocomplete' => 'off']) !!}</div></div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('credit_notification', 'Credit Notification:') !!}{!! Form::select('credit_notification', ['' => 'None', 'settlement' => 'Settlement', 'customer_bill' => 'Customer Bill', 'pumper_dashboard' => 'Pumper Dashboard'], old('credit_notification', data_get($customer, 'credit_notification')), ['class' => 'form-control select2']) !!}</div></div>
                <div class="col-md-3"><div class="form-group">
                    {{-- MA-002 (S-622): new field, same shape as the one below. --}}
                    @if(\Schema::hasColumn('contacts', 'need_to_send_sms'))
                        {!! Form::label('need_to_send_sms', 'Need to Send SMS:') !!}
                        {!! Form::select('need_to_send_sms', ['0' => 'No', '1' => 'Yes'],
                            (string) old('need_to_send_sms', data_get($customer, 'need_to_send_sms', 1)),
                            ['class' => 'form-control']) !!}
                    @endif
                </div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('manual_bill_settlement', 'Manual Bill to Settlement:') !!}{!! Form::select('manual_bill_settlement', ['0' => 'No', '1' => 'Yes'], old('manual_bill_settlement', data_get($customer, 'manual_bill_settlement', 0)), ['class' => 'form-control select2']) !!}</div></div>
                <div class="col-md-3"><div class="form-group">{!! Form::label('sub_customer', 'Sub Customer:') !!}{!! Form::select('sub_customer', ['0' => 'No', '1' => 'Yes'], old('sub_customer', data_get($customer, 'sub_customer', 0)), ['class' => 'form-control select2 customers-sub-customer-switch']) !!}</div></div>
                <div class="col-md-9 customers-sub-customer-list" style="{{ old('sub_customer', data_get($customer, 'sub_customer', 0)) ? '' : 'display:none;' }}"><div class="form-group">{!! Form::label('sub_customers[]', 'Sub Customers:') !!}{!! Form::select('sub_customers[]', $customers, old('sub_customers', !empty(data_get($customer, 'sub_customers')) ? json_decode(data_get($customer, 'sub_customers'), true) : []), ['class' => 'form-control select2', 'multiple' => true]) !!}</div></div>
            </div>
        </div>

        <div class="tab-pane" id="customers_notify_tab">
            <div class="row">
                <div class="col-md-3"><div class="form-group">{!! Form::label('should_notify', 'Should Notify:') !!}{!! Form::select('should_notify', ['0' => 'No', '1' => 'Yes'], old('should_notify', data_get($customer, 'should_notify', 0)), ['class' => 'form-control select2']) !!}</div></div>
                <div class="col-md-9"><div class="form-group">{!! Form::label('notification_contacts', 'Additional Notification Numbers:') !!}{!! Form::textarea('notification_contacts', old('notification_contacts', $notificationContactsDisplay), ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Comma separated mobile numbers']) !!}<small class="text-muted">Same purpose as Contacts Customer notification numbers.</small></div></div>
            </div>
        </div>
    </div>
</div>

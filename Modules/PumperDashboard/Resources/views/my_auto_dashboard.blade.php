@extends('layouts.' . $layout)
@section('title', 'My Auto Dashboard')

<style>
    .my-auto-dashboard-shell {
        max-width: 1180px;
        margin: 0 auto;
        padding: 0 10px;
    }

    .my-auto-tile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, 258px);
        gap: 30px 34px;
        justify-content: center;
        margin-top: 36px;
    }

    .my-auto-action {
        width: 258px;
        height: 180px;
        border: 0;
        color: #fff !important;
        display: flex !important;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 0 24px;
        border-radius: 3px;
        text-decoration: none !important;
        white-space: normal;
        box-shadow: none;
        transition: transform 0.15s ease, opacity 0.15s ease;
    }

    .my-auto-action-label {
        display: block;
        width: 100%;
        text-align: center;
        font-size: 18px;
        font-weight: 400;
        line-height: 1.5;
        letter-spacing: 0;
    }

    .my-auto-action:hover,
    .my-auto-action:focus {
        color: #fff !important;
        opacity: 0.96;
        transform: translateY(-2px);
    }

    .my-auto-subscription-bar {
        border-top: 1px solid #e5e7eb;
        margin-top: 10px;
        padding: 15px 15px 5px;
    }

    .my-auto-subscription-meta {
        color: #666;
        font-size: 13px;
        margin-bottom: 10px;
    }

    @media (max-width: 1200px) {
        .my-auto-tile-grid {
            grid-template-columns: repeat(auto-fit, minmax(240px, 258px));
        }
    }

    @media (max-width: 768px) {
        .my-auto-action {
            width: 100%;
            height: 140px;
            padding: 0 18px;
        }

        .my-auto-action-label {
            font-size: 17px;
        }

        .my-auto-tile-grid {
            grid-template-columns: 1fr;
            gap: 16px;
            margin-top: 24px;
        }
    }
</style>

@section('content')
    <section class="content-header">
        <h1 style="float: left">{{ __('home.welcome_message', ['name' => \Auth::user()->first_name]) }}</h1>
        <h4 style="float: left; margin-top: 5px;">{{ \Carbon\Carbon::now()->format('m/d/Y') }}</h4>
        <h4 style="float: left; margin-top: 5px;">{{ \Carbon\Carbon::now()->format('H:i:s') }}</h4>
    </section>

    <section class="content no-print">
        <div class="clearfix"></div>
        <div class="row">
            <div class="col-md-12 text-center">
                <h2 style="font-weight: bold; color: brown; margin-top: 0;">My Auto Dashboard</h2>
            </div>
        </div>

        <div class="col-md-12">
            <div class="col-md-6"></div>
            <div class="col-md-6">
                <a href="{{ action('Auth\PumpOperatorLoginController@logout') }}" class="btn btn-flat btn-lg pull-right"
                    style="background-color: orange; color: #fff; margin-left: 5px; width: 20%; font-size:1.1vw">
                    @lang('pumperdashboard::lang.logout')
                </a>

                <a href="#" data-container=".pump_operator_modal"
                    data-href="{{ action('\Modules\\PumperDashboard\\Http\\Controllers\\PumpOperatorController@update_passcode') }}"
                    class="btn btn-flat btn-lg pull-right btn-primary btn-modal"
                    style="margin-left: 5px; width: 25%; font-size:1.1vw">
                    @lang('pumperdashboard::lang.update_passcode')
                </a>
            </div>
        </div>

        <div class="clearfix"></div>
        <br>

        <div class="my-auto-dashboard-shell">
            <div class="my-auto-tile-grid">
                <a href="#" id="open_register_my_auto_dashboard" class="btn btn-flat my-auto-action" style="background: #ff5a36;">
                    <span class="my-auto-action-label">Register My Auto</span>
                </a>
                <a href="#" id="open_add_subscription_dashboard" class="btn btn-flat my-auto-action" style="background: #c99310;">
                    <span class="my-auto-action-label">Add Subscription</span>
                </a>
                <a href="#" id="open_list_registration_dashboard" class="btn btn-flat my-auto-action" style="background: #347ee6;">
                    <span class="my-auto-action-label">List Registration</span>
                </a>
                <a href="#" id="open_subscription_details_dashboard" class="btn btn-flat my-auto-action" style="background: #6d48d7;">
                    <span class="my-auto-action-label">Subscription</span>
                </a>
                <a href="#" class="btn btn-flat my-auto-action my-auto-coming-soon" style="background: #1faa45;">
                    <span class="my-auto-action-label">Transactions</span>
                </a>
            </div>
        </div>

        <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="my_auto_register_modal_dashboard"
            role="dialog" tabindex="-1">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content" style="padding: 30px; overflow-y: auto; height: 80vh">
                    <p class="form-header">Register My Auto</p>
                    {!! Form::open([
                        'url' => route('business.postRegister'),
                        'method' => 'post',
                        'id' => 'my_auto_register_form_dashboard',
                        'files' => true,
                    ]) !!}
                    @include('business.partials.register_form')
                    <div class="my-auto-subscription-bar">
                        <div class="my-auto-subscription-meta">
                            Selected package:
                            <strong id="selected_my_auto_package_name_dashboard">No package selected</strong>
                        </div>
                        <div class="my-auto-subscription-meta">
                            Subscription cycle:
                            <strong id="selected_my_auto_subscription_cycle_dashboard">Not selected</strong>
                        </div>
                        <div class="my-auto-subscription-meta">
                            Amount to Auto load:
                            <strong id="selected_my_auto_amount_to_auto_load_dashboard">Not entered</strong>
                        </div>
                        <input type="hidden" name="subscription_cycle" id="subscription_cycle_dashboard" value="">
                        <input type="hidden" name="amount_to_auto_load" id="amount_to_auto_load_dashboard" value="">
                        <button type="button" class="btn btn-warning" id="open_my_auto_subscription_modal_dashboard">
                            Add Subscription
                        </button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>

        <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="my_auto_subscription_modal_dashboard"
            role="dialog" tabindex="-1">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Add Subscription</h4>
                        <div style="float: right; margin-right: 20px; font-weight: 600; color: #8b4513;">
                            <span id="my_auto_number_preview_dashboard">My Auto Number</span>
                        </div>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            {!! Form::label('my_auto_package_id_dashboard', __('superadmin::lang.subscription_packages') . ':*') !!}
                            {!! Form::select('my_auto_package_id_dashboard', $my_auto_packages, null, [
                                'class' => 'form-control',
                                'id' => 'my_auto_package_id_dashboard',
                                'placeholder' => __('messages.please_select'),
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::label('my_auto_subscription_cycle_dashboard', 'Subscription Cycle:*') !!}
                            {!! Form::select('my_auto_subscription_cycle_dashboard', [
                                'Daily' => 'Daily',
                                'Monthly' => 'Monthly',
                                'Biannually' => 'Biannually',
                                'Annually' => 'Annually',
                            ], null, [
                                'class' => 'form-control',
                                'id' => 'my_auto_subscription_cycle_dashboard',
                                'placeholder' => __('messages.please_select'),
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::label('my_auto_amount_to_auto_load_dashboard', 'Amount to Auto load:') !!}
                            {!! Form::text('my_auto_amount_to_auto_load_dashboard', null, [
                                'class' => 'form-control input_number',
                                'id' => 'my_auto_amount_to_auto_load_dashboard',
                                'placeholder' => 'Enter amount',
                            ]) !!}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" id="apply_my_auto_subscription_dashboard">Save</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    </div>
                </div>
            </div>
        </div>

        <div aria-hidden="true" aria-labelledby="exampleModalLongTitle" class="modal fade" id="my_auto_add_subscription_modal_dashboard"
            role="dialog" tabindex="-1">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    {!! Form::open([
                        'url' => route('pumper-dashboard.my-auto-subscriptions.startCheckout'),
                        'method' => 'post',
                        'id' => 'my_auto_add_subscription_form_dashboard',
                    ]) !!}
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Add Subscription</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            {!! Form::label('my_auto_business_id_dashboard', 'Select My Auto:*') !!}
                            {!! Form::select('business_id', $my_auto_business_options, null, [
                                'class' => 'form-control',
                                'id' => 'my_auto_business_id_dashboard',
                                'placeholder' => 'Select My Auto',
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::label('my_auto_package_id_existing_dashboard', __('superadmin::lang.subscription_packages') . ':*') !!}
                            {!! Form::select('package_id', $my_auto_packages, null, [
                                'class' => 'form-control',
                                'id' => 'my_auto_package_id_existing_dashboard',
                                'placeholder' => __('messages.please_select'),
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::label('my_auto_subscription_cycle_existing_dashboard', 'Subscription Cycle:*') !!}
                            {!! Form::select('subscription_cycle', [
                                'Daily' => 'Daily',
                                'Monthly' => 'Monthly',
                                'Biannually' => 'Biannually',
                                'Annually' => 'Annually',
                            ], null, [
                                'class' => 'form-control',
                                'id' => 'my_auto_subscription_cycle_existing_dashboard',
                                'placeholder' => __('messages.please_select'),
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                        <div class="form-group">
                            {!! Form::label('my_auto_amount_to_auto_load_existing_dashboard', 'Amount to Auto load:') !!}
                            {!! Form::text('amount_to_auto_load', null, [
                                'class' => 'form-control input_number',
                                'id' => 'my_auto_amount_to_auto_load_existing_dashboard',
                                'placeholder' => 'Enter amount',
                            ]) !!}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Proceed to Payment</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>

        <div class="modal fade" id="my_auto_list_registration_modal_dashboard" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">List Registration</h4>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date &amp; Time</th>
                                        <th>My Auto Number</th>
                                        <th>Package</th>
                                        <th>Subscription Amount</th>
                                        <th>Agent Name</th>
                                        <th>Subscription ends date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($my_auto_registrations as $registration)
                                        <tr>
                                            <td>{{ $registration['date_time'] }}</td>
                                            <td>{{ $registration['my_auto_number'] }}</td>
                                            <td>{{ $registration['package'] }}</td>
                                            <td>{{ number_format((float) $registration['subscription_amount'], 2) }}</td>
                                            <td>{{ $registration['agent_name'] }}</td>
                                            <td>{{ $registration['subscription_ends_date'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No registrations found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="my_auto_subscription_details_modal_dashboard" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Payment Details</h4>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Date &amp; Time</th>
                                        <th>My Auto No</th>
                                        <th>Package</th>
                                        <th>Subscription Amount</th>
                                        <th>Payment Method</th>
                                        <th>Currency</th>
                                        <th>Payment Reference No</th>
                                        <th>Subscription Cycle</th>
                                        <th>Subscription starts on</th>
                                        <th>Subscription ends on</th>
                                        <th>Payment Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($my_auto_subscription_rows as $subscription_row)
                                        <tr>
                                            <td>
                                                <button
                                                    type="button"
                                                    class="btn btn-xs btn-primary open-my-auto-subscription-edit"
                                                    data-id="{{ $subscription_row['id'] }}"
                                                    data-action="Edit"
                                                    data-date-time="{{ $subscription_row['date_time'] }}"
                                                    data-my-auto-number="{{ $subscription_row['my_auto_number'] }}"
                                                    data-package="{{ $subscription_row['package'] }}"
                                                    data-subscription-amount="{{ $subscription_row['subscription_amount'] }}"
                                                    data-payment-method="{{ $subscription_row['payment_method'] }}"
                                                    data-currency="{{ $subscription_row['currency'] }}"
                                                    data-payment-reference-no="{{ $subscription_row['payment_reference_no'] }}"
                                                    data-subscription-cycle="{{ $subscription_row['subscription_cycle'] }}"
                                                    data-subscription-starts-on="{{ $subscription_row['subscription_starts_on'] }}"
                                                    data-subscription-ends-on="{{ $subscription_row['subscription_ends_on'] }}"
                                                    data-subscription-starts-on-input="{{ $subscription_row['subscription_starts_on_input'] }}"
                                                    data-subscription-ends-on-input="{{ $subscription_row['subscription_ends_on_input'] }}"
                                                    data-payment-status="{{ $subscription_row['payment_status'] }}">
                                                    Edit
                                                </button>
                                            </td>
                                            <td>{{ $subscription_row['date_time'] }}</td>
                                            <td>{{ $subscription_row['my_auto_number'] }}</td>
                                            <td>{{ $subscription_row['package'] ?: '-' }}</td>
                                            <td>{{ number_format((float) $subscription_row['subscription_amount'], 2) }}</td>
                                            <td>{{ $subscription_row['payment_method'] }}</td>
                                            <td>{{ $subscription_row['currency'] }}</td>
                                            <td>{{ $subscription_row['payment_reference_no'] ?: '-' }}</td>
                                            <td>{{ $subscription_row['subscription_cycle'] ?: '-' }}</td>
                                            <td>{{ $subscription_row['subscription_starts_on'] ?: '-' }}</td>
                                            <td>{{ $subscription_row['subscription_ends_on'] ?: '-' }}</td>
                                            <td>{{ $subscription_row['payment_status'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="12" class="text-center">No subscription details found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="my_auto_subscription_edit_modal_dashboard" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    {!! Form::open([
                        'url' => '',
                        'method' => 'post',
                        'id' => 'my_auto_subscription_edit_form_dashboard',
                    ]) !!}
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title">Payment Details</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Action</label>
                            <input type="text" class="form-control" id="edit_action_dashboard" readonly>
                        </div>
                        <div class="form-group">
                            <label>Date &amp; Time</label>
                            <input type="text" class="form-control" id="edit_date_time_dashboard" readonly>
                        </div>
                        <div class="form-group">
                            <label>My Auto No</label>
                            <input type="text" class="form-control" id="edit_my_auto_number_dashboard" readonly>
                        </div>
                        <div class="form-group">
                            <label>Package</label>
                            <input type="text" class="form-control" id="edit_package_dashboard" readonly>
                        </div>
                        <div class="form-group">
                            <label>Subscription Amount</label>
                            <input type="text" class="form-control" id="edit_subscription_amount_dashboard" readonly>
                        </div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <input type="text" class="form-control" id="edit_payment_method_dashboard" readonly>
                        </div>
                        <div class="form-group">
                            <label>Currency</label>
                            <input type="text" class="form-control" id="edit_currency_dashboard" readonly>
                        </div>
                        <div class="form-group">
                            <label>Payment Status</label>
                            <input type="text" class="form-control" id="edit_payment_status_dashboard" readonly>
                        </div>
                        <div class="form-group">
                            <label id="edit_payment_reference_label_dashboard">Payment Reference No</label>
                            <input type="text" class="form-control" name="payment_reference_no" id="edit_payment_reference_no_dashboard">
                            <p class="help-block" id="edit_payment_reference_help_dashboard" style="margin-bottom: 0;"></p>
                        </div>
                        <div class="form-group">
                            <label>Subscription Cycle</label>
                            <select class="form-control" name="subscription_cycle" id="edit_subscription_cycle_dashboard">
                                <option value="">Please select</option>
                                <option value="Daily">Daily</option>
                                <option value="Monthly">Monthly</option>
                                <option value="Biannually">Biannually</option>
                                <option value="Annually">Annually</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subscription starts on</label>
                            <input type="date" class="form-control" name="subscription_starts_on" id="edit_subscription_starts_on_dashboard">
                        </div>
                        <div class="form-group">
                            <label>Subscription ends on</label>
                            <input type="date" class="form-control" name="subscription_ends_on" id="edit_subscription_ends_on_dashboard">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>

        <div class="modal fade pump_operator_modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    </section>
@endsection

@section('javascript')
<script>
    $(document).ready(function () {
        function toggleMyAutoModeForForm(formSelector, forceChecked) {
            var $form = $(formSelector);
            if (!$form.length) {
                return;
            }

            var $checkbox = $form.find('#is_my_auto');
            if (typeof forceChecked !== 'undefined') {
                $checkbox.prop('checked', forceChecked);
            }

            var isMyAuto = $checkbox.is(':checked');

            $form.find('.js-business-name-label').text(isMyAuto ? 'My Auto Number:' : '{{ __('business.business_name') }}:');
            $form.find('.js-mobile-label').text(isMyAuto ? 'Mobile No:*' : '{{ __('lang_v1.business_telephone') }}:*');
            $form.find('.js-alternate-label').text(isMyAuto ? 'Other Mobile Nos:' : '{{ __('business.alternate_number') }}:');
            $form.find('.js-alternate-help').toggleClass('my-auto-hidden', !isMyAuto);

            $form.find('.js-currency-wrap, .js-website-wrap, .js-show-for-customers-wrap, .js-business-categories-wrap, .js-business-type-wrap, .js-email-wrap')
                .toggleClass('my-auto-hidden', isMyAuto);

            $form.find('[name="currency_id"], [name="website"], [name="show_for_customers"], [name="business_categories[]"], [name="business_type_id"], #b_email')
                .prop('disabled', isMyAuto);

            if (isMyAuto) {
                $form.find('#show_for_customers').prop('checked', false);
                $form.find('.business_categories_div').addClass('hide');
                $form.find('#b_email').val('');
            }
        }

        function updateMyAutoNumberPreviewDashboard() {
            var number = $('#my_auto_register_form_dashboard').find('#b_name').val() || 'My Auto Number';
            $('#my_auto_number_preview_dashboard').text(number);
        }

        $('.select2_register').select2({
            width: '100%'
        });

        $('#my_auto_package_id_dashboard').select2({
            width: '100%',
            dropdownParent: $('#my_auto_subscription_modal_dashboard')
        });

        $('#my_auto_subscription_cycle_dashboard').select2({
            width: '100%',
            dropdownParent: $('#my_auto_subscription_modal_dashboard')
        });

        $('#my_auto_business_id_dashboard').select2({
            width: '100%',
            dropdownParent: $('#my_auto_add_subscription_modal_dashboard')
        });

        $('#my_auto_package_id_existing_dashboard').select2({
            width: '100%',
            dropdownParent: $('#my_auto_add_subscription_modal_dashboard')
        });

        $('#my_auto_subscription_cycle_existing_dashboard').select2({
            width: '100%',
            dropdownParent: $('#my_auto_add_subscription_modal_dashboard')
        });

        $('#open_register_my_auto_dashboard').on('click', function (e) {
            e.preventDefault();
            $('#my_auto_register_modal_dashboard').modal('show');
        });

        $('#my_auto_register_modal_dashboard').on('shown.bs.modal', function () {
            toggleMyAutoModeForForm('#my_auto_register_form_dashboard', true);
            updateMyAutoNumberPreviewDashboard();
        });

        $(document).on('change', '#my_auto_register_form_dashboard #is_my_auto', function() {
            toggleMyAutoModeForForm('#my_auto_register_form_dashboard');
        });

        $(document).on('keyup change', '#my_auto_register_form_dashboard #b_name', function () {
            updateMyAutoNumberPreviewDashboard();
        });

        $('#open_my_auto_subscription_modal_dashboard').on('click', function () {
            updateMyAutoNumberPreviewDashboard();
            @if ($my_auto_packages->isEmpty())
                toastr.error('No My Auto packages are available.');
                return;
            @endif
            $('#my_auto_subscription_modal_dashboard').modal('show');
        });

        $('#apply_my_auto_subscription_dashboard').on('click', function () {
            var packageId = $('#my_auto_package_id_dashboard').val();
            var packageName = $('#my_auto_package_id_dashboard option:selected').text();
            var subscriptionCycle = $('#my_auto_subscription_cycle_dashboard').val();
            var amountToAutoLoad = $('#my_auto_amount_to_auto_load_dashboard').val();

            if (!packageId) {
                toastr.error('Please select a package.');
                return;
            }

            if (!subscriptionCycle) {
                toastr.error('Please select subscription cycle.');
                return;
            }

            $('#my_auto_register_form_dashboard').find('.package_id').val(packageId);
            $('#subscription_cycle_dashboard').val(subscriptionCycle);
            $('#amount_to_auto_load_dashboard').val(amountToAutoLoad);
            $('#selected_my_auto_package_name_dashboard').text(packageName);
            $('#selected_my_auto_subscription_cycle_dashboard').text(subscriptionCycle);
            $('#selected_my_auto_amount_to_auto_load_dashboard').text(amountToAutoLoad || 'Not entered');
            $('#my_auto_subscription_modal_dashboard').modal('hide');
        });

        $('#open_list_registration_dashboard').on('click', function (e) {
            e.preventDefault();
            $('#my_auto_list_registration_modal_dashboard').modal('show');
        });

        $('#open_add_subscription_dashboard').on('click', function (e) {
            e.preventDefault();
            @if ($my_auto_business_options->isEmpty())
                toastr.error('No registered My Auto numbers are available yet.');
                return;
            @endif
            @if ($my_auto_packages->isEmpty())
                toastr.error('No My Auto packages are available.');
                return;
            @endif
            $('#my_auto_add_subscription_modal_dashboard').modal('show');
        });

        $('#my_auto_add_subscription_form_dashboard').on('submit', function (e) {
            if (!$('#my_auto_business_id_dashboard').val()) {
                e.preventDefault();
                toastr.error('Please select a My Auto number.');
                return;
            }

            if (!$('#my_auto_package_id_existing_dashboard').val()) {
                e.preventDefault();
                toastr.error('Please select a package.');
                return;
            }

            if (!$('#my_auto_subscription_cycle_existing_dashboard').val()) {
                e.preventDefault();
                toastr.error('Please select subscription cycle.');
            }
        });

        $('#open_subscription_details_dashboard').on('click', function (e) {
            e.preventDefault();
            $('#my_auto_subscription_details_modal_dashboard').modal('show');
        });

        $('.open-my-auto-subscription-edit').on('click', function () {
            var $button = $(this);
            var paymentMethod = $button.data('payment-method');
            var isOffline = paymentMethod === 'Offline';

            $('#edit_action_dashboard').val($button.data('action') || 'Edit');
            $('#edit_date_time_dashboard').val($button.data('date-time'));
            $('#edit_my_auto_number_dashboard').val($button.data('my-auto-number'));
            $('#edit_package_dashboard').val($button.data('package'));
            $('#edit_subscription_amount_dashboard').val($button.data('subscription-amount'));
            $('#edit_payment_method_dashboard').val(paymentMethod);
            $('#edit_currency_dashboard').val($button.data('currency'));
            $('#edit_payment_status_dashboard').val($button.data('payment-status'));
            $('#edit_payment_reference_no_dashboard').val($button.data('payment-reference-no'));
            $('#edit_payment_reference_label_dashboard').text(
                isOffline ? 'Payment Reference No (Manual Entry if Offline)' : 'Payment Reference No (Payment Gateway Reference)'
            );
            $('#edit_payment_reference_help_dashboard').text(
                isOffline
                    ? 'Enter the manually recorded reference number for the offline payment.'
                    : 'This is the payment gateway reference for the online payment.'
            );
            $('#edit_subscription_cycle_dashboard').val($button.data('subscription-cycle'));
            $('#edit_subscription_starts_on_dashboard').val($button.data('subscription-starts-on-input'));
            $('#edit_subscription_ends_on_dashboard').val($button.data('subscription-ends-on-input'));
            $('#my_auto_subscription_edit_form_dashboard').attr('action', '{{ url('pumper-dashboard/pump-operators/my-auto-subscriptions') }}/' + $button.data('id'));
            $('#my_auto_subscription_edit_modal_dashboard').modal('show');
        });

        $('.my-auto-coming-soon').on('click', function(e) {
            e.preventDefault();
            toastr.info('This button is not connected yet.');
        });
    });
</script>
@endsection

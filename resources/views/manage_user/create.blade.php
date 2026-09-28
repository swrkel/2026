@extends('layouts.app')

@section('title', __('user.add_user'))
@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <style>
        .iti--allow-dropdown {
            z-index: 21221222 !important;
        }

        .iti {
            width: 100%;
        }
    </style>
@endsection
@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('user.add_user')</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        {!! Form::open([
            'url' => action('ManageUserController@store'),
            'method' => 'post',
            'id' => 'user_add_form',
            'files' => true,
        ]) !!}
        <div class="row">
            <div class="col-md-12">
                @component('components.widget')
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="checkbox">
                                    <br />
                                    <label>
                                        {!! Form::checkbox('selected_employee', 1, false, ['class' => 'input-icheck', 'id' => 'selected_employee']) !!}
                                        {{ __('lang_v1.allow_selected_employee') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 hide selected_employees_div">
                            <div class="form-group">
                                {!! Form::label('selected_employee', __('lang_v1.select_employee') . ':') !!}
                                <div class="form-group">
                                    {!! Form::select('employee_id', $employees->pluck('name', 'id'), null, [
                                        'class' => 'form-control',
                                        'id' => 'employees-select',
                                    ]) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>

                    <div class="row">
                        <div class="col-md-1">
                            <div class="form-group">
                                {!! Form::label('surname', __('business.prefix') . ':') !!}
                                {!! Form::text('surname', null, ['class' => 'form-control', 'placeholder' => __('business.prefix_placeholder')]) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('first_name', __('business.first_name') . ':*') !!}
                                {!! Form::text('first_name', null, [
                                    'class' => 'form-control',
                                    'required',
                                    'placeholder' => __('business.first_name'),
                                    'id' => 'first-name',
                                ]) !!}

                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('last_name', __('business.last_name') . ':') !!}
                                {!! Form::text('last_name', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('business.last_name'),
                                    'id' => 'last-name',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <p class="text-danger" style="font-size: small"><strong>Required to Enter</strong></p>
                                {!! Form::label('mobile', __('business.mobile_number') . ':*') !!}
                                @php
                                    $input_phone_name = 'mobile';
                                @endphp
                                @include('components.phone_feild_component')
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('user_group_id', __('user_group.user_group') . ':') !!}
                                {!! Form::select('user_group_id', $user_groups, null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('messages.please_select'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('designation', __('user.designation') . ':*') !!}
                                {!! Form::text('designation', null, [
                                    'class' => 'form-control',
                                    'required',
                                    'placeholder' => __('user.designation'),
                                    'id' => 'designation',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('email', __('business.email') . ':*') !!}
                                {!! Form::text('email', null, ['class' => 'form-control', 'required', 'placeholder' => __('business.email')]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('username', __('business.username') . ':') !!}
                                @if (!empty($username_ext))
                                    <div class="input-group">
                                        {!! Form::text('username', null, ['class' => 'form-control', 'placeholder' => __('business.username')]) !!}
                                        <span class="input-group-addon">{{ $username_ext }}</span>
                                    </div>
                                    <p class="help-block" id="show_username"></p>
                                @else
                                    {!! Form::text('username', null, ['class' => 'form-control', 'placeholder' => __('business.username')]) !!}
                                @endif
                                <p class="help-block">@lang('lang_v1.username_help')</p>
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('password', __('business.password') . ':*') !!}
                                {!! Form::password('password', [
                                    'class' => 'form-control',
                                    'required',
                                    'placeholder' => __('business.password'),
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('confirm_password', __('business.confirm_password') . ':*') !!}
                                {!! Form::password('confirm_password', [
                                    'class' => 'form-control',
                                    'required',
                                    'placeholder' => __('business.confirm_password'),
                                ]) !!}
                            </div>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="row">
                        <div class="col-md-6">
                            {!! Form::label('language', __('lang_v1.language') . ':*') !!}
                            <select class="form-control" name="language">
                                @foreach (config('constants.langs') as $key => $val)
                                    <option value="{{ $key }}" @if ((empty(request()->lang) && config('app.locale') == $key) || request()->lang == $key) selected @endif>
                                        {{ $val['full_name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            {!! Form::label('profile_photo', __('business.profile_photo') . ':*') !!}
                            {!! Form::file('profile_photo', null, ['class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('is_active', 'active', true, ['class' => 'input-icheck status']) !!}
                                    {{ __('lang_v1.status_for_user') }}
                                </label>
                                @show_tooltip(__('lang_v1.tooltip_enable_user_active'))
                            </div>
                        </div>
                    </div>

                    @if ($property_module_permission)
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('is_property_user', 1, false, ['class' => 'input-icheck status']) !!}
                                        {{ __('lang_v1.is_property_user') }}
                                    </label>

                                </div>
                            </div>
                        </div>
                    @endif
                    <div class="col-md-4">
                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    {!! Form::checkbox('sales_agent', 1, false, ['class' => 'input-icheck status']) !!}
                                    {{ __('lang_v1.sales_agent') }}
                                </label>

                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>

            @if ($member_module_permission)
                <div class="col-md-12">
                    @component('components.widget', ['title' => __('lang_v1.roles_and_permissions')])
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('role', __('user.role') . ':*') !!}
                                @show_tooltip(__('lang_v1.admin_role_location_permission_help'))
                                {!! Form::select('role', $roles, null, ['class' => 'form-control select2']) !!}
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="col-md-3">
                            <h4>@lang('member::lang.access_balamandala') @show_tooltip(__('tooltip.access_locations_permission'))</h4>
                        </div>

                        <div class="col-md-3 check_group">
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('access_all_balamandalaya', 'access_all_balamandala', false, [
                                            'class' => 'input-icheck check_all',
                                        ]) !!} {{ __('member::lang.all_balamandala') }}
                                    </label>
                                </div>
                            </div>
                            @foreach ($bala_mandalaya_areas as $bala_mandalaya)
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            {!! Form::checkbox('bala_mandalaya_permissions[]', 'balamandalaya.' . $bala_mandalaya->id, false, [
                                                'class' => 'input-icheck',
                                            ]) !!} {{ $bala_mandalaya->balamandalaya }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="col-md-3 check_group">
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('access_all_gramaseva_vasama', 'access_all_gramaseva_vasam', false, [
                                            'class' => 'input-icheck check_all',
                                        ]) !!} {{ __('member::lang.all_gramaseva_vasam') }}
                                    </label>
                                </div>
                            </div>
                            @foreach ($gramasevaka_areas as $gramasevaka_area)
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            {!! Form::checkbox('gramaseva_vasama_permissions[]', 'gramaseva_vasama.' . $gramasevaka_area->id, false, [
                                                'class' => 'input-icheck',
                                            ]) !!} {{ $gramasevaka_area->gramaseva_vasama }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="col-md-3 check_group">
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        {!! Form::checkbox('access_all_member_group', 'access_all_member_group', false, [
                                            'class' => 'input-icheck check_all',
                                        ]) !!} {{ __('member::lang.all_member_group') }}
                                    </label>
                                </div>
                            </div>
                            @foreach ($member_groups as $member_group)
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            {!! Form::checkbox('member_group_permissions[]', 'member_group.' . $member_group->id, false, [
                                                'class' => 'input-icheck',
                                            ]) !!} {{ $member_group->member_group }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endcomponent
                </div>
            @else
                <div class="col-md-12">
                    @component('components.widget', ['title' => __('lang_v1.roles_and_permissions')])
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('role', __('user.role') . ':*') !!}
                                @show_tooltip(__('lang_v1.admin_role_location_permission_help'))
                                {!! Form::select('role', $roles, null, ['class' => 'form-control select2']) !!}
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="col-md-3">
                            <h4>@lang('role.access_locations') @show_tooltip(__('tooltip.access_locations_permission'))</h4>
                        </div>
                        <div class="col-md-9">
                            @foreach ($locations as $location)
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            {!! Form::checkbox('location_permissions[]', 'location.' . $location->id, false, ['class' => 'input-icheck']) !!} {{ $location->display_name ?? $location->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                            @if($locations->count() == 0)
                                <div class="col-md-12">
                                    <p class="text-warning">No business locations found. Please check your business locations setup.</p>
                                </div>
                            @endif
                        </div>
                        <div class="clearfix"></div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('user_store', __('store.assign_store'), '') !!}@show_tooltip(__('lang_v1.multiple_select'))
                                {!! Form::select('user_store[]', $store, false, ['class' => 'form-control', 'multiple']) !!}
                            </div>
                        </div>
                    @endcomponent
                </div>
            @endif
        </div>

        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['title' => __('sale.sells')])
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('cmmsn_percent', __('lang_v1.cmmsn_percent') . ':') !!}
                            @show_tooltip(__('lang_v1.commsn_percent_help'))
                            {!! Form::text('cmmsn_percent', null, [
                                'class' => 'form-control input_number',
                                'placeholder' => __('lang_v1.cmmsn_percent'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('max_sales_discount_percent', __('lang_v1.max_sales_discount_percent') . ':') !!}
                            @show_tooltip(__('lang_v1.max_sales_discount_percent_help'))
                            {!! Form::text('max_sales_discount_percent', null, [
                                'class' => 'form-control input_number',
                                'placeholder' => __('lang_v1.max_sales_discount_percent'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="clearfix"></div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <div class="checkbox">
                                <br />
                                <label>
                                    {!! Form::checkbox('selected_contacts', 1, false, ['class' => 'input-icheck', 'id' => 'selected_contacts']) !!}
                                    {{ __('lang_v1.allow_selected_contacts') }}
                                </label>
                                @show_tooltip(__('lang_v1.allow_selected_contacts_tooltip'))
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4 hide selected_contacts_div">
                        <div class="form-group">
                            {!! Form::label('selected_contacts', __('lang_v1.selected_contacts') . ':') !!}
                            <div class="form-group">
                                {!! Form::select('selected_contact_ids[]', $contacts, null, [
                                    'class' => 'form-control select2',
                                    'multiple',
                                    'style' => 'width: 100%;',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="col-md-4">
                    <div class="form-group">
                        <div class="checkbox">
                           <br />
                            <label>
                                {!! Form::checkbox('selected_locations', 1, false, ['class' => 'input-icheck', 'id' => 'selected_locations']) !!}
                                {{ __('lang_v1.allow_selected_locations') }}
                            </label>
                            @show_tooltip(__('lang_v1.allow_selected_locations_tooltip'))
                        </div>
                    </div>
                </div>
                <div class="col-sm-4 hide selected_locations_div">
                    <div class="form-group">
                        {!! Form::label('selected_locations', __('lang_v1.selected_locations') . ':') !!}
                        <div class="form-group">
                            {!! Form::select('selected_location_ids[]', $locations->pluck('display_name', 'id'), null, [
                                'class' => 'form-control select2',
                                'multiple',
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>


        @if (!$member_module_permission)
            @include('user.edit_profile_form_part')
        @endif

        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary pull-right"
                    id="submit_user_button">@lang('messages.save')</button>
            </div>
        </div>
        {!! Form::close() !!}
    @stop
    @section('javascript')
        <script>
            $(document).ready(function() {
                var $selectedEmployee = $('#selected_employee');
                var $employeesSelect = $('#employees-select');
                var $firstName = $('#first-name');
                var $lastName = $('#last-name');
                var $userDob = $('#user_dob');
                var $currentAddress = $('#current_address');
                $('#selected_contacts').on('ifChecked', function(event) {
                    $('div.selected_contacts_div').removeClass('hide');
                });
                $('#selected_contacts').on('ifUnchecked', function(event) {
                    $('div.selected_contacts_div').addClass('hide');
                });

                $('#selected_locations').on('ifChecked', function(event) {
                    $('div.selected_locations_div').removeClass('hide');
                    $('input[name="location_permissions[]"]').iCheck('uncheck');
                });
                $('#selected_locations').on('ifUnchecked', function(event) {
                    $('div.selected_locations_div').addClass('hide');
                });

                $selectedEmployee.on('ifChecked', function(event) {
                    $('div.selected_employees_div').removeClass('hide');
                });
                $selectedEmployee.on('ifUnchecked', function(event) {
                    $('div.selected_employees_div').addClass('hide');
                });
                $employeesSelect.change(function() {
                    var $employees = @json($employees);
                    var selectedEmployeeId = $employeesSelect.val();
                    var selectedEmployee = $employees.find(function(employee) {
                        return employee.id == selectedEmployeeId;
                    });

                    $firstName.val(selectedEmployee.name.split(' ')[0]);
                    $firstName.prop('readOnly', true);
                    $lastName.val(selectedEmployee.name.split(' ')[1]);
                    $lastName.prop('readOnly', true);
                    $userDob.val(selectedEmployee.dob);
                    $currentAddress.val(selectedEmployee.address);

                });
                $('#username').change(function() {
                    if ($('#show_username').length > 0) {
                        if ($(this).val().trim() != '') {
                            $('#show_username').html("{{ __('lang_v1.your_username_will_be') }}: <b>" + $(this)
                                .val() + "{{ $username_ext }}</b>");
                        } else {
                            $('#show_username').html('');
                        }
                    }
                });
            });

            $.validator.addMethod('noLeadingZero', function(value, element) {
                return this.optional(element) || !/^0/.test(value);
            });

            $('form#user_add_form').validate({
                ignore: ":hidden:not(#mobile_code):not(#mobile_phone):not(#mobile_country_selected)",
                rules: {
                    first_name: {
                        required: true,
                    },
                    designation: {
                        required: true,
                    },
                    email: {
                        email: true,
                        remote: {
                            url: "/business/register/check-email",
                            type: "post",
                            data: {
                                email: function() {
                                    return $("#email").val();
                                }
                            }
                        }
                    },
                    password: {
                        required: true,
                        minlength: 5
                    },
                    confirm_password: {
                        equalTo: "#password"
                    },
                    username: {
                        minlength: 5,
                        remote: {
                            url: "/business/register/check-username",
                            type: "post",
                            data: {
                                username: function() {
                                    return $("#username").val();
                                },
                                @if (!empty($username_ext))
                                    username_ext: "{{ $username_ext }}"
                                @endif
                            }
                        }
                    },
                    "mobile[country_selected]": {
                        required: true,
                        digits: true,
                        min: 1
                    },
                    "mobile[code]": {
                        required: true
                    },
                    "mobile[number]": {
                        required: true,
                        digits: true,
                        noLeadingZero: true
                    }
                },
                messages: {
                    password: {
                        minlength: 'Password should be minimum 5 characters',
                    },
                    confirm_password: {
                        equalTo: 'Should be same as password'
                    },
                    username: {
                        remote: 'Invalid username or User already exist'
                    },
                    email: {
                        remote: '{{ __('validation.unique', ['attribute' => __('business.email')]) }}'
                    },
                    "mobile[country_selected]": {
                        required: 'Please select the country.',
                        min: 'Please select the country.'
                    },
                    "mobile[code]": {
                        required: 'Please select the country.'
                    },
                    "mobile[number]": {
                        required: 'Mobile number is required.',
                        digits: 'Please enter a valid mobile number.',
                        noLeadingZero: 'Enter mobile number without starting 0.'
                    }
                }
            })
        </script>
    @endsection

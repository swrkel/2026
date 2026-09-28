<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('\Modules\Superadmin\Http\Controllers\AgentController@store'), 'method' => 'post', 'files' => true, 'id' => 'add_agent_form']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('superadmin::lang.agent_registration')</h4>
        </div>

        <div class="modal-body">
                        <div class="row">
                    <div class="form-group col-md-4">
                    {!! Form::label('date', __('superadmin::lang.date_and_time') . ':*') !!}
                    {!! Form::text('date', now()->format('Y-m-d H:i:s'), ['class' => 'form-control', 'readonly']) !!}
                    </div>
                    <div class="form-group col-md-4">
                    {!! Form::label('name', __('superadmin::lang.full_name') . ':*') !!}
                    {!! Form::text('name', null, ['class' => 'form-control', 'required']) !!}
                    </div>
                    <div class="form-group col-md-4">
                    {!! Form::label('agent_code', __('superadmin::lang.agent_code') . ':*') !!}
                    {!! Form::text('agent_code', $agent_code, ['class' => 'form-control', 'readonly']) !!}
                    </div>
            </div>
            <div class="row">
                    <div class="form-group col-md-4">
                    {!! Form::label('address', __('superadmin::lang.address') . ':') !!}
                    {!! Form::text('address', null, ['class' => 'form-control']) !!}
                    </div>
                    <div class="form-group col-md-4">
                    {!! Form::label('country_id', __('superadmin::lang.country') . ':*') !!}
                    <select name="country_id" id="agent_country_id" class="form-control" style="width:100%;" required>
                    <option value="">@lang('superadmin::lang.please_select')</option>
                    @foreach($countries as $country)
                    <option value="{{$country->id}}" data-country-code="{{$country->country_code}}">{{$country->country}}</option>
                    @endforeach
                    </select>
                    </div>
                    <div class="form-group col-md-4">
                    {!! Form::label('district_id', __('superadmin::lang.district') . ':*') !!}
                    <div class="input-group">
                    <select name="district_id" id="agent_district_id" class="form-control" style="width:100%;" required>
                    <option value="">@lang('superadmin::lang.please_select')</option>
                    @foreach($districts as $district)
                    <option value="{{$district->id}}">{{$district->name}}</option>
                    @endforeach
                    </select>
                    <span class="input-group-btn">
                    <button class="btn btn-primary" id="add_district_btn" type="button">+</button>
                    </span>
                    </div>
                    </div>
            </div>
            <div class="row">
                    <div class="form-group col-md-4">
                    {!! Form::label('city', __('superadmin::lang.city') . ':*') !!}
                    <div class="input-group">
                    <select name="city" id="agent_city" class="form-control" style="width:100%;" required>
                    <option value="">@lang('superadmin::lang.please_select')</option>
                    @foreach($cities as $city)
                    <option value="{{$city->name}}">{{$city->name}}</option>
                    @endforeach
                    </select>
                    <span class="input-group-btn">
                    <button class="btn btn-primary" id="add_city_btn" type="button">+</button>
                    </span>
                    </div>
                    </div>
                    <div class="form-group col-md-4">
                    {!! Form::label('mobile_number', __('superadmin::lang.mobile_no_1') . ':*') !!}
                    {!! Form::text('mobile_number', null, ['class' => 'form-control mobile-no', 'required']) !!}
                    </div>
                    <div class="form-group col-md-4">
                    {!! Form::label('mobile_no_2', __('superadmin::lang.mobile_no_2') . ':') !!}
                    {!! Form::text('mobile_no_2', null, ['class' => 'form-control mobile-no']) !!}
                    </div>
            </div>
            <div class="row">
                    <div class="form-group col-md-4">
                    {!! Form::label('mobile_no_3', __('superadmin::lang.mobile_no_3') . ':') !!}
                    {!! Form::text('mobile_no_3', null, ['class' => 'form-control mobile-no']) !!}
                    </div>
                    <div class="form-group col-md-4">
                    {!! Form::label('land_number', __('superadmin::lang.land_number') . ':') !!}
                    {!! Form::text('land_number', null, ['class' => 'form-control']) !!}
                    </div>
                    <div class="form-group col-md-4">
                    {!! Form::label('email', __('superadmin::lang.email') . ':') !!}
                    {!! Form::email('email', null, ['class' => 'form-control']) !!}
                    </div>
            </div>
            <div class="row">
                    <div class="form-group col-md-4">
                    {!! Form::label('username', __('superadmin::lang.username') . ':*') !!}
                    {!! Form::text('username', null, ['class' => 'form-control', 'required']) !!}
                    </div>
                    <div class="form-group col-md-4">
                    {!! Form::label('nic_number', __('superadmin::lang.nic_number') . ':*') !!}
                    {!! Form::text('nic_number', null, ['class' => 'form-control', 'required']) !!}
                    </div>
                    <div class="form-group col-md-4">
                    {!! Form::label('referral_code', __('superadmin::lang.referral_code') . ':*') !!}
                    <div class="input-group">
                    <select name="referral_code" id="agent_referral_code" class="form-control" style="width:100%;" required>
                    <option value="">@lang('superadmin::lang.please_select')</option>
                    @foreach($referral_codes as $code)
                    <option value="{{$code}}">{{$code}}</option>
                    @endforeach
                    <option value="{{$default_referral_code}}" selected>{{$default_referral_code}}</option>
                    </select>
                    <span class="input-group-btn">
                    <button class="btn btn-primary" id="new_referral_code_btn" type="button">+</button>
                    </span>
                    </div>
                    </div>
            </div>
            <div class="row">
                    <div class="form-group col-md-4">
                    {!! Form::label('bank_name', __('superadmin::lang.bank_name') . ':*') !!}
                    {!! Form::text('bank_name', null, ['class' => 'form-control', 'required']) !!}
                    </div>
                    <div class="form-group col-md-4">
                    {!! Form::label('account_number', __('superadmin::lang.account_number') . ':*') !!}
                    {!! Form::text('account_number', null, ['class' => 'form-control', 'required']) !!}
                    </div>
                    <div class="form-group col-md-4">
                    {!! Form::label('branch', __('superadmin::lang.branch') . ':*') !!}
                    {!! Form::text('branch', null, ['class' => 'form-control', 'required']) !!}
                    </div>
            </div>
            <div class="row">
                    <div class="form-group col-md-4">
                    {!! Form::label('nic_copy', __('superadmin::lang.upload_nic_image') . ':') !!}
                    {!! Form::file('nic_copy', ['class' => 'form-control']) !!}
                    </div>
                    <div class="form-group col-md-4">
                    {!! Form::label('agent_photo', __('superadmin::lang.upload_your_photo') . ':') !!}
                    {!! Form::file('agent_photo', ['class' => 'form-control']) !!}
                    </div>
            </div>

            <div class="alert alert-info">
                @lang('superadmin::lang.password_auto_generated')
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>

<script>
    setTimeout(function() {
        $('#agent_country_id, #agent_district_id, #agent_city, #agent_referral_code').select2({ 
            width: '100%',
            minimumResultsForSearch: 1 
        });
    }, 200);

    function getSelectedCountryCode() {
        var selected = $('#agent_country_id option:selected');
        var code = selected.data('country-code') || '';
        return code ? ('+' + String(code).toUpperCase()) : '';
    }

    function applyCountryCodeToMobiles() {
        var prefix = getSelectedCountryCode();
        if (!prefix) {
            return;
        }

        $('.mobile-no').each(function() {
            var currentVal = $.trim($(this).val());
            if (!currentVal) {
                $(this).val(prefix);
                return;
            }
            if (currentVal && currentVal.indexOf('+') !== 0) {
                $(this).val(prefix + currentVal);
            }
        });
    }

    $('#agent_country_id').on('change', function() {
        applyCountryCodeToMobiles();
        var countryId = $(this).val();
        $('#agent_district_id').empty().append('<option value="">' + @json(__('superadmin::lang.please_select')) + '</option>');
        if (!countryId) {
            return;
        }
        $.get("{{ url('superadmin/agents/get-districts') }}/" + countryId, function(response) {
            $.each(response, function(id, name) {
                $('#agent_district_id').append('<option value="' + id + '">' + name + '</option>');
            });
            $('#agent_district_id').select2({ width: '100%', minimumResultsForSearch: 1 });
        });
    });

    $('#agent_district_id').on('change', function() {
        var districtId = $(this).val();
        $('#agent_city').empty().append('<option value="">' + @json(__('superadmin::lang.please_select')) + '</option>');
        if (!districtId) {
            return;
        }
        $.get("{{ url('superadmin/agents/get-cities') }}/" + districtId, function(response) {
            $.each(response, function(value, text) {
                $('#agent_city').append('<option value="' + value + '">' + text + '</option>');
            });
            $('#agent_city').select2({ width: '100%', minimumResultsForSearch: 1 });
        });
    });

    $('#add_district_btn').on('click', function() {
        var countryId = $('#agent_country_id').val();
        if (!countryId) {
            toastr.error(@json(__('superadmin::lang.please_select_country_first')));
            return;
        }
        var districtName = prompt(@json(__('superadmin::lang.enter_new_district')));
        if (!districtName) {
            return;
        }
        $.post("{{ url('superadmin/agents/store-district') }}", {
            _token: "{{ csrf_token() }}",
            country_id: countryId,
            name: districtName
        }, function(response) {
            if (response.success) {
                var districtId = response.id;
                var districtName = String(response.name || '').trim();
                if (!districtId || !districtName) {
                    toastr.error(@json(__('messages.something_went_wrong')));
                    return;
                }

                if ($('#agent_district_id option[value="' + districtId + '"]').length === 0) {
                    $('#agent_district_id').append('<option value="' + districtId + '">' + districtName + '</option>');
                }
                $('#agent_district_id').val(districtId).trigger('change');
                toastr.success(@json(__('superadmin::lang.district_added')));
            }
        });
    });

    $('#add_city_btn').on('click', function() {
        var districtId = $('#agent_district_id').val();
        if (!districtId) {
            toastr.error(@json(__('superadmin::lang.please_select_district_first')));
            return;
        }
        var cityName = prompt(@json(__('superadmin::lang.enter_new_city')));
        if (!cityName) {
            return;
        }
        $.post("{{ url('superadmin/agents/store-city') }}", {
            _token: "{{ csrf_token() }}",
            district_id: districtId,
            name: cityName
        }, function(response) {
            if (response.success) {
                var city = String(response.name || '').trim();
                if (!city) {
                    toastr.error(@json(__('messages.something_went_wrong')));
                    return;
                }

                if ($('#agent_city option[value="' + city.replace(/"/g, '\\"') + '"]').length === 0) {
                    $('#agent_city').append('<option value="' + city + '">' + city + '</option>');
                }
                $('#agent_city').val(city).trigger('change');
                toastr.success(@json(__('superadmin::lang.city_added')));
            }
        });
    });

    $('#new_referral_code_btn').on('click', function() {
        $.get("{{ url('superadmin/agents/next-referral-code') }}", function(response) {
            if (!response || !response.success || !response.code) {
                toastr.error(@json(__('messages.something_went_wrong')));
                return;
            }

            var code = String(response.code).trim();
            if (!code) {
                toastr.error(@json(__('messages.something_went_wrong')));
                return;
            }

            if ($('#agent_referral_code option[value="' + code.replace(/"/g, '\\"') + '"]').length === 0) {
                $('#agent_referral_code').append('<option value="' + code + '">' + code + '</option>');
            }
            $('#agent_referral_code').val(code).trigger('change');
        }).fail(function() {
            toastr.error(@json(__('messages.something_went_wrong')));
        });
    });
</script>

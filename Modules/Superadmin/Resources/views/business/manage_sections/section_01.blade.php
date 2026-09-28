{{--
    MA-002: extracted from business/manage.blade.php, which was 14,602 lines.

    The lines below are BYTE-IDENTICAL to the original - nothing was rewritten,
    reindented or reordered. The parent file @includes this at exactly the same
    point, so the rendered page is unchanged.

    Only blocks whose own tags balance were moved. Three blocks in the original
    do not close their divs within their own boundaries, so they stay in the
    parent rather than risk moving markup that depends on what surrounds it.
--}}
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px">
                      <div class="card-header text-center">
                        <h4> @lang('superadmin::lang.business_name'): {{$business->name}}</h4>
                        <hr>
                      </div>
                      <div class="card-body">
                        <div class="row">
                                <div class="col-md-2">
                                    <div class="from-group">
                                        {!! Form::label('annual_fee_package', __('superadmin::lang.annual_fee_package'), ['class' =>
                                        'search_label']) !!}
                                        {!! Form::text('annual_fee_package', !empty($package_manage) ?
                                        number_format($package_manage->price, 2, '.', '') : null, ['class' => 'form-control', 'id'
                                        =>
                                        'annual_fee_package', 'placeholder' => __('superadmin::lang.annual_fee_package')]) !!}
                                    </div>
                                </div>
                                <input type="hidden" name="package_manage_id"
                                       value="{{!empty($package_manage) ? $package_manage->id : null}}">
                                <div class="col-sm-2">
                                    <div class="form-group">
                                        <label for="currency_id" class="search_label">Currency:</label>
                                        <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-money"></i>
                                    </span>
                                            {!! Form::select('currency_id', $currencies, !empty($package_manage) ?
                                            $package_manage->currency_id : null, ['class' => 'form-control
                                            select2','placeholder' => __('business.currency_placeholder'), 'required', 'id' =>
                                            'currency_id_manage']) !!}
                                        </div>
                                    </div>
                                </div>
                                
                                
                                <div class="col-sm-4">
                                    <div class="form-group">
                                        <label for="sa_permission_search_input">Search Section Headings:</label>
                                        @include('superadmin::layouts.partials.search_settings', ['section_heading_only' => true])
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label for="search_settings">Individual Package:</label>
                                    <select name="individual_package" id="individual_package" class="form-control ">
                                        <option value="">Please Select</option>
                                        <option value="yes">Yes</option>
                                        <option value="No">No</option>
                                    </select>
                                </div>
                                <div class="col-sm-2">
                                    <label for=""></label>
                                    <div class="checkbox mt-10">
                                         <label>
                                             {!! Form::checkbox('opt_verification', 1,$business->owner->setting->opt_verification_enabled ?? 0, [ 'class' => 'input-icheck-red','id' =>	'opt_verification']) !!} {{ __( 'superadmin::lang.OTP_verification_enabled' ) }} </label>
                                         </label>
                                     </div>
                                 </div>
    
                                
                            </div>

                        @if(!empty($package_permission_blueprint_active))
                            <div class="alert alert-info sa-package-blueprint-notice" style="margin:12px 0 18px;">
                                <i class="fa fa-cubes"></i>
                                <strong>Package-controlled permissions:</strong>
                                Only modules and features included in
                                <strong>{{ $package_permission_blueprint_name ?: 'the assigned package' }}</strong>
                                are shown below. Update the package once to apply the same setup consistently
                                to every business using it.
                            </div>
                        @endif

                        <div class="row">
                                
                                
                                <div class="col-sm-3 product_count">
                                    <div class="form-group">
                                        {!! Form::label('product_count', __('superadmin::lang.product_count').':') !!}
                                        {!! Form::number('product_count', !empty($manage_module_enable['product_count']) ? $manage_module_enable['product_count'] :
                                        $previous_package_data['product_count'], ['class' => 'form-control', 'required', 'min' =>
                                        0]) !!}
    
                                        <span class="help-block">
                                    @lang('superadmin::lang.infinite_help')
                                </span>
                                    </div>
                                </div>
                                <div class="col-sm-3 product_count">
                                    <div class="form-group">
                                        {!! Form::label('location_count', __('superadmin::lang.location_count').':') !!}
                                        {!! Form::number('location_count', !empty($manage_module_enable['location_count']) ? $manage_module_enable['location_count']
                                        : $previous_package_data['location_count'], ['class' => 'form-control', 'required', 'min' =>
                                        0]) !!}
    
                                        <span class="help-block">
                                    @lang('superadmin::lang.infinite_help')
                                </span>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="from-group">
                                        {!! Form::label('vat_effective_date', __('superadmin::lang.vat_effective_date'), ['class' =>
                                        'search_label']) !!}
                                        {!! Form::date('vat_effective_date', !empty($manage_module_enable['vat_effective_date']) ?
                                        $manage_module_enable['vat_effective_date'] : null, ['class' => 'form-control', 'id'
                                        =>
                                        'vat_effective_date', 'required', 'placeholder' => __('superadmin::lang.vat_effective_date')]) !!}
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="from-group">
                                        {!! Form::label('post_dated_cheques_effective_date', __('superadmin::lang.post_dated_cheques_effective_date'), ['class' =>
                                        'search_label']) !!}
                                        {!! Form::date('post_dated_cheques_effective_date', !empty($manage_module_enable['post_dated_cheques_effective_date']) ?
                                        $manage_module_enable['post_dated_cheques_effective_date'] : null, ['class' => 'form-control', 'id'
                                        =>
                                        'post_dated_cheques_effective_date', 'required', 'placeholder' => __('superadmin::lang.post_dated_cheques_effective_date')]) !!}
                                    </div>
                                </div>
                                
                                <div class="clearfix"></div>
                                
                                <h5>{{__('superadmin::lang.register_count')}}</h5>
                                
                               @foreach ($business_locations as $location)
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            {!! Form::label('register_count['.$location->id.']', $location->name.':') !!}
                                            {!! Form::number(
                                                'register_count[' . $location->id . ']',
                                                isset($manage_module_enable['register_count'][$location->id]) ? $manage_module_enable['register_count'][$location->id] : null,
                                                ['class' => 'form-control', 'required', 'min' => 0]
                                            ) !!}
                                            <span class="help-block">
                                                @lang('superadmin::lang.infinite_help')
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
  
                                <div class="col-md-3">
                                    <div class="from-group">
                                {!! Form::label('whatsapp_phone_no', __('superadmin::lang.whatsapp_phone_no'), ['class' =>
                                        'search_label']) !!}
                                        {!! Form::text('whatsapp_phone_no', !empty($manage_module_enable['whatsapp_phone_no']) ?
                                        $manage_module_enable['whatsapp_phone_no'] : null, ['class' => 'form-control', 'id'
                                        =>
                                        'whatsapp_phone_no', 'placeholder' => __('superadmin::lang.whatsapp_phone_no')]) !!} 
                                        </div>
                                        </div>
                                <div class="col-sm-3">
                                    <label for=""></label>
                                    <div class="checkbox  mt-10">
                                        <label>
                                            {!! Form::checkbox('day_end_enable', 1, $business->day_end_enable , [ 'class' => 'input-icheck-red ch_select','id' =>	'day_end_enable']) !!} {{ __( 'superadmin::lang.day_end' ) }} </label>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <label for=""></label>
                                    <div class="checkbox mt-10">
                                         <label>
                                             {!! Form::checkbox('re_captcha_enabled', 1,$business->owner->setting->re_captcha_enabled ?? 0, [ 'class' => 'input-icheck-red','id' =>	're_captcha_enabled']) !!} {{ __( 'superadmin::lang.ReCAPTCHA_enabled_for_business' ) }} </label>
                                         </label>
                                     </div>
                                 </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {!! Form::label('max_disk_size', __('Disk Size')) !!}
                                        {!! Form::number('max_disk_size', $effective_disk_size, [
                                            'class' => 'form-control',
                                            'min' => 0,
                                            'step' => '0.01',
                                            'placeholder' => __('Disk Size (MB)')
                                        ]) !!}
                                        <span class="help-block">Disk Size (MB)</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        {!! Form::label('business_type_id', __('Business Type') . ':') !!}
                                        {!! Form::select('business_type_id',
                                            $business_types,
                                            !empty($business->business_type_id) ? $business->business_type_id : null,
                                            ['class' => 'form-control', 'placeholder' => __('Please Select')]
                                        ) !!}
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <label for=""></label>
                                 <div class="checkbox mt-10">
                                    <label>
                                        {!! Form::checkbox('sms_non_delivery', 1, $business->sms_non_delivery ? true : false, [ 'class' => 'input-icheck-red ch_select', 'id' => 'sms_non_delivery']) !!}
                                        {{ __( 'superadmin::lang.sms_non_delivery' ) }}
                                    </label>
                                </div>

                                </div>
                                <!-- <div class="col-sm-3">
                                    <label for=""></label>
                                 <div class="checkbox mt-10">
                                    <label>
                                        {!! Form::checkbox('pos_80_mm', 1, $business->pos_80_mm ? true : false, [ 'class' => 'input-icheck-red ch_select', 'id' => 'pos_80_mm']) !!}
                                        {{ __( 'POS 80 mm' ) }}
                                    </label>
                                </div>

                                </div> -->

                                 <div class="col-sm-3">
                                    <label for=""></label>
                                    <div class="checkbox  mt-10">
                                        <label>
                                            {!! Form::checkbox('pos_80_mm', 1, $business->pos_80_mm , [ 'class' => 'input-icheck-red ch_select','id' =>	'pos_80_mm']) !!} {{ __( 'POS 80 mm' ) }} </label>
                                        </label>
                                    </div>
                                </div>
                                {{---<div class="form-group">
                                    <div class="checkbox">
                                        {!! Form::checkbox('select_all', 1, false, ['class' => 'input-icheck-red select_all',])
                                        !!}{{__('superadmin::lang.select_all')}}
                                    </div>
                                </div> --}}
                            </div>
                      </div>
                      
                    </div>

                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px">
                        <div class="card-header text-center">
                            <h4>@lang('superadmin::lang.super_admin_settings')</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                        <div class="row">
                            {{-- Super Admin Name --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('super_admin_name', __('superadmin::lang.super_admin_name')) !!}
                                    {!! Form::text('super_admin_name', $module_activation_data['super_admin_name'] ?? env('SUPER_ADMIN_NAME'), ['class' => 'form-control']) !!}
                                </div>
                            </div>

                            {{-- Super Admin Address --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('super_admin_address', __('superadmin::lang.super_admin_address')) !!}
                                    {!! Form::text('super_admin_address', $module_activation_data['super_admin_address'] ?? env('SUPER_ADMIN_ADDRESS'), ['class' => 'form-control']) !!}
                                </div>
                            </div>

                            {{-- Super Admin Contact --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('super_admin_contact', __('superadmin::lang.super_admin_contact')) !!}
                                    {!! Form::text('super_admin_contact', $module_activation_data['super_admin_contact'] ?? env('SUPER_ADMIN_CONTACT'), ['class' => 'form-control']) !!}
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            {{-- VAT Registered --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('super_admin_vat_registered', __('superadmin::lang.vat_registered')) !!}
                                    {!! Form::select('super_admin_vat_registered', ['no' => 'No', 'yes' => 'Yes'], $module_activation_data['super_admin_vat_registered'] ?? env('SUPER_ADMIN_VAT_REGISTERED'), ['class' => 'form-control', 'id' => 'vat_registered']) !!}
                                </div>
                            </div>

                            {{-- VAT Number (conditionally visible) --}}
                            <div class="col-md-4" id="vat_number_section" style="{{ ($module_activation_data['super_admin_vat_registered'] ?? env('SUPER_ADMIN_VAT_REGISTERED')) == 'yes' ? '' : 'display:none;' }}">
                                <div class="form-group">
                                    {!! Form::label('super_admin_vat_number', __('superadmin::lang.vat_number')) !!}
                                    {!! Form::text('super_admin_vat_number', $module_activation_data['super_admin_vat_number'] ?? env('SUPER_ADMIN_VAT_NUMBER'), ['class' => 'form-control']) !!}
                                </div>
                            </div>

                            {{-- App Footer --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    {!! Form::label('app_footer', __('superadmin::lang.app_footer')) !!}
                                    {!! Form::text('app_footer', $module_activation_data['app_footer'] ?? env('APP_FOOTER'), ['class' => 'form-control']) !!}
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Invoice Footer --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('invoice_footer', __('superadmin::lang.invoice_footer')) !!}
                                    {!! Form::textarea('invoice_footer', $module_activation_data['invoice_footer'] ?? env('INVOICE_FOOTER'), ['class' => 'form-control', 'rows' => 3]) !!}
                                </div>
                            </div>

                            {{-- Report/Page Footer --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('report_footer', __('superadmin::lang.report_footer')) !!}
                                    {!! Form::textarea('report_footer', $module_activation_data['report_footer'] ?? env('REPORT_FOOTER'), ['class' => 'form-control', 'rows' => 3]) !!}
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Payment Gateways --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('payment_gateways', __('superadmin::lang.payment_gateways')) !!}
                                    {!! Form::select('payment_gateways', [
                                        'stripe' => 'Stripe',
                                        'paypal' => 'PayPal',
                                        'razorpay' => 'Razorpay',
                                        'pesapal' => 'Pesapal',
                                        'payhere' => 'PayHere',
                                        'online' => 'Pay Online',
                                        'offline' => 'Pay Offline',
                                    ], $module_activation_data['payment_gateways'] ?? env('PAYMENT_GATEWAYS'), ['class' => 'form-control', 'placeholder' => __('superadmin::lang.select_payment_gateway')]) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    </div>

                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4>Manage Businesses and Locations</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.manage_location')</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::hidden('enable_restaurant', 0) !!}
                                    {!! Form::checkbox('enable_restaurant', 1, !empty($manage_module_enable['enable_restaurant']), ['class' => 'input-icheck-red ch_select restaurant_module']) !!}
                                </div>
                            </div>

                            <div class="row" style="margin-top: 15px;">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Database</label>
                                        <select name="manage_databases[]" id="manage_databases" class="form-control select2" multiple="multiple" style="width: 100%;"
                                                data-options-url="{{ url('superadmin/business/get-manage-business-options') }}">
                                            @foreach($all_databases as $db)
                                                <option value="{{ $db }}" {{ in_array((string)$db, array_map('strval', $manage_module_enable['manage_databases'] ?? [])) ? 'selected' : '' }}>{{ $db }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Businesses</label>
                                        <select name="manage_businesses[]" id="manage_businesses" class="form-control select2" multiple="multiple" style="width: 100%;" disabled>
                                            @foreach($all_businesses as $bId => $bName)
                                                <option value="{{ $bId }}" data-database="{{ explode('::', $bId)[0] }}" {{ in_array((string)$bId, array_map('strval', $manage_module_enable['manage_businesses'] ?? [])) ? 'selected' : '' }}>{{ $bName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Business Locations</label>
                                        <select name="manage_business_locations[]" id="manage_business_locations" class="form-control select2" multiple="multiple" style="width: 100%;">
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row" id="selected_businesses_locations_display" style="margin-top: 10px; display: none;">
                                <div class="col-md-12">
                                    <strong>Selected:</strong>
                                    <div id="selected_tag_list" style="margin-top: 6px;"></div>
                                </div>
                            </div>
                            <div class="row" style="margin-top: 15px;">
                                <div class="col-md-12">
                                    {!! Form::hidden('doc_management_show_business_location', 0) !!}
                                    <div class="checkbox">
                                        <label>
                                            {!! Form::checkbox(
                                                'doc_management_show_business_location',
                                                1,
                                                !empty($manage_module_enable['doc_management_show_business_location']),
                                                ['class' => 'input-icheck-red ch_select']
                                            ) !!}
                                            Show Business Location dropdown in Doc Management / Upload / Add & Edit Pages
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <hr>

                            <div class="row restaurant_module_locations check_group">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __('role.all_locations') }}
                                        </label>
                                    </div>
                                </div>
                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox('module_permission_location[restaurant_module]['.$location->id.']', 1,
                                                    !empty($module_permission_locations_value['restaurant_module']->locations) ?
                                                    array_key_exists($location->id, $module_permission_locations_value['restaurant_module']->locations) : false,
                                                    ['class' => 'input-icheck-red ch_select']) !!}
                                                {{ $location->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <hr>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __('role.select_all') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.new_permission')</label>
                                </div>
                                <div class="col-md-2">
                                    {!! Form::hidden('new_permission', 0) !!}
                                    {!! Form::checkbox('new_permission', 1, !empty($manage_module_enable['new_permission']), ['class' => 'input-icheck-red ch_select']) !!}
                                </div>
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.enable_booking')</label>
                                </div>
                                <div class="col-md-2">
                                    {!! Form::hidden('enable_booking', 0) !!}
                                    {!! Form::checkbox('enable_booking', 1, !empty($manage_module_enable['enable_booking']), ['class' => 'input-icheck-red ch_select']) !!}
                                </div>
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.orders')</label>
                                </div>
                                <div class="col-md-2">
                                    {!! Form::hidden('orders', 0) !!}
                                    {!! Form::checkbox('orders', 1, !empty($manage_module_enable['orders']), ['class' => 'input-icheck-red ch_select']) !!}
                                </div>
                            </div>
                            <hr>
                        </div>
                    </div>

                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px">
                        <div class="card-header text-center">
                            <h4>@lang('superadmin::lang.daily_collection_settings')</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                           <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all_daily_collection input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                           <div id="daily_collection_body">
                           <div class="row">
                                <div class="col-md-3">
                                    <div class="checkbox-wrapper">
                                        {!! Form::hidden('daily_collection', 0) !!}
                                        {!! Form::checkbox('daily_collection', 1,
                                        !empty($manage_module_enable['daily_collection']) ? true : false, ['class' =>
                                        'input-icheck-red ch_select daily_collection_cb daily_collection_mode_cb', 'id' => 'daily_collection']) !!}
                                        <label class="search_label">@lang('superadmin::lang.daily_collection_sub_menu')</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="checkbox-wrapper">
                                        {!! Form::hidden('daily_collection_sw', 0) !!}
                                        {!! Form::checkbox('daily_collection_sw', 1,
                                        !empty($manage_module_enable['daily_collection_sw']) ? true : false, ['class' =>
                                        'input-icheck-red ch_select daily_collection_cb daily_collection_mode_cb', 'id' => 'daily_collection_sw']) !!}
                                        <label class="search_label">@lang('superadmin::lang.daily_collection_sw')</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="checkbox-wrapper">
                                        {!! Form::checkbox('same_order_no_daily_collection', 1,
                                        !empty($manage_module_enable['same_order_no_daily_collection']) ? true : false, ['class' =>
                                        'input-icheck-red ch_select daily_collection_cb']) !!}
                                        <label class="search_label">@lang('superadmin::lang.same_order_no')</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('daily_shift_page', 1,
                                    !empty($manage_module_enable['daily_shift_page']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select daily_collection_cb']) !!}<label
                                            class="search_label">@lang('superadmin::lang.daily_shift_page')</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('daily_cash_tab', 1,
                                    !empty($manage_module_enable['daily_cash_tab']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select daily_collection_cb']) !!}<label
                                            class="search_label">Daily Cash Tab</label>
                                </div>
                                <div class="col-md-2">
                                    {!! Form::checkbox('daily_collections_settings_setting', 1,
                                    !empty($manage_module_enable['daily_collections_settings_setting']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select daily_collection_cb']) !!}<label
                                            class="search_label">Settings</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    {!! Form::checkbox('daily_credit_sales', 1,
                                    !empty($manage_module_enable['daily_credit_sales']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select daily_collection_cb']) !!}<label
                                            class="search_label">Daily Credit Sale Tab</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('daily_cards', 1,
                                    !empty($manage_module_enable['daily_cards']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select daily_collection_cb']) !!}<label
                                            class="search_label">Daily Cards Tab</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('daily_shortage_excess', 1,
                                    !empty($manage_module_enable['daily_shortage_excess']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select daily_collection_cb']) !!}<label
                                            class="search_label">Daily Shortage Excess Tab</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('daily_cheques', 1,
                                    !empty($manage_module_enable['daily_cheques']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select daily_collection_cb']) !!}<label
                                            class="search_label">Daily Cheque Tab</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    {!! Form::checkbox('collection_summary', 1,
                                    !empty($manage_module_enable['collection_summary']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select daily_collection_cb']) !!}<label
                                            class="search_label">Collection Summary Tab</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('daily_collections_settings', 1,
                                    !empty($manage_module_enable['daily_collections_settings']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select daily_collection_cb']) !!}<label
                                            class="search_label">Daily Collection Settings Tab</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('daily_cash_status', 1,
                                    !empty($manage_module_enable['daily_cash_status']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select daily_collection_cb']) !!}<label
                                            class="search_label">Daily Cash Status Tab</label>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('daily_shift', 1,
                                    !empty($manage_module_enable['daily_shift']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select daily_collection_cb']) !!}<label
                                            class="search_label">Daily Shift Tab</label>
                                </div>
                            </div>
                           </div>{{-- end #daily_collection_body --}}
                        </div>   

                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px">
                      <div class="card-header text-center">
                        <h4> @lang('superadmin::lang.subscription_reminder'):</h4>
                        <hr>
                      </div>
                      <div class="card-body">
                        <div class="row">
                                <div class="col-md-3">
                                    <div class="from-group">
                                        {!! Form::label('expiry_date', __('superadmin::lang.expiry_date'), ['class' =>
                                        'search_label']) !!}
                                        {!! Form::text('expiry_date', !empty($subscription) ?
                                        @format_date($subscription->end_date) : null, ['class' => 'form-control', 'id'
                                        =>
                                        'expiry_date','disabled', 'placeholder' => __('superadmin::lang.expiry_date')]) !!}
                                    </div>
                                </div>
                                
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('first_reminder', __('superadmin::lang.first_reminder').':') !!}
                                        {!! Form::number('first_reminder', !empty($manage_module_enable['first_reminder']) ? $manage_module_enable['first_reminder']
                                        : 0, ['class' => 'form-control', 'required', 'min' =>
                                        0]) !!}
                                    </div>
                                </div>
                                
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('second_reminder', __('superadmin::lang.second_reminder').':') !!}
                                        {!! Form::number('second_reminder', !empty($manage_module_enable['second_reminder']) ? $manage_module_enable['second_reminder']
                                        : 0, ['class' => 'form-control', 'required', 'min' =>
                                        0]) !!}
                                    </div>
                                </div>
                                
                                <div class="col-sm-3">
                                    <div class="form-group">
                                        {!! Form::label('third_reminder', __('superadmin::lang.third_reminder').':') !!}
                                        {!! Form::number('third_reminder', !empty($manage_module_enable['third_reminder']) ? $manage_module_enable['third_reminder']
                                        : 0, ['class' => 'form-control', 'required', 'min' =>
                                        0]) !!}
                                    </div>
                                </div>
                                
                                
                                
                            </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <label>@lang('superadmin::lang.message_content')</label>
                                <textarea class="form-control" rows="5" name="message_content" required>{{!empty($manage_module_enable['message_content']) ? $manage_module_enable['message_content'] : ''}}</textarea>
                            </div>
                        </div>
                        <div class="row" id="amounts_row">
          
                        <div class="col-sm-12">
                              {!! Form::label('amount', __( 'superadmin::lang.phone_no' ) .":*") !!}
                          </div>
                          
                          @if(!empty($manage_module_enable['reminder_phone']))
                            @foreach(json_decode($manage_module_enable['reminder_phone'],true) as $key => $one_phone)
                                <div class="form-group col-sm-4 @if($key > 0) added-amount @endif">
                                    <div class="input-group">
                                      {!! Form::text('reminder_phone[]', $one_phone, ['class' => 'form-control', 'required','placeholder' => __(
                                        'superadmin::lang.phone_no'),]) !!}
                                        @if($key == 0)
                                            <span  class="input-group-addon bg-info" id="add_amount"> + </span>
                                        @else
                                            <span  class="input-group-addon bg-danger remove_amount"> - </span>
                                        @endif
                                      
                                    </div>
                                  </div>
                            @endforeach
                          @else
                            <div class="form-group col-sm-4">
                                <div class="input-group">
                                  {!! Form::text('reminder_phone[]', null, ['class' => 'form-control', 'required','placeholder' => __(
                                    'superadmin::lang.phone_no'),]) !!}
                                  <span  class="input-group-addon bg-info" id="add_amount"> + </span>
                                </div>
                              </div>
                          @endif
                          
                          
                          
                        </div>
                      </div>
                      
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px">
                      <div class="card-header text-center">
                        <h4> @lang('superadmin::lang.regenerate_vat'):</h4>
                        <hr>
                      </div>
                      <div class="card-body">
                        <div class="row">
                            <h5>@lang('superadmin::lang.single_transaction')</h5>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                {!! Form::checkbox('individual_sale', 1,
                                !empty($manage_module_enable['individual_sale']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.sales')</label>
                            </div> 
                            
                            <div class="col-md-4">
                                {!! Form::checkbox('individual_purchase', 1,
                                !empty($manage_module_enable['individual_purchase']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.purchases')</label>
                            </div> 
                            
                            <div class="col-md-4">
                                {!! Form::checkbox('individual_expense', 1,
                                !empty($manage_module_enable['individual_expense']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.expenses')</label>
                            </div> 
                            
                        </div>
                        <hr>
                        
                        <div class="row">
                            <h5>@lang('superadmin::lang.date_range')</h5>
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                {!! Form::checkbox('range_sale', 1,
                                !empty($manage_module_enable['range_sale']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.sales')</label>
                            </div> 
                            
                            <div class="col-md-4">
                                {!! Form::checkbox('range_purchase', 1,
                                !empty($manage_module_enable['range_purchase']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.purchases')</label>
                            </div> 
                            
                            <div class="col-md-4">
                                {!! Form::checkbox('range_expense', 1,
                                !empty($manage_module_enable['range_expense']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.expenses')</label>
                            </div> 
                            
                        </div>
                        
                      </div>
                      
                    </div>
                    
                
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px">
                      <div class="card-header text-center">
                        <h4> @lang('superadmin::lang.not_subscribed'):</h4>
                        <hr>
                      </div>
                      <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2">
                             {!! Form::hidden('ns_deposits_module', 0) !!}
                                {!! Form::checkbox('ns_deposits_module', 1,
                                !empty($manage_module_enable['ns_deposits_module']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.ns_deposit_module')</label>
                            </div> 
                            
                            <div class="col-md-2">
                            {!! Form::hidden('ns_asset_module', 0) !!}
                                {!! Form::checkbox('ns_asset_module', 1,
                                !empty($manage_module_enable['ns_asset_module']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.ns_asset_management')</label>
                            </div> 
                            
                            <div class="col-md-2">
                            {!! Form::hidden('ns_vat_module', 0) !!}
                                {!! Form::checkbox('ns_vat_module', 1,
                                !empty($manage_module_enable['ns_vat_module']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.ns_vat_module')</label>
                            </div> 
                            
                            <div class="col-md-2">
                            {!! Form::hidden('ns_discount_module', 0) !!}
                                {!! Form::checkbox('ns_discount_module', 1,
                                !empty($manage_module_enable['ns_discount_module']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.ns_discount_module')</label>
                            </div> 
                            
                            
                            
                            <div class="col-md-2">
                            {!! Form::hidden('ns_dsr_module', 0) !!}
                                {!! Form::checkbox('ns_dsr_module', 1,
                                !empty($manage_module_enable['ns_dsr_module']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.ns_dsr_module')</label>
                            </div> 
                             
                        </div>
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                            {!! Form::hidden('ns_cash', 0) !!}
                                {!! Form::checkbox('ns_cash', 1,
                                !empty($manage_module_enable['ns_cash']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.settlement')-@lang('petro::lang.cash')</label>
                            </div> 
                            
                            <div class="col-md-3">
                            {!! Form::hidden('ns_cash_deposit', 0) !!}
                                {!! Form::checkbox('ns_cash_deposit', 1,
                                !empty($manage_module_enable['ns_cash_deposit']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.settlement')-@lang('petro::lang.cash_deposit')</label>
                            </div> 
                            
                            <div class="col-md-3">
                            {!! Form::hidden('ns_cards', 0) !!}
                                {!! Form::checkbox('ns_cards', 1,
                                !empty($manage_module_enable['ns_cards']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.settlement')-@lang('petro::lang.cards')</label>
                            </div> 
                            
                            <div class="col-md-3">
                            {!! Form::hidden('ns_cheques', 0) !!}
                                {!! Form::checkbox('ns_cheques', 1,
                                !empty($manage_module_enable['ns_cheques']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.settlement')-@lang('petro::lang.cheques')</label>
                            </div> 
                            
                            <div class="col-md-3">
                            {!! Form::hidden('ns_expenses', 0) !!}
                                {!! Form::checkbox('ns_expenses', 1,
                                !empty($manage_module_enable['ns_expenses']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.settlement')-@lang('petro::lang.expneses')</label>
                            </div> 
                            
                            <div class="col-md-3">
                            {!! Form::hidden('ns_shortage', 0) !!}
                                {!! Form::checkbox('ns_shortage', 1,
                                !empty($manage_module_enable['ns_shortage']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.settlement')-@lang('petro::lang.shortage')</label>
                            </div> 
                            
                            <div class="col-md-3">
                            {!! Form::hidden('ns_excess', 0) !!}
                                {!! Form::checkbox('ns_excess', 1,
                                !empty($manage_module_enable['ns_excess']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.settlement')-@lang('petro::lang.excess')</label>
                            </div> 
                            
                            <div class="col-md-3">
                            {!! Form::hidden('ns_credit_sales', 0) !!}
                                {!! Form::checkbox('ns_credit_sales', 1,
                                !empty($manage_module_enable['ns_credit_sales']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.settlement')-@lang('petro::lang.credit_sales')</label>
                            </div> 
                            
                            <div class="col-md-3">
                            {!! Form::hidden('prefill_credit_sale_details', 0) !!}
                                {!! Form::checkbox('prefill_credit_sale_details', 1,
                                !empty($manage_module_enable['prefill_credit_sale_details']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('petro::lang.prefill_credit_sale_details')</label>
                            </div>
                            
                            <div class="col-md-3">
                            {!! Form::hidden('ns_loan_payments', 0) !!}
                                {!! Form::checkbox('ns_loan_payments', 1,
                                !empty($manage_module_enable['ns_loan_payments']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.settlement')-@lang('petro::lang.loan_payments')</label>
                            </div> 
                            
                            <div class="col-md-3">
                            {!! Form::hidden('ns_drawing_payments', 0) !!}
                                {!! Form::checkbox('ns_drawing_payments', 1,
                                !empty($manage_module_enable['ns_drawing_payments']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.settlement')-@lang('petro::lang.drawing_payments')</label>
                            </div> 
                            
                            <div class="col-md-3">
                            {!! Form::hidden('ns_customer_loans', 0) !!}
                                {!! Form::checkbox('ns_customer_loans', 1,
                                !empty($manage_module_enable['ns_customer_loans']) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('superadmin::lang.settlement')-@lang('petro::lang.customer_loans')</label>
                            </div>
                            
                            <div class="col-md-3">
                            {!! Form::hidden('ns_petro_sms_notifications', 0) !!}
                                {!! Form::checkbox('ns_petro_sms_notifications', 1,
                                (!empty($manage_module_enable['ns_petro_sms_notifications'])   || !array_key_exists('ns_petro_sms_notifications',$manage_module_enable)) ? true : false, ['class' => 'input-icheck-red ch_select']) !!}
                                <label class="search_label">@lang('petro::lang.petro_sms_notifications')</label>
                            </div>
                            
                        </div>
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-3">
                                {!! Form::label('ns_font_family', __( 'superadmin::lang.ns_font_family' ) .":") !!}
                                 {!! Form::select('ns_font_family', $fonts, !empty($manage_module_enable['ns_font_family']) ?
                                    $manage_module_enable['ns_font_family'] : null, [
                                                'class' => 'form-control select2',
                                                'style' => 'width:100%',
                                                'placeholder' => __('lang_v1.all'),
                                                'required'
                                            ])
                                        !!}  
                            </div>
                            
                            <div class="col-md-3">
                                {!! Form::label('ns_font_size', __( 'superadmin::lang.ns_font_size' ) .":") !!}
                                 {!! Form::text('ns_font_size', !empty($manage_module_enable['ns_font_size']) ?
                                    $manage_module_enable['ns_font_size'] : null, [
                                                'class' => 'form-control',
                                                'style' => 'width:100%',
                                                'placeholder' => __('superadmin::lang.ns_font_size')
                                            ])
                                        !!}  
                            </div>
                            <div class="form-group col-md-3">
                                {!! Form::label('ns_font_color', __( 'superadmin::lang.ns_font_color' )) !!}
                                {!! Form::text('ns-font-color-picker', null, ['class' => 'form-control ns-font-color-picker', 'id' => 'ns-font-color-picker', 'placeholder' => __( 'superadmin::lang.ns_font_color' )])
                                !!}
                                {!! Form::hidden('ns_font_color', !empty($manage_module_enable['ns_font_color']) ?
                                    $manage_module_enable['ns_font_color'] : null, ['id' => 'ns_font_color']) !!}
                             </div>
                             
                             <div class="form-group col-md-3">
                                {!! Form::label('ns_background_color', __( 'superadmin::lang.ns_background_color' )) !!}
                                {!! Form::text('ns-background-color-picker', null, ['class' => 'form-control ns-background-color-picker', 'id' => 'ns-background-color-picker', 'placeholder' => __( 'superadmin::lang.ns_background_color' )])
                                !!}
                                {!! Form::hidden('ns_background_color', !empty($manage_module_enable['ns_background_size']) ?
                                    $manage_module_enable['ns_background_size'] : null, ['id' => 'ns_background_color']) !!}
                             </div>
                             
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <label>@lang('superadmin::lang.message_content')</label>
                                <textarea class="form-control" rows="5" name="notsubscribed_message_content" required>{{!empty($manage_module_enable['notsubscribed_message_content']) ? $manage_module_enable['notsubscribed_message_content'] : ''}}</textarea>
                            </div>
                        </div>
                        
                      </div>
                      
                    </div>
                    
                    
                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4>Supplier Module</h4>
                            <hr>
                        </div>
                        <div class="card-body suppliers-module-permissions">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2"><b>Module Name</b></div>
                                <div class="col-md-1"><b>Enable</b></div>
                                <div class="col-md-1"><b>Status</b></div>
                                <div class="col-md-1"><b>Interval</b></div>
                                <div class="col-md-1"><b>Interval Length</b></div>
                                <div class="col-md-2"><b>Activated on</b></div>
                                <div class="col-md-2"><b>Expiry</b></div>
                                <div class="col-md-2 text-center"><h5><b>Module Price</b></h5></div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">Supplier Module</label>
                                </div>
                                <div class="col-md-1">
                                    {!! Form::hidden('suppliers_module', 0) !!}
                                    {!! Form::checkbox('suppliers_module', 1, !empty($manage_module_enable['suppliers_module']) ? true : false,
                                    ['class' => 'input-icheck-red ch_select suppliers_module suppliers-main-toggle', 'id' => 'suppliers_module_checkbox']) !!}
                                </div>
                                <div class="col-md-1">
                                    @php
                                        $supplierModuleIsEnabled = !empty($manage_module_enable['suppliers_module']);
                                        $supplierActivatedOn = !empty($module_activation_data['suppliers_activated_on']) ? $module_activation_data['suppliers_activated_on'] : date('Y-m-d');
                                        $supplierInterval = !empty($module_activation_data['suppliers_interval']) ? $module_activation_data['suppliers_interval'] : 'Years';
                                        $supplierLength = !empty($module_activation_data['suppliers_length']) ? (int) $module_activation_data['suppliers_length'] : 1;
                                        $supplierExpiryDate = !empty($module_activation_data['suppliers_expiry_date']) ? $module_activation_data['suppliers_expiry_date'] : null;
                                        if (empty($supplierExpiryDate)) {
                                            try {
                                                $supplierExpiryDateCarbon = \Carbon\Carbon::parse($supplierActivatedOn);
                                                if ($supplierInterval === 'Months') {
                                                    $supplierExpiryDateCarbon->addMonths(max(1, $supplierLength));
                                                } elseif ($supplierInterval === 'Days') {
                                                    $supplierExpiryDateCarbon->addDays(max(1, $supplierLength));
                                                } else {
                                                    $supplierExpiryDateCarbon->addYears(max(1, $supplierLength));
                                                }
                                                $supplierExpiryDate = $supplierExpiryDateCarbon->format('Y-m-d');
                                            } catch (\Throwable $supplierDateException) {
                                                $supplierExpiryDate = \Carbon\Carbon::parse(date('Y-m-d'))->addYears(1)->format('Y-m-d');
                                            }
                                        }
                                    @endphp
                                    @if($supplierModuleIsEnabled && (empty($supplierExpiryDate) || strtotime($supplierExpiryDate) >= time()))
                                        <span class="label label-pill label-primary">Active</span>
                                    @elseif($supplierModuleIsEnabled && !empty($supplierExpiryDate) && strtotime($supplierExpiryDate) < time())
                                        <span class="label label-pill label-danger">Expired</span>
                                    @else
                                        <span class="badge badge-danger">Not Set</span>
                                    @endif
                                </div>
                                <div class="col-md-1">
                                    {!! Form::select('suppliers_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['suppliers_interval']) ?
                                    $module_activation_data['suppliers_interval'] : 'Years', ['id' => 'suppliers_interval', 'class' => 'form-control act_interval', 'style' => 'width:100%']) !!}
                                </div>
                                <div class="col-md-1">
                                    {!! Form::number('suppliers_length', !empty($module_activation_data['suppliers_length']) ?
                                    $module_activation_data['suppliers_length'] : 1, ['class' => 'form-control act_length', 'id' => 'suppliers_length']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('suppliers_activated_on', !empty($module_activation_data['suppliers_activated_on']) ?
                                    $module_activation_data['suppliers_activated_on'] : date('Y-m-d'), ['class' => 'form-control date-picker', 'id' => 'suppliers_activated_on', 'readonly']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('suppliers_expiry_date', $supplierExpiryDate, ['class' => 'form-control date-picker', 'id' => 'suppliers_expiry_date', 'readonly']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::number('suppliers_module_value', !empty($module_enable_price['suppliers_module']) ?
                                    $module_enable_price['suppliers_module'] : 0, ['class' => 'form-control input_number', 'id' => 'suppliers_module_value']) !!}
                                </div>
                            </div>

                            <div class="row" style="margin-top: 15px;">
                                <div class="col-sm-12">
                                    <label>Supplier Quick Notification Type (SMS)</label>
                                    <select name="supplier_quick_notification_type[]" class="form-control select2" multiple style="width:100%;">
                                        @php
                                            $supplierQuickTypes = !empty($manage_module_enable['supplier_quick_notification_type']) ? (array) $manage_module_enable['supplier_quick_notification_type'] : [];
                                        @endphp
                                        <option value="supplier" {{ in_array('supplier', $supplierQuickTypes) ? 'selected' : '' }}>Supplier</option>
                                        <option value="all_purchase" {{ in_array('all_purchase', $supplierQuickTypes) ? 'selected' : '' }}>All Purchase</option>
                                        <option value="payment" {{ in_array('payment', $supplierQuickTypes) ? 'selected' : '' }}>Payment</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row" style="margin-top: 15px;">
                                <div class="col-sm-12">
                                    <button type="button" class="btn btn-primary btn-sm suppliers-select-all" style="margin-bottom: 10px;">Select All</button>
                                    <button type="button" class="btn btn-default btn-sm suppliers-clear-all" style="margin-bottom: 10px;">Clear All</button>
                                </div>
                                @php
                                    $supplierModulePages = [
                                        'suppliers_dashboard' => 'Dashboard',
                                        'suppliers_all_suppliers' => 'All Suppliers',
                                        'suppliers_add_supplier' => 'Add Supplier',
                                        'suppliers_contact_group' => 'Contact Group',
                                        'suppliers_import_contacts' => 'Import Contact Tab Page',
                                        'suppliers_product_mappings' => 'Set Supplier Map Products',
                                        'suppliers_payments' => 'Supplier Payments',
                                        'suppliers_purchase_history' => 'Supplier Purchase History',
                                        'suppliers_ledger' => 'Supplier Ledger',
                                        'suppliers_statement' => 'Supplier Statement',
                                        'suppliers_aging' => 'Supplier Aging',
                                        'suppliers_stock_report' => 'Supplier Stock Report',
                                        'suppliers_issue_payment_details' => 'Issued Payment Details',
                                        'suppliers_user_activity' => 'Contact User Activity',
                                        'suppliers_reports' => 'Supplier Reports',
                                        'suppliers_settings' => 'Settings',
                                        'contact_supplier' => 'Supplier Tab Page',
                                        'contact_group_supplier' => 'Contact Group Supplier Tab Page',
                                        'contact_list_supplier_map_products' => 'List Supplier Map Products',
                                        'contact_add_supplier_products' => 'Add Supplier Map Products',
                                    ];
                                @endphp
                                @foreach($supplierModulePages as $supplierKey => $supplierLabel)
                                    <div class="col-md-3 col-sm-4">
                                        <div class="checkbox">
                                            <label class="flex-label search_label">
                                                {!! Form::hidden($supplierKey, 0) !!}
                                                {!! Form::checkbox($supplierKey, 1, !empty($manage_module_enable[$supplierKey]) ? true : false, ['class' => 'input-icheck-red ch_select suppliers-page-checkbox', 'id' => $supplierKey]) !!}
                                                {{ $supplierLabel }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            var supplierCard = document.querySelector('.suppliers-module-permissions');
                            if (!supplierCard) { return; }
                            var selectAll = supplierCard.querySelector('.suppliers-select-all');
                            var clearAll = supplierCard.querySelector('.suppliers-clear-all');
                            var checkboxes = supplierCard.querySelectorAll('.suppliers-page-checkbox, .suppliers-main-toggle');
                            if (selectAll) {
                                selectAll.addEventListener('click', function () {
                                    checkboxes.forEach(function (box) {
                                        box.checked = true;
                                        if (window.jQuery) { window.jQuery(box).trigger('ifChecked').trigger('change'); }
                                    });
                                });
                            }
                            if (clearAll) {
                                clearAll.addEventListener('click', function () {
                                    checkboxes.forEach(function (box) {
                                        box.checked = false;
                                        if (window.jQuery) { window.jQuery(box).trigger('ifUnchecked').trigger('change'); }
                                    });
                                });
                            }
                        });
                    </script>

<div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.manufacturing_module')</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2  text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.manufacturing_module')</label>
                                </div>
                                
                                <div class="col-md-1">
                                    <label></label>
                                    {!! Form::hidden('mf_module', 0) !!}
                                    {!! Form::checkbox('mf_module', 1, !empty($manage_module_enable['mf_module']) ? true : false,
                                    ['class' => 'input-icheck-red ch_select mf_module', 'id' => 'mf_module_checkbox']) !!}
                                </div>
                                
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['mf_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['mf_expiry_date']))
                                        @if(strtotime($module_activation_data['mf_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['mf_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('mf_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['mf_interval']) ?
                                    $module_activation_data['mf_interval'] : 'Years', [
                                                'id' => 'mf_interval',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%'
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::number('mf_length', !empty($module_activation_data['mf_length']) ?
                                    $module_activation_data['mf_length'] : 1, ['class' => 'form-control act_length', 'id' => 'mf_length', 'min' => 1]) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('mf_activated_on', !empty($module_activation_data['mf_activated_on']) ?
                                    $module_activation_data['mf_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control act_on', 'id' => 'mf_activated_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('mf_expiry_date', !empty($module_activation_data['mf_expiry_date']) ?
                                    $module_activation_data['mf_expiry_date'] : null, ['class' => 'form-control act_expiry', 'id' => 'mf_expiry_date', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('mf_price', !empty($module_activation_data['mf_price']) ?
                                    $module_activation_data['mf_price'] : null, ['class' => 'form-control', 'id' => 'mf_price']) !!}
                                </div>
                            </div>
                            <div class="row mf_module_locations check_group">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox('module_permission_location[mf_module]['.$location->id.']', 1,
                                                !empty($module_permission_locations_value['mf_module']->locations) ?
                                                array_key_exists($location->id,
                                                $module_permission_locations_value['mf_module']->locations) : false, ['class' =>
                                                'input-icheck-red ch_select location_checkbox']) !!}
                                                {{$location->name}}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4>@lang('superadmin::lang.agent_module')</h4>
                            <hr>
                        </div>
                        <div class="card-body">
                            <div class="row bg-danger" style="margin-bottom: 10px;">
                                <div class="col-md-2">
                                    <b>Module Name</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Enable</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Status</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval</b>
                                </div>
                                <div class="col-md-1">
                                    <b>Interval Length</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Activated on</b>
                                </div>
                                <div class="col-md-2">
                                    <b>Expiry</b>
                                </div>
                                <div class="col-md-2 text-center">
                                    <h5><b>Module Price</b></h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <label class="search_label">@lang('superadmin::lang.agent_module')</label>
                                </div>

                                <div class="col-md-1">
                                    <label></label>
                                    {!! Form::hidden('agent_module', 0) !!}
                                    {!! Form::checkbox('agent_module', 1, !empty($manage_module_enable['agent_module']) ? true : false,
                                    ['class' => 'input-icheck-red ch_select agent_module', 'id' => 'agent_module_checkbox']) !!}
                                </div>

                                <div class="col-md-1">
                                    @if(empty($module_activation_data['agent_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['agent_expiry_date']))
                                        @if(strtotime($module_activation_data['agent_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif

                                        @if(strtotime($module_activation_data['agent_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif

                                    @endif
                                </div>

                                <div class="col-md-1">
                                    {!! Form::select('agent_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['agent_interval']) ?
                                    $module_activation_data['agent_interval'] : 'Years', [
                                                'id' => 'agent_interval',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%'
                                            ])
                                        !!}

                                </div>

                                <div class="col-md-1">
                                    {!! Form::number('agent_length', !empty($module_activation_data['agent_length']) ?
                                    $module_activation_data['agent_length'] : 1, ['class' => 'form-control act_length', 'id' => 'agent_length', 'min' => 1]) !!}
                                </div>

                                <div class="col-md-2">
                                    {!! Form::date('agent_activated_on', !empty($module_activation_data['agent_activated_on']) ?
                                    $module_activation_data['agent_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control act_on', 'id' => 'agent_activated_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('agent_expiry_date', !empty($module_activation_data['agent_expiry_date']) ?
                                    $module_activation_data['agent_expiry_date'] : null, ['class' => 'form-control act_expiry', 'id' => 'agent_expiry_date', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('agent_price', !empty($module_activation_data['agent_price']) ?
                                    $module_activation_data['agent_price'] : null, ['class' => 'form-control', 'id' => 'agent_price']) !!}
                                </div>
                            </div>
                            <div class="row agent_module_locations check_group">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox('module_permission_location[agent_module]['.$location->id.']', 1,
                                                !empty($module_permission_locations_value['agent_module']->locations) ?
                                                array_key_exists($location->id, $module_permission_locations_value['agent_module']->locations) : false,
                                                ['class' => 'input-icheck-red ch_select location_checkbox']) !!}
                                                {{$location->name}}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

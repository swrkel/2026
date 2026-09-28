{{--
    MA-002: extracted from business/manage.blade.php, which was 14,602 lines.

    The lines below are BYTE-IDENTICAL to the original - nothing was rewritten,
    reindented or reordered. The parent file @includes this at exactly the same
    point, so the rendered page is unchanged.

    Only blocks whose own tags balance were moved. Three blocks in the original
    do not close their divs within their own boundaries, so they stay in the
    parent rather than risk moving markup that depends on what surrounds it.
--}}
                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4>Petro PD</h4>
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
                                    <label class="search_label">Petro PD</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('petro_pd_module', 0) !!}
                                    {!! Form::checkbox('petro_pd_module', 1,
                                    !empty($manage_module_enable['petro_pd_module']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select', 'id' => 'petro_pd_module_checkbox']) !!}
                                </div>
                                <div class="col-md-1">
                                    @php
                                        $petroPdEnabledForManage = !empty($manage_module_enable['petro_pd_module'])
                                            || !empty($manage_module_enable['petropd_module'])
                                            || !empty($manage_module_enable['enable_petro_pd_module']);
                                        $petroPdExpiry = $module_activation_data['petro_pd_expiry_date'] ?? null;
                                        $petroPdExpiryTimestamp = !empty($petroPdExpiry) ? strtotime($petroPdExpiry) : false;
                                    @endphp
                                    @if($petroPdEnabledForManage && (empty($petroPdExpiry) || $petroPdExpiryTimestamp === false || $petroPdExpiryTimestamp >= strtotime(date('Y-m-d'))))
                                        <span class="label label-pill label-primary">active</span>
                                    @elseif(!empty($petroPdExpiry) && $petroPdExpiryTimestamp !== false && $petroPdExpiryTimestamp < strtotime(date('Y-m-d')))
                                        <span class="label label-pill label-danger">expired</span>
                                    @else
                                        <span class="badge badge-danger">disabled</span>
                                    @endif
                                </div>
                                <div class="col-md-1">
                                        {!! Form::select('petro_pd_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['petro_pd_interval']) ?
                                        $module_activation_data['petro_pd_interval'] : 'Years', [
                                                'id' => 'petro_pd_interval',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%'
                                            ])
                                            !!}
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::number('petro_pd_length', !empty($module_activation_data['petro_pd_length']) ?
                                        $module_activation_data['petro_pd_length'] : 1, ['class' => 'form-control act_length', 'id' => 'petro_pd_length', 'min' => 1]) !!}
                                    </div>
                                <div class="col-md-2">
                                    {!! Form::date('petro_pd_activated_on', !empty($module_activation_data['petro_pd_activated_on']) ?
                                    $module_activation_data['petro_pd_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control act_on', 'id' => 'petro_pd_activated_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('petro_pd_expiry_date', !empty($module_activation_data['petro_pd_expiry_date']) ?
                                    $module_activation_data['petro_pd_expiry_date'] : null, ['class' => 'form-control act_expiry', 'id' => 'petro_pd_expiry_date', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('petro_pd_price', !empty($module_activation_data['petro_pd_price']) ?
                                    $module_activation_data['petro_pd_price'] : null, ['class' => 'form-control', 'id' => 'petro_pd_price']) !!}
                                </div>
                            </div>
                            <div class="row petro_pd_module_locations">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                </div>
                                <div class="col-md-8">
                                </div>
                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox('module_permission_location[petro_pd_module]['.$location->id.']', 1,
                                                !empty($module_permission_locations_value['petro_pd_module']->locations) ?
                                                array_key_exists($location->id,
                                                $module_permission_locations_value['petro_pd_module']->locations) : false, ['class' =>
                                                'input-icheck-red ch_select location_checkbox']) !!}
                                                {{$location->name}}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="row check_group" style="margin-top: 10px;">
                                <div class="col-sm-12" style="margin-bottom: 5px;">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red"> {{ __('role.select_all') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('petro_pd_pd_settlement', 1,
                                                !empty($manage_module_enable['petro_pd_pd_settlement']) ? true : false,
                                                ['class' => 'input-icheck-red ch_select', 'id' => 'petro_pd_pd_settlement']) !!}
                                            PD Settlements
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('petro_pd_pd_operators', 1,
                                                !empty($manage_module_enable['petro_pd_pd_operators']) ? true : false,
                                                ['class' => 'input-icheck-red ch_select', 'id' => 'petro_pd_pd_operators']) !!}
                                            PD Operators
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('petro_pd_user_activity', 1,
                                                !empty($manage_module_enable['petro_pd_user_activity']) ? true : false,
                                                ['class' => 'input-icheck-red ch_select', 'id' => 'petro_pd_user_activity']) !!}
                                            User Activity - Petro PD
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('petro_pd_list_pd_settlement', 1,
                                                !empty($manage_module_enable['petro_pd_list_pd_settlement']) ? true : false,
                                                ['class' => 'input-icheck-red ch_select', 'id' => 'petro_pd_list_pd_settlement']) !!}
                                            List PD Settlements
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label class="flex-label search_label">
                                            {!! Form::checkbox('petro_pd_settings', 1,
                                                !empty($manage_module_enable['petro_pd_settings']) ? true : false,
                                                ['class' => 'input-icheck-red ch_select', 'id' => 'petro_pd_settings']) !!}
                                            Petro PD Settings
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- End Petro PD Module Section --}}

                    {{-- EV Charging Module Section --}}
                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4>EV Charging</h4>
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
                                    <label class="search_label">EV Charging</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('ev_charging_module', 0) !!}
                                    {!! Form::checkbox('ev_charging_module', 1,
                                    !empty($manage_module_enable['ev_charging_module']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select', 'id' => 'ev_charging_module_checkbox']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['ev_charging_expiry_date']))
                                        <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['ev_charging_expiry_date']))
                                        @if(strtotime($module_activation_data['ev_charging_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        @if(strtotime($module_activation_data['ev_charging_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                    @endif
                                </div>
                                <div class="col-md-1">
                                        {!! Form::select('ev_charging_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['ev_charging_interval']) ?
                                        $module_activation_data['ev_charging_interval'] : 'Years', [
                                                'id' => 'ev_charging_interval',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%'
                                            ])
                                            !!}
                             
                                     </div>
                                     
                                     <div class="col-md-1">
                                         {!! Form::number('ev_charging_length', !empty($module_activation_data['ev_charging_length']) ?
                                         $module_activation_data['ev_charging_length'] : 1, ['class' => 'form-control act_length', 'id' => 'ev_charging_length', 'min' => 1]) !!}
                                     </div>
                                 <div class="col-md-2">
                                     {!! Form::date('ev_charging_activated_on', !empty($module_activation_data['ev_charging_activated_on']) ?
                                     $module_activation_data['ev_charging_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control act_on', 'id' => 'ev_charging_activated_on']) !!}
                                 </div>
                                 <div class="col-md-2">
                                     {!! Form::date('ev_charging_expiry_date', !empty($module_activation_data['ev_charging_expiry_date']) ?
                                     $module_activation_data['ev_charging_expiry_date'] : null, ['class' => 'form-control act_expiry', 'id' => 'ev_charging_expiry_date', 'disabled' => 'disabled']) !!}
                                 </div>
                                 <div class="col-md-2">
                                     {!! Form::text('ev_charging_price', !empty($module_activation_data['ev_charging_price']) ?
                                     $module_activation_data['ev_charging_price'] : null, ['class' => 'form-control', 'id' => 'ev_charging_price']) !!}
                                 </div>
                            </div>
                            <div class="row ev_charging_module_locations">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                </div>
                                <div class="col-md-8">
                                </div>
                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox('module_permission_location[ev_charging_module]['.$location->id.']', 1,
                                                !empty($module_permission_locations_value['ev_charging_module']->locations) ?
                                                array_key_exists($location->id,
                                                $module_permission_locations_value['ev_charging_module']->locations) : false, ['class' =>
                                                'input-icheck-red ch_select location_checkbox']) !!}
                                                {{$location->name}}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    {{-- End EV Charging Module Section --}}

                    {{-- Patient Module Section --}}
                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4>Patient Module</h4>
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
                                    <label class="search_label">Patient Module</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('patient_module', 0) !!}
                                    {!! Form::checkbox('patient_module', 1,
                                    !empty($manage_module_enable['patient_module']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select', 'id' => 'patient_module_checkbox']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['patient_module_expiry_date']))
                                        <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['patient_module_expiry_date']))
                                        @if(strtotime($module_activation_data['patient_module_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        @if(strtotime($module_activation_data['patient_module_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                    @endif
                                </div>
                                <div class="col-md-1">
                                        {!! Form::select('patient_module_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['patient_module_interval']) ?
                                        $module_activation_data['patient_module_interval'] : 'Years', [
                                                'id' => 'patient_module_interval',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%'
                                            ])
                                            !!}
                             
                                     </div>
                                     
                                     <div class="col-md-1">
                                         {!! Form::number('patient_module_length', !empty($module_activation_data['patient_module_length']) ?
                                         $module_activation_data['patient_module_length'] : 1, ['class' => 'form-control act_length', 'id' => 'patient_module_length', 'min' => 1]) !!}
                                     </div>
                                 <div class="col-md-2">
                                     {!! Form::date('patient_module_activated_on', !empty($module_activation_data['patient_module_activated_on']) ?
                                     $module_activation_data['patient_module_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control act_on', 'id' => 'patient_module_activated_on']) !!}
                                 </div>
                                 <div class="col-md-2">
                                     {!! Form::date('patient_module_expiry_date', !empty($module_activation_data['patient_module_expiry_date']) ?
                                     $module_activation_data['patient_module_expiry_date'] : null, ['class' => 'form-control act_expiry', 'id' => 'patient_module_expiry_date', 'disabled' => 'disabled']) !!}
                                 </div>
                                 <div class="col-md-2">
                                     {!! Form::text('patient_module_price', !empty($module_activation_data['patient_module_price']) ?
                                     $module_activation_data['patient_module_price'] : null, ['class' => 'form-control', 'id' => 'patient_module_price']) !!}
                                 </div>
                            </div>
                            <div class="row patient_module_locations">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                </div>
                                <div class="col-md-8">
                                </div>
                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox('module_permission_location[patient_module]['.$location->id.']', 1,
                                                !empty($module_permission_locations_value['patient_module']->locations) ?
                                                array_key_exists($location->id,
                                                $module_permission_locations_value['patient_module']->locations) : false, ['class' =>
                                                'input-icheck-red ch_select location_checkbox']) !!}
                                                {{$location->name}}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    {{-- End Patient Module Section --}}

                    {{-- Patient Test Record Section --}}
                    <div class="card text-left bg-success" style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                        <div class="card-header text-center">
                            <h4>Patient Test Record</h4>
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
                                    <label class="search_label">Patient Test Record</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('patient_test_module', 0) !!}
                                    {!! Form::checkbox('patient_test_module', 1,
                                    !empty($manage_module_enable['patient_test_module']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select', 'id' => 'patient_test_module_checkbox']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['patient_test_module_expiry_date']))
                                        <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['patient_test_module_expiry_date']))
                                        @if(strtotime($module_activation_data['patient_test_module_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        @if(strtotime($module_activation_data['patient_test_module_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                    @endif
                                </div>
                                <div class="col-md-1">
                                        {!! Form::select('patient_test_module_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['patient_test_module_interval']) ?
                                        $module_activation_data['patient_test_module_interval'] : 'Years', [
                                                'id' => 'patient_test_module_interval',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%'
                                            ])
                                            !!}
                             
                                     </div>
                                     
                                     <div class="col-md-1">
                                         {!! Form::number('patient_test_module_length', !empty($module_activation_data['patient_test_module_length']) ?
                                         $module_activation_data['patient_test_module_length'] : 1, ['class' => 'form-control act_length', 'id' => 'patient_test_module_length', 'min' => 1]) !!}
                                     </div>
                                 <div class="col-md-2">
                                     {!! Form::date('patient_test_module_activated_on', !empty($module_activation_data['patient_test_module_activated_on']) ?
                                     $module_activation_data['patient_test_module_activated_on'] : \Carbon\Carbon::today()->format('Y-m-d'), ['class' => 'form-control act_on', 'id' => 'patient_test_module_activated_on']) !!}
                                 </div>
                                 <div class="col-md-2">
                                     {!! Form::date('patient_test_module_expiry_date', !empty($module_activation_data['patient_test_module_expiry_date']) ?
                                     $module_activation_data['patient_test_module_expiry_date'] : null, ['class' => 'form-control act_expiry', 'id' => 'patient_test_module_expiry_date', 'disabled' => 'disabled']) !!}
                                 </div>
                                 <div class="col-md-2">
                                     {!! Form::text('patient_test_module_price', !empty($module_activation_data['patient_test_module_price']) ?
                                     $module_activation_data['patient_test_module_price'] : null, ['class' => 'form-control', 'id' => 'patient_test_module_price']) !!}
                                 </div>
                            </div>
                            <div class="row patient_test_module_locations">
                                <div class="col-md-3">
                                    <p style="padding-top: 9px;"><b>@lang('superadmin::lang.select_locations'): </b></p>
                                </div>
                                <div class="col-md-8">
                                </div>
                                <br>
                                @foreach ($business_locations as $location)
                                    <div class="col-md-3">
                                        <div class="checkbox">
                                            <label>
                                                {!! Form::checkbox('module_permission_location[patient_test_module]['.$location->id.']', 1,
                                                !empty($module_permission_locations_value['patient_test_module']->locations) ?
                                                array_key_exists($location->id,
                                                $module_permission_locations_value['patient_test_module']->locations) : false, ['class' =>
                                                'input-icheck-red ch_select location_checkbox']) !!}
                                                {{$location->name}}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    {{-- End Patient Test Record Section --}}

                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.tasks_management')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.tasks_management')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('tasks_management', 0) !!}
                                    {!! Form::checkbox('tasks_management', 1,
                                    !empty($manage_module_enable['tasks_management']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['tasks_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['tasks_expiry_date']))
                                        @if(strtotime($module_activation_data['tasks_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['tasks_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::select('tasks_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['tasks_interval']) ?
                                    $module_activation_data['tasks_interval'] : null, [
                                                'id' => 'status',
                                                'class' => 'form-control act_interval',
                                                'style' => 'width:100%',
                                                'placeholder' => __('lang_v1.all')
                                            ])
                                        !!}   
                        
                                </div>
                                
                                <div class="col-md-1">
                                    {!! Form::text('tasks_length', !empty($module_activation_data['tasks_length']) ?
                                    $module_activation_data['tasks_length'] : null, ['class' => 'form-control act_length']) !!}
                                </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('tasks_activated_on', !empty($module_activation_data['tasks_activated_on']) ?
                                    $module_activation_data['tasks_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('tasks_expiry_date', !empty($module_activation_data['tasks_expiry_date']) ?
                                    $module_activation_data['tasks_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('tasks_price', !empty($module_activation_data['tasks_price']) ?
                                    $module_activation_data['tasks_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                                
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red">
                                            {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    {!! Form::checkbox('notes_page', 1,
                                    !empty($manage_module_enable['notes_page']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.notes_page')</label>
                                </div>
                                <div class="col-md-4">
                                    {!! Form::checkbox('tasks_page', 1,
                                    !empty($manage_module_enable['tasks_page']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.tasks_page')</label>
                                </div>
                                <div class="col-md-4">
                                    {!! Form::checkbox('reminder_page', 1,
                                    !empty($manage_module_enable['reminder_page']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.reminder_page')</label>
                                </div>
                            </div>
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.repair_module')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.repair_module')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('repair_module', 0) !!}
                                    {!! Form::checkbox('repair_module', 1,
                                    !empty($manage_module_enable['repair_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['repair_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['repair_expiry_date']))
                                        @if(strtotime($module_activation_data['repair_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['repair_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('repair_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['repair_interval']) ?
                                        $module_activation_data['repair_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('repair_length', !empty($module_activation_data['repair_length']) ?
                                        $module_activation_data['repair_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('repair_activated_on', !empty($module_activation_data['repair_activated_on']) ?
                                    $module_activation_data['repair_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('repair_expiry_date', !empty($module_activation_data['repair_expiry_date']) ?
                                    $module_activation_data['repair_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('repair_price', !empty($module_activation_data['repair_price']) ?
                                    $module_activation_data['repair_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red">
                                            {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::checkbox('job_sheets', 1,
                                    !empty($manage_module_enable['job_sheets']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.job_sheets')</label>
                                </div>
                                <div class="col-md-4">
                                    {!! Form::checkbox('add_job_sheet', 1,
                                    !empty($manage_module_enable['add_job_sheet']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.add_job_sheet')</label>
                                </div>
                                <div class="col-md-4">
                                    {!! Form::checkbox('list_invoice', 1,
                                    !empty($manage_module_enable['list_invoice']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.list_invoice')</label>
                                </div>
                                <div class="col-md-4">
                                    {!! Form::checkbox('add_invoice', 1,
                                    !empty($manage_module_enable['add_invoice']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.add_invoice')</label>
                                </div>
                                <div class="col-md-4">
                                    {!! Form::checkbox('brands', 1,
                                    !empty($manage_module_enable['brands']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label class="search_label">@lang('superadmin::lang.brands')</label>
                                </div>
                                <div class="col-md-4">
                                    {!! Form::checkbox('repair_settings', 1,
                                    !empty($manage_module_enable['repair_settings']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.repair_settings')</label>
                                </div>
                            </div>
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.fleet_module')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.fleet_module')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('fleet_module', 0) !!}
                                    {!! Form::checkbox('fleet_module', 1,
                                    !empty($manage_module_enable['fleet_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['fleet_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['fleet_expiry_date']))
                                        @if(strtotime($module_activation_data['fleet_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['fleet_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('fleet_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['fleet_interval']) ?
                                        $module_activation_data['fleet_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('fleet_length', !empty($module_activation_data['fleet_length']) ?
                                        $module_activation_data['fleet_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('fleet_activated_on', !empty($module_activation_data['fleet_activated_on']) ?
                                    $module_activation_data['fleet_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('fleet_expiry_date', !empty($module_activation_data['fleet_expiry_date']) ?
                                    $module_activation_data['fleet_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('fleet_price', !empty($module_activation_data['fleet_price']) ?
                                    $module_activation_data['fleet_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red">
                                            {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                
                                 <div class="col-md-4">
                                    {!! Form::checkbox('fleet_settings', 1,
                                    !empty($manage_module_enable['fleet_settings']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.fleet_settings')</label>
                                </div>
                                
                                 <div class="col-md-4">
                                    {!! Form::checkbox('add_trip_operations', 1,
                                    !empty($manage_module_enable['add_trip_operations']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.add_trip_operations')</label>
                                </div>
                                
                                 <div class="col-md-4">
                                    {!! Form::checkbox('list_fleet', 1,
                                    !empty($manage_module_enable['list_fleet']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.list_fleet')</label>
                                </div>
                                
                                 <div class="col-md-4">
                                    {!! Form::checkbox('milage_changes', 1,
                                    !empty($manage_module_enable['milage_changes']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.milage_changes')</label>
                                </div>
                                
                                 <div class="col-md-4">
                                    {!! Form::checkbox('list_trip_operations', 1,
                                    !empty($manage_module_enable['list_trip_operations']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.list_trip_operations')</label>
                                </div>
                                
                                 <div class="col-md-4">
                                    {!! Form::checkbox('fleet_invoices', 1,
                                    !empty($manage_module_enable['fleet_invoices']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.fleet_invoices')</label>
                                </div>
                                
                                 <div class="col-md-4">
                                    {!! Form::checkbox('fuel_management', 1,
                                    !empty($manage_module_enable['fuel_management']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.fuel_management')</label>
                                </div>
                                
                                 <div class="col-md-4">
                                    {!! Form::checkbox('fleet_p_l', 1,
                                    !empty($manage_module_enable['fleet_p_l']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}<label
                                            class="search_label">@lang('superadmin::lang.fleet_p_l')</label>
                                </div>
                                
                                
                                <div class="col-sm-3 product_count">
                                    <div class="form-group">
                                        {!! Form::label('vehicle_count', __('superadmin::lang.no_of_vehicles').':') !!}
                                        {!! Form::number('vehicle_count', !empty($manage_module_enable['vehicle_count']) ? $manage_module_enable['vehicle_count']
                                        : $previous_package_data['vehicle_count'], ['class' => 'form-control', 'required', 'min' =>
                                        0]) !!}
    
                                        <span class="help-block">
                                        @lang('superadmin::lang.infinite_help')
                                    </span>
                                    </div>
                                </div>
                                
                                <div class="col-sm-3 product_count">
                                    <div class="form-group">
                                        {!! Form::label('category_count', __('superadmin::lang.no_of_product_categories').':') !!}
                                        {!! Form::number('category_count', !empty($manage_module_enable['category_count']) ? $manage_module_enable['category_count']
                                        : $previous_package_data['category_count'], ['class' => 'form-control', 'required', 'min' =>
                                        0]) !!}
    
                                        <span class="help-block">
                                        @lang('superadmin::lang.infinite_help')
                                    </span>
                                    </div>
                                </div>
                            </div>
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.mpcs_module')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.mpcs_module')</label>
                                </div>
                                <div class="col-md-1">
                               
                                    {!! Form::hidden('mpcs_module', 0) !!} <!-- Hidden input to ensure unchecked state is sent -->
                                    {!! Form::checkbox('mpcs_module', 1, 
                                    isset($manage_module_enable['mpcs_module']) && $manage_module_enable['mpcs_module'] == 1, 
                                    ['class' => 'input-icheck-red ch_select', 'id'=>'mpcs_module']) !!}
                                </div>

                               
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['mpcs_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['mpcs_expiry_date']))
                                        @if(strtotime($module_activation_data['mpcs_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['mpcs_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('mpcs_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['mpcs_interval']) ?
                                        $module_activation_data['mpcs_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('mpcs_length', !empty($module_activation_data['mpcs_length']) ?
                                        $module_activation_data['mpcs_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('mpcs_activated_on', !empty($module_activation_data['mpcs_activated_on']) ?
                                    $module_activation_data['mpcs_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('mpcs_expiry_date', !empty($module_activation_data['mpcs_expiry_date']) ?
                                    $module_activation_data['mpcs_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('mpcs_price', !empty($module_activation_data['mpcs_price']) ?
                                    $module_activation_data['mpcs_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red">
                                            {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::checkbox('mpcs_form_settings', 1,
                                    !empty($manage_module_enable['mpcs_form_settings']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select
                                    ']) !!}<label class="search_label">@lang('mpcs::lang.mpcs_form_settings')</label>
                                </div>
                                <div class="col-md-4">
                                    {!! Form::checkbox('list_opening_values', 1,
                                    !empty($manage_module_enable['list_opening_values']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select
                                    ']) !!}<label class="search_label">@lang('mpcs::lang.list_opening_values')</label>
                                </div>
                                
                                <div class="col-md-4">
                                    {!! Form::checkbox('merge_sub_category', 1,
                                    !empty($manage_module_enable['merge_sub_category']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select
                                    ch_select']) !!} <label class="search_label">@lang('superadmin::lang.merge_sub_category')</label>
                                </div>
                                 <div class="col-md-4">
                                    {!! Form::checkbox('stock_taking_approval', 1,
                                    !empty($manage_module_enable['stock_taking_approval']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select
                                    ch_select']) !!} <label class="search_label">@lang('superadmin::lang.stock_taking_approval')</label>
                                </div>
                                 <div class="col-md-4">
                                    {!! Form::checkbox('authorized_signature', 1,
                                    !empty($manage_module_enable['authorized_signature']) ? true : false, ['class' =>
                                    'input-icheck-red ch_select
                                    ch_select']) !!} <label class="search_label">@lang('superadmin::lang.authorized_signature')</label>
                                </div>
                            </div>
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.backup_module')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.backup_module')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('backup_module', 0) !!}
                                    {!! Form::checkbox('backup_module', 1,
                                    !empty($manage_module_enable['backup_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['backup_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['backup_expiry_date']))
                                        @if(strtotime($module_activation_data['backup_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['backup_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('backup_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['backup_interval']) ?
                                        $module_activation_data['backup_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('backup_length', !empty($module_activation_data['backup_length']) ?
                                        $module_activation_data['backup_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('backup_activated_on', !empty($module_activation_data['backup_activated_on']) ?
                                    $module_activation_data['backup_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('backup_expiry_date', !empty($module_activation_data['backup_expiry_date']) ?
                                    $module_activation_data['backup_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('backup_price', !empty($module_activation_data['backup_price']) ?
                                    $module_activation_data['backup_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                 <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red">
                                            {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    {!! Form::checkbox('restore_module', 1,
                                    !empty($manage_module_enable['restore_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                    
                                    <label class="search_label">Restore Backups</label>
                                </div>
                                
                            </div>
                            <hr>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <label>Notify the Business Owner by SMS every time the backup is restored:</label><br>
                                    {!! Form::select('notify_backup_restore_sms', 
                                        ['No' => 'No', 'Yes' => 'Yes'], 
                                        !empty($module_activation_data['notify_backup_restore_sms']) ? $module_activation_data['notify_backup_restore_sms'] : 'No', 
                                        ['class' => 'form-control', 'id' => 'notify_backup_restore_sms']
                                    ) !!}
                                </div>

                                <div class="col-md-8" id="backup_sms_numbers_container" style="display: none; margin-top: 10px;">
                                    <label>Mobile Numbers (comma-separated or press enter to add multiple):</label>
                                    {!! Form::text('backup_sms_numbers', 
                                        !empty($module_activation_data['backup_sms_numbers']) ? $module_activation_data['backup_sms_numbers'] : null, 
                                        ['class' => 'form-control', 'id' => 'backup_sms_numbers', 'placeholder' => 'e.g. +251912345678, +251987654321']
                                    ) !!}
                                    <small class="text-muted">Multiple numbers can be added separated by commas.</small>
                                </div>
                            </div>
                         </div>
                          
                    </div>
                    
                    <div class="card text-left bg-success"  style="border: 1px solid #D9D8D8; margin-bottom: 30px;">
                          <div class="card-header text-center">
                            <h4> @lang('superadmin::lang.contact_module')</h4>
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
                                    <label class="search_label">@lang('superadmin::lang.contact_module')</label>
                                </div>
                                <div class="col-md-1">
                                {!! Form::hidden('contact_module', 0) !!}
                                    {!! Form::checkbox('contact_module', 1,
                                    !empty($manage_module_enable['contact_module']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                </div>
                                <div class="col-md-1">
                                    @if(empty($module_activation_data['contact_expiry_date']))
                                    <span class="badge badge-danger">not set</span>
                                    @endif
                                    @if(!empty($module_activation_data['contact_expiry_date']))
                                        @if(strtotime($module_activation_data['contact_expiry_date']) >= time())
                                            <span class="label label-pill label-primary">active</span>
                                        @endif
                                        
                                        @if(strtotime($module_activation_data['contact_expiry_date']) < time())
                                            <span class="label label-pill label-danger">expired</span>
                                        @endif
                                        
                                    @endif
                                </div>
                                
                                <div class="col-md-1">
                                        {!! Form::select('contact_interval', ['Years' => 'Years', 'Months' => 'Months', 'Days' => 'Days'], !empty($module_activation_data['contact_interval']) ?
                                        $module_activation_data['contact_interval'] :null, [ 'class' => 'form-control act_interval','style' => 'width:100%', 'placeholder' => __('lang_v1.all')
                                                ])
                                            !!}   
                            
                                    </div>
                                    
                                    <div class="col-md-1">
                                        {!! Form::text('contact_length', !empty($module_activation_data['contact_length']) ?
                                        $module_activation_data['contact_length'] : null, ['class' => 'form-control act_length']) !!}
                                    </div>
                                
                                <div class="col-md-2">
                                    {!! Form::date('contact_activated_on', !empty($module_activation_data['contact_activated_on']) ?
                                    $module_activation_data['contact_activated_on'] : null, ['class' => 'form-control act_on']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::date('contact_expiry_date', !empty($module_activation_data['contact_expiry_date']) ?
                                    $module_activation_data['contact_expiry_date'] : null, ['class' => 'form-control act_expiry', 'disabled' => 'disabled']) !!}
                                </div>
                                <div class="col-md-2">
                                    {!! Form::text('contact_price', !empty($module_activation_data['contact_price']) ?
                                    $module_activation_data['contact_price'] : null, ['class' => 'form-control']) !!}
                                </div>
                            </div>
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="search_label">@lang('superadmin::lang.customer_credit_notification_type')</label><br>
                                    
                                    {!! Form::select('customer_credit_notification_type[]', ['settlement' => __('contact.settlement'), 'customer_bill' => __('contact.bill_to_customer'),'pumper_dashboard' => __('contact.pumper_dashboard')],
                                    $customer_credit_notification_type, ['class' => 'form-control
                                    select2', 'multiple', 'id' => 'customer_credit_notification_type']) !!}
                                </div>
                            </div>
                            <hr>
                           
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" class="check_all input-icheck-red">
                                            {{ __( 'role.select_all' ) }}
                                        </label>
                                    </div>
                                </div>
                                
                               
                                <div class="col-md-3">
                                    {!! Form::checkbox('contact_supplier', 1,
                                    !empty($manage_module_enable['contact_supplier']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.contact_supplier')</label>
                                </div>
                          
                                <div class="col-md-3">
                                    {!! Form::checkbox('contact_customer', 1,
                                    !empty($manage_module_enable['contact_customer']) ? true : false, ['class' =>
                                    'input-icheck-red
                                    ch_select']) !!}
                                    <label class="search_label">@lang('superadmin::lang.contact_customer')</label>
                                </div>
                                <div class="clearfix"></div>
                                
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_supplier', 1, !empty($manage_module_enable['contact_supplier']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_supplier']) !!}
                                        {{__('superadmin::lang.supplier_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_customer', 1, !empty($manage_module_enable['contact_customer']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_customer']) !!}
                                        {{__('superadmin::lang.customer_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_group_customer', 1, !empty($manage_module_enable['contact_group_customer']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_group_customer']) !!}
                                        {{__('superadmin::lang.contact_group_customer_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_group_supplier', 1, !empty($manage_module_enable['contact_group_supplier']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_group_supplier']) !!}
                                        {{__('superadmin::lang.contact_group_supplier_tab_page')}}
                                    </label>
                                </div>
                            </div>
                        
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('import_contact', 1, !empty($manage_module_enable['import_contact']) ? true : false, ['class' => 'input-icheck-red ch_select','id'=>'import_contact']) !!}
                                        {{__('superadmin::lang.import_contact_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_reference', 1, !empty($manage_module_enable['customer_reference']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'customer_reference']) !!}
                                        {{__('superadmin::lang.customer_reference_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_statement', 1, !empty($manage_module_enable['customer_statement']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'customer_statement']) !!}
                                        {{__('superadmin::lang.customer_statement_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_payment', 1, !empty($manage_module_enable['customer_payment']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=> 'customer_payment']) !!}
                                        {{__('superadmin::lang.customer_payment_tab_page')}}
                                    </label>
                                </div>
                            </div>
                        
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('outstanding_received', 1, !empty($manage_module_enable['outstanding_received']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'outstanding_received']) !!}
                                        {{__('superadmin::lang.outstanding_received_tab_page')}}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('stock_taking_page', 1, !empty($manage_module_enable['stock_taking_page']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'stock_taking_page']) !!}
                                        {{__('superadmin::lang.stock_taking_page')}}
                                    </label>

                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('issue_payment_detail', 1, !empty($manage_module_enable['issue_payment_detail']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'issue_payment_detail']) !!}
                                        {{__('superadmin::lang.issue_payment_detail_tab_page')}}
                                    </label>

                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('edit_received_outstanding', 1, !empty($manage_module_enable['edit_received_outstanding']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'edit_received_outstanding']) !!}
                                        {{__('superadmin::lang.edit_received_outstanding')}}
                                    </label>
                                </div>
                            </div>
                            
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_payment_simple', 1, !empty($manage_module_enable['customer_payment_simple']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'customer_payment_simple']) !!}
                                        {{__('superadmin::lang.customer_payment_simple')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_payment_bulk', 1, !empty($manage_module_enable['customer_payment_bulk']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'customer_payment_bulk']) !!}
                                        {{__('superadmin::lang.customer_payment_bulk')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('list_customer_payments', 1, !empty($manage_module_enable['list_customer_payments']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'list_customer_payments']) !!}
                                        {{__('superadmin::lang.list_customer_payments')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_interest', 1, !empty($manage_module_enable['customer_interest']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'customer_interest']) !!}
                                        {{__('superadmin::lang.customer_interest')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('interest_settings', 1, !empty($manage_module_enable['interest_settings']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'interest_settings']) !!}
                                        {{__('superadmin::lang.interest_settings')}}
                                    </label>
                                </div>
                            </div>
                            
                             <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('ledger_discount', 1, !empty($manage_module_enable['ledger_discount']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'ledger_discount']) !!}
                                        {{__('superadmin::lang.ledger_discount')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('customer_statement_pmt', 1, !empty($manage_module_enable['customer_statement_pmt']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'customer_statement_pmt']) !!}
                                        {{__('contact.customer_statements_with_payment')}}
                                    </label>
                                </div>
                            </div>
                            
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_list_customer_loans', 1, !empty($manage_module_enable['contact_list_customer_loans']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_list_customer_loans']) !!}
                                        {{__('superadmin::lang.contact_list_customer_loans')}}
                                    </label>
                                </div>
                            </div>

                            <!-- <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('loan_module', 1, !empty($manage_module_enable['loan_module']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'loan_module']) !!}
                                        {{__('loan::lang.loan')}}
                                    </label>
                                </div>
                            </div> -->
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_settings', 1, !empty($manage_module_enable['contact_settings']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_settings']) !!}
                                        {{__('superadmin::lang.contact_settings')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_list_supplier_map_products', 1, !empty($manage_module_enable['contact_list_supplier_map_products']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_list_supplier_map_products']) !!}
                                        {{__('superadmin::lang.contact_list_supplier_map_products')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_add_supplier_products', 1, !empty($manage_module_enable['contact_add_supplier_products']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_add_supplier_products']) !!}
                                        {{__('superadmin::lang.contact_add_supplier_products')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_import_opening_balalnces', 1, !empty($manage_module_enable['contact_import_opening_balalnces']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_import_opening_balalnces']) !!}
                                        {{__('superadmin::lang.contact_import_opening_balalnces')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-sm-3">
                                <div class="checkbox">
                                    <label class="flex-label search_label">
                                        {!! Form::checkbox('contact_returned_cheque_details', 1, !empty($manage_module_enable['contact_returned_cheque_details']) ? true : false, ['class' => 'input-icheck-red ch_select', 'id'=>'contact_returned_cheque_details']) !!}
                                        {{__('superadmin::lang.contact_returned_cheque_details')}}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                {!! Form::checkbox('delete_customer_statement', 1,
                                (!empty($manage_module_enable['delete_customer_statement'])  || !array_key_exists('delete_customer_statement',$manage_module_enable)) ? true : false, ['class' =>
                                'input-icheck-red
                                ch_select']) !!}<label
                                        class="search_label">@lang('lang_v1.vat.delete_customer_statement')</label>
                            </div>
                            
                            <div class="col-md-3">
                                {!! Form::checkbox('delete_statement_payment', 1,
                                (!empty($manage_module_enable['delete_statement_payment'])  || !array_key_exists('delete_statement_payment',$manage_module_enable)) ? true : false, ['class' =>
                                'input-icheck-red
                                ch_select']) !!}<label
                                        class="search_label">@lang('lang_v1.vat.delete_statement_payment')</label>
                            </div>
                              <div class="col-md-3">
                                {!! Form::checkbox('credit_customer_manual_bills', 1,
                                (!empty($manage_module_enable['credit_customer_manual_bills'])  || !array_key_exists('credit_customer_manual_bills',$manage_module_enable)) ? true : false, ['class' =>
                                'input-icheck-red
                                ch_select']) !!}<label
                                        class="search_label">Credit Customer Manual Bills</label>
                            </div>
                            <div class="col-md-3">
                                {!! Form::checkbox('manual_bill', 1,
                                !empty($manage_module_enable['manual_bill']) ? true : false,
                                ['class' => 'input-icheck-red ch_select', 'id'=>'manual_bill']) !!}
                                <label class="search_label">Manual Bill</label>
                            </div>

                        </div>
                            
                         </div>
                          
                    </div>

                    {{-- S330: Standalone Customers Module page switches.
                         Kept next to Contact Module so Super Admin can enable all
                         System Customers pages and the Login -> System Customers
                         menu reads the same package keys. --}}
                    @includeIf('customers::superadmin.manage_customers_module', ['manage_module_enable' => $manage_module_enable])

                    <!-- Loan Module -->
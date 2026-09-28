@php $request = request(); @endphp

{{-- Communication Hub standalone module menu: submenu is module-owned. --}}
@if(\App\Utils\SidebarPermissionUtil::isVisibleInSidebar('communication_hub'))
    @includeIf('communicationhub::partials.sidebar')
@endif

                                        {{-- HR Manager standalone module menu. --}}
@if(\App\Utils\SidebarPermissionUtil::isEnabled('hrmanager_module'))
    @includeIf('hrmanager::partials.sidebar_v2')
@endif


{{-- Manage Side Bar is the authoritative parent gate; user/page permissions remain separate. --}}
@if(auth()->check() && \App\Utils\SidebarPermissionUtil::isVisibleInSidebar('my_health'))
    @php
        $__myhealth_active = request()->segment(1) === 'myhealth' || request()->segment(1) === 'myhealth-register';
    @endphp
    <li class="nav-item {{ $__myhealth_active ? 'active active-sub' : '' }}" data-sidebar-module="my_health">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#myhealth-member-module-menu"
            aria-expanded="{{ $__myhealth_active ? 'true' : 'false' }}" aria-controls="myhealth-member-module-menu">
            <i class="fa fa-heartbeat"></i>
            <span>My Health</span>
        </a>
        <div id="myhealth-member-module-menu" class="collapse {{ $__myhealth_active ? 'show' : '' }}" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">My Health:</h6>
                <a class="collapse-item {{ request()->segment(1) == 'myhealth' && request()->segment(2) == '' ? 'active' : '' }}" href="{{ url('/myhealth') }}">Dashboard</a>
                <a class="collapse-item {{ request()->segment(1) == 'myhealth' && request()->segment(2) == 'members' ? 'active' : '' }}" href="{{ url('/myhealth/members') }}">Members</a>
                <a class="collapse-item {{ request()->segment(1) == 'myhealth' && request()->segment(2) == 'doctors' ? 'active' : '' }}" href="{{ url('/myhealth/doctors') }}">Doctors</a>
                <a class="collapse-item {{ request()->segment(1) == 'myhealth' && request()->segment(2) == 'pharmacy' ? 'active' : '' }}" href="{{ url('/myhealth/pharmacy') }}">Pharmacy</a>
                <a class="collapse-item {{ request()->segment(1) == 'myhealth' && request()->segment(2) == 'insurance' ? 'active' : '' }}" href="{{ url('/myhealth/insurance') }}">Insurance</a>
                <a class="collapse-item {{ request()->segment(1) == 'myhealth' && request()->segment(2) == 'telemedicine' ? 'active' : '' }}" href="{{ url('/myhealth/telemedicine') }}">Telemedicine</a>
                <a class="collapse-item {{ request()->segment(1) == 'myhealth' && request()->segment(2) == 'billing' ? 'active' : '' }}" href="{{ url('/myhealth/billing') }}">Billing & Claims</a>
                <a class="collapse-item {{ request()->segment(1) == 'myhealth' && request()->segment(2) == 'reports' ? 'active' : '' }}" href="{{ url('/myhealth/reports') }}">Reports</a>
                <a class="collapse-item {{ request()->segment(1) == 'myhealth' && request()->segment(2) == 'settings' ? 'active' : '' }}" href="{{ url('/myhealth/settings') }}">Settings</a>
                <h6 class="collapse-header">Member Portal:</h6>
                <a class="collapse-item {{ request()->segment(1) == 'myhealth-register' && request()->segment(2) == '' ? 'active' : '' }}" href="{{ url('/myhealth-register') }}">Member Registration</a>
                <a class="collapse-item {{ request()->segment(1) == 'myhealth-register' && request()->segment(2) == 'login' ? 'active' : '' }}" href="{{ url('/myhealth-register/login') }}">Member Login</a>
            </div>
        </div>
    </li>
@endif

@if(\App\Utils\SidebarPermissionUtil::isEnabled('mpcs_module'))
<li class="nav-item {{ in_array($request->segment(1), ['mpcs']) ? 'active active-sub' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#mpcs-menu"
        aria-expanded="true" aria-controls="mpcs-menu">
        <i class="fa fa-calculator"></i>
        <span>@lang('mpcs::lang.mpcs')</span>
    </a>
    <div id="mpcs-menu" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">@lang('mpcs::lang.mpcs'):</h6>
            {{-- Form Set 1 - Hidden as per Task 7788 --}}
            {{-- @if(auth()->user()->can('f16a_form') || auth()->user()->can('f15a9abc_form') || auth()->user()->can('f16a_form') || auth()->user()->can('f21c_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'form-set-1' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\MPCSController@FromSet1')}}">@lang('mpcs::lang.form_set_1')</a>
            @endif --}}
            @if(auth()->user()->can('f9a_form') || auth()->user()->can('f9a_settings_form'))
          
            <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'form-9a' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\MPCSController@From9A')}}">@lang('mpcs::lang.form_9_a')</a>
            @endif
            
             
            
@php
    $canF9CForm = auth()->user()->can('f9c_form');
    $canF9CSettingsForm = auth()->user()->can('f9c_settings_form');
@endphp

 
@if($canF9CForm || $canF9CSettingsForm)
    <a class="collapse-item {{ request()->segment(1) == 'mpcs' && request()->segment(2) == 'form-9c' ? 'active' : '' }}" 
       href="{{ action('\Modules\MPCS\Http\Controllers\MPCSController@From9C') }}">
       @lang('mpcs::lang.9c_cash_form')
    </a>
@else
    <p>Access Denied</p>
@endif
@php
    $canF9CcrForm = auth()->user()->can('f9c_form');
    $canF9CcrSettingsForm = auth()->user()->can('f9c_settings_form');
@endphp

 
@if($canF9CcrForm || $canF9CcrSettingsForm)
    <a class="collapse-item {{ request()->segment(1) == 'mpcs' && request()->segment(2) == 'form-9ccr' ? 'active' : '' }}" 
       href="{{ action('\Modules\MPCS\Http\Controllers\MPCSController@From9CCR') }}">
       @lang('mpcs::lang.9c_credit_form')
    </a>
@else
    <p>Access Denied</p>
@endif

@if(auth()->user()->can('f10_form'))
    <a class="collapse-item {{ request()->segment(1) == 'mpcs' && request()->segment(2) == 'F10' ? 'active' : '' }}" 
       href="{{ action('\Modules\MPCS\Http\Controllers\F10FormController@index') }}">
        F 10 Form
    </a>
@endif
            
            <!--By Zamaluddin : Time 04:20 PM : 28 January 2025-->
            
          
            
            @if(auth()->user()->can('f15_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F15' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\F15FormController@index')}}">@lang('mpcs::lang.F15_form')</a>
            
            @endif
            
            <!--End-->
            
            @if(auth()->user()->can('f14_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F14' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\NewF14FormController@index')}}">@lang('mpcs::lang.F14_form')</a>
            
            @endif
            
            
            
            @if(auth()->user()->can('f16a_form') || auth()->user()->can('f16_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == '16A' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\F16AFormController@index')}}">@lang('mpcs::lang.F16A_form')</a>
            @endif
            
              {{--@if(auth()->user()->can('f21_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F21' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\F21CFormController@index')}}">@lang('mpcs::lang.F21C_form')</a>
            @endif --}} 
            
            @if(auth()->user()->can('f17_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F17' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\F17FormController@index')}}">@lang('mpcs::lang.F17_form')</a>
            @endif

            @if(auth()->user()->can('f18_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F18' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\F18FormController@index')}}">@lang('mpcs::lang.F18_form')</a>
            @endif
{{--            @if(auth()->user()->can('f14b_form') || auth()->user()->can('f20_form'))--}}
{{--                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F14B_F20_Forms' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\F20F14bFormController@index')}}">@lang('mpcs::lang.F20andF14b_form')</a>--}}
{{--            @endif--}}
           
            @if(auth()->user()->can('f20_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == '20Form' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\F20FormController@index')}}">@lang('mpcs::lang.20form')</a>
            @endif
            @if(auth()->user()->can('f21_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == '21Form' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\F21FormController@get21Form')}}">@lang('mpcs::lang.f21_form')</a>
            @endif
           
            @if(auth()->user()->can('f21_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F21Form' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\F21FormController@index')}}">@lang('mpcs::lang.F21C_form')</a>
            @endif
           
            @if(auth()->user()->can('f22_stock_taking_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F22_stock_taking' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\F22FormController@F22StockTaking')}}">@lang('mpcs::lang.F22StockTaking_form')</a>
                
            @endif
            @if(auth()->user()->can('f25_form') || auth()->user()->can('f22_stock_taking_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F25' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\F25FormController@index')}}">@lang('mpcs::lang.F25_form')</a>
            @endif
            <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'forms-setting' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\FormsSettingController@index')}}">@lang('mpcs::lang.mpcs_forms_setting')</a>
        </div>
    </div>
</li>
@endif


{{-- Uniform sidebar behavior: one expand/collapse owner + one search owner. --}}
@includeIf('layouts.partials.sidebar-unified-controller')
@includeIf('layouts.partials.sidebar-unified-search')

{{-- Standalone Purchase module (separate from core Purchases). --}}
@includeIf('purchase::layouts.sidebar')

{{-- Standalone modules not already integrated into this v2 sidebar. --}}
@includeIf('layouts.partials.sidebar-sections.sidebar-new-operations-modules')
@includeIf('layouts.partials.automatic-module-sidebar')

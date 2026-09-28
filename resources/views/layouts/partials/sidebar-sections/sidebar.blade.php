@includeIf('hrmanager::partials.sidebar_v2')
@php $request = request(); @endphp

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

{{-- GSB-015: MPCS sidebar uses stable URL paths instead of action() to avoid missing route-action exceptions. --}}
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
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'form-set-1' ? 'active' : '' }}" href="{{ url('/mpcs/form-set-1') }}">@lang('mpcs::lang.form_set_1')</a>
            @endif --}}
            @if(auth()->user()->can('f9a_form') || auth()->user()->can('f9a_settings_form'))
          
            <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'form-9a' ? 'active' : '' }}" href="{{ url('/mpcs/form-9a') }}">@lang('mpcs::lang.form_9_a')</a>
            @endif
            
             
            
@php
    $canF9CForm = auth()->user()->can('f9c_form');
    $canF9CSettingsForm = auth()->user()->can('f9c_settings_form');
@endphp

 
@if($canF9CForm || $canF9CSettingsForm)
    <a class="collapse-item {{ request()->segment(1) == 'mpcs' && request()->segment(2) == 'form-9c' ? 'active' : '' }}" 
       href="{{ url('/mpcs/form-9c') }}">
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
       href="{{ url('/mpcs/form-9ccr') }}">
       @lang('mpcs::lang.9c_credit_form')
    </a>
@else
    <p>Access Denied</p>
@endif

@if(auth()->user()->can('f10_form'))
    <a class="collapse-item {{ request()->segment(1) == 'mpcs' && request()->segment(2) == 'F10' ? 'active' : '' }}" 
       href="{{ url('/mpcs/F10') }}">
        F 10 Form
    </a>
@endif
            
            <!--By Zamaluddin : Time 04:20 PM : 28 January 2025-->
            
          
            
            @if(auth()->user()->can('f15_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F15' ? 'active' : '' }}" href="{{ url('/mpcs/F15') }}">@lang('mpcs::lang.F15_form')</a>
            
            @endif

            @if(auth()->user()->can('f15_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && in_array(strtolower((string) $request->segment(2)), ['f15-new', 'f15new', 'f15-daily-report'], true) ? 'active' : '' }}"
                   href="{{ url('/mpcs/F15-New') }}"><i class="fa fa-line-chart"></i> F 15 Daily Report - New</a>
            @endif
            
            <!--End-->
            
            @if(auth()->user()->can('f14_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F14' ? 'active' : '' }}" href="{{ url('/mpcs/F14B') }}">@lang('mpcs::lang.F14_form')</a>
            
            @endif
            
            
            
            @if(auth()->user()->can('f16a_form') || auth()->user()->can('f16_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == '16A' ? 'active' : '' }}" href="{{ url('/mpcs/16A') }}">@lang('mpcs::lang.F16A_form')</a>
            @endif
            
              {{--@if(auth()->user()->can('f21_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F21' ? 'active' : '' }}" href="{{ url('/mpcs/F21') }}">@lang('mpcs::lang.F21C_form')</a>
            @endif --}} 
            
            @if(auth()->user()->can('f17_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F17' ? 'active' : '' }}" href="{{ url('/mpcs/F17') }}">@lang('mpcs::lang.F17_form')</a>
            @endif

            @if(auth()->user()->can('f18_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F18' ? 'active' : '' }}" href="{{ url('/mpcs/F18') }}">@lang('mpcs::lang.F18_form')</a>
            @endif
{{--            @if(auth()->user()->can('f14b_form') || auth()->user()->can('f20_form'))--}}
{{--                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F14B_F20_Forms' ? 'active' : '' }}" href="{{action('\Modules\MPCS\Http\Controllers\F20F14bFormController@index')}}">@lang('mpcs::lang.F20andF14b_form')</a>--}}
{{--            @endif--}}
           
            @if(auth()->user()->can('f20_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == '20Form' ? 'active' : '' }}" href="{{ url('/mpcs/20Form') }}">@lang('mpcs::lang.20form')</a>
            @endif
            {{-- F20 CDS: show menu without permission blockage because this page is a new form design page and direct URL already works. --}}
            <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F20-CDS' ? 'active' : '' }}" href="{{ url('/mpcs/F20-CDS') }}">F 20 Form – CDS</a>
            @if(auth()->user()->can('f21_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == '21Form' ? 'active' : '' }}" href="{{ url('/mpcs/21Form') }}">@lang('mpcs::lang.f21_form')</a>
            @endif
           
            @if(auth()->user()->can('f21_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F21Form' ? 'active' : '' }}" href="{{ url('/mpcs/21CForm') }}">@lang('mpcs::lang.F21C_form')</a>
            @endif
           
            @if(auth()->user()->can('f22_stock_taking_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F22_stock_taking' ? 'active' : '' }}" href="{{ url('/mpcs/F22_stock_taking') }}">@lang('mpcs::lang.F22StockTaking_form')</a>
                
            @endif
            @if(auth()->user()->can('f25_form') || auth()->user()->can('f22_stock_taking_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F25' ? 'active' : '' }}" href="{{ url('/mpcs/F25') }}">@lang('mpcs::lang.F25_form')</a>
            @endif
            <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'forms-setting' ? 'active' : '' }}" href="{{ url('/mpcs/forms-setting') }}">@lang('mpcs::lang.mpcs_forms_setting')</a>
        </div>
    </div>
</li>

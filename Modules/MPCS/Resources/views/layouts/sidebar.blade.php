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
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'form-set-1' ? 'active' : '' }}" href="{{url('/mpcs/form-set-1')}}">@lang('mpcs::lang.form_set_1')</a>
            @endif --}}
            @if(auth()->user()->can('f9a_form') || auth()->user()->can('f9a_settings_form'))
          
            <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'form-9a' ? 'active' : '' }}" href="{{url('/mpcs/form-9a')}}">@lang('mpcs::lang.form_9_a')</a>
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
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F14' ? 'active' : '' }}" href="{{url('/mpcs/F14')}}">@lang('mpcs::lang.F14_form')</a>
            
            @endif
            
            
            
            @if(auth()->user()->can('f16a_form') || auth()->user()->can('f16_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == '16A' ? 'active' : '' }}" href="{{url('/mpcs/16A')}}">@lang('mpcs::lang.F16A_form')</a>
            @endif
            
              {{--@if(auth()->user()->can('f21_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F21' ? 'active' : '' }}" href="{{url('/mpcs/F21')}}">@lang('mpcs::lang.F21C_form')</a>
            @endif --}} 
            
            @if(auth()->user()->can('f17_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F17' ? 'active' : '' }}" href="{{url('/mpcs/F17')}}">@lang('mpcs::lang.F17_form')</a>
            @endif

            @if(auth()->user()->can('f18_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F18' ? 'active' : '' }}" href="{{url('/mpcs/F18')}}">@lang('mpcs::lang.F18_form')</a>
            @endif
{{--            @if(auth()->user()->can('f14b_form') || auth()->user()->can('f20_form'))--}}
{{--                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F14B_F20_Forms' ? 'active' : '' }}" href="{{url('/mpcs/F14B_F20_Forms')}}">@lang('mpcs::lang.F20andF14b_form')</a>--}}
{{--            @endif--}}
           
            @if(auth()->user()->can('f20_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == '20Form' ? 'active' : '' }}" href="{{url('/mpcs/20Form')}}">@lang('mpcs::lang.20form')</a>
            @endif
            {{-- F20 CDS: show menu without permission blockage because this page is a new form design page and direct URL already works. --}}
            <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F20-CDS' ? 'active' : '' }}" href="{{ url('/mpcs/F20-CDS') }}">F 20 Form – CDS</a>
            @if(auth()->user()->can('f21_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == '21Form' ? 'active' : '' }}" href="{{url('/mpcs/21Form')}}">@lang('mpcs::lang.f21_form')</a>
            @endif
           
            @if(auth()->user()->can('f21_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F21Form' ? 'active' : '' }}" href="{{url('/mpcs/21CForm')}}">@lang('mpcs::lang.F21C_form')</a>
            @endif
           
            @if(auth()->user()->can('f22_stock_taking_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F22_stock_taking' ? 'active' : '' }}" href="{{url('/mpcs/F22_stock_taking')}}">@lang('mpcs::lang.F22StockTaking_form')</a>
                
            @endif
            @if(auth()->user()->can('f25_form') || auth()->user()->can('f22_stock_taking_form'))
                <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F25' ? 'active' : '' }}" href="{{url('/mpcs/F25')}}">@lang('mpcs::lang.F25_form')</a>
            @endif
            <a class="collapse-item {{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'forms-setting' ? 'active' : '' }}" href="{{url('/mpcs/forms-setting')}}">@lang('mpcs::lang.mpcs_forms_setting')</a>
        </div>
    </div>
</li>


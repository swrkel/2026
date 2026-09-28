<li
class="treeview {{ in_array($request->segment(1), ['mpcs']) ? 'active active-sub' : '' }}"
id="tour_step5">
<a href="#" id="tour_step5_menu"><i class="fa fa-calculator"></i> <span>@lang('mpcs::lang.mpcs')</span>
  <span class="pull-right-container">
    <i class="fa fa-angle-left pull-right"></i>
  </span>
</a>
<ul class="treeview-menu">
  {{-- Form Set 1 - Hidden as per Task 7788 --}}
  {{-- @if(auth()->user()->can('f9c_form') || auth()->user()->can('f15a9abc_form') || auth()->user()->can('f16a_form') || auth()->user()->can('f21c_form'))
  <li class="{{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'form-set-1' ? 'active' : '' }}"><a
      href="{{url('/mpcs/form-set-1')}}"><i class="fa fa-file-text-o"></i>@lang('mpcs::lang.form_set_1')</a>
  </li>
  @endif --}}
  @if(auth()->user()->can('f9a_form') || auth()->user()->can('f9a_settings_form'))
  <li class="{{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'form-9a' ? 'active' : '' }}"><a
      href="{{url('/mpcs/form-9a')}}"><i class="fa fa-file-text-o"></i>@lang('mpcs::lang.form_9_a')</a>
  </li>
  @endif
  <li class="{{ $request->segment(1) == 'mpcs' && in_array(strtolower((string) $request->segment(2)), ['f15-new', 'f15-daily-report'], true) ? 'active' : '' }}">
    <a href="{{ url('/mpcs/F15-New') }}"><i class="fa fa-file-text-o"></i>F 15 Daily Report - New</a>
  </li>
  @if(auth()->user()->can('f17_form'))
  <li class="{{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F17' ? 'active' : '' }}"><a
      href="{{url('/mpcs/F17')}}"><i class="fa fa-file-text-o"></i>@lang('mpcs::lang.F17_form')</a>
  </li>
  @endif
  @if(auth()->user()->can('f18_form'))
  <li class="{{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F18' ? 'active' : '' }}"><a
      href="{{url('/mpcs/F18')}}"><i class="fa fa-file-text-o"></i>@lang('mpcs::lang.F18_form')</a>
  </li>
  @endif
  @if(auth()->user()->can('f14b_form') || auth()->user()->can('f20_form'))
  <li class="{{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F14B_F20_Forms' ? 'active' : '' }}"><a
      href="{{url('/mpcs/F14B_F20_Forms')}}"><i class="fa fa-file-text-o"></i>@lang('mpcs::lang.F20andF14b_form')</a>
  </li>
  @endif
  @if(auth()->user()->can('f20_form') || auth()->user()->can('superadmin'))
  <li class="{{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F20-CDS' ? 'active' : '' }}"><a
      href="{{url('/mpcs/F20-CDS')}}"><i class="fa fa-file-text-o"></i>F 20 Form – CDS</a>
  </li>
  @endif
  @if(auth()->user()->can('f21_form'))
  <li class="{{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F21' ? 'active' : '' }}"><a
      href="{{url('/mpcs/F21')}}"><i class="fa fa-file-text-o"></i>@lang('mpcs::lang.F21_form')</a>
  </li>
  @endif
  @if(auth()->user()->can('f22_stock_taking_form'))
  <li class="{{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F22_stock_taking' ? 'active' : '' }}"><a
      href="{{url('/mpcs/F22_stock_taking')}}"><i class="fa fa-file-text-o"></i>@lang('mpcs::lang.F22StockTaking_form')</a>
  </li>
  @endif
  @if(auth()->user()->can('f25_form') || auth()->user()->can('f22_stock_taking_form'))
  <li class="{{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'F25' ? 'active' : '' }}"><a
      href="{{url('/mpcs/F25')}}"><i class="fa fa-file-text-o"></i>@lang('mpcs::lang.F25_form')</a>
  </li>
  @endif
  <li class="{{ $request->segment(1) == 'mpcs' && $request->segment(2) == 'forms-setting' ? 'active' : '' }}"><a
      href="{{url('/mpcs/forms-setting')}}"><i class="fa fa-cogs"></i>@lang('mpcs::lang.mpcs_forms_setting')</a>
  </li>
  
</ul>
</li>

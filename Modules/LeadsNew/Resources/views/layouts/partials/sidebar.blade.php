@php($request = request())
<li class="treeview {{ $request->segment(1) === 'leads-new' ? 'active active-sub' : '' }}">
    <a href="#"><i class="fa fa-lg fa-lightbulb-o"></i> <span>Leads-New</span>
        <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
    </a>
    <ul class="treeview-menu">
        <li class="{{ $request->segment(1) === 'leads-new' && !$request->segment(2) ? 'active' : '' }}">
            <a href="{{ url('/leads-new') }}"><i class="fa fa-dashboard"></i> {{ __('leadsnew::messages.dashboard') }}</a>
        </li>
        <li class="{{ $request->segment(1) === 'leads-new' && $request->segment(2) === 'leads' ? 'active' : '' }}">
            <a href="{{ url('/leads-new/leads') }}"><i class="fa fa-list"></i> {{ __('leadsnew::messages.leads') }}</a>
        </li>
        <li class="{{ $request->segment(1) === 'leads-new' && $request->segment(2) === 'add-leads' ? 'active' : '' }}">
            <a href="{{ url('/leads-new/leads/create') }}"><i class="fa fa-plus"></i> {{ __('leadsnew::messages.add_lead') }}</a>
        </li>
        <li><a href="{{ url('/leads-new/opportunities') }}"><i class="fa fa-line-chart"></i> {{ __('leadsnew::messages.opportunities') }}</a></li>
        <li><a href="{{ url('/leads-new/calendar') }}"><i class="fa fa-calendar"></i> {{ __('leadsnew::messages.calendar') }}</a></li>
        <li><a href="{{ url('/leads-new/reports') }}"><i class="fa fa-bar-chart"></i> {{ __('leadsnew::messages.reports') }}</a></li>
        <li><a href="{{ url('/leads-new/settings') }}"><i class="fa fa-cogs"></i> {{ __('leadsnew::messages.settings') }}</a></li>
    </ul>
</li>

@php($request = request())
<li class="nav-item {{ $request->segment(1) === 'leads-new' ? 'active active-sub' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#leads-new-menu" aria-expanded="true" aria-controls="leads-new-menu">
        <i class="fa fa-lightbulb-o"></i><span>Leads-New</span>
    </a>
    <div id="leads-new-menu" class="collapse {{ $request->segment(1) === 'leads-new' ? 'show' : '' }}">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Leads-New</h6>
            <a class="collapse-item" href="{{ url('/leads-new') }}">{{ __('leadsnew::messages.dashboard') }}</a>
            <a class="collapse-item" href="{{ url('/leads-new/leads') }}">{{ __('leadsnew::messages.leads') }}</a>
            <a class="collapse-item" href="{{ url('/leads-new/leads/create') }}">{{ __('leadsnew::messages.add_lead') }}</a>
            <a class="collapse-item" href="{{ url('/leads-new/opportunities') }}">{{ __('leadsnew::messages.opportunities') }}</a>
            <a class="collapse-item" href="{{ url('/leads-new/calendar') }}">{{ __('leadsnew::messages.calendar') }}</a>
            <a class="collapse-item" href="{{ url('/leads-new/reports') }}">{{ __('leadsnew::messages.reports') }}</a>
            <a class="collapse-item" href="{{ url('/leads-new/settings') }}">{{ __('leadsnew::messages.settings') }}</a>
        </div>
    </div>
</li>

{{-- Sales Agent Management Menu --}}

@if ($can_view_sales_agent_sidebar)
    <li class="nav-item {{ in_array($request->segment(1), ['salesagent']) ? 'active active-sub' : '' }}">
        <a class="nav-link collapsed"
           href="#"
           data-toggle="collapse"
           data-target="#sales-agent-menu"
           aria-expanded="true"
           aria-controls="sales-agent-menu">
            <i class="fa fa-user-secret"></i>
            <span>@lang('lang_v1.sales_agents')</span>
        </a>

        <div id="sales-agent-menu"
             class="collapse {{ in_array($request->segment(1), ['salesagent']) ? 'show' : '' }}"
             aria-labelledby="headingPages"
             data-parent="#accordionSidebar">

            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">@lang('lang_v1.sales_agents'):</h6>

                <a class="collapse-item {{ $request->segment(1) == 'salesagent' && $request->segment(2) == 'management' ? 'active' : '' }}"
                   href="{{ url('/salesagent/management') }}">
                    Dis Sales Agents
                </a>
            </div>
        </div>
    </li>
@endif

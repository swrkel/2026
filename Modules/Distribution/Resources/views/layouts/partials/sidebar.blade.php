<li class="treeview {{ in_array($request->segment(1), ['fleet-management']) ? 'active active-sub' : '' }}" id="tour_step5">
    <a href="#"><i class="fa fa-ship"></i> <span>@lang('distribution::lang.fleet_management')</span>
        <span class="pull-right-container">
            <i class="fa fa-angle-left pull-right"></i>
        </span>
    </a>
    <ul class="treeview-menu">
        <li class="{{ $request->segment(2) == 'fleet' ? 'active' : '' }}"><a
                href="{{action('\Modules\Distribution\Http\Controllers\DistributionController@index')}}"><i class="fa fa-list"></i>
                @lang('distribution::lang.list_fleet')</a>
        </li>
        <li class="{{ $request->segment(2) == 'route-operation' ? 'active' : '' }}"><a
                href="{{action('\Modules\Distribution\Http\Controllers\RouteOperationController@index')}}"><i class="fa fa-taxi"></i>
                @lang('distribution::lang.route_operation')</a>
        </li>
        <li class="{{ $request->segment(2) == 'settings' ? 'active' : '' }}"><a
                href="{{action('\Modules\Distribution\Http\Controllers\SettingController@index')}}"><i class="fa fa-cogs"></i>
                @lang('distribution::lang.fleet_settings')</a>
        </li>
        <li class="{{ $request->segment(2) == 'sales-orders' && $request->segment(3) == '' ? 'active' : '' }}">
            <a href="{{ route('distribution.sales_orders.index') }}"><i class="fa fa-list"></i> Sales Orders</a>
        </li>
        <li class="{{ $request->segment(2) == 'invoices' && $request->segment(3) == '' ? 'active' : '' }}">
            <a href="{{ route('distribution.invoices.index') }}"><i class="fa fa-file-text"></i> Dis. Invoices</a>
        </li>
        <li class="{{ $request->segment(2) == 'invoices' && $request->segment(3) == 'create' ? 'active' : '' }}">
            <a href="{{ route('distribution.invoices.create') }}"><i class="fa fa-plus-circle"></i> Create Dis. Invoice</a>
        </li>

    </ul>
</li>

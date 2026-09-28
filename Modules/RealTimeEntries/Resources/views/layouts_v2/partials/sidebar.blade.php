@php
    $business_id = request()->session()->get('user.business_id') ?? request()->session()->get('business.id');
    $subscription = !empty($business_id) ? Modules\Superadmin\Entities\Subscription::current_subscription($business_id) : null;
    $package_details = [];

    if (!empty($subscription) && !empty($subscription->package_details)) {
        $package_details = is_array($subscription->package_details)
            ? $subscription->package_details
            : (array) $subscription->package_details;
    }

    $real_time_entries_enabled = !empty($package_details['real_time_entries'])
        || !empty($package_details['real_time_entries_module'])
        || !empty($package_details['enable_real_time_entries'])
        || !empty($package_details['enable_real_time_entries_module']);

    $user = auth()->user();
    $is_business_admin = $user && method_exists($user, 'hasRole') && $user->hasRole('Admin#' . $business_id);

    $can_view_real_time_entries = $real_time_entries_enabled && $user && (
        $user->can('superadmin')
        || $is_business_admin
        || $user->can('real_time_entries.access')
        || $user->can('real_time_entries.view')
        || $user->can('real_time_entries.payments')
        || $user->can('real_time_entries.other_sales')
        || $user->can('real_time_entries.reports')
        || $user->can('real_time_entries.settings')
        || $user->can('real_time_entries')
        || $user->can('realtimeentries.access')
    );
@endphp

@if($can_view_real_time_entries)
<li class="nav-item">
    <a class="nav-link collapsed {{ request()->routeIs('realtime.*') ? 'active active-sub' : '' }}" href="#"
        data-toggle="collapse" data-target="#real-time-entries-menu"
        aria-expanded="{{ request()->routeIs('realtime.*') ? 'true' : 'false' }}" aria-controls="real-time-entries-menu">
        <i class="fa fa-tint fa-lg"></i>
        <span>@lang('realtimeentries::lang.real_time_entries')</span>
    </a>

    <div id="real-time-entries-menu" class="collapse {{ request()->routeIs('realtime.*') ? 'show' : '' }}"
        aria-labelledby="headingPages" data-parent="#accordionSidebar">

        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">
                @lang('realtimeentries::lang.real_time_entries'):
            </h6>

            @if($user->can('superadmin') || $is_business_admin || $user->can('real_time_entries.access') || $user->can('real_time_entries.view') || $user->can('real_time_entries.payments') || $user->can('real_time_entries'))
                <a class="collapse-item {{ request()->routeIs('realtime.real-time-payments') ? 'active' : '' }}"
                    href="{{ route('realtime.real-time-payments') }}">
                    @lang('realtimeentries::lang.real_time_payments')
                </a>
            @endif

            @if($user->can('superadmin') || $is_business_admin || $user->can('real_time_entries.access') || $user->can('real_time_entries.other_sales') || $user->can('real_time_entries'))
                <a class="collapse-item {{ request()->routeIs('realtime.other-sales') ? 'active' : '' }}"
                    href="{{ route('realtime.other-sales') }}">
                    @lang('realtimeentries::lang.other_sales')
                </a>

                <a class="collapse-item {{ request()->routeIs('realtime.list-other-sales') ? 'active' : '' }}"
                    href="{{ route('realtime.list-other-sales') }}">
                    @lang('realtimeentries::lang.list_other_sales')
                </a>
            @endif

            @if($user->can('superadmin') || $is_business_admin || $user->can('real_time_entries.access') || $user->can('real_time_entries.reports') || $user->can('real_time_entries'))
                <a class="collapse-item {{ request()->routeIs('realtime.meters-with-payments') ? 'active' : '' }}"
                    href="{{ route('realtime.meters-with-payments') }}">
                    @lang('realtimeentries::lang.meters_with_payments')
                </a>

                <a class="collapse-item {{ request()->routeIs('realtime.payment-summary') ? 'active' : '' }}"
                    href="{{ route('realtime.payment-summary') }}">
                    @lang('realtimeentries::lang.payment_summary')
                </a>
            @endif

            @if($user->can('superadmin') || $is_business_admin || $user->can('real_time_entries.access') || $user->can('real_time_entries.settings') || $user->can('real_time_entries'))
                <a class="collapse-item {{ request()->routeIs('realtime.real-time-settings') ? 'active' : '' }}"
                    href="{{ route('realtime.real-time-settings') }}">
                    @lang('realtimeentries::lang.real_time_settings')
                </a>
            @endif
        </div>
    </div>
</li>
@endif

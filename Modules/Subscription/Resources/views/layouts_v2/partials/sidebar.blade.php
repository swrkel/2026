@can('superadmin')
<li class="nav-item {{ request()->segment(1) === 'subscription' ? 'active active-sub' : '' }}">
    <a class="nav-link" href="{{ route('subscription.estate.index') }}">
        <i class="fa fa-calendar-check-o"></i>
        <span>Subscription Management</span>
    </a>
</li>
@endcan

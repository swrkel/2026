@php
    $membershipNewMenu = \Modules\MembershipNew\app\Menu\MembershipNewNavigationRegistry::sidebar();
    $membershipNewEnabled = !empty($show_membership_new_menu) || !empty($membership_new_module);
    $membershipNewUser = auth()->user();

    // Parent visibility remains controlled by the host's Membership-New flag.
    // Business Admin (Admin#<business>) and superadmin can see every registered
    // page route. Normal users see only pages granted to their role.
    $membershipNewPrivileged = !empty($is_admin) || !empty($is_privileged_sidebar_user);

    if ($membershipNewUser) {
        try {
            if ($membershipNewUser->can('superadmin')) {
                $membershipNewPrivileged = true;
            }
        } catch (\Throwable $e) {
            // Continue with business-admin role detection below.
        }
    }

    if (!$membershipNewPrivileged && $membershipNewUser && method_exists($membershipNewUser, 'hasRole')) {
        try {
            $membershipNewBusinessId = session('business.id')
                ?: session('user.business_id')
                ?: session('business_id')
                ?: ($membershipNewUser->business_id ?? null);

            if (!empty($membershipNewBusinessId)) {
                $membershipNewPrivileged = $membershipNewUser->hasRole('Admin#' . $membershipNewBusinessId);
            }
        } catch (\Throwable $e) {
            $membershipNewPrivileged = false;
        }
    }

    $membershipNewVisibleItems = collect($membershipNewMenu['items'] ?? [])->filter(function ($item) use ($membershipNewUser, $membershipNewPrivileged) {
        if (!$membershipNewUser || empty($item['route']) || !\Illuminate\Support\Facades\Route::has($item['route'])) {
            return false;
        }

        if ($membershipNewPrivileged) {
            return true;
        }

        try {
            return !empty($item['permission']) && $membershipNewUser->can($item['permission']);
        } catch (\Throwable $e) {
            return false;
        }
    })->values();

    $membershipNewCanOpen = $membershipNewEnabled
        && \Illuminate\Support\Facades\Route::has($membershipNewMenu['route'] ?? '')
        && $membershipNewVisibleItems->isNotEmpty();

    $membershipNewActive = request()->is('membership-new') || request()->is('membership-new/*');
@endphp

@if($membershipNewCanOpen)
<li class="nav-item {{ $membershipNewActive ? 'active active-sub' : '' }}">
    <a class="nav-link collapsed"
       href="#"
       data-toggle="collapse"
       data-target="#membership-new-menu"
       aria-expanded="{{ $membershipNewActive ? 'true' : 'false' }}"
       aria-controls="membership-new-menu">
        <i class="{{ $membershipNewMenu['icon'] ?? 'fa fa-id-card' }}"></i>
        <span>Membership-New</span>
    </a>

    <div id="membership-new-menu"
         class="collapse {{ $membershipNewActive ? 'show' : '' }}"
         data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Membership-New:</h6>

            @foreach($membershipNewVisibleItems as $item)
                <a class="collapse-item {{ request()->routeIs($item['route']) ? 'active active-sub' : '' }}"
                   href="{{ route($item['route']) }}">
                    {{ $item['title'] }}
                </a>
            @endforeach
        </div>
    </div>
</li>
@endif

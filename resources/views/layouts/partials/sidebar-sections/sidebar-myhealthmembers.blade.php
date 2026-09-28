
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

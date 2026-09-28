{{--
    Central Super Admin sidebar. Genuine Super Admin must be able to see every
    installed module. Tenant/business permission gates do not apply here; Login
    As Business uses the normal business sidebar instead.
--}}
@php
    $superadminInstalledModules = [];
    try {
        foreach (\App\Services\AutomaticModuleRegistry::all() as $installedModule) {
            $key = \App\Services\AutomaticModuleRegistry::normalizeKey((string) ($installedModule['key'] ?? ''));
            if ($key === '') {
                continue;
            }
            $title = trim((string) ($installedModule['title'] ?? $installedModule['name'] ?? $key));
            $primaryUrl = trim((string) ($installedModule['primary_url'] ?? ''));
            $superadminInstalledModules[$key] = [
                'key' => $key,
                'title' => $title !== '' ? $title : ucwords(str_replace('_', ' ', $key)),
                'url' => $primaryUrl,
            ];
        }
        uasort($superadminInstalledModules, static function ($a, $b) {
            return strnatcasecmp((string) $a['title'], (string) $b['title']);
        });
    } catch (\Throwable $e) {
        $superadminInstalledModules = [];
    }

    // Simple Audit has a dedicated Central-only sidebar section.
    // Keep it out of the generic installed-module catalogue to avoid duplicates.
    unset($superadminInstalledModules['simple_audit']);
@endphp
<ul class="custom-overflow sidebar-menu navbar-nav bg-gradient-primary sidebar sidebar-dark accordion"
    id="accordionSidebar" style="width:220px !important;max-height:100vh;">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ url('/superadmin') }}">
        <div class="sidebar-brand-text mx-3">SYZYGY</div>
    </a>

    <hr class="sidebar-divider">

    <li style="padding:0 10px 12px;">
        <input type="text" id="sidebarFilter" placeholder="Filter menu..." class="form-control">
    </li>

    {{-- Central-only standalone modules are explicit top-level sections. --}}
    @include('layouts.partials.sidebar-sections.sidebar-simple-audit')

    @includeIf('superadmin::layouts_v2.partials.sidebar', ['isCentralSuperAdminSidebar' => true])

    {{-- Genuine Super Admin catalogue: every installed/registered module is visible. --}}
    @if(!empty($superadminInstalledModules))
        <li class="nav-item">
            <a class="nav-link collapsed" href="#" data-toggle="collapse"
               data-target="#superadmin-installed-modules-menu" aria-expanded="false"
               aria-controls="superadmin-installed-modules-menu">
                <i class="fa fa-th-large"></i>
                <span>All Installed Modules ({{ count($superadminInstalledModules) }})</span>
            </a>
            <div id="superadmin-installed-modules-menu" class="collapse" data-parent="#accordionSidebar">
                <div class="bg-white py-2 collapse-inner rounded" style="max-height:62vh;overflow-y:auto;">
                    <h6 class="collapse-header">Installed Modules:</h6>
                    @foreach($superadminInstalledModules as $installedModule)
                        @if(!empty($installedModule['url']))
                            <a class="collapse-item superadmin-installed-module-link"
                               data-module-find="{{ strtolower($installedModule['title'] . ' ' . $installedModule['key']) }}"
                               href="{{ url($installedModule['url']) }}">{{ $installedModule['title'] }}</a>
                        @else
                            <span class="collapse-item superadmin-installed-module-link text-muted"
                                  data-module-find="{{ strtolower($installedModule['title'] . ' ' . $installedModule['key']) }}"
                                  title="This installed module does not publish a primary URL.">{{ $installedModule['title'] }}</span>
                        @endif
                    @endforeach
                </div>
            </div>
        </li>
    @endif

    {{-- Help Guide must remain available in the central Super Admin area too. --}}
    <li class="nav-item {{ request()->segment(1) === 'helpguide' ? 'active active-sub' : '' }}">
        <a class="nav-link {{ request()->segment(1) === 'helpguide' ? '' : 'collapsed' }}" href="#"
           data-toggle="collapse" data-target="#superadmin-helpguide-menu"
           aria-expanded="{{ request()->segment(1) === 'helpguide' ? 'true' : 'false' }}"
           aria-controls="superadmin-helpguide-menu">
            <i class="fa fa-question-circle"></i>
            <span>Help Guide</span>
        </a>
        <div id="superadmin-helpguide-menu"
             class="collapse {{ request()->segment(1) === 'helpguide' ? 'show' : '' }}"
             data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Help Guide:</h6>
                <a class="collapse-item" href="{{ url('/helpguide/dashboard') }}">Dashboard</a>
                <a class="collapse-item" href="{{ \Illuminate\Support\Facades\Route::has('frontend') ? route('frontend') : url('/helpguide') }}" target="_blank">Home Page</a>
                <a class="collapse-item" href="{{ url('/helpguide/dashboard/tickets') }}">Tickets</a>
                <a class="collapse-item" href="{{ url('/helpguide/dashboard#/articles') }}">Articles</a>
                <a class="collapse-item" href="{{ url('/helpguide/dashboard#/categories') }}">Categories</a>
                <a class="collapse-item" href="{{ url('/helpguide/dashboard#/modules') }}">Modules</a>
                <a class="collapse-item" href="{{ url('/helpguide/dashboard/settings') }}">Settings</a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider d-none d-md-block">
</ul>

@includeIf('layouts.partials.sidebar-unified-controller')
@includeIf('layouts.partials.sidebar-unified-search')

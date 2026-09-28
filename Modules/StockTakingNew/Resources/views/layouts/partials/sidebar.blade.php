@inject('request', 'Illuminate\Http\Request')

@php
    /*
     * Module-owned sidebar definition.
     *
     * The application AutomaticModuleRegistry discovers this view from the
     * StockTakingNew module folder and appends it to the active sidebar. The
     * parent business switch and direct-route guard remain controlled by
     * Super Admin > All Businesses > Manage.
     */
    $__stkNewItems = [];
    $__stkNewActive = $request->segment(1) === 'stock-taking-new';

    try {
        $__stkNewUser = auth()->user();
        $__stkNewCanShow = static function (string $userPermission, string $businessPermission) use ($__stkNewUser): bool {
            return $__stkNewUser
                && $__stkNewUser->can($userPermission)
                && \App\Utils\SidebarPermissionUtil::isAutomaticPermissionEnabled($businessPermission);
        };

        if (\Illuminate\Support\Facades\Route::has('stock-taking-new.dashboard')
            && $__stkNewCanShow('stock_taking_new.dashboard.view', 'stock_taking_new_dashboard')) {
            $__stkNewItems[] = [
                'label' => 'Dashboard',
                'icon' => 'fa fa-dashboard',
                'url' => route('stock-taking-new.dashboard'),
                'active' => request()->routeIs('stock-taking-new.dashboard*'),
            ];
        }

        if (\Illuminate\Support\Facades\Route::has('stock-taking-new.sessions.index')
            && $__stkNewCanShow('stock_taking_new.sessions.view', 'stock_taking_new_sessions_view')) {
            $__stkNewItems[] = [
                'label' => 'Stock Taking Sessions',
                'icon' => 'fa fa-list-alt',
                'url' => route('stock-taking-new.sessions.index'),
                'active' => request()->routeIs('stock-taking-new.sessions.index')
                    || request()->routeIs('stock-taking-new.sessions.show')
                    || request()->routeIs('stock-taking-new.sessions.edit')
                    || request()->routeIs('stock-taking-new.counts.*')
                    || request()->routeIs('stock-taking-new.import.*')
                    || request()->routeIs('stock-taking-new.documents.*')
                    || request()->routeIs('stock-taking-new.shares.*'),
            ];
        }

        if (\Illuminate\Support\Facades\Route::has('stock-taking-new.sessions.create')
            && $__stkNewCanShow('stock_taking_new.sessions.create', 'stock_taking_new_sessions_create')) {
            $__stkNewItems[] = [
                'label' => 'New Stock Take',
                'icon' => 'fa fa-plus-circle',
                'url' => route('stock-taking-new.sessions.create'),
                'active' => request()->routeIs('stock-taking-new.sessions.create'),
            ];
        }

        if (\Illuminate\Support\Facades\Route::has('stock-taking-new.approvals.index')
            && $__stkNewCanShow('stock_taking_new.approvals.view', 'stock_taking_new_approvals_view')) {
            $__stkNewItems[] = [
                'label' => 'Approvals',
                'icon' => 'fa fa-check-circle',
                'url' => route('stock-taking-new.approvals.index'),
                'active' => request()->routeIs('stock-taking-new.approvals.*'),
            ];
        }

        if (\Illuminate\Support\Facades\Route::has('stock-taking-new.reports.index')
            && $__stkNewCanShow('stock_taking_new.reports.view', 'stock_taking_new_reports_view')) {
            $__stkNewItems[] = [
                'label' => 'Reports',
                'icon' => 'fa fa-bar-chart',
                'url' => route('stock-taking-new.reports.index'),
                'active' => request()->routeIs('stock-taking-new.reports.*'),
            ];
        }

        if (\Illuminate\Support\Facades\Route::has('stock-taking-new.templates.index')
            && $__stkNewCanShow('stock_taking_new.templates.manage', 'stock_taking_new_templates_view')) {
            $__stkNewItems[] = [
                'label' => 'Count Templates',
                'icon' => 'fa fa-clone',
                'url' => route('stock-taking-new.templates.index'),
                'active' => request()->routeIs('stock-taking-new.templates.*'),
            ];
        }

        if (\Illuminate\Support\Facades\Route::has('stock-taking-new.schedules.index')
            && $__stkNewCanShow('stock_taking_new.schedules.manage', 'stock_taking_new_schedules_view')) {
            $__stkNewItems[] = [
                'label' => 'Schedules',
                'icon' => 'fa fa-calendar-check-o',
                'url' => route('stock-taking-new.schedules.index'),
                'active' => request()->routeIs('stock-taking-new.schedules.*'),
            ];
        }

        if (\Illuminate\Support\Facades\Route::has('stock-taking-new.settings.index')
            && $__stkNewCanShow('stock_taking_new.settings.manage', 'stock_taking_new_settings_view')) {
            $__stkNewItems[] = [
                'label' => 'Settings',
                'icon' => 'fa fa-cogs',
                'url' => route('stock-taking-new.settings.index'),
                'active' => request()->routeIs('stock-taking-new.settings.*'),
            ];
        }
    } catch (\Throwable $__stkNewSidebarException) {
        $__stkNewItems = [];
    }
@endphp

@if($__stkNewItems !== [])
    <li class="nav-item {{ $__stkNewActive ? 'active active-sub' : '' }}" data-sidebar-module="stock_taking_new">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#stock-taking-new-menu"
           aria-expanded="{{ $__stkNewActive ? 'true' : 'false' }}" aria-controls="stock-taking-new-menu">
            <i class="fa fa-check-square-o"></i>
            <span>Stock Taking - New</span>
        </a>
        <div id="stock-taking-new-menu"
             class="collapse {{ $__stkNewActive ? 'show' : '' }}"
             data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Stock Taking - New:</h6>
                @foreach($__stkNewItems as $__stkNewItem)
                    <a class="collapse-item {{ !empty($__stkNewItem['active']) ? 'active' : '' }}"
                       href="{{ $__stkNewItem['url'] }}">
                        <i class="{{ $__stkNewItem['icon'] }} mr-1"></i>
                        {{ $__stkNewItem['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </li>
@endif

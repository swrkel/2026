@php
    use Illuminate\Support\Facades\Route;

    $business_id = request()->session()->get('user.business_id');
    $hasDailyCollectionSw = false;

    if (!empty($business_id) && class_exists(\App\Utils\ModuleUtil::class)) {
        try {
            $hasDailyCollectionSw = \App\Utils\ModuleUtil::hasThePermissionInSubscription($business_id, 'daily_collection_sw');
        } catch (\Throwable $e) {
            $hasDailyCollectionSw = false;
        }
    }

    $user = auth()->check() ? auth()->user() : null;
    $isSuperadmin = $user && method_exists($user, 'can') && $user->can('superadmin');
    $routeExists = Route::has('dailycollectionsw.daily-collection-sw.index');
    $isActive = request()->is('dailycollectionsw*') || request()->routeIs('dailycollectionsw.*');
@endphp

@if(($hasDailyCollectionSw || $isSuperadmin) && $routeExists)
    <li class="{{ $isActive ? 'active' : '' }}">
        <a href="{{ route('dailycollectionsw.daily-collection-sw.index') }}">
            <i class="fa fa-briefcase"></i>
            <span>Daily Collection SW</span>
        </a>
    </li>
@endif

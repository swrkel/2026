@php
    $hotelMenu = config('hotelmanagement_menu', []);
    $hotelSections = $hotelMenu['sections'] ?? [];
    $currentRouteName = optional(request()->route())->getName() ?? '';
@endphp

<nav class="hm-module-nav" aria-label="Hotel Management sections">
    @foreach($hotelSections as $item)
        @php
            $allowed = true;

            if (!empty($item['permission']) && auth()->check()) {
                try {
                    $allowed = auth()->user()->can($item['permission']) || auth()->user()->can('hotel.view');
                } catch (Throwable $e) {
                    $allowed = true;
                }
            }

            $itemRoute = $item['route'] ?? '';
            $isActive = false;

            if ($itemRoute === 'hotel-management.dashboard') {
                $isActive = $currentRouteName === 'hotel-management.dashboard'
                    || \Illuminate\Support\Str::startsWith($currentRouteName, 'hotel-management.dashboards.');
            } elseif ($itemRoute === 'hotel-management.reports.index') {
                $isActive = \Illuminate\Support\Str::startsWith($currentRouteName, 'hotel-management.reports.');
            } elseif ($itemRoute !== '') {
                $routeGroup = \Illuminate\Support\Str::beforeLast($itemRoute, '.');
                $isActive = $currentRouteName === $itemRoute
                    || ($routeGroup !== 'hotel-management'
                        && \Illuminate\Support\Str::startsWith($currentRouteName, $routeGroup.'.'));
            }
        @endphp

        @if($allowed && $itemRoute !== '' && Route::has($itemRoute))
            <a href="{{ route($itemRoute) }}"
               class="{{ $isActive ? 'active' : '' }}"
               @if($isActive) aria-current="page" @endif>
                {{ $item['label'] }}
            </a>
        @endif
    @endforeach
</nav>

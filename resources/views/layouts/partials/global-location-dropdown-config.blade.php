@php
    $__erpLocationAccess = app(\App\Services\BusinessLocationAccessService::class);
    $__erpLocationDropdownLocations = [];
    $__erpLocationDropdownBusinessId = auth()->check()
        ? $__erpLocationAccess->businessId()
        : null;
    $__erpEnforceLocationBoundary = auth()->check()
        && !$__erpLocationAccess->isCentralSuperAdmin();

    if (!empty($__erpLocationDropdownBusinessId)) {
        try {
            $__erpLocationDropdownLocations = \App\BusinessLocation::getDropdownCollection(
                (int) $__erpLocationDropdownBusinessId
            )->map(static function ($location) {
                return [
                    'id' => (string) $location->id,
                    'name' => (string) $location->name,
                ];
            })->values()->all();
        } catch (\Throwable $exception) {
            $__erpLocationDropdownLocations = [];
        }
    }
@endphp

<script>
    window.erpLocationDropdownConfig = Object.assign(
        {},
        window.erpLocationDropdownConfig || {},
        {
            locations: @json($__erpLocationDropdownLocations),
            businessId: @json($__erpLocationDropdownBusinessId),
            enforceBusinessBoundary: @json($__erpEnforceLocationBoundary)
        }
    );
</script>

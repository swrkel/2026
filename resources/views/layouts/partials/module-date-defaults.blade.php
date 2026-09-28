@php
    $__erp_module_date_config = [
        'enabled' => false,
        'moduleKey' => 'Core',
        'source' => 'computer',
        'globalDate' => null,
        'businessDateFormat' => 'm/d/Y',
    ];

    if (auth()->check()) {
        try {
            $__erp_module_date_config = app(\App\Services\ModuleDateDefaultService::class)
                ->configurationForRequest(request());
        } catch (\Throwable $__erp_module_date_error) {
            report($__erp_module_date_error);
        }
    }
@endphp
<script>
    window.ERP_MODULE_DATE_DEFAULTS = @json($__erp_module_date_config);
</script>
<script src="{{ asset('js/module-date-defaults.js?v=20260821-1') }}"></script>

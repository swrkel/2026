@php
    $mpcsPageName = $mpcs_page ?? null;
    $mpcsPageConfig = $mpcs_config ?? [];
    $mpcsAssetPath = public_path('modules/mpcs/js/mpcs.js');
    $mpcsAssetVersion = file_exists($mpcsAssetPath)
        ? filemtime($mpcsAssetPath)
        : (config('app.asset_version') ?? '1');
@endphp

<script type="text/javascript">
    window.MPCS_BOOTSTRAP = {
        page: @json($mpcsPageName),
        config: @json($mpcsPageConfig),
    };
</script>
<script type="text/javascript"
        src="{{ asset('modules/mpcs/js/mpcs.js') }}?v={{ $mpcsAssetVersion }}"
        onerror="console.error('[MPCS] Failed to load the module JavaScript file. Native form submission remains available.');">
</script>

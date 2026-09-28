{{--
    Keep this as the final operational script in authenticated layouts.
    Page/module handlers load first; the global guard then cooperates with them.
--}}
<script type="text/javascript" src="{{ asset('js/erp-global-operation-guard.js?v=' . (file_exists(public_path('js/erp-global-operation-guard.js')) ? filemtime(public_path('js/erp-global-operation-guard.js')) : $asset_v)) }}"></script>
<script type="text/javascript" src="{{ asset('js/erp-global-message-system.js?v=' . (file_exists(public_path('js/erp-global-message-system.js')) ? filemtime(public_path('js/erp-global-message-system.js')) : $asset_v)) }}"></script>

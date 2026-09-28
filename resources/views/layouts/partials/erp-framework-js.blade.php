{{-- ERP Experience Framework V4 JS master loader. Load after jQuery/DataTables/Bootstrap. --}}
<script src="{{ asset('js/erp-experience-framework-v4.js?v=' . (file_exists(public_path('js/erp-experience-framework-v4.js')) ? filemtime(public_path('js/erp-experience-framework-v4.js')) : $asset_v)) }}"></script>

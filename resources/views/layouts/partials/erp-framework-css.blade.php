{{-- ERP Experience Framework V4 CSS master loader. Load after existing ERP CSS. --}}
<link rel="stylesheet" href="{{ asset('css/erp-experience-framework-v4.css?v=' . (file_exists(public_path('css/erp-experience-framework-v4.css')) ? filemtime(public_path('css/erp-experience-framework-v4.css')) : $asset_v)) }}">

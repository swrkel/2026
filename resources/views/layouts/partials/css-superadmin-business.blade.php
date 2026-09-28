{{-- Minimal stylesheet bundle for /superadmin/business. --}}
<link rel="stylesheet" href="{{ asset('bootstrap/css/bootstrap.min.css?v=' . $asset_v) }}">
<link rel="stylesheet" href="{{ asset('v2/css/sidebar.min.css?v=8') }}">
<link rel="stylesheet" href="{{ asset('v2/css/themify-icons.css') }}">
<link rel="stylesheet" href="{{ asset('v2/css/typography.css') }}">
<link rel="stylesheet" href="{{ asset('v2/css/default-css.css') }}">
<link rel="stylesheet" href="{{ asset('v2/css/styles.css') }}">
<link rel="stylesheet" href="{{ asset('v2/css/responsive.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/font-awesome/css/font-awesome.min.css?v=' . $asset_v) }}">
<link rel="stylesheet" href="{{ asset('AdminLTE/css/AdminLTE.min.css?v=' . $asset_v) }}">
<link rel="stylesheet" href="{{ asset('AdminLTE/css/skins/_all-skins.min.css?v=' . $asset_v) }}">
<link rel="stylesheet" href="{{ asset('AdminLTE/plugins/select2/css/select2.min.css?v=' . $asset_v) }}">
<link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css?v=' . $asset_v) }}">
<link rel="stylesheet" href="{{ asset('css/app.css?v=' . $asset_v) }}">
<link rel="stylesheet" href="{{ asset('v2/css/syzygy-classic-sidebar.css?v=18') }}">

{{-- Transfer-safe current-host links and inline sidebar fallback. --}}
@include('layouts.partials.sidebar-critical-runtime-css')

{{-- Shared mutation progress and duplicate-action guard for Super Admin business pages. --}}
<link rel="stylesheet" href="{{ asset('css/erp-global-operation-guard.css?v=' . (file_exists(public_path('css/erp-global-operation-guard.css')) ? filemtime(public_path('css/erp-global-operation-guard.css')) : $asset_v)) }}">

{{-- Global success/error message colours and notification presentation. --}}
<link rel="stylesheet" href="{{ asset('css/erp-global-message-system.css?v=' . (file_exists(public_path('css/erp-global-message-system.css')) ? filemtime(public_path('css/erp-global-message-system.css')) : $asset_v)) }}">

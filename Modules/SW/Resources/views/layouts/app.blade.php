{{--
    SW Module layout.

    Extends the application layout rather than carrying its own <html>. A module
    with a standalone layout renders as a separate application inside the ERP -
    no sidebar, no header, different typography - which is what happened with
    Poultry and with the Help Guide admin pages.

    Styling is written here rather than borrowed from another module. The POS
    layout references ch-shell / ch-hero classes that exist only in a separate
    copy of the application, so pages using them render an empty band.
--}}

@extends('layouts.app')

@section('title', $title ?? 'SW Module')

@section('content')

@push('css')
<style>
.sw-head{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;
    flex-wrap:wrap;padding:16px 20px;background:#fff;border:1px solid #e3e8f0;
    border-radius:12px;box-shadow:0 2px 8px rgba(23,32,51,.05);margin-bottom:16px}
.sw-eyebrow{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
    color:#8b96ab;margin-bottom:4px}
.sw-head h2{font-size:19px;font-weight:700;margin:0;color:#172033}
.sw-head p{margin:6px 0 0;color:#67738a;font-size:13px;max-width:70ch;line-height:1.5}
.sw-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}

.sw-card{background:#fff;border:1px solid #e3e8f0;border-radius:12px;
    box-shadow:0 2px 8px rgba(23,32,51,.05);padding:18px;margin-bottom:16px}
.sw-card h3{font-size:15px;font-weight:700;margin:0 0 14px;color:#172033}

.sw-btn{border:0;border-radius:8px;padding:9px 15px;font-weight:600;font-size:13px;
    cursor:pointer;background:#1f6feb;color:#fff;text-decoration:none;display:inline-block}
.sw-btn:hover,.sw-btn:focus{background:#1a5fd0;color:#fff;text-decoration:none}
.sw-btn.secondary{background:#eef2f7;color:#2b3648}
.sw-btn.secondary:hover{background:#e2e8f1;color:#2b3648}
.sw-btn.danger{background:#dc2626}
.sw-btn.danger:hover{background:#b91c1c}
.sw-btn[disabled]{opacity:.5;cursor:not-allowed}

.sw-field{margin-bottom:13px}
.sw-field label{display:block;font-weight:700;font-size:12px;color:#4b5870;margin-bottom:6px}
.sw-field input,.sw-field select,.sw-field textarea{width:100%;border:1px solid #d9e0ea;
    border-radius:8px;padding:9px 11px;background:#fff;font-size:13px}
.sw-field input:focus,.sw-field select:focus,.sw-field textarea:focus{
    outline:0;border-color:#1f6feb;box-shadow:0 0 0 3px rgba(31,111,235,.10)}

.sw-table{width:100%;border-collapse:collapse;font-size:13px}
.sw-table th{padding:9px 10px;border-bottom:2px solid #e5eaf1;text-align:left;
    font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#68758b}
.sw-table td{padding:9px 10px;border-bottom:1px solid #eef2f7;vertical-align:middle}
.sw-table tbody tr:hover{background:#f8fafd}
.sw-table .num{text-align:right;font-variant-numeric:tabular-nums}

.sw-badge{display:inline-block;border-radius:999px;padding:3px 10px;font-size:11px;font-weight:600}
.sw-badge.open{background:#eaf7ee;color:#29733a}
.sw-badge.closed{background:#fef3c7;color:#92400e}
.sw-badge.settled{background:#e0e7ff;color:#3730a3}
.sw-badge.void{background:#f1f3f7;color:#6b7688}

.sw-note{font-size:12px;color:#6f7b90;line-height:1.5}
.sw-empty{text-align:center;padding:30px 20px;color:#69758b}

/* Daily Cash Status */
.sw-status-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}
.sw-stat{border:1px solid #e3e8f0;border-radius:10px;padding:14px;background:#fbfcfe}
.sw-stat .k{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#68758b;font-weight:700}
.sw-stat .v{font-size:19px;font-weight:700;margin-top:6px;font-variant-numeric:tabular-nums}
.sw-stat.total{background:#eef4ff;border-color:#c9d9ff}
.sw-stat.total .v{color:#1f4bd8}
.sw-stat .sign{color:#8b96ab;font-weight:400}

/* Tabs */
.sw-tabs{display:flex;gap:4px;border-bottom:2px solid #e5eaf1;margin-bottom:16px;flex-wrap:wrap}
.sw-tab{padding:9px 16px;border:0;background:none;font-size:13px;font-weight:600;
    color:#68758b;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px}
.sw-tab.active{color:#1f6feb;border-bottom-color:#1f6feb}
.sw-panel{display:none}
.sw-panel.active{display:block}
</style>
@endpush

<section class="content">
    <div style="padding:0 4px">

        <div class="sw-head">
            <div>
                <div class="sw-eyebrow">SW Module</div>
                <h2>{{ $heading ?? ($title ?? 'SW Module') }}</h2>
                @isset($subheading)<p>{{ $subheading }}</p>@endisset
            </div>
            <div class="sw-actions">
                <a href="{{ route('sw.operators.index') }}" class="sw-btn secondary">
                    <i class="fa fa-users"></i> SW Operators</a>
                <a href="{{ route('sw.list-shifts.index') }}" class="sw-btn secondary">
                    <i class="fa fa-list-alt"></i> List SW Shifts</a>
                @if(auth()->user()->can('superadmin') || auth()->user()->can('sw.daily_shift.view'))
                    <a href="{{ route('sw.shift-operations.index') }}" class="sw-btn secondary">
                        <i class="fa fa-clock-o"></i> Shift Operations</a>
                @endif
                <a href="{{ route('sw.settlements.index') }}" class="sw-btn secondary">
                    <i class="fa fa-list"></i> List SW Settlements</a>
            </div>
        </div>

        @php
            /*
             | SW controllers use the application's normal flash structure:
             |     ['success' => 1, 'msg' => '...']
             | while a few older actions still flash a plain string. Rendering
             | the whole array through {{ }} calls htmlspecialchars(array) and
             | crashes the page after a successful save. Normalise both forms
             | to a scalar message before Blade escapes it.
            */
            $__sw_status = session('status');
            $__sw_status_message = is_array($__sw_status)
                ? ($__sw_status['msg'] ?? $__sw_status['message'] ?? null)
                : $__sw_status;

            $__sw_error = session('error');
            $__sw_error_message = is_array($__sw_error)
                ? ($__sw_error['msg'] ?? $__sw_error['message'] ?? null)
                : $__sw_error;

            $__sw_status_message = is_scalar($__sw_status_message) ? (string) $__sw_status_message : null;
            $__sw_error_message = is_scalar($__sw_error_message) ? (string) $__sw_error_message : null;
        @endphp

        @if($__sw_status_message !== null && $__sw_status_message !== '')
            <div class="alert alert-success"><i class="fa fa-check-circle"></i> {{ $__sw_status_message }}</div>
        @endif
        @if($__sw_error_message !== null && $__sw_error_message !== '')
            <div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> {{ $__sw_error_message }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> {{ $errors->first() }}</div>
        @endif

        @yield('sw_content')

    </div>
</section>

@endsection

@push('javascript')
@stack('sw_scripts')
@endpush

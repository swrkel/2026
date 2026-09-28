@extends('layouts.app')

@section('title', 'Banners Management')

@section('content')
@php
    $bannerAllToken = \Modules\Superadmin\Services\BannerIdleService::ALL;
    $selectedTenantIds = old('banner_tenant_ids', $selectedTenantIds ?? []);
    $selectedBusinessTargets = old(
        'banner_business_targets',
        !empty($settings['all_businesses'])
            ? [$bannerAllToken]
            : (array) ($settings['business_targets'] ?? [])
    );
    $idleSeconds = old('banner_idle_seconds', (int) ($settings['idle_seconds'] ?? 0));
@endphp

<style>
.sa-banner-page {
    --sa-banner-blue:#2563eb;
    --sa-banner-ink:#0f172a;
    --sa-banner-muted:#64748b;
    --sa-banner-border:#e2e8f0;
}
.sa-banner-page .sa-banner-card {
    background:#fff;
    border:1px solid var(--sa-banner-border);
    border-radius:16px;
    box-shadow:0 10px 28px rgba(15,23,42,.07);
    overflow:visible;
}
.sa-banner-page .sa-banner-card-head {
    border-bottom:1px solid #eef2f7;
    padding:20px 22px 16px;
}
.sa-banner-page .sa-banner-card-head h3 {
    color:var(--sa-banner-ink);
    font-size:18px;
    font-weight:700;
    margin:0 0 6px;
}
.sa-banner-page .sa-banner-card-head p {
    color:var(--sa-banner-muted);
    margin:0;
}
.sa-banner-page .sa-banner-card-body { padding:22px; }
.sa-banner-page .sa-banner-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(285px,1fr));
    gap:18px;
}
.sa-banner-page .sa-banner-field label {
    color:#334155;
    display:block;
    font-size:13px;
    font-weight:700;
    margin-bottom:8px;
}
.sa-banner-page .sa-banner-field .form-control,
.sa-banner-page .sa-banner-field .select2-container { width:100% !important; }
.sa-banner-page .sa-banner-field small {
    color:var(--sa-banner-muted);
    display:block;
    line-height:1.45;
    margin-top:7px;
}
.sa-banner-page .select2-results__options {
    max-height:300px !important;
    overflow-y:auto !important;
}
.sa-banner-page .sa-banner-info {
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:10px;
    color:#475569;
    font-size:13px;
    line-height:1.55;
    margin-top:20px;
    padding:13px 15px;
}
.sa-banner-page .sa-banner-actions {
    align-items:center;
    border-top:1px solid #eef2f7;
    display:flex;
    gap:10px;
    justify-content:flex-end;
    margin-top:22px;
    padding-top:18px;
}
.sa-banner-page .sa-banner-save {
    border-radius:8px;
    font-weight:700;
    min-width:120px;
    padding:9px 18px;
}
</style>

<section class="content-header">
    <h1>Banners Management</h1>
    <p class="text-muted">Control when idle-screen banners appear and which tenants and businesses receive them.</p>
</section>

<section class="content sa-banner-page">
    @if(session('status') && is_array(session('status')))
        <div class="alert {{ !empty(session('status')['success']) ? 'alert-success' : 'alert-danger' }}">
            {{ session('status')['msg'] ?? '' }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Could not save.</strong>
            <ul style="margin:8px 0 0 18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('superadmin.banner-management.update') }}" id="sa-banner-management-form">
        @csrf

        <div class="sa-banner-card">
            <div class="sa-banner-card-head">
                <h3><i class="fa fa-picture-o"></i> Idle Screen Banner Settings</h3>
                <p>
                    Central banners are combined with banners added directly inside the applicable tenant.
                    When several banners are available, they rotate using each banner's Display Duration configured in
                    Super Admin Settings → Banners.
                </p>
            </div>

            <div class="sa-banner-card-body">
                <div class="sa-banner-grid">
                    <div class="sa-banner-field">
                        <label for="sa_banner_idle_seconds">Show the Banners in Users' screen when the screen is idle more than</label>
                        <div class="input-group">
                            <input type="number"
                                   id="sa_banner_idle_seconds"
                                   name="banner_idle_seconds"
                                   class="form-control"
                                   min="0"
                                   step="1"
                                   value="{{ (int) $idleSeconds }}"
                                   required>
                            <span class="input-group-addon">Seconds</span>
                        </div>
                        <small>Enter the idle time in seconds. Example: 30 seconds = 0.5 minutes. Enter 0 to keep the idle-screen banner display disabled.</small>
                    </div>

                    <div class="sa-banner-field">
                        <label for="sa_banner_tenants">Tenant Name (Database Name) / Tenant UID</label>
                        <select id="sa_banner_tenants"
                                name="banner_tenant_ids[]"
                                class="form-control"
                                multiple>
                            <option value="{{ $bannerAllToken }}" {{ in_array($bannerAllToken, array_map('strval', (array) $selectedTenantIds), true) ? 'selected' : '' }}>All</option>
                            @foreach($tenantOptions as $tenantOption)
                                <option value="{{ $tenantOption['id'] }}"
                                    {{ in_array((string) $tenantOption['id'], array_map('strval', (array) $selectedTenantIds), true) ? 'selected' : '' }}>
                                    {{ $tenantOption['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <small>“All” is the first option. Type to filter tenants and scroll through the available list.</small>
                    </div>

                    <div class="sa-banner-field">
                        <label for="sa_banner_businesses">Business</label>
                        <select id="sa_banner_businesses"
                                name="banner_business_targets[]"
                                class="form-control"
                                multiple>
                            <option value="{{ $bannerAllToken }}" {{ in_array($bannerAllToken, array_map('strval', (array) $selectedBusinessTargets), true) ? 'selected' : '' }}>All</option>
                            @foreach($businessOptions as $businessOption)
                                <option value="{{ $businessOption['value'] }}"
                                    {{ in_array((string) $businessOption['value'], array_map('strval', (array) $selectedBusinessTargets), true) ? 'selected' : '' }}>
                                    {{ $businessOption['label'] }}
                                </option>
                            @endforeach
                        </select>
                        <small>The Business list refreshes automatically for the selected tenant(s). Type to filter and use the scroll bar to review the list.</small>
                    </div>
                </div>

                <div class="sa-banner-info">
                    <strong>Default:</strong> All Tenants + All Businesses. Selecting “All” also covers tenants or businesses added later.
                    This page stores only the central idle-banner targeting settings; it does not change packages, module permissions, Manage New, or business subscriptions.
                </div>

                <div class="sa-banner-actions">
                    <span id="sa-banner-save-note" class="text-muted" style="margin-right:auto;">No unsaved changes.</span>
                    <button type="submit" class="btn btn-primary sa-banner-save">
                        <i class="fa fa-save"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>
@stop

@section('javascript')
<script>
$(function () {
    'use strict';

    var allToken = @json($bannerAllToken);
    var businessOptionsUrl = @json(route('superadmin.banner-management.business-options'));
    var $tenants = $('#sa_banner_tenants');
    var $businesses = $('#sa_banner_businesses');
    var initialBusinessSelection = @json(array_values(array_map('strval', (array) $selectedBusinessTargets)));
    var initialLoad = true;

    function initialiseSelect($element, placeholder) {
        if (!$.fn.select2) {
            return;
        }
        if ($element.hasClass('select2-hidden-accessible')) {
            $element.select2('destroy');
        }
        $element.select2({
            width: '100%',
            closeOnSelect: false,
            placeholder: placeholder
        });
    }

    function enforceAll($element) {
        var values = $element.val() || [];
        if (values.indexOf(allToken) !== -1 && values.length > 1) {
            var previous = $element.data('sa-last-selection') || [];
            var previouslyHadAll = previous.indexOf(allToken) !== -1;
            values = previouslyHadAll
                ? values.filter(function (value) { return value !== allToken; })
                : [allToken];
            $element.val(values).trigger('change.select2');
        }
        $element.data('sa-last-selection', ($element.val() || []).slice());
    }

    function setBusinessOptions(rows, selected) {
        selected = selected || [];
        $businesses.empty();
        $businesses.append(new Option(
            'All',
            allToken,
            false,
            selected.indexOf(allToken) !== -1 || selected.length === 0
        ));

        (rows || []).forEach(function (row) {
            var value = String(row.value || '');
            if (!value) {
                return;
            }
            $businesses.append(new Option(
                row.label || value,
                value,
                false,
                selected.indexOf(value) !== -1
            ));
        });

        initialiseSelect($businesses, 'Select Business');
        $businesses.data('sa-last-selection', ($businesses.val() || []).slice());
    }

    function loadBusinesses() {
        var tenantIds = $tenants.val() || [allToken];
        var selected = initialLoad ? initialBusinessSelection : [allToken];

        $.ajax({
            url: businessOptionsUrl,
            method: 'GET',
            dataType: 'json',
            data: { tenant_ids: tenantIds }
        }).done(function (response) {
            setBusinessOptions(response && response.data ? response.data : [], selected);
            initialLoad = false;
        }).fail(function () {
            if (!initialLoad) {
                setBusinessOptions([], [allToken]);
            }
            initialLoad = false;
        });
    }

    initialiseSelect($tenants, 'Select Tenant');
    initialiseSelect($businesses, 'Select Business');
    $tenants.data('sa-last-selection', ($tenants.val() || []).slice());
    $businesses.data('sa-last-selection', ($businesses.val() || []).slice());

    $tenants.on('change', function () {
        enforceAll($tenants);
        loadBusinesses();
        $('#sa-banner-save-note').text('Unsaved changes.');
    });

    $businesses.on('change', function () {
        enforceAll($businesses);
        $('#sa-banner-save-note').text('Unsaved changes.');
    });

    $('#sa_banner_idle_seconds').on('change input', function () {
        $('#sa-banner-save-note').text('Unsaved changes.');
    });
});
</script>
@stop

@php
    $bannerIdleSettings = $bannerManagement['settings'] ?? [
        'idle_minutes' => 0,
        'all_tenants' => true,
        'tenant_ids' => [],
        'all_businesses' => true,
        'business_targets' => [],
    ];
    $bannerTenantOptions = $bannerManagement['tenant_options'] ?? [];
    $bannerBusinessOptions = $bannerManagement['business_options'] ?? [];
    $bannerAllToken = \Modules\Superadmin\Services\BannerIdleService::ALL;
    $selectedTenantIds = !empty($bannerIdleSettings['all_tenants'])
        ? [$bannerAllToken]
        : (array) ($bannerIdleSettings['tenant_ids'] ?? []);
    $selectedBusinessTargets = !empty($bannerIdleSettings['all_businesses'])
        ? [$bannerAllToken]
        : (array) ($bannerIdleSettings['business_targets'] ?? []);
@endphp

<style>
.sa-banner-management {
    background:#fff;
    border:1px solid #dfe5ec;
    border-radius:10px;
    padding:18px;
    margin-bottom:18px;
}
.sa-banner-management h3 {
    margin:0 0 6px;
    font-size:16px;
    font-weight:650;
}
.sa-banner-management .sa-banner-help {
    color:#667085;
    font-size:13px;
    margin-bottom:18px;
}
.sa-banner-management-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(280px,1fr));
    gap:16px;
}
.sa-banner-field label {
    display:block;
    font-size:13px;
    font-weight:650;
    margin-bottom:7px;
}
.sa-banner-field .form-control,
.sa-banner-field .select2-container {
    width:100% !important;
}
.sa-banner-field small {
    display:block;
    margin-top:6px;
    color:#667085;
    line-height:1.4;
}
.sa-banner-management .select2-results__options {
    max-height:280px !important;
    overflow-y:auto !important;
}
.sa-banner-management-status {
    margin-top:16px;
    padding:10px 12px;
    background:#f8fafc;
    border:1px solid #e4e7ec;
    border-radius:7px;
    font-size:12.5px;
    color:#475467;
}
</style>

<div class="sa-banner-management">
    <h3>Banners Management</h3>
    <div class="sa-banner-help">
        This section is centrally managed; targeting is controlled by the Tenant and Business selections below.
        Central banners that apply to a tenant are combined with banners added directly in that tenant.
        When more than one banner is available, each banner uses its own Display Duration from
        Super Admin Settings → Banners before rotating to the next banner.
    </div>

    <div class="sa-banner-management-grid">
        <div class="sa-banner-field">
            <label for="sa_banner_idle_minutes">Show the Banners in Users' screen when the screen is idle more than</label>
            <div class="input-group">
                <input type="number"
                       id="sa_banner_idle_minutes"
                       name="banner_idle_minutes"
                       class="form-control"
                       min="0"
                       step="1"
                       value="{{ (int) ($bannerIdleSettings['idle_minutes'] ?? 0) }}"
                       required>
                <span class="input-group-addon">Minutes</span>
            </div>
            <small>Enter 1 or more minutes to enable. 0 keeps idle-screen banners disabled.</small>
        </div>

        <div class="sa-banner-field">
            <label for="sa_banner_tenants">Tenant Name (Database Name) / Tenant UID</label>
            <select id="sa_banner_tenants"
                    name="banner_tenant_ids[]"
                    class="form-control"
                    multiple>
                <option value="{{ $bannerAllToken }}" {{ in_array($bannerAllToken, $selectedTenantIds, true) ? 'selected' : '' }}>All</option>
                @foreach($bannerTenantOptions as $tenantOption)
                    <option value="{{ $tenantOption['id'] }}"
                        {{ in_array((string) $tenantOption['id'], array_map('strval', $selectedTenantIds), true) ? 'selected' : '' }}>
                        {{ $tenantOption['label'] }}
                    </option>
                @endforeach
            </select>
            <small>Type to filter. The list is scrollable; “All” is always the first option.</small>
        </div>

        <div class="sa-banner-field">
            <label for="sa_banner_businesses">Business</label>
            <select id="sa_banner_businesses"
                    name="banner_business_targets[]"
                    class="form-control"
                    multiple>
                <option value="{{ $bannerAllToken }}" {{ in_array($bannerAllToken, $selectedBusinessTargets, true) ? 'selected' : '' }}>All</option>
                @foreach($bannerBusinessOptions as $businessOption)
                    <option value="{{ $businessOption['value'] }}"
                        {{ in_array((string) $businessOption['value'], array_map('strval', $selectedBusinessTargets), true) ? 'selected' : '' }}>
                        {{ $businessOption['label'] }}
                    </option>
                @endforeach
            </select>
            <small>Businesses are refreshed automatically from the selected tenant(s). Type to filter and scroll to review the list.</small>
        </div>
    </div>

    <div class="sa-banner-management-status">
        Default targeting is <strong>All Tenants + All Businesses</strong>. Selecting “All” represents every current and future option in that dropdown.
    </div>
</div>

<script>
(function ($) {
    'use strict';

    if (!$ || !$.fn || !$.fn.select2) {
        return;
    }

    var allToken = @json($bannerAllToken);
    var businessOptionsUrl = @json(route('superadmin.banner.business-options'));
    var $tenants = $('#sa_banner_tenants');
    var $businesses = $('#sa_banner_businesses');
    var initialBusinessSelection = @json(array_values(array_map('strval', $selectedBusinessTargets)));
    var initialLoad = true;

    function initialiseSelect($element, placeholder) {
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
            // If All was just selected, it wins. If a specific option was just
            // selected while All was already active, the specific selection wins.
            var last = $element.data('sa-last-selection') || [];
            var hadAll = last.indexOf(allToken) !== -1;
            if (hadAll) {
                values = values.filter(function (v) { return v !== allToken; });
            } else {
                values = [allToken];
            }
            $element.val(values).trigger('change.select2');
        }
        $element.data('sa-last-selection', ($element.val() || []).slice());
    }

    function setBusinessOptions(rows, selected) {
        selected = selected || [];
        $businesses.empty();
        $businesses.append(new Option('All', allToken, false, selected.indexOf(allToken) !== -1 || selected.length === 0));

        (rows || []).forEach(function (row) {
            var value = String(row.value || '');
            if (!value) { return; }
            $businesses.append(new Option(row.label || value, value, false, selected.indexOf(value) !== -1));
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
    });

    $businesses.on('change', function () {
        enforceAll($businesses);
    });

    $('#sa_banner_idle_minutes, #sa_banner_tenants, #sa_banner_businesses').on('change input', function () {
        var note = document.getElementById('pmp-save-note');
        if (note) {
            note.textContent = 'Banners Management settings changed.';
        }
    });
})(window.jQuery);
</script>

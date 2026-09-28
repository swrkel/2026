@extends('membershipnew::layouts.app')
@php
    $title = 'Membership Settings';
    $membershipTypes = $settingOptions->get('membership_type', collect());
    $membershipStatuses = $settingOptions->get('membership_status', collect());
    $renewalPeriodRows = $settingOptions->get('renewal_period', collect());
    $amountRows = $settingOptions->get('registration_renewal_amount', collect());
@endphp

@section('membership-content')
<div class="mn-panel mn-settings-tabs-panel">
    <div class="mn-settings-tabs" role="tablist" aria-label="Membership Settings">
        <button type="button" class="mn-settings-tab {{ $activeTab === 'regions' ? 'active' : '' }}" role="tab" aria-selected="{{ $activeTab === 'regions' ? 'true' : 'false' }}" data-settings-tab="regions"><i class="fa fa-map-marker"></i> Regions</button>
        <button type="button" class="mn-settings-tab {{ $activeTab === 'membership-types' ? 'active' : '' }}" role="tab" aria-selected="{{ $activeTab === 'membership-types' ? 'true' : 'false' }}" data-settings-tab="membership-types"><i class="fa fa-id-badge"></i> Membership Types</button>
        <button type="button" class="mn-settings-tab {{ $activeTab === 'membership-status' ? 'active' : '' }}" role="tab" aria-selected="{{ $activeTab === 'membership-status' ? 'true' : 'false' }}" data-settings-tab="membership-status"><i class="fa fa-check-circle"></i> Membership Status</button>
        <button type="button" class="mn-settings-tab {{ $activeTab === 'renewal-period' ? 'active' : '' }}" role="tab" aria-selected="{{ $activeTab === 'renewal-period' ? 'true' : 'false' }}" data-settings-tab="renewal-period"><i class="fa fa-calendar"></i> Renewal Period</button>
        <button type="button" class="mn-settings-tab {{ $activeTab === 'registration-renewal-amount' ? 'active' : '' }}" role="tab" aria-selected="{{ $activeTab === 'registration-renewal-amount' ? 'true' : 'false' }}" data-settings-tab="registration-renewal-amount"><i class="fa fa-money"></i> Registration / Renewal Amount</button>
    </div>
</div>

<div id="mn-settings-status" class="mn-alert alert-success" style="{{ session('status') ? '' : 'display:none' }}" aria-live="polite">{{ session('status') }}</div>

{{-- REGIONS --}}
<section class="mn-settings-tab-pane" data-settings-pane="regions" {{ $activeTab === 'regions' ? '' : 'hidden' }}>
    <div class="mn-panel">
        <div class="mn-report-card-header">
            <div><h3>Regions</h3><p>Maintain business-specific membership regions.</p></div>
            @can('membership_new.settings.create')
                <button type="button" class="mn-btn mn-btn-primary" data-open-settings-modal="mn-region-modal"><i class="fa fa-plus"></i> Add Region</button>
            @endcan
        </div>

        <form method="GET" action="{{ route('membership-new.settings.index') }}" class="mn-settings-filter" id="mn-region-filter-form">
            <input type="hidden" name="tab" value="regions">
            <div class="mn-filter-field mn-filter-date-range"><label for="mn-date-range">Date Range</label><select name="date_range" id="mn-date-range">
                <option value="this_year" {{ $dateRange === 'this_year' ? 'selected' : '' }}>This Year</option>
                <option value="last_year" {{ $dateRange === 'last_year' ? 'selected' : '' }}>Last Year</option>
                <option value="this_fy" {{ $dateRange === 'this_fy' ? 'selected' : '' }}>This FY</option>
                <option value="last_fy" {{ $dateRange === 'last_fy' ? 'selected' : '' }}>Last FY</option>
                <option value="custom" {{ $dateRange === 'custom' ? 'selected' : '' }}>Custom</option>
                <option value="all" {{ $dateRange === 'all' ? 'selected' : '' }}>All Dates</option>
            </select></div>
            <div class="mn-filter-field mn-custom-date-field {{ $dateRange === 'custom' ? '' : 'mn-date-hidden' }}"><label for="mn-from-date">From</label><input type="date" name="from_date" id="mn-from-date" value="{{ request('from_date', $dateRange === 'custom' ? $fromDate : '') }}"></div>
            <div class="mn-filter-field mn-custom-date-field {{ $dateRange === 'custom' ? '' : 'mn-date-hidden' }}"><label for="mn-to-date">To</label><input type="date" name="to_date" id="mn-to-date" value="{{ request('to_date', $dateRange === 'custom' ? $toDate : '') }}"></div>
            <div class="mn-filter-field"><label for="mn-region-no-filter">Region No</label><input type="text" name="region_no" id="mn-region-no-filter" value="{{ request('region_no') }}" placeholder="Search Region No"></div>
            <div class="mn-filter-field"><label for="mn-region-filter">Region</label><input type="text" name="region" id="mn-region-filter" value="{{ request('region') }}" placeholder="Search Region"></div>
            <div class="mn-filter-field"><label for="mn-added-by-filter">Added By</label><input type="text" name="added_by" id="mn-added-by-filter" value="{{ request('added_by') }}" placeholder="Search Added By"></div>
            <div class="mn-filter-field mn-filter-per-page"><label for="mn-per-page">Rows / Page</label><select name="per_page" id="mn-per-page">@foreach([10,25,50,100,200] as $size)<option value="{{ $size }}" {{ (int) $perPage === $size ? 'selected' : '' }}>{{ $size }}</option>@endforeach</select></div>
        </form>

        <div class="mn-table-wrap">
            <table class="mn-table" id="mn-regions-table">
                <thead><tr><th>Action</th><th>Date &amp; Time</th><th>Region No</th><th>Region</th><th>Added By</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($regions as $region)
                    <tr data-row-id="{{ $region->id }}">
                        <td>
                            @can('membership_new.settings.create')
                            <details class="mn-row-action"><summary class="mn-btn mn-btn-primary mn-btn-sm"><i class="fa fa-bars"></i> Action <i class="fa fa-caret-down"></i></summary><div class="mn-row-action-menu">
                                <button type="button" class="mn-row-action-item mn-row-edit" data-edit-region data-id="{{ $region->id }}" data-date="{{ optional($region->date)->format('Y-m-d') }}" data-region-no="{{ $region->region_no }}" data-region="{{ $region->region }}" data-update-url="{{ route('membership-new.settings.regions.update', $region->id) }}"><i class="fa fa-pencil"></i> Edit</button>
                                <button type="button" class="mn-row-action-item mn-row-status" data-toggle-setting data-url="{{ route('membership-new.settings.regions.toggle-status', $region->id) }}"><i class="fa fa-exchange"></i> Change Status</button>
                            </div></details>
                            @endcan
                        </td>
                        <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($region->date, $region->created_at) }}</td>
                        <td>{{ $region->region_no }}</td>
                        <td>{{ $region->region }}</td>
                        <td>{{ $region->added_by_name }}</td>
                        <td><span class="mn-status {{ $region->is_active ? '' : 'inactive' }}" data-status-cell>{{ $region->is_active ? 'Enabled' : 'Disabled' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="mn-report-empty mn-settings-empty">No regions found for the selected filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mn-pagination">{{ $regions->links() }}</div>
    </div>
</section>

{{-- MEMBERSHIP TYPES --}}
<section class="mn-settings-tab-pane" data-settings-pane="membership-types" {{ $activeTab === 'membership-types' ? '' : 'hidden' }}>
    <div class="mn-panel">
        <div class="mn-report-card-header"><div><h3>Membership Types</h3><p>Add the membership types used by this business.</p></div>@can('membership_new.settings.create')<button type="button" class="mn-btn mn-btn-primary" data-open-settings-modal="mn-membership-type-modal"><i class="fa fa-plus"></i> Add Membership Type</button>@endcan</div>
        <div class="mn-table-wrap"><table class="mn-table" id="mn-membership-types-table">
            <thead><tr><th>Action</th><th>Date &amp; Time</th><th>Membership Type</th><th>Added By</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($membershipTypes as $option)
                <tr data-row-id="{{ $option->id }}">
                    <td>
                        @can('membership_new.settings.create')
                        <details class="mn-row-action"><summary class="mn-btn mn-btn-primary mn-btn-sm"><i class="fa fa-bars"></i> Action <i class="fa fa-caret-down"></i></summary><div class="mn-row-action-menu">
                            <button type="button" class="mn-row-action-item mn-row-edit" data-edit-option data-group="membership_type" data-id="{{ $option->id }}" data-value="{{ $option->setting_value }}" data-update-url="{{ route('membership-new.settings.options.update', $option->id) }}"><i class="fa fa-pencil"></i> Edit</button>
                            <button type="button" class="mn-row-action-item mn-row-status" data-toggle-setting data-url="{{ route('membership-new.settings.options.toggle-status', $option->id) }}"><i class="fa fa-exchange"></i> Change Status</button>
                        </div></details>
                        @endcan
                    </td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($option->created_at) }}</td>
                    <td>{{ $option->setting_value }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($option) }}</td>
                    <td><span class="mn-status {{ $option->is_active ? '' : 'inactive' }}" data-status-cell>{{ $option->is_active ? 'Enabled' : 'Disabled' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="mn-report-empty mn-settings-empty">No Membership Types have been added yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</section>

{{-- MEMBERSHIP STATUS --}}
<section class="mn-settings-tab-pane" data-settings-pane="membership-status" {{ $activeTab === 'membership-status' ? '' : 'hidden' }}>
    <div class="mn-panel">
        <div class="mn-report-card-header"><div><h3>Membership Status</h3><p>Add the membership status values used by this business.</p></div>@can('membership_new.settings.create')<button type="button" class="mn-btn mn-btn-primary" data-open-settings-modal="mn-membership-status-modal"><i class="fa fa-plus"></i> Add Membership Status</button>@endcan</div>
        <div class="mn-table-wrap"><table class="mn-table" id="mn-membership-status-table">
            <thead><tr><th>Action</th><th>Date &amp; Time</th><th>Membership Status</th><th>Added By</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($membershipStatuses as $option)
                <tr data-row-id="{{ $option->id }}">
                    <td>
                        @can('membership_new.settings.create')
                        <details class="mn-row-action"><summary class="mn-btn mn-btn-primary mn-btn-sm"><i class="fa fa-bars"></i> Action <i class="fa fa-caret-down"></i></summary><div class="mn-row-action-menu">
                            <button type="button" class="mn-row-action-item mn-row-edit" data-edit-option data-group="membership_status" data-id="{{ $option->id }}" data-value="{{ $option->setting_value }}" data-update-url="{{ route('membership-new.settings.options.update', $option->id) }}"><i class="fa fa-pencil"></i> Edit</button>
                            <button type="button" class="mn-row-action-item mn-row-status" data-toggle-setting data-url="{{ route('membership-new.settings.options.toggle-status', $option->id) }}"><i class="fa fa-exchange"></i> Change Status</button>
                        </div></details>
                        @endcan
                    </td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($option->created_at) }}</td>
                    <td>{{ $option->setting_value }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($option) }}</td>
                    <td><span class="mn-status {{ $option->is_active ? '' : 'inactive' }}" data-status-cell>{{ $option->is_active ? 'Enabled' : 'Disabled' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="mn-report-empty mn-settings-empty">No Membership Status values have been added yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</section>

{{-- RENEWAL PERIOD --}}
<section class="mn-settings-tab-pane" data-settings-pane="renewal-period" {{ $activeTab === 'renewal-period' ? '' : 'hidden' }}>
    <div class="mn-panel">
        <div class="mn-report-card-header"><div><h3>Renewal Period</h3><p>Select from Daily, Weekly, Monthly or Annually.</p></div>@can('membership_new.settings.create')<button type="button" class="mn-btn mn-btn-primary" data-open-settings-modal="mn-renewal-period-modal"><i class="fa fa-plus"></i> Add Renewal Period</button>@endcan</div>
        <div class="mn-table-wrap"><table class="mn-table" id="mn-renewal-period-table">
            <thead><tr><th>Action</th><th>Date &amp; Time</th><th>Renewal Period</th><th>Added By</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($renewalPeriodRows as $option)
                <tr data-row-id="{{ $option->id }}">
                    <td>
                        @can('membership_new.settings.create')
                        <details class="mn-row-action"><summary class="mn-btn mn-btn-primary mn-btn-sm"><i class="fa fa-bars"></i> Action <i class="fa fa-caret-down"></i></summary><div class="mn-row-action-menu">
                            <button type="button" class="mn-row-action-item mn-row-edit" data-edit-option data-group="renewal_period" data-id="{{ $option->id }}" data-value="{{ strtolower((string) $option->setting_key) }}" data-update-url="{{ route('membership-new.settings.options.update', $option->id) }}"><i class="fa fa-pencil"></i> Edit</button>
                            <button type="button" class="mn-row-action-item mn-row-status" data-toggle-setting data-url="{{ route('membership-new.settings.options.toggle-status', $option->id) }}"><i class="fa fa-exchange"></i> Change Status</button>
                        </div></details>
                        @endcan
                    </td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($option->created_at) }}</td>
                    <td>{{ $option->setting_value }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($option) }}</td>
                    <td><span class="mn-status {{ $option->is_active ? '' : 'inactive' }}" data-status-cell>{{ $option->is_active ? 'Enabled' : 'Disabled' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="mn-report-empty mn-settings-empty">No Renewal Periods have been added yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</section>

{{-- REGISTRATION / RENEWAL AMOUNT --}}
<section class="mn-settings-tab-pane" data-settings-pane="registration-renewal-amount" {{ $activeTab === 'registration-renewal-amount' ? '' : 'hidden' }}>
    <div class="mn-panel">
        <div class="mn-report-card-header"><div><h3>Registration / Renewal Amount</h3><p>Entering an amount is optional.</p></div>@can('membership_new.settings.create')<button type="button" class="mn-btn mn-btn-primary" data-open-settings-modal="mn-registration-renewal-amount-modal"><i class="fa fa-plus"></i> Add Amount</button>@endcan</div>
        <div class="mn-table-wrap"><table class="mn-table" id="mn-registration-renewal-amount-table">
            <thead><tr><th>Action</th><th>Date &amp; Time</th><th>Registration / Renewal Amount</th><th>Added By</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($amountRows as $option)
                <tr data-row-id="{{ $option->id }}">
                    <td>
                        @can('membership_new.settings.create')
                        <details class="mn-row-action"><summary class="mn-btn mn-btn-primary mn-btn-sm"><i class="fa fa-bars"></i> Action <i class="fa fa-caret-down"></i></summary><div class="mn-row-action-menu">
                            <button type="button" class="mn-row-action-item mn-row-edit" data-edit-option data-group="registration_renewal_amount" data-id="{{ $option->id }}" data-amount="{{ $option->amount }}" data-update-url="{{ route('membership-new.settings.options.update', $option->id) }}"><i class="fa fa-pencil"></i> Edit</button>
                            <button type="button" class="mn-row-action-item mn-row-status" data-toggle-setting data-url="{{ route('membership-new.settings.options.toggle-status', $option->id) }}"><i class="fa fa-exchange"></i> Change Status</button>
                        </div></details>
                        @endcan
                    </td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::dateTime($option->created_at) }}</td>
                    <td class="mn-money">{{ $option->amount === null ? '' : \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::money($option->amount) }}</td>
                    <td>{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::addedBy($option) }}</td>
                    <td><span class="mn-status {{ $option->is_active ? '' : 'inactive' }}" data-status-cell>{{ $option->is_active ? 'Enabled' : 'Disabled' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="5" class="mn-report-empty mn-settings-empty">No Registration / Renewal Amounts have been added yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</section>

{{-- ADD REGION MODAL --}}
<div class="mn-modal-backdrop" id="mn-region-modal" aria-hidden="true"><div class="mn-modal-card" role="dialog" aria-modal="true"><div class="mn-modal-header"><div><div class="mn-eyebrow">Membership Settings</div><h3>Add Region</h3></div><button type="button" class="mn-modal-close" data-close-settings-modal aria-label="Close">&times;</button></div><form method="POST" action="{{ route('membership-new.settings.regions.store') }}" data-settings-ajax-form>@csrf<div class="alert alert-danger mn-alert" data-form-error style="display:none"></div><div class="mn-form-grid"><label>Date<input type="date" name="date" value="{{ old('date', $today) }}" required></label><label>Region No<input type="text" name="region_no" maxlength="50" required autocomplete="off"></label><label class="mn-wide">Region<input type="text" name="region" maxlength="150" required autocomplete="off"></label></div><div class="mn-modal-actions"><button type="button" class="mn-btn mn-btn-light" data-close-settings-modal>Cancel</button><button type="submit" class="mn-btn mn-btn-success" data-save-button><i class="fa fa-save"></i> <span>Save Region</span></button></div></form></div></div>

{{-- EDIT REGION MODAL --}}
<div class="mn-modal-backdrop" id="mn-region-edit-modal" aria-hidden="true"><div class="mn-modal-card" role="dialog" aria-modal="true"><div class="mn-modal-header"><div><div class="mn-eyebrow">Membership Settings</div><h3>Edit Region</h3></div><button type="button" class="mn-modal-close" data-close-settings-modal aria-label="Close">&times;</button></div><form method="POST" action="" data-settings-ajax-form data-edit-form="region">@csrf @method('PATCH')<div class="alert alert-danger mn-alert" data-form-error style="display:none"></div><div class="mn-form-grid"><label>Date<input type="date" name="date" required></label><label>Region No<input type="text" name="region_no" maxlength="50" required></label><label class="mn-wide">Region<input type="text" name="region" maxlength="150" required></label></div><div class="mn-modal-actions"><button type="button" class="mn-btn mn-btn-light" data-close-settings-modal>Cancel</button><button type="submit" class="mn-btn mn-btn-success" data-save-button><i class="fa fa-save"></i> <span>Update Region</span></button></div></form></div></div>

{{-- ADD MEMBERSHIP TYPE MODAL --}}
<div class="mn-modal-backdrop" id="mn-membership-type-modal" aria-hidden="true"><div class="mn-modal-card" role="dialog" aria-modal="true"><div class="mn-modal-header"><div><div class="mn-eyebrow">Membership Settings</div><h3>Add Membership Type</h3></div><button type="button" class="mn-modal-close" data-close-settings-modal>&times;</button></div><form method="POST" action="{{ route('membership-new.settings.options.store') }}" data-settings-ajax-form>@csrf<input type="hidden" name="setting_group" value="membership_type"><div class="alert alert-danger mn-alert" data-form-error style="display:none"></div><div class="mn-form-grid mn-settings-single-field"><label>Membership Type<input type="text" name="setting_value" maxlength="150" required autocomplete="off"></label></div><div class="mn-modal-actions"><button type="button" class="mn-btn mn-btn-light" data-close-settings-modal>Cancel</button><button type="submit" class="mn-btn mn-btn-success" data-save-button><i class="fa fa-save"></i> <span>Save Membership Type</span></button></div></form></div></div>

{{-- EDIT MEMBERSHIP TYPE MODAL --}}
<div class="mn-modal-backdrop" id="mn-membership-type-edit-modal" aria-hidden="true"><div class="mn-modal-card" role="dialog" aria-modal="true"><div class="mn-modal-header"><div><div class="mn-eyebrow">Membership Settings</div><h3>Edit Membership Type</h3></div><button type="button" class="mn-modal-close" data-close-settings-modal>&times;</button></div><form method="POST" action="" data-settings-ajax-form data-edit-form="membership_type">@csrf @method('PATCH')<div class="alert alert-danger mn-alert" data-form-error style="display:none"></div><div class="mn-form-grid mn-settings-single-field"><label>Membership Type<input type="text" name="setting_value" maxlength="150" required></label></div><div class="mn-modal-actions"><button type="button" class="mn-btn mn-btn-light" data-close-settings-modal>Cancel</button><button type="submit" class="mn-btn mn-btn-success" data-save-button><i class="fa fa-save"></i> <span>Update Membership Type</span></button></div></form></div></div>

{{-- ADD MEMBERSHIP STATUS MODAL --}}
<div class="mn-modal-backdrop" id="mn-membership-status-modal" aria-hidden="true"><div class="mn-modal-card" role="dialog" aria-modal="true"><div class="mn-modal-header"><div><div class="mn-eyebrow">Membership Settings</div><h3>Add Membership Status</h3></div><button type="button" class="mn-modal-close" data-close-settings-modal>&times;</button></div><form method="POST" action="{{ route('membership-new.settings.options.store') }}" data-settings-ajax-form>@csrf<input type="hidden" name="setting_group" value="membership_status"><div class="alert alert-danger mn-alert" data-form-error style="display:none"></div><div class="mn-form-grid mn-settings-single-field"><label>Membership Status<input type="text" name="setting_value" maxlength="100" required></label></div><div class="mn-modal-actions"><button type="button" class="mn-btn mn-btn-light" data-close-settings-modal>Cancel</button><button type="submit" class="mn-btn mn-btn-success" data-save-button><i class="fa fa-save"></i> <span>Save Membership Status</span></button></div></form></div></div>

{{-- EDIT MEMBERSHIP STATUS MODAL --}}
<div class="mn-modal-backdrop" id="mn-membership-status-edit-modal" aria-hidden="true"><div class="mn-modal-card" role="dialog" aria-modal="true"><div class="mn-modal-header"><div><div class="mn-eyebrow">Membership Settings</div><h3>Edit Membership Status</h3></div><button type="button" class="mn-modal-close" data-close-settings-modal>&times;</button></div><form method="POST" action="" data-settings-ajax-form data-edit-form="membership_status">@csrf @method('PATCH')<div class="alert alert-danger mn-alert" data-form-error style="display:none"></div><div class="mn-form-grid mn-settings-single-field"><label>Membership Status<input type="text" name="setting_value" maxlength="100" required></label></div><div class="mn-modal-actions"><button type="button" class="mn-btn mn-btn-light" data-close-settings-modal>Cancel</button><button type="submit" class="mn-btn mn-btn-success" data-save-button><i class="fa fa-save"></i> <span>Update Membership Status</span></button></div></form></div></div>

{{-- ADD RENEWAL PERIOD MODAL --}}
<div class="mn-modal-backdrop" id="mn-renewal-period-modal" aria-hidden="true"><div class="mn-modal-card" role="dialog" aria-modal="true"><div class="mn-modal-header"><div><div class="mn-eyebrow">Membership Settings</div><h3>Add Renewal Period</h3></div><button type="button" class="mn-modal-close" data-close-settings-modal>&times;</button></div><form method="POST" action="{{ route('membership-new.settings.options.store') }}" data-settings-ajax-form>@csrf<input type="hidden" name="setting_group" value="renewal_period"><div class="alert alert-danger mn-alert" data-form-error style="display:none"></div><div class="mn-form-grid mn-settings-single-field"><label>Renewal Period<select name="setting_value" required><option value="">Select Renewal Period</option>@foreach($renewalPeriods as $periodValue => $periodLabel)<option value="{{ $periodValue }}">{{ $periodLabel }}</option>@endforeach</select></label></div><div class="mn-modal-actions"><button type="button" class="mn-btn mn-btn-light" data-close-settings-modal>Cancel</button><button type="submit" class="mn-btn mn-btn-success" data-save-button><i class="fa fa-save"></i> <span>Save Renewal Period</span></button></div></form></div></div>

{{-- EDIT RENEWAL PERIOD MODAL --}}
<div class="mn-modal-backdrop" id="mn-renewal-period-edit-modal" aria-hidden="true"><div class="mn-modal-card" role="dialog" aria-modal="true"><div class="mn-modal-header"><div><div class="mn-eyebrow">Membership Settings</div><h3>Edit Renewal Period</h3></div><button type="button" class="mn-modal-close" data-close-settings-modal>&times;</button></div><form method="POST" action="" data-settings-ajax-form data-edit-form="renewal_period">@csrf @method('PATCH')<div class="alert alert-danger mn-alert" data-form-error style="display:none"></div><div class="mn-form-grid mn-settings-single-field"><label>Renewal Period<select name="setting_value" required><option value="">Select Renewal Period</option>@foreach($renewalPeriods as $periodValue => $periodLabel)<option value="{{ $periodValue }}">{{ $periodLabel }}</option>@endforeach</select></label></div><div class="mn-modal-actions"><button type="button" class="mn-btn mn-btn-light" data-close-settings-modal>Cancel</button><button type="submit" class="mn-btn mn-btn-success" data-save-button><i class="fa fa-save"></i> <span>Update Renewal Period</span></button></div></form></div></div>

{{-- ADD REGISTRATION / RENEWAL AMOUNT MODAL --}}
<div class="mn-modal-backdrop" id="mn-registration-renewal-amount-modal" aria-hidden="true"><div class="mn-modal-card" role="dialog" aria-modal="true"><div class="mn-modal-header"><div><div class="mn-eyebrow">Membership Settings</div><h3>Add Registration / Renewal Amount</h3></div><button type="button" class="mn-modal-close" data-close-settings-modal>&times;</button></div><form method="POST" action="{{ route('membership-new.settings.options.store') }}" data-settings-ajax-form>@csrf<input type="hidden" name="setting_group" value="registration_renewal_amount"><div class="alert alert-danger mn-alert" data-form-error style="display:none"></div><div class="mn-form-grid mn-settings-single-field"><label>Registration / Renewal Amount <span class="mn-optional">(Optional)</span><input type="number" name="amount" min="0" step="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::moneyStep() }}" inputmode="decimal"></label></div><div class="mn-modal-actions"><button type="button" class="mn-btn mn-btn-light" data-close-settings-modal>Cancel</button><button type="submit" class="mn-btn mn-btn-success" data-save-button><i class="fa fa-save"></i> <span>Save Amount</span></button></div></form></div></div>

{{-- EDIT REGISTRATION / RENEWAL AMOUNT MODAL --}}
<div class="mn-modal-backdrop" id="mn-registration-renewal-amount-edit-modal" aria-hidden="true"><div class="mn-modal-card" role="dialog" aria-modal="true"><div class="mn-modal-header"><div><div class="mn-eyebrow">Membership Settings</div><h3>Edit Registration / Renewal Amount</h3></div><button type="button" class="mn-modal-close" data-close-settings-modal>&times;</button></div><form method="POST" action="" data-settings-ajax-form data-edit-form="registration_renewal_amount">@csrf @method('PATCH')<div class="alert alert-danger mn-alert" data-form-error style="display:none"></div><div class="mn-form-grid mn-settings-single-field"><label>Registration / Renewal Amount <span class="mn-optional">(Optional)</span><input type="number" name="amount" min="0" step="{{ \Modules\MembershipNew\app\Utils\MembershipNewFormatUtil::moneyStep() }}" inputmode="decimal"></label></div><div class="mn-modal-actions"><button type="button" class="mn-btn mn-btn-light" data-close-settings-modal>Cancel</button><button type="submit" class="mn-btn mn-btn-success" data-save-button><i class="fa fa-save"></i> <span>Update Amount</span></button></div></form></div></div>

<style>
.mn-row-action{display:block;min-width:110px}.mn-row-action>summary{list-style:none;user-select:none}.mn-row-action>summary::-webkit-details-marker{display:none}.mn-row-action-menu{display:grid;gap:6px;width:150px;margin-top:7px;padding:7px;background:#fff;border:1px solid #dbe7f3;border-radius:11px;box-shadow:0 10px 24px rgba(15,23,42,.12);position:absolute;z-index:60}.mn-row-action-item{border:0;display:flex!important;align-items:center;gap:7px;min-height:32px;padding:7px 9px;border-radius:8px;color:#fff!important;font-size:12px;font-weight:800!important;white-space:nowrap;cursor:pointer;text-align:left}.mn-row-edit{background:#f59e0b}.mn-row-status{background:#0891b2}.mn-settings-tab-pane .mn-table td:first-child{position:relative}.mn-status.inactive{background:#f1f5f9;color:#64748b}
</style>
@endsection

@push('scripts')
<script>
(function () {
    var statusBox = document.getElementById('mn-settings-status');
    var dateRange = document.getElementById('mn-date-range');
    var customDateFields = document.querySelectorAll('.mn-custom-date-field');
    var tabButtons = document.querySelectorAll('[data-settings-tab]');
    var tabPanes = document.querySelectorAll('[data-settings-pane]');
    var initialTab = @json($activeTab);
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    function setActiveTab(tabName, updateUrl) {
        tabButtons.forEach(function (button) { var active = button.getAttribute('data-settings-tab') === tabName; button.classList.toggle('active', active); button.setAttribute('aria-selected', active ? 'true' : 'false'); });
        tabPanes.forEach(function (pane) { pane.hidden = pane.getAttribute('data-settings-pane') !== tabName; });
        if (updateUrl && window.history && window.URL) { var url = new URL(window.location.href); url.searchParams.set('tab', tabName); window.history.replaceState({}, '', url.toString()); }
    }
    tabButtons.forEach(function (button) { button.addEventListener('click', function () { setActiveTab(button.getAttribute('data-settings-tab'), true); }); });
    setActiveTab(initialTab, false);

    function syncDateFields() { if (!dateRange) return; var showCustom = dateRange.value === 'custom'; customDateFields.forEach(function (field) { field.classList.toggle('mn-date-hidden', !showCustom); }); }
    if (dateRange) dateRange.addEventListener('change', syncDateFields); syncDateFields();

    function openModal(modal) { if (!modal) return; modal.classList.add('open'); modal.setAttribute('aria-hidden', 'false'); document.body.classList.add('mn-modal-open'); var firstField = modal.querySelector('input:not([type="hidden"]), select, textarea'); if (firstField) setTimeout(function(){ firstField.focus(); },0); }
    function closeModal(modal) { if (!modal) return; modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); if (!document.querySelector('.mn-modal-backdrop.open')) document.body.classList.remove('mn-modal-open'); }
    document.querySelectorAll('[data-open-settings-modal]').forEach(function (button) { button.addEventListener('click', function () { openModal(document.getElementById(button.getAttribute('data-open-settings-modal'))); }); });
    document.querySelectorAll('[data-close-settings-modal]').forEach(function (button) { button.addEventListener('click', function () { closeModal(button.closest('.mn-modal-backdrop')); }); });
    document.querySelectorAll('.mn-modal-backdrop').forEach(function (modal) { modal.addEventListener('click', function (event) { if (event.target === modal) closeModal(modal); }); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') document.querySelectorAll('.mn-modal-backdrop.open').forEach(closeModal); });

    function showFormError(form, message) { var box = form.querySelector('[data-form-error]'); if (!box) return; box.textContent = message || 'Unable to save the setting.'; box.style.display = ''; }
    function clearFormError(form) { var box = form.querySelector('[data-form-error]'); if (!box) return; box.textContent = ''; box.style.display = 'none'; }
    function showStatus(message) { if (!statusBox) return; statusBox.textContent = message || 'Setting saved successfully.'; statusBox.style.display = ''; clearTimeout(statusBox._mnTimer); statusBox._mnTimer = setTimeout(function(){ statusBox.style.display='none'; },3500); }
    function firstValidationMessage(payload) { if (payload && payload.errors) { var keys=Object.keys(payload.errors); if(keys.length && payload.errors[keys[0]] && payload.errors[keys[0]].length) return payload.errors[keys[0]][0]; } return (payload && payload.message) || 'Unable to save the setting.'; }

    function escapeHtml(value) { var div=document.createElement('div'); div.textContent=value == null ? '' : String(value); return div.innerHTML; }
    function statusHtml(row) { return '<span class="mn-status '+(row.is_active ? '' : 'inactive')+'" data-status-cell>'+escapeHtml(row.status)+'</span>'; }
    function optionTableId(group) {
        var map={membership_type:'mn-membership-types-table',membership_status:'mn-membership-status-table',renewal_period:'mn-renewal-period-table',registration_renewal_amount:'mn-registration-renewal-amount-table'};
        return map[group] || '';
    }
    function optionModalId(group) {
        var map={membership_type:'mn-membership-type-edit-modal',membership_status:'mn-membership-status-edit-modal',renewal_period:'mn-renewal-period-edit-modal',registration_renewal_amount:'mn-registration-renewal-amount-edit-modal'};
        return map[group] || '';
    }
    function optionDisplay(row) {
        if (row.setting_group === 'registration_renewal_amount') {
            return row.amount === null || row.amount === '' ? '' : Number(row.amount).toLocaleString('en-US',{minimumFractionDigits:(window.MembershipNew&&window.MembershipNew.currencyPrecision)||2,maximumFractionDigits:(window.MembershipNew&&window.MembershipNew.currencyPrecision)||2});
        }
        return row.setting_value || '';
    }
    function actionHtml(row, kind) {
        var editAttrs;
        if (kind === 'region') {
            editAttrs = 'data-edit-region data-id="'+row.id+'" data-date="'+escapeHtml(row.date)+'" data-region-no="'+escapeHtml(row.region_no)+'" data-region="'+escapeHtml(row.region)+'" data-update-url="'+escapeHtml(row.update_url)+'"';
        } else {
            editAttrs = 'data-edit-option data-group="'+escapeHtml(row.setting_group)+'" data-id="'+row.id+'" data-value="'+escapeHtml(row.setting_group === 'renewal_period' ? (row.setting_key || '').toLowerCase() : (row.setting_value || ''))+'" data-amount="'+escapeHtml(row.amount === null ? '' : row.amount)+'" data-update-url="'+escapeHtml(row.update_url)+'"';
        }
        return '<details class="mn-row-action"><summary class="mn-btn mn-btn-primary mn-btn-sm"><i class="fa fa-bars"></i> Action <i class="fa fa-caret-down"></i></summary><div class="mn-row-action-menu"><button type="button" class="mn-row-action-item mn-row-edit" '+editAttrs+'><i class="fa fa-pencil"></i> Edit</button><button type="button" class="mn-row-action-item mn-row-status" data-toggle-setting data-url="'+escapeHtml(row.toggle_url)+'"><i class="fa fa-exchange"></i> Change Status</button></div></details>';
    }
    function upsertRegionRow(row) {
        var table=document.getElementById('mn-regions-table'); if(!table || !table.tBodies[0]) return;
        var body=table.tBodies[0]; var empty=body.querySelector('.mn-settings-empty'); if(empty&&empty.parentNode) empty.parentNode.remove();
        var tr=body.querySelector('tr[data-row-id="'+row.id+'"]'); if(!tr){tr=document.createElement('tr');tr.setAttribute('data-row-id',row.id);body.insertBefore(tr,body.firstChild);}
        tr.innerHTML='<td>'+actionHtml(row,'region')+'</td><td>'+escapeHtml(row.date_time)+'</td><td>'+escapeHtml(row.region_no)+'</td><td>'+escapeHtml(row.region)+'</td><td>'+escapeHtml(row.added_by)+'</td><td>'+statusHtml(row)+'</td>';
    }
    function upsertOptionRow(row) {
        var table=document.getElementById(optionTableId(row.setting_group)); if(!table || !table.tBodies[0]) return;
        var body=table.tBodies[0]; var empty=body.querySelector('.mn-settings-empty'); if(empty&&empty.parentNode) empty.parentNode.remove();
        var tr=body.querySelector('tr[data-row-id="'+row.id+'"]'); if(!tr){tr=document.createElement('tr');tr.setAttribute('data-row-id',row.id);body.insertBefore(tr,body.firstChild);}
        tr.innerHTML='<td>'+actionHtml(row,'option')+'</td><td>'+escapeHtml(row.date_time)+'</td><td>'+escapeHtml(optionDisplay(row))+'</td><td>'+escapeHtml(row.added_by)+'</td><td>'+statusHtml(row)+'</td>';
    }
    function upsertReturnedRow(payload) {
        if (!payload || !payload.row) return;
        if (payload.kind === 'region' || payload.row.region_no !== undefined) upsertRegionRow(payload.row);
        else upsertOptionRow(payload.row);
    }

    document.addEventListener('click', function(event){
        var editRegion=event.target.closest('[data-edit-region]');
        if(editRegion){
            var modal=document.getElementById('mn-region-edit-modal'); var form=modal.querySelector('form');
            form.action=editRegion.getAttribute('data-update-url');
            form.querySelector('[name="date"]').value=editRegion.getAttribute('data-date')||'';
            form.querySelector('[name="region_no"]').value=editRegion.getAttribute('data-region-no')||'';
            form.querySelector('[name="region"]').value=editRegion.getAttribute('data-region')||'';
            openModal(modal); return;
        }
        var editOption=event.target.closest('[data-edit-option]');
        if(editOption){
            var group=editOption.getAttribute('data-group')||'membership_type';
            var modal2=document.getElementById(optionModalId(group)); if(!modal2) return;
            var form2=modal2.querySelector('form'); form2.action=editOption.getAttribute('data-update-url');
            var valueField=form2.querySelector('[name="setting_value"]'); if(valueField) valueField.value=editOption.getAttribute('data-value')||'';
            var amountField=form2.querySelector('[name="amount"]'); if(amountField) amountField.value=editOption.getAttribute('data-amount')||'';
            openModal(modal2); return;
        }
        var toggle=event.target.closest('[data-toggle-setting]');
        if(toggle){
            event.preventDefault(); toggle.disabled=true;
            fetch(toggle.getAttribute('data-url'),{method:'PATCH',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf},credentials:'same-origin'})
                .then(function(r){return r.json().then(function(p){if(!r.ok) throw new Error(firstValidationMessage(p));return p;});})
                .then(function(p){upsertReturnedRow(p);showStatus(p.message);})
                .catch(function(e){showStatus(e.message||'Unable to change status.');})
                .finally(function(){toggle.disabled=false;});
        }
    });

    document.querySelectorAll('[data-settings-ajax-form]').forEach(function(form){ if(!window.fetch) return; form.addEventListener('submit',function(event){ event.preventDefault(); clearFormError(form); var btn=form.querySelector('[data-save-button]'); var label=btn?btn.querySelector('span'):null; var original=label?label.textContent:''; if(btn)btn.disabled=true;if(label)label.textContent='Saving...'; var method=(form.querySelector('input[name="_method"]')||{}).value||'POST'; fetch(form.action,{method:method,body:new FormData(form),headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf},credentials:'same-origin'}).then(function(r){return r.json().catch(function(){return {};}).then(function(p){if(!r.ok)throw new Error(firstValidationMessage(p));return p;});}).then(function(p){ upsertReturnedRow(p); showStatus(p.message); if(!form.hasAttribute('data-edit-form')) form.reset(); var dateInput=form.querySelector('input[name="date"]');if(dateInput && !form.hasAttribute('data-edit-form'))dateInput.value=@json($today); closeModal(form.closest('.mn-modal-backdrop')); }).catch(function(e){showFormError(form,e.message||'Unable to save the setting.');}).finally(function(){if(btn)btn.disabled=false;if(label)label.textContent=original;}); }); });
})();
</script>
@endpush

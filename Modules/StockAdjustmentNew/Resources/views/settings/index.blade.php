@extends('stockadjustmentnew::layouts.app')

@section('san_title', 'Stock Adjustment Settings')
@section('san_subtitle', 'Control numbering, workflow, stock rules, batches and accounting mappings for this business')

@section('san_content')
@php
    /*
     |--------------------------------------------------------------------------
     | IS1966: saving a mapping must not drop the form into Edit mode.
     |--------------------------------------------------------------------------
     |
     | This read:  $mapping = $editingMapping ?: $savedMapping;
     |
     | $editingMapping is set by ?edit_mapping=<id> - the user chose to edit.
     | $savedMapping is set by ?saved_mapping=<id> after a successful SAVE, and
     | exists only to render the green "Saved Mapping: ..." confirmation below.
     |
     | Treating the two the same put the form into Edit mode straight after a
     | save: the heading became "Edit Accounting Mapping", the button "Update
     | Mapping", a "Cancel Edit" link appeared, and the next submit would have
     | updated the record just created instead of adding a new one.
     |
     | Only an explicit edit request puts the form in Edit mode now. The saved
     | confirmation still shows, because it reads $savedMapping directly.
     */
    $mapping = $editingMapping;
    $mappingRoute = $mapping
        ? route('stock-adjustment-new.settings.mappings.update', $mapping)
        : route('stock-adjustment-new.settings.mappings.store');
    $mappingMethod = $mapping ? 'PUT' : 'POST';
@endphp

@if ($errors->generalSettings->any())
    <div class="alert alert-danger san-validation-summary">
        <strong>Please correct the following module settings:</strong>
        <ul>
            @foreach ($errors->generalSettings->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('stock-adjustment-new.settings.general.update') }}" class="san-panel san-settings-panel">
    @csrf
    @method('PUT')

    <div class="san-settings-section-heading">
        <div>
            <h3>General & Numbering</h3>
            <p>These settings are business-specific and apply only to Stock Adjustment New.</p>
        </div>
        <button type="submit" class="btn btn-primary">Save Module Settings</button>
    </div>

    <div class="row">
        <div class="col-md-3">
            <label for="san-number-prefix">Adjustment Number Prefix</label>
            <input id="san-number-prefix" name="number_prefix" class="form-control" maxlength="30"
                   value="{{ old('number_prefix', $settings['number_prefix']) }}" required>
        </div>
        <div class="col-md-3">
            <label for="san-number-padding">Number Padding</label>
            <input id="san-number-padding" name="number_padding" type="number" min="3" max="10"
                   class="form-control" value="{{ old('number_padding', $settings['number_padding']) }}" required>
        </div>
        <div class="col-md-3">
            <label for="san-default-type">Default Adjustment Type</label>
            <select id="san-default-type" name="default_adjustment_type" class="form-control" data-san-managed-select2>
                @foreach (['quantity' => 'Quantity', 'value' => 'Value', 'damage' => 'Damage', 'expiry' => 'Expiry'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('default_adjustment_type', $settings['default_adjustment_type'] ?? 'quantity') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="san-page-size">Default List Page Size</label>
            <select id="san-page-size" name="default_page_size" class="form-control" data-san-managed-select2>
                @foreach ([10, 25, 50, 100] as $size)
                    <option value="{{ $size }}" @selected((int) old('default_page_size', $settings['default_page_size']) === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <label for="san-qty-decimals">Quantity Decimals</label>
            <input id="san-qty-decimals" name="quantity_decimals" type="number" min="0" max="8"
                   class="form-control" value="{{ old('quantity_decimals', $settings['quantity_decimals']) }}" required>
        </div>
        <div class="col-md-3">
            <label for="san-amount-decimals">Amount Decimals</label>
            <input id="san-amount-decimals" name="amount_decimals" type="number" min="0" max="8"
                   class="form-control" value="{{ old('amount_decimals', $settings['amount_decimals']) }}" required>
        </div>
        <div class="col-md-3">
            <label for="san-batch-method">Batch Selection Order</label>
            <select id="san-batch-method" name="batch_selection_method" class="form-control" data-san-managed-select2>
                @foreach (['fefo' => 'FEFO - Earliest expiry first', 'fifo' => 'FIFO - Oldest batch first', 'manual' => 'Manual - Batch number order'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('batch_selection_method', $settings['batch_selection_method']) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="san-max-backdate">Maximum Backdate Days</label>
            <input id="san-max-backdate" name="max_backdate_days" type="number" min="0" max="36500"
                   class="form-control" value="{{ old('max_backdate_days', $settings['max_backdate_days']) }}"
                   placeholder="No limit" data-san-max-backdate>
        </div>
    </div>

    <hr>

    <div class="san-settings-section-heading san-settings-subheading">
        <div>
            <h3>Workflow & Validation</h3>
            <p>Enable only the controls required by the operating process.</p>
        </div>
    </div>

    <div class="san-setting-switch-grid">
        @foreach ([
            'require_reason' => ['Require Reason', 'A reason must be selected before saving.'],
            'require_location' => ['Require Location', 'Always enabled because stock and account posting is location-specific.'],
            'require_store' => ['Require Store', 'A store must be entered for every adjustment.'],
            'require_approval' => ['Require Approval', 'Submitted adjustments require approval before posting.'],
            'auto_submit' => ['Auto Submit New Adjustments', 'New records skip Draft and enter the configured approval flow.'],
            'auto_post_after_approval' => ['Auto Post After Approval', 'Approved records are posted automatically.'],
            'require_batch_when_available' => ['Require Batch When Available', 'A positive-stock batch must be selected when batches exist.'],
            'hide_zero_stock_products' => ['Hide Zero-stock Products', 'Product search returns only products with positive stock.'],
            'allow_negative_stock' => ['Allow Negative Counted Quantity', 'Counted quantity may be below zero.'],
            'allow_zero_unit_cost' => ['Allow Zero Unit Cost', 'Lines may be saved when cost is zero.'],
            'allow_backdated_adjustments' => ['Allow Backdated Adjustments', 'Dates before today are accepted.'],
            'allow_future_dated_adjustments' => ['Allow Future-dated Adjustments', 'Dates after today are accepted.'],
        ] as $key => [$label, $help])
            <label class="san-setting-switch">
                @if($key === 'require_location')
                    <input type="hidden" name="require_location" value="1">
                @endif
                <input type="checkbox" name="{{ $key }}" value="1"
                       @checked($key === 'require_location' || (bool) old($key, $settings[$key]))
                       @if($key === 'require_location') disabled @endif
                       @if($key === 'allow_backdated_adjustments') data-san-allow-backdate @endif>
                <span class="san-setting-switch-body">
                    <strong>{{ $label }}</strong>
                    <small>{{ $help }}</small>
                </span>
            </label>
        @endforeach
    </div>
</form>

<form id="accounting-mapping-form" method="POST" action="{{ $mappingRoute }}" class="san-panel san-settings-panel" data-san-mapping-form>
    @csrf
    @if($mappingMethod === 'PUT')
        @method('PUT')
    @endif

    @if ($errors->mappingSettings->any())
        <div class="alert alert-danger san-validation-summary san-mapping-validation-summary">
            <strong>Please correct the Accounting Mapping:</strong>
            <ul>
                @foreach ($errors->mappingSettings->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="san-settings-section-heading">
        <div>
            <h3>{{ $mapping ? 'Edit' : 'Add' }} Accounting Mapping</h3>
            <p>Map adjustment types and product categories to the related finance and stock accounts.</p>
        </div>
        @if($mapping)
            <a href="{{ route('stock-adjustment-new.settings.index') }}" class="btn btn-default">Cancel Edit</a>
        @endif
    </div>

    <div class="row">
        <div class="col-md-3">
            <label for="san-effective-from">Effective From</label>
            <input id="san-effective-from" name="effective_from" type="datetime-local" class="form-control"
                   value="{{ old('effective_from', optional($mapping?->effective_from)->format('Y-m-d\TH:i') ?: now()->format('Y-m-d\TH:i')) }}" required>
        </div>
        <div class="col-md-3">
            <label for="san-category">Category</label>
            <select id="san-category" name="category_id" class="form-control san-static-select2{{ $errors->mappingSettings->has('category_id') ? ' is-invalid' : '' }}" data-san-category data-san-static-select2 data-san-managed-select2>
                <option value="">All categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category['id'] }}" @selected((string) old('category_id', $mapping?->category_id) === (string) $category['id'])>{{ $category['name'] }}</option>
                @endforeach
            </select>
            @if($errors->mappingSettings->has('category_id'))
                <div class="text-danger san-field-error">{{ $errors->mappingSettings->first('category_id') }}</div>
            @endif
        </div>
        <div class="col-md-3">
            <label for="san-sub-category">Sub Category</label>
            <select id="san-sub-category" name="sub_category_id" class="form-control san-static-select2{{ $errors->mappingSettings->has('sub_category_id') ? ' is-invalid' : '' }}" data-san-sub-category data-san-static-select2 data-san-managed-select2>
                <option value="">All sub categories</option>
                @foreach($subCategories as $subCategory)
                    <option value="{{ $subCategory['id'] }}" data-parent-id="{{ $subCategory['parent_id'] }}"
                            @selected((string) old('sub_category_id', $mapping?->sub_category_id) === (string) $subCategory['id'])>
                        {{ $subCategory['name'] }}
                    </option>
                @endforeach
            </select>
            @if($errors->mappingSettings->has('sub_category_id'))
                <div class="text-danger san-field-error">{{ $errors->mappingSettings->first('sub_category_id') }}</div>
            @endif
        </div>
        <div class="col-md-3">
            <label for="san-stock-account-group">Stock Account Group</label>
            <select id="san-stock-account-group" name="stock_account_group_id" class="form-control" data-san-managed-select2 data-san-account-group>
                <option value="">Please select</option>
                @foreach($accountGroups as $group)
                    <option value="{{ $group['id'] }}" @selected((string) old('stock_account_group_id', $mapping?->stock_account_group_id) === (string) $group['id'])>{{ $group['name'] }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <label for="san-stock-account">Stock Account</label>
            <select id="san-stock-account" name="stock_account_id" class="form-control" data-san-managed-select2 data-san-stock-account required>
                <option value="">Please select</option>
                @foreach($accounts as $account)
                    <option value="{{ $account['id'] }}" data-group-id="{{ $account['group_id'] }}"
                            @selected((string) old('stock_account_id', $mapping?->stock_account_id) === (string) $account['id'])>
                        {{ $account['code'] !== '' ? $account['code'].' - ' : '' }}{{ $account['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="san-increase-account">Increase - Account to Link</label>
            <select id="san-increase-account" name="increase_account_id" class="form-control" data-san-managed-select2 required>
                <option value="">Please select</option>
                @foreach($accounts as $account)
                    <option value="{{ $account['id'] }}" @selected((string) old('increase_account_id', $mapping?->increase_account_display_id) === (string) $account['id'])>
                        {{ $account['code'] !== '' ? $account['code'].' - ' : '' }}{{ $account['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="san-decrease-account">Decrease - Account to Link</label>
            <select id="san-decrease-account" name="decrease_account_id" class="form-control" data-san-managed-select2 required>
                <option value="">Please select</option>
                @foreach($accounts as $account)
                    <option value="{{ $account['id'] }}" @selected((string) old('decrease_account_id', $mapping?->decrease_account_display_id) === (string) $account['id'])>
                        {{ $account['code'] !== '' ? $account['code'].' - ' : '' }}{{ $account['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 san-active-setting">
            <label class="san-setting-switch san-setting-switch-compact">
                <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $mapping?->is_active ?? true))>
                <span class="san-setting-switch-body">
                    <strong>Active Mapping</strong>
                    <small>Allow this mapping to be used.</small>
                </span>
            </label>
        </div>
    </div>

    <div class="row san-mapping-notes-row">
        <div class="col-md-9">
            <label for="san-mapping-notes">Notes</label>
            <input id="san-mapping-notes" name="notes" class="form-control" maxlength="1000"
                   value="{{ old('notes', $mapping?->notes) }}">
        </div>
        <div class="col-md-3 san-mapping-save-cell">
            <button type="submit" class="btn btn-primary">{{ $mapping ? 'Update Mapping' : 'Save Mapping' }}</button>
        </div>
    </div>

    @if(empty($categories) || empty($accounts))
        <div class="alert alert-warning san-reference-warning">
            The page remains operational, but some shared Category or Finance tables were not found in this tenant database. Missing dropdowns will populate automatically after those common modules are installed.
        </div>
    @endif

    @if($savedMapping)
        <div class="alert alert-success san-saved-mapping-summary">
            <strong>Saved Mapping:</strong>
            Category: {{ $categoryNames[$savedMapping->category_id] ?? 'All' }} |
            Sub Category: {{ $categoryNames[$savedMapping->sub_category_id] ?? 'All' }} |
            Stock Account: {{ $accountNames[$savedMapping->stock_account_id] ?? '—' }} |
            Increase Account: {{ $accountNames[$savedMapping->increase_account_display_id] ?? '—' }} |
            Decrease Account: {{ $accountNames[$savedMapping->decrease_account_display_id] ?? '—' }}
        </div>
    @endif
</form>

<div id="accounting-mappings-list" class="san-panel san-settings-panel">
    <div class="san-settings-section-heading">
        <div>
            <h3>Accounting Mappings</h3>
            <p>Newest effective mapping is evaluated first for the matching category.</p>
        </div>
    </div>

    <div class="table-responsive san-settings-table-wrap">
        <table class="table table-bordered table-hover san-settings-table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Effective<br>From</th>
                    <th>Category</th>
                    <th>Sub Category</th>
                    <th>Stock Account</th>
                    <th>Increase Account</th>
                    <th>Decrease Account</th>
                    <th>Status</th>
                    <th>Added By</th>
                </tr>
            </thead>
            <tbody>
                @foreach($mappings as $row)
                    <tr>
                        <td class="san-settings-actions-cell">
                            <div class="btn-group">
                                <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-san-mapping-action-toggle aria-haspopup="true" aria-expanded="false">
                                    Action <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a href="{{ route('stock-adjustment-new.settings.index', ['view_mapping' => $row->id]) }}#accounting-mappings-list">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                    </li>
                                    <li class="divider"></li>
                                    <li>
                                        <a href="{{ route('stock-adjustment-new.settings.index', ['edit_mapping' => $row->id]) }}#accounting-mapping-form">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                        <td>{{ optional($row->effective_from)->format('Y-m-d H:i') ?: '—' }}</td>
                        <td>{{ $categoryNames[$row->category_id] ?? 'All' }}</td>
                        <td>{{ $categoryNames[$row->sub_category_id] ?? 'All' }}</td>
                        <td>{{ $accountNames[$row->stock_account_id] ?? '—' }}</td>
                        <td>{{ $accountNames[$row->increase_account_display_id] ?? '—' }}</td>
                        <td>{{ $accountNames[$row->decrease_account_display_id] ?? '—' }}</td>
                        <td><span class="san-status">{{ $row->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td>{{ $row->created_by ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($mappings->isEmpty())
        <div class="san-table-empty">No accounting mappings have been created yet.</div>
    @endif

    {{ $mappings->links() }}
</div>

@if($viewingMapping)
    <div class="san-mapping-view-overlay" role="dialog" aria-modal="true" aria-labelledby="san-mapping-view-title">
        <div class="san-mapping-view-dialog">
            <div class="san-mapping-view-header">
                <div>
                    <h3 id="san-mapping-view-title">Accounting Mapping Details</h3>
                    <p>View the saved Stock Adjustment accounting mapping.</p>
                </div>
                <a href="{{ route('stock-adjustment-new.settings.index') }}#accounting-mappings-list" class="san-mapping-view-close" aria-label="Close">&times;</a>
            </div>

            <div class="san-mapping-view-grid">
                <div><span>Effective From</span><strong>{{ optional($viewingMapping->effective_from)->format('Y-m-d H:i') ?: '—' }}</strong></div>
                <div><span>Category</span><strong>{{ $categoryNames[$viewingMapping->category_id] ?? 'All' }}</strong></div>
                <div><span>Sub Category</span><strong>{{ $categoryNames[$viewingMapping->sub_category_id] ?? 'All' }}</strong></div>
                <div><span>Stock Account Group</span><strong>{{ $groupNames[$viewingMapping->stock_account_group_id] ?? '—' }}</strong></div>
                <div><span>Stock Account</span><strong>{{ $accountNames[$viewingMapping->stock_account_id] ?? '—' }}</strong></div>
                <div><span>Increase Account</span><strong>{{ $accountNames[$viewingMapping->increase_account_display_id] ?? '—' }}</strong></div>
                <div><span>Decrease Account</span><strong>{{ $accountNames[$viewingMapping->decrease_account_display_id] ?? '—' }}</strong></div>
                <div><span>Status</span><strong>{{ $viewingMapping->is_active ? 'Active' : 'Inactive' }}</strong></div>
                <div><span>Added By</span><strong>{{ $viewingMapping->created_by ?: '—' }}</strong></div>
            </div>

            <div class="san-mapping-view-notes">
                <span>Notes</span>
                <strong>{{ $viewingMapping->notes ?: '—' }}</strong>
            </div>

            <div class="san-mapping-view-footer">
                <a href="{{ route('stock-adjustment-new.settings.index', ['edit_mapping' => $viewingMapping->id]) }}#accounting-mapping-form" class="btn btn-primary">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <a href="{{ route('stock-adjustment-new.settings.index') }}#accounting-mappings-list" class="btn btn-default">Close</a>
            </div>
        </div>
    </div>
@endif

<script>
(function () {
    'use strict';

    function refreshSelect2(select) {
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            window.jQuery(select).trigger('change.select2');
        }
    }

    function initialiseManagedSelect(select) {
        if (!select || !window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) return;

        var $select = window.jQuery(select);
        try {
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
        } catch (error) {}

        // Remove stale containers left by a global Select2 initialiser. This is
        // the source of the duplicate search box seen on the Edit Mapping page.
        var sibling = select.nextElementSibling;
        while (sibling && sibling.classList && sibling.classList.contains('select2-container')) {
            var stale = sibling;
            sibling = sibling.nextElementSibling;
            stale.remove();
        }

        $select.removeClass('select2-hidden-accessible')
            .removeAttr('data-select2-id aria-hidden tabindex')
            .removeData('select2')
            .select2({
                width: '100%',
                minimumResultsForSearch: 0
            });
    }

    function filterSubCategories() {
        var category = document.querySelector('[data-san-category]');
        var subCategory = document.querySelector('[data-san-sub-category]');
        if (!category || !subCategory) return;

        var selectedCategory = String(category.value || '');
        var invalidSelectionCleared = false;

        Array.prototype.forEach.call(subCategory.options, function (option, index) {
            if (index === 0) return;

            var parentId = String(option.dataset.parentId || '');
            var allowed = !selectedCategory || parentId === selectedCategory;
            option.hidden = !allowed;
            option.disabled = !allowed;

            // Do not retain a stale child when Category changes. Select2 can
            // otherwise post the previous sub-category even though it is no
            // longer visible under the new Category.
            if (!allowed && option.selected) {
                option.selected = false;
                invalidSelectionCleared = true;
            }
        });

        if (invalidSelectionCleared) {
            subCategory.value = '';
        }
        refreshSelect2(subCategory);
    }

    function syncCategoryFromSubCategory() {
        var category = document.querySelector('[data-san-category]');
        var subCategory = document.querySelector('[data-san-sub-category]');
        if (!category || !subCategory || !subCategory.value) return;

        var selectedOption = subCategory.options[subCategory.selectedIndex];
        var parentId = selectedOption ? String(selectedOption.dataset.parentId || '') : '';
        if (!parentId) return;

        // Selecting a child first should also select its real parent. This keeps
        // the submitted pair valid even if the host Select2 triggers events in
        // a different order.
        if (!category.value) {
            category.value = parentId;
            refreshSelect2(category);
        }

        filterSubCategories();
    }

    function filterStockAccounts(preserveSelected) {
        var group = document.querySelector('[data-san-account-group]');
        var account = document.querySelector('[data-san-stock-account]');
        if (!group || !account) return;

        var selectedGroup = group.value;
        Array.prototype.forEach.call(account.options, function (option, index) {
            if (index === 0) return;
            var allowed = !selectedGroup || !option.dataset.groupId || option.dataset.groupId === selectedGroup;
            if (preserveSelected && option.selected) allowed = true;
            option.hidden = !allowed;
            option.disabled = !allowed;
            if (!preserveSelected && !allowed && option.selected) option.selected = false;
        });
        refreshSelect2(account);
    }

    function toggleBackdateLimit() {
        var allow = document.querySelector('[data-san-allow-backdate]');
        var limit = document.querySelector('[data-san-max-backdate]');
        if (!allow || !limit) return;
        limit.disabled = !allow.checked;
        if (!allow.checked) limit.value = '';
    }

    document.addEventListener('change', function (event) {
        if (event.target.matches('[data-san-category]')) filterSubCategories();
        if (event.target.matches('[data-san-sub-category]')) syncCategoryFromSubCategory();
        if (event.target.matches('[data-san-account-group]')) filterStockAccounts(false);
        if (event.target.matches('[data-san-allow-backdate]')) toggleBackdateLimit();
    });

    function bootManagedSelects() {
        document.querySelectorAll('[data-san-managed-select2], .san-settings-panel select.select2')
            .forEach(initialiseManagedSelect);
        filterSubCategories();
        filterStockAccounts(true);
        toggleBackdateLimit();
    }

    filterSubCategories();
    filterStockAccounts(true);
    toggleBackdateLimit();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootManagedSelects);
    } else {
        bootManagedSelects();
    }

    // Host layouts sometimes initialise Select2 after module content. Re-run
    // once after those global handlers so stale/duplicate containers are removed.
    window.setTimeout(bootManagedSelects, 350);

    @if ($errors->mappingSettings->any())
    window.setTimeout(function () {
        var mappingForm = document.getElementById('accounting-mapping-form');
        if (mappingForm) {
            mappingForm.scrollIntoView({ behavior: 'auto', block: 'start' });
        }
    }, 0);
    @endif
})();
</script>
<script>
/*
 * IS2065 / IS2058 / 8052: Accounting Mappings -> Action -> View/Edit.
 *
 * The mapping table must stay inside the page.  Bootstrap responsive
 * table wrappers clip dropdowns, and some host layouts also install their own
 * dropdown handlers.  Do not rely on either behaviour here: intercept only this
 * module's Action toggle, clone its menu to <body>, and position the clone against
 * the viewport. The real View/Edit URLs are preserved unchanged.
 */
(function () {
    'use strict';

    var toggleSelector = '.san-settings-actions-cell [data-san-mapping-action-toggle]';
    var portalClass = 'san-mapping-action-portal';
    var currentToggle = null;

    function closePortal() {
        var portals = document.querySelectorAll('body > .' + portalClass);
        Array.prototype.forEach.call(portals, function (portal) {
            if (portal.parentNode) portal.parentNode.removeChild(portal);
        });

        if (currentToggle) {
            currentToggle.setAttribute('aria-expanded', 'false');
            var group = currentToggle.closest('.btn-group');
            if (group) group.classList.remove('open');
        }
        currentToggle = null;
    }

    function positionPortal(toggle, portal) {
        var rect = toggle.getBoundingClientRect();
        var vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
        var vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);
        var gap = 8;
        var width = Math.min(190, Math.max(150, vw - (gap * 2)));

        portal.style.width = width + 'px';
        portal.style.minWidth = width + 'px';
        portal.style.maxWidth = width + 'px';
        portal.style.visibility = 'hidden';
        portal.style.display = 'block';
        portal.style.position = 'fixed';
        portal.style.left = '0px';
        portal.style.top = '0px';
        portal.style.maxHeight = 'none';
        portal.style.overflowX = 'hidden';
        portal.style.overflowY = 'auto';

        var naturalHeight = Math.max(portal.scrollHeight, 80);
        var spaceBelow = Math.max(0, vh - rect.bottom - gap);
        var spaceAbove = Math.max(0, rect.top - gap);
        var availableHeight = Math.max(100, vh - (gap * 2));
        var top;
        var maxHeight;

        if (naturalHeight <= spaceBelow) {
            top = rect.bottom + 4;
            maxHeight = spaceBelow;
        } else if (naturalHeight <= spaceAbove) {
            top = Math.max(gap, rect.top - naturalHeight - 4);
            maxHeight = spaceAbove;
        } else {
            top = gap;
            maxHeight = availableHeight;
        }

        var left = Math.min(
            Math.max(gap, rect.left),
            Math.max(gap, vw - width - gap)
        );

        portal.style.left = Math.round(left) + 'px';
        portal.style.top = Math.round(top) + 'px';
        portal.style.maxHeight = Math.round(Math.min(maxHeight, availableHeight)) + 'px';
        portal.style.visibility = 'visible';
    }

    function openPortal(toggle) {
        var group = toggle.closest('.btn-group');
        if (!group) return;

        var source = group.querySelector(':scope > .dropdown-menu');
        if (!source) return;

        var wasCurrent = currentToggle === toggle && document.querySelector('body > .' + portalClass);
        closePortal();
        if (wasCurrent) return;

        var portal = source.cloneNode(true);
        portal.classList.add('san-detached-menu', portalClass);
        portal.classList.remove('dropdown-menu-right');
        document.body.appendChild(portal);

        currentToggle = toggle;
        group.classList.add('open');
        toggle.setAttribute('aria-expanded', 'true');
        positionPortal(toggle, portal);
    }

    // Capture phase runs before Bootstrap/global delegated dropdown handlers.
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest ? event.target.closest(toggleSelector) : null;
        if (toggle) {
            event.preventDefault();
            event.stopPropagation();
            if (event.stopImmediatePropagation) event.stopImmediatePropagation();
            openPortal(toggle);
            return;
        }

        var portal = event.target.closest ? event.target.closest('.' + portalClass) : null;
        if (portal) {
            // Keep native anchor navigation / form submission working.
            return;
        }

        closePortal();
    }, true);

    window.addEventListener('resize', closePortal, { passive: true });
    window.addEventListener('scroll', closePortal, { passive: true });

    var wrapper = document.querySelector('.san-settings-table-wrap');
    if (wrapper) wrapper.addEventListener('scroll', closePortal, { passive: true });
})();
</script>
@endsection

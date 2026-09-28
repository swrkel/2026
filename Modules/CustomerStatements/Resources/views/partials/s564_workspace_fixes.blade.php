<!-- S569: isolated Customer Statement tabs/save controls and guarded numbering deletion. -->
<div id="customer-statements-s569-runtime"
    class="hide"
    aria-hidden="true"
    data-active-section="{{ $customerStatementActiveSection ?? 'customer' }}"
    data-numbering-show-url="{{ $customerStatementNumberingShowUrl ?? '' }}"
    data-numbering-store-url="{{ $customerStatementNumberingStoreUrl ?? '' }}"
    data-next-number-url="{{ $customerStatementNextNumberUrl ?? '' }}"
    data-statement-store-url="{{ $customerStatementStoreUrl ?? '' }}"
    data-bill-list-url="{{ $customerStatementBillListUrl ?? '' }}"
    data-statement-list-url="{{ $customerStatementListUrl ?? '' }}"
    data-settings-list-url="{{ $customerStatementSettingsListUrl ?? '' }}"></div>

<script type="text/x-template" id="customer-statement-alert-settings-template">
    @include('customerstatements::partials.alert_settings')
</script>

<style id="customer-statements-s569-style">
    #customer_statement_alert_settings { display: none; }
    body[data-customer-statement-active-section="alert-settings"] #customer_statement_alert_settings {
        display: block;
    }
    .customer-statement-alert-settings-page .box { margin-bottom: 0; }
    #statement_settings_table .cs-numbering-delete[disabled] {
        cursor: not-allowed;
        opacity: .55;
    }
</style>

<script id="customer-statements-s569-script">
(function ($) {
    'use strict';

    if (!$) {
        return;
    }

    var $config = $('#customer-statements-s569-runtime');
    if (!$config.length) {
        return;
    }

    var activeSection = String($config.attr('data-active-section') || 'customer');
    var numberingShowUrl = String($config.attr('data-numbering-show-url') || '');
    var numberingStoreUrl = String($config.attr('data-numbering-store-url') || '');
    var nextNumberUrl = String($config.attr('data-next-number-url') || '');
    var statementStoreUrl = String($config.attr('data-statement-store-url') || '');
    var billListUrl = String($config.attr('data-bill-list-url') || '');
    var statementListUrl = String($config.attr('data-statement-list-url') || '');
    var settingsListUrl = String($config.attr('data-settings-list-url') || '');
    var saveInProgress = false;
    var nextNumberRequest = 0;
    var nextNumberTimer = null;

    function normaliseText(value) {
        return String(value || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function requestPath(url) {
        try {
            var parser = document.createElement('a');
            parser.href = String(url || '');
            return String(parser.pathname || '').replace(/\/+$/, '');
        } catch (error) {
            return String(url || '').split('?')[0].replace(/\/+$/, '');
        }
    }

    function requestMethod(options) {
        return String((options && (options.type || options.method)) || 'GET').toUpperCase();
    }

    function csrfHeaders() {
        var token = $('meta[name="csrf-token"]').attr('content') || '';
        return token ? { 'X-CSRF-TOKEN': token } : {};
    }

    function showMessage(type, message) {
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message);
            return;
        }

        if (message) {
            window.alert(message);
        }
    }

    /*
     * The legacy page creates its DataTables during DOM-ready with hard-coded
     * Customers URLs. Redirect only the three module-owned read requests. Saving is handled
     * explicitly by bindSaveButton(), so no global POST interception is used.
     */
    if ($.ajaxPrefilter) {
        $.ajaxPrefilter(function (options) {
            var path = requestPath(options.url);
            var method = requestMethod(options);

            if (
                method === 'GET'
                && billListUrl
                && (/\/customers\/customer-statement$/.test(path)
                    || /\/customer-statement$/.test(path))
            ) {
                options.url = billListUrl;
                return;
            }

            if (
                method === 'GET'
                && statementListUrl
                && (/\/customers\/customer-statement\/get-statement-list$/.test(path)
                    || /\/customer-statement\/get-statement-list$/.test(path))
            ) {
                options.url = statementListUrl;
                return;
            }

            if (
                method === 'GET'
                && settingsListUrl
                && (/\/customers\/customer-statement-settings$/.test(path)
                    || /\/customer-statement-settings$/.test(path))
            ) {
                options.url = settingsListUrl;
            }
        });
    }

    function workspaceContent() {
        return $('#customer_statements').closest('.tab-content').first();
    }

    function tabLinks() {
        return $('.customer-statement-is1638-tabs .nav-tabs > li > a');
    }

    function sectionFromText(text) {
        text = normaliseText(text);

        if (text.indexOf('list customer statement') !== -1) {
            return 'list';
        }
        if (text.indexOf('customer statement settings') !== -1) {
            return 'numbering-settings';
        }
        if (text.indexOf('statement print format') !== -1) {
            return 'print-formats';
        }
        if (text.indexOf('alert settings') !== -1) {
            return 'alert-settings';
        }
        if (text.indexOf('statement settings') !== -1) {
            return 'statement-settings';
        }
        if (text.indexOf('customer statement') !== -1) {
            return 'customer';
        }

        return '';
    }

    function targetForSection(section) {
        var targets = {
            customer: '#customer_statements',
            list: '#list_customer_statements',
            'statement-settings': '#logos',
            'numbering-settings': '#settings_customer_statements',
            'print-formats': '#font_settings',
            'alert-settings': '#customer_statement_alert_settings'
        };

        return targets[section] || '';
    }

    function ensureAlertPane() {
        var $content = workspaceContent();
        if (!$content.length || $('#customer_statement_alert_settings').length) {
            return;
        }

        var template = $('#customer-statement-alert-settings-template').html() || '';
        $('<div/>', {
            id: 'customer_statement_alert_settings',
            'class': 'tab-pane',
            'aria-hidden': 'true'
        }).html(template).appendTo($content);
    }

    function prepareTabs() {
        ensureAlertPane();

        tabLinks().each(function () {
            var $link = $(this);
            var section = sectionFromText($link.text());
            var target = targetForSection(section);

            if (!section || !target || !$(target).length) {
                return;
            }

            $link
                .attr('href', target)
                .attr('data-customer-statement-section', section);
        });

        /* Statement Print Formats is the existing font/print-format editor. */
        $('#list_statement_payments')
            .removeClass('active in show')
            .hide()
            .attr('aria-hidden', 'true');

        $('#font_settings').find('button[type="submit"]').filter(function () {
            return normaliseText($(this).text()).indexOf('save font settings') !== -1;
        }).each(function () {
            var $button = $(this);
            $button.contents().filter(function () {
                return this.nodeType === 3;
            }).remove();
            $button.append(' Save Print Format Settings');
        });
    }

    function linkForSection(section) {
        return tabLinks()
            .filter('[data-customer-statement-section="' + section + '"]')
            .first();
    }

    function adjustVisibleTables() {
        window.setTimeout(function () {
            if (!$.fn.dataTable) {
                return;
            }

            try {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            } catch (error) {
                /* Table may not yet be initialised. */
            }
        }, 40);
    }

    function reloadDataTable(selector, url) {
        if (!$.fn.DataTable || !$.fn.DataTable.isDataTable(selector)) {
            return;
        }

        try {
            var table = $(selector).DataTable();
            if (url && table.ajax && typeof table.ajax.url === 'function') {
                table.ajax.url(url).load(null, false);
            } else if (table.ajax && typeof table.ajax.reload === 'function') {
                table.ajax.reload(null, false);
            }
        } catch (error) {
            /* Do not block the page when a hidden DataTable is still booting. */
        }
    }

    function activateSection(section) {
        prepareTabs();

        var target = targetForSection(section);
        var $pane = target ? $(target).first() : $();
        var $content = workspaceContent();
        var $link = linkForSection(section);

        if (!$pane.length || !$content.length || !$link.length) {
            return false;
        }

        $content.children('.tab-pane')
            .removeClass('active in show')
            .hide()
            .attr('aria-hidden', 'true');

        $pane
            .addClass('active in show')
            .show()
            .attr('aria-hidden', 'false');

        tabLinks().closest('li').removeClass('active');
        $link.closest('li').addClass('active');

        activeSection = section;
        $('body').attr('data-customer-statement-active-section', section);

        if (section === 'list') {
            reloadDataTable('#customer_statement_list_table', statementListUrl);
        } else if (section === 'numbering-settings') {
            reloadDataTable('#statement_settings_table', settingsListUrl);
        }

        adjustVisibleTables();
        return true;
    }

    function bindTabs() {
        prepareTabs();

        /*
         * Bind directly to these six links. stopImmediatePropagation prevents the
         * older delegated Bootstrap/fallback handlers from running afterwards,
         * without removing click handlers from unrelated page controls.
         */
        tabLinks()
            .off('click.customerStatementsS569')
            .on('click.customerStatementsS569', function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();

                var section = String($(this).attr('data-customer-statement-section') || '');
                if (section) {
                    activateSection(section);
                }

                return false;
            });
    }

    function modeSelect() {
        return $('#enable_separate_customer_statement_no').first();
    }

    function firstAvailable(primarySelector, fallbackSelector) {
        var $primary = $(primarySelector).first();
        return $primary.length ? $primary : $(fallbackSelector).first();
    }

    function settingsCustomerSelect() {
        return firstAvailable('#statement_customer_id', '#customer_id');
    }

    function customerStartingInput() {
        return firstAvailable('#statement_starting_no_customer', '#starting_no');
    }

    function generalStartingInput() {
        return firstAvailable('#statement_starting_no_all', '#starting_no_all');
    }

    function selectedMode() {
        var $select = modeSelect();
        var value = normaliseText($select.val());
        var text = normaliseText($select.find('option:selected').text());

        return value === '0' || value === 'general' || text.indexOf('general') !== -1
            ? 'general'
            : 'customer';
    }

    function updateModeOptions() {
        modeSelect().find('option').each(function () {
            var $option = $(this);
            var value = normaliseText($option.val());

            if (value === '1' || value === 'customer' || value === 'customer-wise') {
                $option.text('Customer-wise');
            } else if (value === '0' || value === 'general') {
                $option.text('General');
            }
        });
    }

    function applyModeVisibility(mode) {
        var customerWise = mode === 'customer';
        $('.customer_separate_field').toggleClass('hide', !customerWise).toggle(customerWise);
        $('.customer_separate_field_no').toggleClass('hide', customerWise).toggle(!customerWise);
    }

    function setMode(mode) {
        var $select = modeSelect();
        if (!$select.length) {
            return;
        }

        var desired = mode === 'general'
            ? ['0', 'general']
            : ['1', 'customer', 'customer-wise'];

        $.each(desired, function (_, value) {
            if ($select.find('option[value="' + value + '"]').length) {
                $select.val(value);
                return false;
            }
        });

        $select.trigger('change.select2');
        applyModeVisibility(mode);
    }

    function refreshNumberingSettings(customerId) {
        if (!numberingShowUrl) {
            return;
        }

        $.ajax({
            method: 'get',
            url: numberingShowUrl,
            data: customerId ? { customer_id: customerId } : {},
            cache: false,
            success: function (result) {
                if (!result || Number(result.success) !== 1 || !result.data) {
                    return;
                }

                updateModeOptions();
                setMode(result.data.mode);

                if (result.data.mode === 'general') {
                    generalStartingInput().val(result.data.starting_no);
                } else if (customerId) {
                    customerStartingInput().val(result.data.starting_no);
                }
            }
        });
    }

    function reloadNumberingTable() {
        reloadDataTable('#statement_settings_table', settingsListUrl);
    }

    function saveNumberingSettings(event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        var $button = $(event.currentTarget);
        var mode = selectedMode();
        var customerId = settingsCustomerSelect().val();
        var startingNo = mode === 'general'
            ? generalStartingInput().val()
            : customerStartingInput().val();

        if (mode === 'customer' && !customerId) {
            showMessage('error', 'Please select a customer for Customer-wise numbering.');
            return false;
        }

        startingNo = parseInt(startingNo, 10);
        if (!startingNo || startingNo < 1) {
            showMessage('error', 'Please enter a valid starting number.');
            return false;
        }

        $button.prop('disabled', true);

        $.ajax({
            method: 'post',
            url: numberingStoreUrl,
            dataType: 'json',
            headers: csrfHeaders(),
            data: {
                mode: mode,
                enable_separate_customer_statement_no: mode === 'customer' ? 1 : 0,
                customer_id: mode === 'customer' ? customerId : '',
                starting_no: startingNo
            },
            success: function (result) {
                if (result && Number(result.success) === 1) {
                    showMessage(
                        'success',
                        result.msg || 'Customer Statement numbering settings saved successfully.'
                    );
                    refreshNumberingSettings(customerId);
                    reloadNumberingTable();
                } else {
                    showMessage(
                        'error',
                        (result && result.msg) || 'Unable to save the numbering settings.'
                    );
                }
            },
            error: function (xhr) {
                var response = xhr.responseJSON || {};
                showMessage('error', response.msg || 'Unable to save the numbering settings.');
            },
            complete: function () {
                $button.prop('disabled', false);
            }
        });

        return false;
    }

    function deleteNumberingSetting($button) {
        if ($button.prop('disabled')) {
            return false;
        }

        var url = String($button.attr('data-delete-url') || '');
        if (!url) {
            showMessage('error', 'The numbering setting delete URL is missing.');
            return false;
        }

        var question = 'Delete this unused Customer Statement numbering code?';
        if (!window.confirm(question)) {
            return false;
        }

        $button.prop('disabled', true);

        $.ajax({
            method: 'delete',
            url: url,
            dataType: 'json',
            headers: csrfHeaders(),
            success: function (result) {
                if (result && Number(result.success) === 1) {
                    showMessage('success', result.msg || 'Numbering code deleted successfully.');
                    reloadNumberingTable();
                    refreshNumberingSettings(settingsCustomerSelect().val());
                } else {
                    showMessage(
                        'error',
                        (result && result.msg) || 'Unable to delete the numbering code.'
                    );
                }
            },
            error: function (xhr) {
                var response = xhr.responseJSON || {};
                showMessage('error', response.msg || 'Unable to delete the numbering code.');
            },
            complete: function () {
                $button.prop('disabled', false);
            }
        });

        return false;
    }

    function bindNumberingControls() {
        updateModeOptions();

        modeSelect()
            .off('change.customerStatementsS569')
            .on('change.customerStatementsS569', function () {
                applyModeVisibility(selectedMode());
            });

        settingsCustomerSelect()
            .off('change.customerStatementsS569')
            .on('change.customerStatementsS569', function () {
                refreshNumberingSettings($(this).val());
            });

        $('.settings_statement_btn, #settings_statement_btn')
            .attr('type', 'button')
            .off('click.customerStatementsS569')
            .on('click.customerStatementsS569', saveNumberingSettings);

        $(document)
            .off('click.customerStatementsS569Edit', '.cs-numbering-edit')
            .on('click.customerStatementsS569Edit', '.cs-numbering-edit', function (event) {
                event.preventDefault();
                setMode('customer');
                settingsCustomerSelect().val($(this).data('customer-id')).trigger('change');
                customerStartingInput().val($(this).data('starting-no'));
            })
            .off('click.customerStatementsS569Delete', '.cs-numbering-delete')
            .on('click.customerStatementsS569Delete', '.cs-numbering-delete', function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();
                deleteNumberingSetting($(this));
            });

        refreshNumberingSettings(settingsCustomerSelect().val());
    }

    function parseDate(value) {
        value = String(value || '').trim();
        if (!value || !window.moment) {
            return '';
        }

        var formats = [];
        if (window.moment_date_format) {
            formats.push(window.moment_date_format);
        }
        formats.push('YYYY-MM-DD', 'DD/MM/YYYY', 'MM/DD/YYYY', 'DD-MM-YYYY');

        var parsed = window.moment(value, formats, true);
        if (!parsed.isValid()) {
            parsed = window.moment(value);
        }

        return parsed.isValid() ? parsed.format('YYYY-MM-DD') : '';
    }

    function statementDates() {
        var $range = $('#customer_statement_date_range');
        var dates = { start_date: '', end_date: '' };

        if ($range.length && $range.data('daterangepicker')) {
            dates.start_date = $range.data('daterangepicker').startDate.format('YYYY-MM-DD');
            dates.end_date = $range.data('daterangepicker').endDate.format('YYYY-MM-DD');
            return dates;
        }

        var raw = String($range.val() || '');
        var parts = raw.split(/\s+(?:-|~|to)\s+/i);
        if (parts.length === 2) {
            dates.start_date = parseDate(parts[0]);
            dates.end_date = parseDate(parts[1]);
        }

        return dates;
    }

    function refreshStatementNumber() {
        var customerId = $('#customer_statement_customer_id').val();
        if (!customerId || !nextNumberUrl) {
            return;
        }

        var requestId = ++nextNumberRequest;
        var data = statementDates();
        data.customer_id = customerId;

        $.ajax({
            method: 'get',
            url: nextNumberUrl,
            data: data,
            cache: false,
            success: function (result) {
                if (requestId !== nextNumberRequest || !result || !result.statement_no) {
                    return;
                }

                $('#statement_no').val(result.statement_no);
                $('.statement_no').text(result.statement_no);

                if (result.header) {
                    $('#print_header_div').empty().append(result.header);
                }
            }
        });
    }

    function scheduleStatementNumberRefresh() {
        window.clearTimeout(nextNumberTimer);
        nextNumberTimer = window.setTimeout(refreshStatementNumber, 80);
    }

    function saveStatement($button) {
        if (saveInProgress) {
            return false;
        }

        var customerId = $('#customer_statement_customer_id').val();
        var dates = statementDates();

        if (!customerId) {
            showMessage('error', 'Please select a customer.');
            return false;
        }

        if (!dates.start_date || !dates.end_date) {
            showMessage('error', 'Please select a valid statement date range.');
            return false;
        }

        if (!statementStoreUrl) {
            showMessage('error', 'The Customer Statement save URL is missing.');
            return false;
        }

        saveInProgress = true;
        $button.prop('disabled', true);

        $.ajax({
            method: 'post',
            url: statementStoreUrl,
            dataType: 'json',
            headers: csrfHeaders(),
            timeout: 60000,
            data: {
                location_id: $('#customer_statement_location_id').val() || '',
                customer_id: customerId,
                start_date: dates.start_date,
                end_date: dates.end_date,
                statement_no: $('#statement_no').val() || '',
                logo: $('#customer_statement_logos').val() || ''
            },
            success: function (result) {
                if (result && Number(result.success) === 1) {
                    showMessage('success', result.msg || 'Customer Statement saved successfully.');
                    reloadDataTable('#customer_statement_table', '');
                    reloadDataTable('#customer_statement_list_table', statementListUrl);
                    scheduleStatementNumberRefresh();
                } else {
                    showMessage(
                        'error',
                        (result && result.msg) || 'Unable to save the Customer Statement.'
                    );
                }
            },
            error: function (xhr) {
                var response = xhr.responseJSON || {};
                showMessage('error', response.msg || 'Unable to save the Customer Statement.');
            },
            complete: function () {
                saveInProgress = false;
                $button.prop('disabled', false);
            }
        });

        return false;
    }

    function bindSaveButton() {
        var $button = $('#customer_statements .print_report').first();
        if (!$button.length) {
            return;
        }

        /* Remove only the legacy inline call and prevent accidental form submit. */
        $button
            .attr('type', 'button')
            .removeAttr('onclick')
            .off('click.customerStatementsS569Save')
            .on('click.customerStatementsS569Save', function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return saveStatement($(this));
            });

        window.saveDiv = function () {
            return saveStatement($button);
        };
    }

    $(function () {
        bindTabs();
        bindSaveButton();
        bindNumberingControls();

        $('#customer_statement_customer_id, #customer_statement_date_range')
            .off('change.customerStatementsS569Number')
            .on('change.customerStatementsS569Number', scheduleStatementNumberRefresh);

        activateSection(activeSection);
        scheduleStatementNumberRefresh();

        /* Rebind once after Select2/DataTables finish their own DOM-ready work. */
        window.setTimeout(function () {
            bindTabs();
            bindSaveButton();
            activateSection(activeSection);
        }, 250);
    });
})(window.jQuery);
</script>

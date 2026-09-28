/*
 * Task 8046 - List Customer Reference runtime.
 *
 * Served as a static file through the customers.customer_references.runtime
 * route (same pattern as bulk-payment.js), so it stays cacheable. Everything
 * environment-specific arrives through the #cus_ref_config JSON island written
 * by index.blade.php.
 *
 * Responsibilities:
 *   - the server-side DataTable and its seven filters
 *   - the Add popup's staging table, including the post-Add reset rules
 *   - showing/hiding Fuel Type based on "Reference is a Vehicle"
 *   - row actions: view, edit, delete, Active/Inactive toggle
 *   - the QR popup and its WhatsApp/Email actions
 *
 * Written against jQuery, Bootstrap 3 modals, select2 and DataTables, which is
 * what the surrounding ERP theme already loads. It deliberately adds no new
 * front-end dependency.
 */
(function ($) {
    'use strict';

    if (typeof $ === 'undefined') {
        console.error('Customer Reference runtime requires jQuery.');
        return;
    }

    var config = readConfig();
    if (!config) {
        return;
    }

    /*
     * Rows staged in the Add popup but not yet saved.
     *
     * Held in memory rather than written to the server one at a time, because
     * the spec's Save button is what commits them - and committing per-Add
     * would leave orphan rows behind if the user closed the popup.
     */
    var stagedRows = [];

    /* Index of the staged row currently being re-edited, or null. */
    var editingIndex = null;

    /* Id of the saved row currently open in the edit popup, or null. */
    var editingSavedId = null;

    var table = null;

    $(document).ready(function () {
        if (config.installed) {
            initTable();
        }
        bindFilters();
        bindAddPopup();
        bindRowActions();
        bindQrActions();
    });

    /* ----------------------------------------------------------------- */
    /* Configuration                                                      */
    /* ----------------------------------------------------------------- */

    function readConfig() {
        var node = document.getElementById('cus_ref_config');
        if (!node) {
            console.error('Customer Reference configuration block is missing.');
            return null;
        }

        try {
            return JSON.parse(node.textContent || node.innerText || '{}');
        } catch (e) {
            console.error('Customer Reference configuration could not be parsed.', e);
            return null;
        }
    }

    function url(suffix) {
        return config.routes.editBase + suffix;
    }

    /*
     * Every AJAX write carries the CSRF token explicitly. The token is read
     * once from config rather than from a meta tag, so this works on pages
     * where the theme does not emit one.
     */
    function ajaxHeaders() {
        return { 'X-CSRF-TOKEN': config.routes.csrf, 'X-Requested-With': 'XMLHttpRequest' };
    }

    /* ----------------------------------------------------------------- */
    /* DataTable                                                          */
    /* ----------------------------------------------------------------- */

    function initTable() {
        if (!$.fn.DataTable) {
            console.error('Customer Reference list requires DataTables.');
            return;
        }

        table = $('#customer_references_table').DataTable({
            processing: true,
            serverSide: true,
            /*
             * Newest first. A reference list is used to find what was just
             * added far more often than to browse history.
             */
            order: [[1, 'desc']],
            ajax: {
                url: config.routes.data,
                data: function (d) {
                    d.date_range = $('#cus_ref_date_range').val() || '';
                    d.customer_id = $('#cus_ref_filter_customer_id').val() || '';
                    d.status = valueOrBlank($('#cus_ref_filter_status').val());
                    d.is_vehicle = valueOrBlank($('#cus_ref_filter_is_vehicle').val());
                    d.reference_no = $('#cus_ref_filter_reference_no').val() || '';
                    d.fuel_type = $('#cus_ref_filter_fuel_type').val() || '';
                    d.added_by = $('#cus_ref_filter_added_by').val() || '';
                }
            },
            columns: [
                { data: 'action', name: 'action', orderable: false, searchable: false },
                { data: 'reference_datetime', name: 'customer_qr_references.reference_datetime' },
                { data: 'customer_name', name: 'customer_name' },
                { data: 'status_label', name: 'customer_qr_references.is_active', searchable: false },
                { data: 'is_vehicle_label', name: 'customer_qr_references.is_vehicle', searchable: false },
                { data: 'reference_no', name: 'customer_qr_references.reference_no' },
                { data: 'fuel_type_label', name: 'customer_qr_references.fuel_type_name' },
                { data: 'added_by_name', name: 'added_by_name', searchable: false }
            ]
        });
    }

    /*
     * A select2 placeholder yields null, and an untouched native select yields
     * "". Both mean "no filter". Returning "" for both keeps the distinction
     * from a real "0" value, which Status and Is a Vehicle both use.
     */
    function valueOrBlank(value) {
        return (value === null || typeof value === 'undefined') ? '' : value;
    }

    function reloadTable() {
        if (table) {
            table.ajax.reload(null, false);
        }
    }

    /* ----------------------------------------------------------------- */
    /* Filters                                                            */
    /* ----------------------------------------------------------------- */

    function bindFilters() {
        initSelect2($('#cus_ref_filter_customer_id, #cus_ref_filter_status, #cus_ref_filter_is_vehicle, #cus_ref_filter_fuel_type, #cus_ref_filter_added_by'));

        initDateRange($('#cus_ref_date_range'));

        $('#cus_ref_apply_filters').on('click', reloadTable);

        // Typing in Reference No filters on Enter, matching how the other
        // free-text filters in this module behave.
        $('#cus_ref_filter_reference_no').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                reloadTable();
            }
        });

        $('#cus_ref_reset_filters').on('click', function () {
            $('#cus_ref_filter_customer_id, #cus_ref_filter_status, #cus_ref_filter_is_vehicle, #cus_ref_filter_fuel_type, #cus_ref_filter_added_by')
                .val('').trigger('change');
            $('#cus_ref_filter_reference_no').val('');
            clearDateRange($('#cus_ref_date_range'));
            reloadTable();
        });
    }

    /*
     * "Type & Auto Filter" from the top of the spec: every dropdown becomes a
     * searchable select2. Guarded because select2 is initialised lazily by some
     * themes and may not be present when this runs.
     */
    function initSelect2($elements) {
        if (!$.fn.select2) {
            return;
        }

        $elements.each(function () {
            var $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                return;
            }
            $el.select2({
                width: '100%',
                allowClear: true,
                // Modal-hosted dropdowns must render inside the modal, or
                // Bootstrap's focus trap steals the keystrokes and typing in
                // the search box does nothing.
                dropdownParent: $el.closest('.modal').length ? $el.closest('.modal') : $(document.body)
            });
        });
    }

    function initDateRange($input) {
        if (!$input.length || !$.fn.daterangepicker) {
            return;
        }

        $input.daterangepicker(
            {
                autoUpdateInput: false,
                locale: { format: 'DD/MM/YYYY', cancelLabel: 'Clear' },
                ranges: buildDateRanges()
            },
            function (start, end) {
                $input.val(start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY'));
                reloadTable();
            }
        );

        $input.on('cancel.daterangepicker', function () {
            $input.val('');
            reloadTable();
        });
    }

    function buildDateRanges() {
        if (typeof moment === 'undefined') {
            return {};
        }

        return {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [
                moment().subtract(1, 'month').startOf('month'),
                moment().subtract(1, 'month').endOf('month')
            ]
        };
    }

    function clearDateRange($input) {
        $input.val('');
        if ($input.data('daterangepicker')) {
            $input.data('daterangepicker').setStartDate(moment());
            $input.data('daterangepicker').setEndDate(moment());
        }
    }

    /* ----------------------------------------------------------------- */
    /* Add popup                                                          */
    /* ----------------------------------------------------------------- */

    function bindAddPopup() {
        $('#cus_ref_add_button').on('click', function () {
            editingSavedId = null;
            stagedRows = [];
            editingIndex = null;
            resetForm(true);
            renderStagedRows();
            $('#cus_ref_add_modal_label').text('Add Customer Reference');
            $('#cus_ref_save_all').html('<i class="fa fa-save"></i> Save');
            $('#cus_ref_staged_table').closest('.table-responsive').show();
            $('#cus_ref_add_row').show();
            $('#cus_ref_add_modal').modal('show');
        });

        // select2 inside a modal has to be created after the modal is visible,
        // otherwise it measures a hidden element and renders at zero width.
        $('#cus_ref_add_modal').on('shown.bs.modal', function () {
            initSelect2($('#cus_ref_customer_id, #cus_ref_is_vehicle, #cus_ref_fuel_type'));
        });

        $('#cus_ref_is_vehicle').on('change', function () {
            applyVehicleVisibility();
        });

        $('#cus_ref_add_row').on('click', stageCurrentRow);
        $('#cus_ref_cancel_edit').on('click', function () {
            editingIndex = null;
            resetForm(false);
            renderStagedRows();
        });

        $('#cus_ref_save_all').on('click', saveAll);

        // Delegated: the staged table is re-rendered on every change.
        $('#cus_ref_staged_table').on('click', '.cus-ref-staged-edit', function () {
            beginStagedEdit(parseInt($(this).data('index'), 10));
        });

        $('#cus_ref_staged_table').on('click', '.cus-ref-staged-delete', function () {
            removeStagedRow(parseInt($(this).data('index'), 10));
        });
    }

    /*
     * Fuel Type appears only when the reference is a vehicle, per the spec.
     * The value is reset to "Not Known" when the field is hidden so a stale
     * choice cannot travel with a reference that is no longer a vehicle.
     */
    function applyVehicleVisibility() {
        var isVehicle = $('#cus_ref_is_vehicle').val() === '1';
        var $wrapper = $('#cus_ref_fuel_type_wrapper');

        if (isVehicle) {
            $wrapper.show();
            $('#cus_ref_reference_hint').text('Enter the vehicle number.');
        } else {
            $wrapper.hide();
            setSelectValue($('#cus_ref_fuel_type'), config.notKnownValue);
            $('#cus_ref_reference_hint').text('Enter the reference for this customer.');
        }
    }

    /*
     * Reset the entry fields.
     *
     * keepCustomer is false only on a full popup open. After an Add the spec
     * requires the customer to persist - "once Added, need to show the last
     * entered customer name" - while every other field resets and the date and
     * time jump to the current moment.
     */
    function resetForm(fullReset) {
        if (fullReset) {
            setSelectValue($('#cus_ref_customer_id'), '');
        }

        $('#cus_ref_datetime').val(nowForInput());
        setSelectValue($('#cus_ref_is_vehicle'), '0');
        $('#cus_ref_reference_no').val('');
        setSelectValue($('#cus_ref_fuel_type'), config.notKnownValue);
        applyVehicleVisibility();

        $('#cus_ref_add_row').html('<i class="fa fa-plus"></i> Add');
        $('#cus_ref_cancel_edit').hide();
        hideFormErrors();
    }

    function setSelectValue($select, value) {
        $select.val(value);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.trigger('change.select2');
        }
    }

    /*
     * "now" in the format datetime-local expects, in the browser's local time.
     * toISOString would convert to UTC and show the wrong clock time for any
     * user not on UTC.
     */
    function nowForInput() {
        var d = new Date();
        return d.getFullYear() + '-' +
            pad(d.getMonth() + 1) + '-' +
            pad(d.getDate()) + 'T' +
            pad(d.getHours()) + ':' +
            pad(d.getMinutes());
    }

    function pad(n) {
        return (n < 10 ? '0' : '') + n;
    }

    function stageCurrentRow() {
        var row = collectForm();
        var errors = validateRow(row);

        if (errors.length) {
            showFormErrors(errors);
            return;
        }

        hideFormErrors();

        if (editingIndex !== null) {
            stagedRows[editingIndex] = row;
            editingIndex = null;
        } else {
            stagedRows.push(row);
        }

        renderStagedRows();

        // Keep the customer, reset everything else, refresh the timestamp.
        resetForm(false);
    }

    function collectForm() {
        var isVehicle = $('#cus_ref_is_vehicle').val() === '1';

        return {
            customer_id: $('#cus_ref_customer_id').val() || '',
            customer_name: $('#cus_ref_customer_id option:selected').text() || '',
            reference_datetime: $('#cus_ref_datetime').val() || '',
            is_vehicle: isVehicle ? 1 : 0,
            reference_no: $.trim($('#cus_ref_reference_no').val() || ''),
            fuel_type: isVehicle ? ($('#cus_ref_fuel_type').val() || config.notKnownValue) : config.notKnownValue,
            fuel_type_name: isVehicle
                ? ($('#cus_ref_fuel_type option:selected').text() || config.notKnownLabel)
                : ''
        };
    }

    function validateRow(row) {
        var errors = [];

        if (!row.customer_id) {
            errors.push('Select a customer.');
        }
        if (!row.reference_no) {
            errors.push(row.is_vehicle ? 'Enter the vehicle number.' : 'Enter the customer reference.');
        }
        if (!row.reference_datetime) {
            errors.push('Select the date and time.');
        }

        return errors;
    }

    function renderStagedRows() {
        var $body = $('#cus_ref_staged_table tbody');
        $body.empty();

        if (!stagedRows.length) {
            $body.append(
                '<tr class="cus-ref-empty-row"><td colspan="6" class="text-center text-muted">' +
                'No references added yet. Complete the fields above and press Add.' +
                '</td></tr>'
            );
            $('#cus_ref_staged_count').text('');
            return;
        }

        $.each(stagedRows, function (index, row) {
            var $tr = $('<tr></tr>');
            if (editingIndex === index) {
                $tr.addClass('cus-ref-editing');
            }

            $tr.append($('<td></td>').text(displayDateTime(row.reference_datetime)));
            $tr.append($('<td></td>').text(row.customer_name));
            $tr.append($('<td></td>').text(row.is_vehicle ? 'Yes' : 'No'));
            $tr.append($('<td></td>').text(row.reference_no));
            $tr.append($('<td></td>').text(row.is_vehicle ? row.fuel_type_name : ''));

            $tr.append(
                '<td class="text-center">' +
                '<button type="button" class="btn btn-xs btn-primary cus-ref-staged-edit" data-index="' + index + '">' +
                '<i class="fa fa-edit"></i></button> ' +
                '<button type="button" class="btn btn-xs btn-danger cus-ref-staged-delete" data-index="' + index + '">' +
                '<i class="fa fa-trash"></i></button>' +
                '</td>'
            );

            $body.append($tr);
        });

        $('#cus_ref_staged_count').text(stagedRows.length + ' reference(s) ready to save.');
    }

    function displayDateTime(value) {
        if (!value) {
            return '';
        }

        var parts = value.split('T');
        if (parts.length !== 2) {
            return value;
        }

        var date = parts[0].split('-');
        if (date.length !== 3) {
            return value;
        }

        return date[2] + '/' + date[1] + '/' + date[0] + ' ' + parts[1];
    }

    function beginStagedEdit(index) {
        var row = stagedRows[index];
        if (!row) {
            return;
        }

        editingIndex = index;

        setSelectValue($('#cus_ref_customer_id'), row.customer_id);
        $('#cus_ref_datetime').val(row.reference_datetime);
        setSelectValue($('#cus_ref_is_vehicle'), row.is_vehicle ? '1' : '0');
        $('#cus_ref_reference_no').val(row.reference_no);
        applyVehicleVisibility();
        setSelectValue($('#cus_ref_fuel_type'), row.fuel_type);

        $('#cus_ref_add_row').html('<i class="fa fa-check"></i> Update');
        $('#cus_ref_cancel_edit').show();

        renderStagedRows();
    }

    function removeStagedRow(index) {
        stagedRows.splice(index, 1);

        // If the row being edited was removed, drop out of edit mode; if a row
        // before it was removed, the index has shifted by one.
        if (editingIndex === index) {
            editingIndex = null;
            resetForm(false);
        } else if (editingIndex !== null && editingIndex > index) {
            editingIndex -= 1;
        }

        renderStagedRows();
    }

    function saveAll() {
        // Editing an already-saved row reuses this popup, so Save has to route
        // to the update endpoint rather than the bulk store one.
        if (editingSavedId !== null) {
            saveSingleEdit();
            return;
        }

        // A row left half-typed in the fields is easy to forget. Stage it
        // rather than silently discarding it on Save.
        if (hasUnstagedInput()) {
            var row = collectForm();
            var errors = validateRow(row);
            if (errors.length) {
                showFormErrors(errors);
                return;
            }
            stageCurrentRow();
        }

        if (!stagedRows.length) {
            showFormErrors(['Add at least one customer reference before saving.']);
            return;
        }

        var payload = $.map(stagedRows, function (row) {
            return {
                customer_id: row.customer_id,
                reference_datetime: row.reference_datetime,
                is_vehicle: row.is_vehicle,
                reference_no: row.reference_no,
                fuel_type: row.fuel_type
            };
        });

        toggleSaving(true);

        $.ajax({
            url: config.routes.store,
            type: 'POST',
            headers: ajaxHeaders(),
            data: { references: payload },
            success: function (response) {
                toggleSaving(false);
                if (!response || !response.success) {
                    showFormErrors([(response && response.msg) || 'Unable to save.']);
                    return;
                }
                $('#cus_ref_add_modal').modal('hide');
                notify('success', response.msg);
                reloadTable();
            },
            error: function (xhr) {
                toggleSaving(false);
                showFormErrors(extractErrors(xhr));
            }
        });
    }

    function hasUnstagedInput() {
        return $.trim($('#cus_ref_reference_no').val() || '') !== '';
    }

    function toggleSaving(saving) {
        $('#cus_ref_save_all').prop('disabled', saving).html(
            saving
                ? '<i class="fa fa-spinner fa-spin"></i> Saving...'
                : '<i class="fa fa-save"></i> Save'
        );
    }

    function showFormErrors(messages) {
        var $box = $('#cus_ref_form_errors');
        $box.empty();
        $.each(messages, function (i, message) {
            $box.append($('<div></div>').text(message));
        });
        $box.show();
    }

    function hideFormErrors() {
        $('#cus_ref_form_errors').hide().empty();
    }

    /*
     * Pull messages out of a failed response. Laravel returns 422 with an
     * `errors` map for validation and a flat `msg` for handled failures;
     * anything else gets a generic message rather than a raw stack trace.
     */
    function extractErrors(xhr) {
        var payload = xhr && xhr.responseJSON;

        if (payload && payload.errors) {
            var messages = [];
            $.each(payload.errors, function (field, list) {
                messages = messages.concat(list);
            });
            return messages;
        }

        if (payload && payload.msg) {
            return [payload.msg];
        }

        return ['Something went wrong. Please try again.'];
    }

    /* ----------------------------------------------------------------- */
    /* Row actions                                                        */
    /* ----------------------------------------------------------------- */

    function bindRowActions() {
        /*
         * Delegated from document rather than from the table.
         *
         * The Action menu is detached to <body> while open (see
         * bindActionMenuOverflowFix), so a click on a menu item does not bubble
         * through the table at all. Binding to the table would leave every row
         * action silently dead the moment the overflow fix kicked in.
         */
        $(document).on('click', '.cus-ref-view', function (e) {
            e.preventDefault();
            openGenericModal(url('/' + $(this).data('id')));
        });

        $(document).on('click', '.cus-ref-toggle-status', function (e) {
            e.preventDefault();
            toggleStatus($(this).data('id'));
        });

        $(document).on('click', '.cus-ref-edit', function (e) {
            e.preventDefault();
            openSavedEdit($(this).data('id'));
        });

        $(document).on('click', '.cus-ref-delete', function (e) {
            e.preventDefault();
            deleteReference($(this).data('id'));
        });

        bindActionMenuOverflowFix();
    }

    /*
     * Let the Action dropdown escape the table's scroll container.
     *
     * The table sits in a wrapper with overflow-x:auto so eight columns can be
     * reached on a narrow screen. That wrapper establishes a clipping context,
     * and a clipping context cuts off any child that overflows it - including
     * an open dropdown. No z-index fixes this: z-index orders things that are
     * painted, and a clipped element is never painted in the first place.
     *
     * CSS cannot solve it either. overflow-x:auto with overflow-y:visible is
     * not a valid combination; the browser silently promotes the visible axis
     * to auto, so the menu is clipped vertically no matter what is declared.
     *
     * So on open the menu is moved to <body>, positioned under its button, and
     * on close it is put back where it came from. Restoring it matters: the
     * rows are re-rendered on every DataTable redraw, and menus left orphaned
     * in <body> would accumulate on every page change.
     */
    function bindActionMenuOverflowFix() {
        $(document).on('show.bs.dropdown', '#customer_references_table .btn-group', function () {
            var $group = $(this);
            var $menu = $group.children('.dropdown-menu');
            var $toggle = $group.children('.dropdown-toggle');

            if (!$menu.length || !$toggle.length) {
                return;
            }

            var offset = $toggle.offset();

            // Remembered on the element itself so the matching close handler
            // can put it back without needing to identify the row again.
            $menu.data('cusRefOwner', $group);

            $menu.appendTo(document.body)
                .addClass('cus-ref-floating-menu')
                .css({
                    position: 'absolute',
                    top: (offset.top + $toggle.outerHeight()) + 'px',
                    left: offset.left + 'px',
                    display: 'block'
                });
        });

        $(document).on('hidden.bs.dropdown', '#customer_references_table .btn-group', function () {
            restoreFloatingMenus();
        });

        /*
         * A detached menu is positioned in absolute page coordinates, so it
         * would drift away from its button once the page moved. Closing it is
         * more honest than trying to keep it glued.
         */
        $(window).on('scroll resize', function () {
            if ($('body').children('.cus-ref-floating-menu').length) {
                $('#customer_references_table .btn-group').removeClass('open');
                restoreFloatingMenus();
            }
        });
    }

    function restoreFloatingMenus() {
        $('body').children('.cus-ref-floating-menu').each(function () {
            var $menu = $(this);
            var $owner = $menu.data('cusRefOwner');

            $menu.removeClass('cus-ref-floating-menu').removeAttr('style');

            if ($owner && $owner.length) {
                $menu.appendTo($owner);
            } else {
                // Owner row is gone - a redraw happened while the menu was
                // open. Drop it rather than leaving it loose in <body>.
                $menu.remove();
            }
        });
    }

    function toggleStatus(id) {
        $.ajax({
            url: url('/' + id + '/toggle-status'),
            type: 'POST',
            headers: ajaxHeaders(),
            success: function (response) {
                if (!response || !response.success) {
                    notify('error', (response && response.msg) || 'Unable to change the status.');
                    return;
                }
                notify('success', response.msg);
                reloadTable();
            },
            error: function (xhr) {
                notify('error', extractErrors(xhr)[0]);
            }
        });
    }

    function deleteReference(id) {
        if (!window.confirm('Delete this customer reference?')) {
            return;
        }

        $.ajax({
            url: url('/' + id),
            type: 'POST',
            headers: ajaxHeaders(),
            // Method spoofing: some deployments sit behind proxies that drop
            // the body of a real DELETE request.
            data: { _method: 'DELETE' },
            success: function (response) {
                if (!response || !response.success) {
                    notify('error', (response && response.msg) || 'Unable to delete.');
                    return;
                }
                notify('success', response.msg);
                reloadTable();
            },
            error: function (xhr) {
                notify('error', extractErrors(xhr)[0]);
            }
        });
    }

    /*
     * Editing a saved row reuses the Add popup with the staging table hidden,
     * so there is one set of fields and one set of validation rules rather than
     * a second near-identical form.
     */
    function openSavedEdit(id) {
        $.ajax({
            url: url('/' + id + '/edit'),
            type: 'GET',
            headers: ajaxHeaders(),
            success: function (response) {
                if (!response || !response.success) {
                    notify('error', 'Unable to load this customer reference.');
                    return;
                }

                var reference = response.reference;

                editingSavedId = id;
                stagedRows = [];
                editingIndex = null;

                $('#cus_ref_add_modal_label').text('Edit Customer Reference');
                $('#cus_ref_staged_table').closest('.table-responsive').hide();
                $('#cus_ref_add_row').hide();
                $('#cus_ref_cancel_edit').hide();
                $('#cus_ref_staged_count').text('');
                hideFormErrors();

                $('#cus_ref_add_modal').modal('show');

                setSelectValue($('#cus_ref_customer_id'), reference.customer_id);
                $('#cus_ref_datetime').val(reference.reference_datetime || nowForInput());
                setSelectValue($('#cus_ref_is_vehicle'), reference.is_vehicle ? '1' : '0');
                $('#cus_ref_reference_no').val(reference.reference_no);
                applyVehicleVisibility();
                setSelectValue($('#cus_ref_fuel_type'), reference.fuel_type);
            },
            error: function (xhr) {
                notify('error', extractErrors(xhr)[0]);
            }
        });
    }

    function saveSingleEdit() {
        var row = collectForm();
        var errors = validateRow(row);

        if (errors.length) {
            showFormErrors(errors);
            return;
        }

        toggleSaving(true);

        $.ajax({
            url: url('/' + editingSavedId),
            type: 'POST',
            headers: ajaxHeaders(),
            data: {
                _method: 'PUT',
                customer_id: row.customer_id,
                reference_datetime: row.reference_datetime,
                is_vehicle: row.is_vehicle,
                reference_no: row.reference_no,
                fuel_type: row.fuel_type
            },
            success: function (response) {
                toggleSaving(false);
                if (!response || !response.success) {
                    showFormErrors([(response && response.msg) || 'Unable to update.']);
                    return;
                }
                editingSavedId = null;
                $('#cus_ref_add_modal').modal('hide');
                notify('success', response.msg);
                reloadTable();
            },
            error: function (xhr) {
                toggleSaving(false);
                showFormErrors(extractErrors(xhr));
            }
        });
    }

    /* ----------------------------------------------------------------- */
    /* QR popup                                                           */
    /* ----------------------------------------------------------------- */

    function bindQrActions() {
        // Document-delegated: the Action menu is detached to <body> while
        // open, so this click never passes through the table.
        $(document).on('click', '.cus-ref-qr', function (e) {
            e.preventDefault();
            openGenericModal(url('/' + $(this).data('id') + '/qr'));
        });

        var $modal = $('#cus_ref_generic_modal');

        $modal.on('click', '#cus_ref_qr_whatsapp_button', function () {
            shareOnWhatsApp($(this).data('id'));
        });

        $modal.on('click', '#cus_ref_qr_email_button', function () {
            sendQrEmail($(this).data('id'), $(this));
        });
    }

    function shareOnWhatsApp(id) {
        $.ajax({
            url: url('/' + id + '/qr/whatsapp'),
            type: 'GET',
            headers: ajaxHeaders(),
            data: { number: $('#cus_ref_qr_whatsapp').val() || '' },
            success: function (response) {
                if (!response || !response.success) {
                    showQrMessage('error', 'Unable to build the WhatsApp link.');
                    return;
                }

                if (!response.has_number) {
                    showQrMessage('error', 'No WhatsApp number was found. Pick a recipient in WhatsApp.');
                }

                // Opened rather than navigated to, so the list page and the
                // open popup survive the share.
                window.open(response.url, '_blank', 'noopener');
            },
            error: function (xhr) {
                showQrMessage('error', extractErrors(xhr)[0]);
            }
        });
    }

    function sendQrEmail(id, $button) {
        var original = $button.html();
        $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');

        $.ajax({
            url: url('/' + id + '/qr/email'),
            type: 'POST',
            headers: ajaxHeaders(),
            data: { email: $('#cus_ref_qr_email').val() || '' },
            success: function (response) {
                $button.prop('disabled', false).html(original);
                if (!response || !response.success) {
                    showQrMessage('error', (response && response.msg) || 'Unable to send the email.');
                    return;
                }
                showQrMessage('success', response.msg);
            },
            error: function (xhr) {
                $button.prop('disabled', false).html(original);
                showQrMessage('error', extractErrors(xhr)[0]);
            }
        });
    }

    function showQrMessage(type, message) {
        var $error = $('#cus_ref_qr_error');
        var $success = $('#cus_ref_qr_success');

        $error.hide();
        $success.hide();

        if (type === 'success') {
            $success.text(message).show();
        } else {
            $error.text(message).show();
        }
    }

    /* ----------------------------------------------------------------- */
    /* Shared modal                                                       */
    /* ----------------------------------------------------------------- */

    function openGenericModal(target) {
        $.ajax({
            url: target,
            type: 'GET',
            headers: ajaxHeaders(),
            success: function (html) {
                $('#cus_ref_generic_modal_content').html(html);
                $('#cus_ref_generic_modal').modal('show');
                renderBrowserQrCodes();
            },
            error: function (xhr) {
                notify('error', extractErrors(xhr)[0]);
            }
        });
    }

    /*
     * Draw QR codes in the browser when the application has no server-side QR
     * library. The library is loaded on demand from a CDN; if it cannot load,
     * the payload text already rendered in the holder stays visible, so the
     * popup is still usable.
     */
    function renderBrowserQrCodes() {
        if (config.qrServerSide) {
            return;
        }

        var holders = $('.cus-ref-qr-holder[data-qr-payload]').filter(function () {
            return $(this).find('svg, canvas, img').length === 0;
        });

        if (!holders.length) {
            return;
        }

        loadQrLibrary(function (available) {
            if (!available) {
                holders.html('<div class="cus-ref-qr-payload"></div>');
                holders.each(function () {
                    $(this).find('.cus-ref-qr-payload').text($(this).data('qr-payload') || '');
                });
                return;
            }

            holders.each(function () {
                var holder = this;
                $(holder).empty();
                new window.QRCode(holder, {
                    text: $(holder).data('qr-payload') || '',
                    width: 240,
                    height: 240,
                    correctLevel: window.QRCode.CorrectLevel.M
                });
            });
        });
    }

    var qrLibraryState = null;

    function loadQrLibrary(callback) {
        if (typeof window.QRCode !== 'undefined') {
            callback(true);
            return;
        }

        if (qrLibraryState === 'failed') {
            callback(false);
            return;
        }

        var script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js';
        script.onload = function () { callback(typeof window.QRCode !== 'undefined'); };
        script.onerror = function () {
            qrLibraryState = 'failed';
            callback(false);
        };
        document.head.appendChild(script);
    }

    /* ----------------------------------------------------------------- */
    /* Notifications                                                      */
    /* ----------------------------------------------------------------- */

    /*
     * Uses the theme's toastr when it is present and falls back to alert(),
     * so a missing toast library degrades to a visible message rather than
     * silence.
     */
    function notify(type, message) {
        if (typeof toastr !== 'undefined' && toastr[type === 'error' ? 'error' : 'success']) {
            toastr[type === 'error' ? 'error' : 'success'](message);
            return;
        }
        window.alert(message);
    }

})(window.jQuery);

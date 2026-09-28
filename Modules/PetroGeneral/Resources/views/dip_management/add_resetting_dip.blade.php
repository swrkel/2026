<div class="modal-dialog pg-dip-reset-dialog" role="document">

    <div class="modal-content pg-dip-reset-content">



        {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\DipManagementController@saveResettingDip'),

        'method' =>

        'post',

        'id' =>

        'dip_resetting_form' ]) !!}



        <div class="modal-header">

            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span

                    aria-hidden="true">&times;</span></button>

            <h4 class="modal-title">@lang( 'petrogeneral::lang.add_resetting_dip' ) </h4>

        </div>



        <div class="modal-body">
            <div class="col-md-12">

                <div class="row">

                    <div class="col-md-4">

                        <style>
    /*
     * Auto-loaded fields are shaded so it is obvious at a glance which values
     * the system supplies and which the user must type. Requested for Product
     * Name, System Qty, Current Dip Difference and Type; applied to every
     * derived field on the form for consistency.
     *
     * readonly is used rather than disabled throughout: a disabled input is NOT
     * submitted, and these values have to reach the server.
     */
    .pg-auto-field,
    .pg-auto-field:focus,
    select.pg-auto-field + .select2-container .select2-selection {
        background-color: #eceff1 !important;
        color: #37474f !important;
        cursor: not-allowed;
    }

    .pg-auto-field:focus {
        box-shadow: none;
        outline: none;
    }

    /*
     * Dip Reset modal/layout.
     *
     * Keep the whole form inside the browser viewport. The header and footer
     * stay visible while only the modal body scrolls, so the Save button can
     * never fall below the visible page.
     */
    .pg-dip-reset-dialog {
        width: 96vw !important;
        max-width: 1800px !important;
        height: calc(100vh - 20px);
        margin: 10px auto !important;
    }

    .pg-dip-reset-content {
        height: 100%;
        max-height: calc(100vh - 20px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    #dip_resetting_form {
        min-height: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    #dip_resetting_form > .modal-header,
    #dip_resetting_form > .modal-footer {
        flex: 0 0 auto;
    }

    #dip_resetting_form > .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        padding-bottom: 12px;
    }

    #dip_resetting_form > .modal-footer {
        position: relative;
        z-index: 20;
        margin-top: 0;
        background: #fff;
        border-top: 1px solid #e5e7eb;
        box-shadow: 0 -3px 10px rgba(0, 0, 0, .06);
    }

    /*
     * Dip Reset table layout.
     * Column widths are 30% smaller than the previous layout. Horizontal
     * scrolling remains inside the table wrapper when a narrow screen needs it.
     */
    .pg-dip-reset-table-wrap {
        max-height: 46vh;
        overflow: auto;
        position: relative;
        border: 1px solid #d7dee5;
        background: #fff;
    }

    #pg_dip_reset_tank_table {
        width: 100%;
        min-width: 1155px; /* 1650px reduced by 30% */
        table-layout: auto;
        border-collapse: separate;
        border-spacing: 0;
        margin-bottom: 0;
    }

    #pg_dip_reset_tank_table thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #f7f9fb;
        color: #263238;
        vertical-align: middle;
        white-space: nowrap;
        box-shadow: inset 0 -1px 0 #cfd8dc;
    }

    #pg_dip_reset_tank_table td {
        vertical-align: middle;
    }

    #pg_dip_reset_tank_table .pg-col-reset { min-width: 50px; width: 50px; text-align:center; }
    #pg_dip_reset_tank_table .pg-col-tank { min-width: 105px; }
    #pg_dip_reset_tank_table .pg-col-product { width: auto; min-width: 154px; white-space: nowrap; }
    #pg_dip_reset_tank_table .pg-col-system { min-width: 105px; }
    #pg_dip_reset_tank_table .pg-col-dip-qty { min-width: 182px; }
    #pg_dip_reset_tank_table .pg-col-difference { min-width: 116px; }
    #pg_dip_reset_tank_table .pg-col-type { min-width: 105px; }
    #pg_dip_reset_tank_table .pg-col-note { min-width: 252px; }

    .pg-product-name-display {
        display: inline-block;
        white-space: nowrap;
        font-weight: 600;
        color: #37474f;
        padding: 7px 3px;
    }

    .pg-row-note-button {
        width: 100%;
        white-space: nowrap;
    }

    .pg-row-note-state {
        display: block;
        min-height: 18px;
        margin-top: 4px;
        font-size: 11px;
        color: #2e7d32;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /*
     * Lightweight popup used inside the already-open Add Resetting modal.
     * Avoids a nested Bootstrap modal/backdrop conflict.
     */
    #pg_dip_note_popup {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 2100;
        background: rgba(0, 0, 0, .38);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    #pg_dip_note_popup.pg-open {
        display: flex;
    }

    #pg_dip_note_popup .pg-note-popup-card {
        width: min(620px, 95vw);
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 18px 55px rgba(0,0,0,.25);
        overflow: hidden;
    }

    #pg_dip_note_popup .pg-note-popup-header,
    #pg_dip_note_popup .pg-note-popup-footer {
        padding: 14px 18px;
        background: #f7f9fb;
    }

    #pg_dip_note_popup .pg-note-popup-body {
        padding: 18px;
    }

    #pg_dip_note_popup textarea {
        min-height: 130px;
        resize: vertical;
    }

    @media (max-width: 767px) {
        .pg-dip-reset-dialog {
            width: calc(100vw - 10px) !important;
            height: calc(100vh - 10px);
            margin: 5px auto !important;
        }

        .pg-dip-reset-content {
            max-height: calc(100vh - 10px);
        }

        .pg-dip-reset-table-wrap {
            max-height: 42vh;
        }
    }
</style>

<div class="form-group">

                            {!! Form::label('meter_reset_form_no', __( 'petrogeneral::lang.dip_resetting_no' ) . ':*') !!}

                            {!! Form::text('meter_reset_form_no', $meter_reset_form_no, ['class' => 'form-control meter_reset_form_no',

                            'required', 'readonly',

                            'placeholder' => __(

                            'petrogeneral::lang.meter_reset_form_no' ) ]); !!}

                        </div>

                    </div>



                    <div class="col-md-4">

                        <div class="form-group">

                            {!! Form::label('date_and_time', __( 'petrogeneral::lang.date' ) . ':*') !!}

                            {{-- Same fault: targeted as #date_and_time but had no id. --}}
                            {!! Form::text('date_and_time', null, ['class' => 'form-control date_and_time', 'id' => 'date_and_time', 'required', 'readonly',

                            'placeholder' => __(

                            'petrogeneral::lang.date_and_time' ) ]); !!}

                        </div>

                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('transaction_date', __( 'petrogeneral::lang.transaction_date' ) . ':*') !!}
                            {{--
                                URGENT: saving failed with
                                    Column 'transaction_date' cannot be null

                                The field had NO id, but the script targets
                                    $('#transaction_date').datepicker("setDate", new Date())
                                so the picker never attached and the box stayed empty.
                                uf_date() then returned null for the insert.

                                It also carried the class "date_and_time", shared with
                                the Dip Date & Time field - which is why that field
                                received the date instead, and why the SQL showed
                                date_and_time populated while transaction_date was null.

                                Given its own id and its own class, so each field is
                                targeted separately.
                            --}}
                            {!! Form::text('transaction_date', null, ['class' => 'form-control pg-reset-transaction-date', 'required', 'readonly',
                            'id' => 'transaction_date',
                            'placeholder' => __(
                            'petrogeneral::lang.transaction_date' ) ]); !!}
                        </div>
                    </div>



                    <div class="col-md-4">

                        <div class="form-group">

                            {!! Form::label('location_id', __( 'petrogeneral::lang.location' ) . ':*') !!}

                            {!! Form::select('location_id', $business_locations, null , ['class' => 'form-control

                            select2

                            fuel_tank_location', 'required', 'id' => 'location_id',

                            'placeholder' => __(

                            'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}

                        </div>

                    </div>



                    {{--
                        Multi-tank Dip Reset.
                        ------------------------------------------------------
                        Every tank at the chosen location is listed as a row,
                        with System Qty already filled in. The user types Dip Qty
                        against the tanks actually dipped and leaves the rest
                        blank.

                        This replaces choosing one tank at a time. A dip round
                        covers several tanks in one walk, so entering them
                        together matches the real task - and there is no separate
                        "Add" step that can be filled in and then forgotten,
                        which would silently drop a tank from the save.

                        One location per form: the reset document and its
                        adjustments belong to a single location.

                        Per row:
                            difference = System Qty - Dip Qty
                            negative -> Increase
                            positive -> Decrease
                            zero     -> saved, but NO stock adjustment created
                            blank    -> ignored entirely
                    --}}
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Tank Readings:</label>
                            <div class="table-responsive pg-dip-reset-table-wrap">
                                <table class="table table-bordered" id="pg_dip_reset_tank_table">
                                    <thead>
                                        <tr>
                                            {{-- Deliberately no "select all": ticking every
                                                 tank at once is the accident this column exists
                                                 to prevent. --}}
                                            <th class="pg-col-reset text-center">Reset</th>
                                            <th class="pg-col-tank">Tank</th>
                                            <th class="pg-col-product">Product Name</th>
                                            <th class="pg-col-system">System Qty</th>
                                            <th class="pg-col-dip-qty">Dip Qty</th>
                                            <th class="pg-col-difference">Difference</th>
                                            <th class="pg-col-type">Type</th>
                                            <th class="pg-col-note">Note</th>
                                        </tr>
                                    </thead>
                                    <tbody id="pg_dip_reset_tank_rows">
                                        <tr id="pg_dip_reset_empty_row">
                                            <td colspan="8" class="text-center text-muted">
                                                Select a location to load its tanks.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted">
                                Enter Dip Qty only for the tanks you have dipped. Blank rows are ignored.
                                A tank with no difference is recorded, but creates no stock adjustment.
                            </small>
                        </div>
                    </div>



                    {{--
                        Global Reason removed as requested.

                        Each tank already has its own Note field in the table. That
                        per-tank note is what the save logic sends as `reason` for
                        the corresponding reset/stock adjustment, so keeping a second
                        form-level Reason box duplicated the same information and its
                        `required` attribute could block an otherwise valid save.
                    --}}

                </div>

            </div>

            <div class="clearfix"></div>

        </div><!-- /.modal-body -->

        <div class="modal-footer">

            <button type="submit" class="btn btn-primary add_dip_resetting_btn">@lang( 'messages.save' )</button>

            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>

        </div>

        {!! Form::close() !!}

        <div id="pg_dip_note_popup" aria-hidden="true">
                <div class="pg-note-popup-card" role="dialog" aria-modal="true" aria-labelledby="pg_dip_note_popup_title">
                    <div class="pg-note-popup-header">
                        <strong id="pg_dip_note_popup_title">Enter Note</strong>
                    </div>
                    <div class="pg-note-popup-body">
                        <textarea id="pg_dip_note_popup_text" class="form-control" placeholder="Enter note"></textarea>
                    </div>
                    <div class="pg-note-popup-footer text-right">
                        <button type="button" class="btn btn-default" id="pg_dip_note_cancel">Cancel</button>
                        <button type="button" class="btn btn-primary" id="pg_dip_note_add">Add</button>
                    </div>
                </div>
            </div>

    </div><!-- /.modal-content -->

</div><!-- /.modal-dialog -->

<script>

        $('#date_and_time').datepicker("setDate", new Date());

        // $('#date_and_time').datetimepicker({

        //         format: moment_date_format + ' ' + moment_time_format,
        //         ignoreReadonly: true,

        //     });

        /*
         | Transaction Date: defaults to today and stays populated.
         |
         | The field is readonly, so nothing can be typed into it - if the picker
         | fails to write, it stays empty and the save fails on a NOT NULL column.
         | The value is therefore set directly as well, in the format the server
         | parses, so it is never blank.
         */
        $('#transaction_date').datepicker({
            format: (typeof moment_date_format !== 'undefined' && moment_date_format)
                ? moment_date_format.toLowerCase().replace('yyyy', 'yy')
                : 'mm/dd/yyyy',
            autoclose: true,
            todayHighlight: true,
        });

        if (!$('#transaction_date').val()) {
            var pgToday = (typeof moment === 'function')
                ? moment().format(
                    (typeof moment_date_format !== 'undefined' && moment_date_format)
                        ? moment_date_format
                        : 'MM/DD/YYYY'
                  )
                : new Date().toLocaleDateString('en-US');

            $('#transaction_date').val(pgToday);
        }

        // Keep it filled whichever way the user picks a date.
        $(document).off('changeDate.pgResetTxnDate').on('changeDate.pgResetTxnDate', '#transaction_date', function (e) {
            if (e && e.date && typeof moment === 'function') {
                $(this).val(moment(e.date).format(
                    (typeof moment_date_format !== 'undefined' && moment_date_format)
                        ? moment_date_format
                        : 'MM/DD/YYYY'
                ));
            }
        });

        $('#add_reset_tank_id').select2();

        $('#location_id').select2();

        $('#inventory_adjustment_account').select2();

        $('#location_id option:eq(1)').attr('selected', true).trigger('change');

        /*
         |----------------------------------------------------------------------
         | Dip Resetting: fill the difference, the type, and the linked account.
         |----------------------------------------------------------------------
         |
         | Choosing a tank filled nothing - Current Dip Difference stayed empty and
         | Type had to be picked by hand, even though the value determines it.
         |
         | Everything needed already existed and was simply not connected:
         |
         |   - /petro-general/get-tank-balance-by-id returns current_diff_for_reseting
         |   - the Type dropdown already has Increase and Decrease
         |   - the Type change handler already loads the linked account from
         |     /stock-adjustments/inventory-adjustment-account
         |
         | So selecting a tank now fetches the difference, writes it in, sets the
         | Type from its sign, and triggers the existing account lookup.
         |
         | SIGN CONVENTION, from the request:
         |   difference = System Qty - Dip Qty
         |   negative -> INCREASE
         |   positive -> DECREASE
         |
         | Both fields stay disabled, as before - they are derived, not entered.
         | A disabled field is not submitted, so the value is mirrored into a
         | hidden input of the same name and that is what posts.
         */
        /*
         |----------------------------------------------------------------------
         | Multi-tank Dip Reset
         |----------------------------------------------------------------------
         |
         | Choosing a location loads every tank there as a row, with System Qty
         | filled in. Each row calculates independently:
         |
         |     difference = System Qty - Dip Qty
         |     negative -> Increase
         |     positive -> Decrease
         |     zero     -> no adjustment; Type and Account stay blank and the
         |                 Note is set to "No difference"
         |
         | Type and Account are blank on a zero row on purpose: no stock movement
         | is created, so showing a direction would describe something that never
         | happened.
         |
         | Rows post as rows[i][...]; the controller loops them in one database
         | transaction.
         */
        var pgAccountCache = {};

        function pgRowIndex($row) {
            return $row.data('row-index');
        }

        function pgLoadAccountFor($row, type) {
            var $account = $row.find('.pg-row-account');

            if (!type) {
                $account.val('');
                return;
            }

            /*
             * IS2159: the cache key includes the PRODUCT.
             *
             * It was keyed on type alone, which was fine while every increase
             * resolved to the same account. Now that the account comes from a
             * mapping keyed by the product's category, two products on the same
             * form can legitimately map to different accounts - and a type-only
             * cache would give the second one the first one's account.
             */
            var productId = $row.find('input[name$="[product_id]"]').val() || 0;
            var cacheKey = type + ':' + productId;

            if (pgAccountCache[cacheKey] !== undefined) {
                $account.val(pgAccountCache[cacheKey]);
                return;
            }

            $.ajax({
                method: 'get',
                url: '/petro-general/dip-reset/inventory-adjustment-account',
                /*
                 * IS2159: the product id travels with the request.
                 *
                 * The account mapping on the Stock Adjustment New settings page is
                 * keyed by the product's category and sub category, so the server
                 * cannot pick the right rule without knowing which product this
                 * row is for. Only the type was sent before.
                 */
                data: {
                    type: type,
                    product_id: productId
                },
                success: function (result) {
                    /*
                     * The endpoint returns the account mapped to this type for
                     * the product sub category. The first entry is taken because
                     * the mapping yields one account per direction.
                     */
                    var accountId = '';

                    if (result && typeof result === 'object') {
                        var keys = Object.keys(result);
                        if (keys.length) {
                            accountId = keys[0];
                        }
                    }

                    pgAccountCache[cacheKey] = accountId;
                    $account.val(accountId);
                }
            });
        }

        function pgRecalculateRow($row) {
            var systemQty = parseFloat($row.find('.pg-row-system-qty').val());
            var dipQty = $.trim($row.find('.pg-row-dip-qty').val());

            // Blank Dip Qty means the tank was not dipped - leave the row alone.
            if (dipQty === '' || !isFinite(systemQty)) {
                $row.find('.pg-row-difference').val('');
                $row.find('.pg-row-type-text').val('');
                $row.find('.pg-row-type').val('');
                $row.find('.pg-row-account').val('');
                return;
            }

            var diff = systemQty - parseFloat(dipQty);

            if (!isFinite(diff)) {
                return;
            }

            $row.find('.pg-row-difference').val(diff.toFixed(3));

            var type = '';
            // Difference is System Qty - Dip Qty.
            // If the physical dip is higher than the system quantity, stock must
            // INCREASE (negative difference). If it is lower, stock must DECREASE
            // (positive difference).
            if (diff < -0.0005) {
                type = 'increase';
            } else if (diff > 0.0005) {
                type = 'decrease';
            }

            $row.find('.pg-row-type').val(type);
            $row.find('.pg-row-type-text').val(
                type === 'decrease' ? 'Decrease' : (type === 'increase' ? 'Increase' : '')
            );

            var $note = $row.find('.pg-row-note');

            if (type === '') {
                // Recorded as dipped and unchanged - no adjustment, no account.
                $row.find('.pg-row-account').val('');
                $note.val('No difference');
                $row.find('.pg-row-note-state').text('No difference');
            } else {
                if ($note.val() === 'No difference') {
                    $note.val('');
                    $row.find('.pg-row-note-state').text('');
                }
                pgLoadAccountFor($row, type);
            }
        }

        function pgBuildTankRows(tanks) {
            var $body = $('#pg_dip_reset_tank_rows').empty();

            if (!tanks || !tanks.length) {
                $body.append(
                    '<tr><td colspan="8" class="text-center text-muted">No tanks found for this location.</td></tr>'
                );
                return;
            }

            $.each(tanks, function (i, tank) {
                /*
                 * S-DIP: each row is LOCKED until its Reset box is ticked.
                 *
                 * Previously every Dip Qty box was open, so a stray keystroke or
                 * a paste into the wrong row could write a stock adjustment
                 * against a tank nobody meant to touch. Ticking the box is now a
                 * deliberate act, and the row states its intent plainly rather
                 * than relying on "blank means ignore".
                 *
                 * The rows stay VISIBLE and in place - only their inputs are
                 * disabled. Hiding them would reintroduce the "filled it in but
                 * it never saved" trap, because the row would be out of sight.
                 */
                var productName = tank.product_name || '';
                var escapedProductName = $('<div>').text(productName).html();

                var r = '<tr data-row-index="' + i + '">'
                    + '<td class="pg-col-reset text-center">'
                    + '<input type="checkbox" class="pg-row-select" title="Tick to reset this tank">'
                    + '</td>'
                    + '<td class="pg-col-tank">' + $('<div>').text(tank.tank_number || '').html()
                    + '<input type="hidden" name="rows[' + i + '][tank_id]" value="' + tank.tank_id + '">'
                    + '<input type="hidden" name="rows[' + i + '][product_id]" value="' + (tank.product_id || '') + '"></td>'
                    + '<td class="pg-col-product"><span class="pg-product-name-display">' + escapedProductName + '</span>'
                    + '<input type="hidden" name="rows[' + i + '][product_name]" value="' + escapedProductName + '"></td>'
                    + '<td class="pg-col-system"><input type="text" class="form-control pg-auto-field pg-row-system-qty" readonly name="rows[' + i + '][system_qty]" value="' + tank.system_qty + '"></td>'
                    + '<td class="pg-col-dip-qty"><input type="text" class="form-control input_number pg-row-dip-qty" disabled name="rows[' + i + '][dip_qty]" placeholder="Dip Qty"></td>'
                    + '<td class="pg-col-difference"><input type="text" class="form-control pg-auto-field pg-row-difference" readonly name="rows[' + i + '][difference]"></td>'
                    + '<td class="pg-col-type"><input type="text" class="form-control pg-auto-field pg-row-type-text" readonly>'
                    + '<input type="hidden" class="pg-row-type" name="rows[' + i + '][type]">'
                    + '<input type="hidden" class="pg-row-account" name="rows[' + i + '][inventory_adjustment_account]"></td>'
                    + '<td class="pg-col-note">'
                    + '<input type="hidden" class="pg-row-note" name="rows[' + i + '][note]" value="">'
                    + '<button type="button" class="btn btn-info pg-row-note-button" disabled>Click to Enter</button>'
                    + '<span class="pg-row-note-state"></span>'
                    + '</td>'
                    + '</tr>';

                $body.append(r);
            });
        }

        // Changing the location reloads the rows: the previous tanks belong to a
        // different place, so anything typed against them no longer applies.
        /*
         * IS2140: load the tanks for whatever location is ALREADY selected.
         *
         * The tank rows were only built by the location `change` handler. But
         * this modal opens with a location already chosen - often the only one -
         * so `change` never fires and the table sat on "Select a location to load
         * its tanks" with no way to move it: re-picking the same location is not
         * a change either.
         *
         * The loader is now called once when the form is ready, using whatever
         * the field already holds.
         */
        function pgLoadTanksForLocation(locationId) {
            if (!locationId) {
                $('#pg_dip_reset_tank_rows').html(
                    '<tr><td colspan="8" class="text-center text-muted">Select a location to load its tanks.</td></tr>'
                );
                return;
            }

            $('#pg_dip_reset_tank_rows').html(
                '<tr><td colspan="8" class="text-center text-muted">Loading tanks…</td></tr>'
            );

            $.ajax({
                method: 'get',
                url: '/petro-general/dip-reset/tanks-by-location/' + locationId,
                success: function (result) {
                    pgBuildTankRows(result.tanks || []);
                },
                error: function () {
                    $('#pg_dip_reset_tank_rows').html(
                        '<tr><td colspan="8" class="text-center text-danger">Could not load tanks for this location.</td></tr>'
                    );
                }
            });
        }

        /*
         * Run once on open. The modal content is injected, so this script runs
         * as part of that insertion - the field is present by now.
         */
        $(function () {
            var $initialLocation = $('#dip_resetting_form [name="location_id"]');

            if ($initialLocation.length) {
                pgLoadTanksForLocation($initialLocation.val());
            }
        });

        $(document).off('change.pgResetLocation').on('change.pgResetLocation', '#dip_resetting_form [name="location_id"]', function () {
            var locationId = $(this).val();

            pgLoadTanksForLocation(locationId);
        });

        /*
         * S-DIP: the Reset checkbox unlocks and locks its row.
         *
         * Unticking CLEARS the row's entries rather than leaving them in a locked
         * field. A value sitting in a disabled box is ambiguous - the user cannot
         * tell whether it will save. Clearing makes the state honest.
         *
         * disabled inputs are not submitted at all, so an unticked row posts no
         * dip_qty and the controller's existing "blank means ignore" rule skips
         * it, exactly as before. No server change is needed.
         */
        $(document)
            .off('change.pgRowSelect')
            .on('change.pgRowSelect', '.pg-row-select', function () {
                var $row = $(this).closest('tr');
                var on = $(this).is(':checked');

                $row.find('.pg-row-dip-qty').prop('disabled', !on);
                $row.find('.pg-row-note-button').prop('disabled', !on);

                if (on) {
                    $row.find('.pg-row-dip-qty').focus();
                    return;
                }

                $row.find('.pg-row-dip-qty, .pg-row-note').val('');
                $row.find('.pg-row-note-state').text('');
                $row.find('.pg-row-difference, .pg-row-type-text, .pg-row-type, .pg-row-account').val('');
            });

        $(document)
            .off('input.pgDipRow')
            .on('input.pgDipRow', '.pg-row-dip-qty', function () {
                pgRecalculateRow($(this).closest('tr'));
            });

        var pgDipNoteRow = null;

        function pgOpenNotePopup($row) {
            pgDipNoteRow = $row;
            var current = $.trim($row.find('.pg-row-note').val());

            if (current === 'No difference') {
                current = '';
            }

            $('#pg_dip_note_popup_text').val(current);
            $('#pg_dip_note_popup').addClass('pg-open').attr('aria-hidden', 'false');

            setTimeout(function () {
                $('#pg_dip_note_popup_text').focus();
            }, 50);
        }

        function pgCloseNotePopup() {
            $('#pg_dip_note_popup').removeClass('pg-open').attr('aria-hidden', 'true');
            $('#pg_dip_note_popup_text').val('');
            pgDipNoteRow = null;
        }

        $(document)
            .off('click.pgDipNoteOpen')
            .on('click.pgDipNoteOpen', '.pg-row-note-button', function () {
                pgOpenNotePopup($(this).closest('tr'));
            });

        $(document)
            .off('click.pgDipNoteCancel')
            .on('click.pgDipNoteCancel', '#pg_dip_note_cancel', function () {
                pgCloseNotePopup();
            });

        $(document)
            .off('click.pgDipNoteAdd')
            .on('click.pgDipNoteAdd', '#pg_dip_note_add', function () {
                if (!pgDipNoteRow) {
                    pgCloseNotePopup();
                    return;
                }

                var note = $.trim($('#pg_dip_note_popup_text').val());

                pgDipNoteRow.find('.pg-row-note').val(note);
                pgDipNoteRow.find('.pg-row-note-state').text(note ? 'Note entered' : '');
                pgCloseNotePopup();
            });

        $(document)
            .off('keydown.pgDipNote')
            .on('keydown.pgDipNote', function (e) {
                if (e.key === 'Escape' && $('#pg_dip_note_popup').hasClass('pg-open')) {
                    pgCloseNotePopup();
                }
            });

        /*
         * Refuse a save that would achieve nothing, and require a note on any row
         * that creates an adjustment.
         *
         * "No difference to reset." fires only when EVERY row is blank or zero -
         * refusing one unchanged tank out of six would block a legitimate dip
         * round.
         *
         * The server repeats both checks: this is a convenience and can be
         * bypassed, and these writes move stock.
         */
        $(document).off('submit.pgDipReset').on('submit.pgDipReset', '#dip_resetting_form', function (e) {
            var entered = 0;
            var withDifference = 0;
            var missingNote = null;

            $('#pg_dip_reset_tank_rows tr[data-row-index]').each(function () {
                var $row = $(this);

                if ($.trim($row.find('.pg-row-dip-qty').val()) === '') {
                    return;
                }

                entered++;

                if ($.trim($row.find('.pg-row-type').val()) !== '') {
                    withDifference++;

                    if ($.trim($row.find('.pg-row-note').val()) === '' && !missingNote) {
                        missingNote = $row.find('.pg-row-note');
                    }
                }
            });

            /*
             * S-DIP: nothing ticked is its own message.
             *
             * "No difference to reset." would be misleading when the real problem
             * is that no tank was selected - the user has not entered a reading
             * at all, so there is no difference to speak of. Two distinct causes
             * deserve two distinct messages.
             */
            if ($('#pg_dip_reset_tank_rows .pg-row-select:checked').length === 0) {
                e.preventDefault();
                e.stopImmediatePropagation();

                if (window.toastr) {
                    toastr.error('Select at least one tank to reset.');
                } else {
                    alert('Select at least one tank to reset.');
                }

                return false;
            }

            if (entered === 0 || withDifference === 0) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (window.toastr) { toastr.error('No difference to reset.'); } else { alert('No difference to reset.'); }
                return false;
            }

            if (missingNote) {
                e.preventDefault();
                e.stopImmediatePropagation();
                if (window.toastr) { toastr.error('Note is required.'); } else { alert('Note is required.'); }
                pgOpenNotePopup(missingNote.closest('tr'));
                return false;
            }
        });

</script>
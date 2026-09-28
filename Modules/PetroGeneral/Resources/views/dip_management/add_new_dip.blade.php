{{--
    Form width halved: 80% -> 40%, and capped so it never exceeds the screen.
    The wide dialog pushed the tabs off to the right and users kept missing the
    horizontal scrollbar.
--}}
<style>
/*
 |-----------------------------------------------------------------------------
 | Add New Dip - field and column widths.
 |-----------------------------------------------------------------------------
 |
 | Scoped to .pg-add-dip-dialog so no other modal is affected.
 |
 | The percentages below are relative to the row, replacing the Bootstrap
 | col-md-* widths. Bootstrap's grid is 12 columns, so a col-md-3 is 25%; the
 | requested reductions are applied from those starting widths.
 */

/*
 | THE DIALOG WIDTH ITSELF.
 |
 | The inline style="width: 40%" was not winning - the theme sets a width on
 | .modal-dialog with higher specificity, so the dialog stayed almost full
 | screen. !important on a class of our own overrides it.
 |
 | 80% -> 60%. 32% proved too narrow for six fields on a row - labels
 | wrapped and the layout looked cramped. min-width keeps it usable on a
 | large screen, and max-width keeps it inside the viewport on a small one.
 */
.modal-dialog.pg-add-dip-dialog {
    width: 60% !important;
    min-width: 720px;
    max-width: calc(100vw - 24px) !important;
    margin: 20px auto;
}

@media (max-width: 640px) {
    .modal-dialog.pg-add-dip-dialog {
        width: calc(100vw - 16px) !important;
        min-width: 0;
        margin: 8px auto;
    }
}

/*
 |-----------------------------------------------------------------------------
 | Layout.
 |-----------------------------------------------------------------------------
 |
 | Bootstrap's own 12-column grid, NOT a CSS grid.
 |
 | The auto-fit CSS grid that was here placed items into whatever cell came next
 | and left blank cells where a hidden element or an uneven span fell - the empty
 | boxes in the middle of the form. Bootstrap columns that add up to 12 per row
 | are predictable: every field sits exactly where its class says.
 |
 |   Row 1 (12): Reference No 2 | Dip Date & Time 3 | Daily Report Date 3 | Location 4
 |   Row 2 (12): Tanks 2 | Dip Reading 2 | Current Qty 2 | System Current Qty 2
 |               | Difference 2 | Note 2
 |
 | The + button sits inside the Note column, beneath the field.
 */

/* Uniform field height and spacing. */
.pg-add-dip-dialog .form-group { margin-bottom: 12px; }

.pg-add-dip-dialog .form-control,
.pg-add-dip-dialog .select2-container .select2-selection--single {
    height: 36px;
    line-height: 22px;
    font-size: 13px;
}

.pg-add-dip-dialog .select2-container { width: 100% !important; }

/*
 | Labels on ONE line. A wrapped label pushes its field down and breaks the
 | alignment of the whole row - that is what made the form look untidy.
 */
/*
 | Labels WRAP, up to three lines, instead of truncating.
 |
 | They were nowrap + ellipsis, which turned "System Current Qty" into
 | "System Curren..." - the operator could not read which column they were in.
 |
 | A fixed min-height reserves room for two lines on every label, so the fields
 | below them still line up across the row even when one label wraps and its
 | neighbours do not. That was the reason for truncating in the first place.
 */
.pg-add-dip-dialog .form-group > label {
    display: block;
    white-space: normal;
    overflow-wrap: break-word;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.25;
    margin-bottom: 4px;
    min-height: 30px;
}

/* The + button sits outside the 12-column count so the row still totals 12. */
.pg-add-dip-dialog .pg-add-btn-col {
    float: left;
    width: 46px;
    padding-left: 6px;
}

/* The Note button fills its column and shows its state clearly. */
.pg-add-dip-dialog .pg-note-btn {
    height: 36px;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.pg-add-dip-dialog .pg-note-btn.has-note {
    border-color: #00a65a;
    color: #00a65a;
    font-weight: 600;
}

.pg-add-dip-dialog textarea.form-control { height: 36px; min-height: 36px; }

/* The + button sits under the Note field, aligned to the left of its column. */
.pg-add-dip-dialog #add_tank_button {
    width: 36px;
    height: 36px;
    padding: 0;
    line-height: 1;
}

/*
 | Table: headings separated and readable. They were running together -
 | "System Current QtyDifference" - because the cells had no padding.
 */
.pg-add-dip-dialog #tank_table { width: 100%; font-size: 13px; }

.pg-add-dip-dialog #tank_table th {
    padding: 8px 10px;
    border-bottom: 2px solid #ddd;
    white-space: nowrap;
    font-size: 12px;
    font-weight: 700;
}

.pg-add-dip-dialog #tank_table td {
    padding: 8px 10px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

/* Quantities right aligned so the decimal points line up. */
.pg-add-dip-dialog #tank_table td:nth-child(2),
.pg-add-dip-dialog #tank_table td:nth-child(3),
.pg-add-dip-dialog #tank_table td:nth-child(4),
.pg-add-dip-dialog #tank_table td:nth-child(5),
.pg-add-dip-dialog #tank_table th:nth-child(2),
.pg-add-dip-dialog #tank_table th:nth-child(3),
.pg-add-dip-dialog #tank_table th:nth-child(4),
.pg-add-dip-dialog #tank_table th:nth-child(5) {
    text-align: right;
}

/* Nothing may force a column wider than its share. */
.pg-add-dip-dialog .form-group,
.pg-add-dip-dialog .form-control,
.pg-add-dip-dialog .select2-container { max-width: 100%; min-width: 0; }
.pg-add-dip-dialog .select2-container { width: 100% !important; }

/* Labels wrap instead of stretching a narrow column. */
.pg-add-dip-dialog label {
    overflow-wrap: break-word;
    word-break: break-word;
    white-space: normal;
}

/*
 | Table headings on two lines.
 |
 | "Dip Reading", "Current Qty" and "System Current Qty" are forced to wrap so
 | the narrow columns above stay narrow. A nowrap heading would widen the whole
 | column and undo the reductions.
 */
.pg-add-dip-dialog #tank_table th {
    white-space: normal;
    word-break: break-word;
    vertical-align: bottom;
}
.pg-add-dip-dialog #tank_table th.pg-two-line { max-width: 90px; }

.pg-add-dip-dialog #tank_table td {
    word-break: break-word;
    vertical-align: middle;
}

/* Below tablet the fields stack rather than being squeezed to nothing. */
@media (max-width: 991px) {
    .pg-add-dip-dialog .pg-dip-ref-col,
    .pg-add-dip-dialog .pg-dip-datetime-col,
    .pg-add-dip-dialog .pg-dip-report-date-col,
    .pg-add-dip-dialog .pg-dip-location-col,
    .pg-add-dip-dialog .pg-dip-current-qty-col,
    .pg-add-dip-dialog .pg-dip-system-qty-col,
    .pg-add-dip-dialog .pg-dip-difference-col { width: 50%; }
}

/*
 * IS2147: the date/time calendar sits ABOVE the modal.
 *
 * The widget is now attached to <body> so the modal cannot clip it. A Bootstrap
 * modal uses z-index 1050, so anything appended to body renders BEHIND it unless
 * it is lifted above - the calendar would be uncropped but invisible, which is
 * worse than the original fault.
 *
 * 1060 clears the modal while staying below the toast layer.
 */
/*
 * IS2150: the widget sits inside the modal again, so the modal must not clip it.
 *
 * A modal scrolls its body, and a scrolling box crops anything that overflows -
 * which is what cut the calendar off in the first place. Allowing overflow lets
 * the calendar extend past the panel edge while staying anchored to its field.
 *
 * Applied only within THIS modal, so no other dialog's scrolling is affected.
 */
.dip_modal .modal-body,
.dip_modal .modal-content,
.dip_modal .modal-dialog,
.modal_dip_modal .modal-body,
.modal_dip_modal .modal-content {
    overflow: visible !important;
}

/*
 * Above the modal's own stacking context. Without this the calendar renders
 * behind the panel - uncropped but invisible, which is worse than clipped.
 */
.bootstrap-datetimepicker-widget {
    z-index: 1060 !important;
}

/* The widget draws its own table; the theme's global table styling makes the
   day cells oversized and pushes the calendar out of view. */
.bootstrap-datetimepicker-widget table th,
.bootstrap-datetimepicker-widget table td {
    padding: 4px 6px !important;
    border: 0 !important;
    background: transparent !important;
    line-height: 1.2 !important;
    font-size: 13px !important;
}

</style>

<div class="modal-dialog pg-add-dip-dialog" role="document"
     style="width: 60% !important; min-width: 720px; max-width: calc(100vw - 24px) !important; max-height: 100vh;">
    <div class="modal-content">

        {!! Form::open([
            'url' => action('\Modules\PetroGeneral\Http\Controllers\DipManagementController@saveNewDip'),
            'method' => 'post',
            'id' => 'add_new_dip_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('petrogeneral::lang.add_new_dip')</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-2 pg-dip-ref-col">
                        <div class="form-group">
                            {!! Form::label('ref_number', __('petrogeneral::lang.ref_number') . ':*') !!}
                            {!! Form::text('ref_number', $ref_no, [
                                'class' => 'form-control ref_number',
                                'required',
                                'readonly',
                                'placeholder' => __('petrogeneral::lang.ref_number'),
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-3 pg-dip-datetime-col">
                        <div class="form-group">
                            {!! Form::label('date_and_time', __('petrogeneral::lang.date_and_time') . ':*') !!}
                            {{--
                                IS2153: a NATIVE datetime input.

                                This was a readonly text box driven entirely by
                                bootstrap-datetimepicker. That calendar has been
                                fixed three times - a format fallback, attaching it
                                to <body>, then re-anchoring it with overflow
                                opened - and it still does not work reliably. Each
                                attempt corrected a real fault and uncovered
                                another, which is the sign that the plugin itself
                                is the wrong dependency here, not its settings.

                                type="datetime-local" uses the BROWSER's own
                                calendar. It cannot be clipped by the modal, cannot
                                be mispositioned, and cannot fail to initialise -
                                there is nothing to initialise. The same change
                                fixed the F22 date and the Credit Sale order date.

                                It also removes the readonly attribute, which was
                                only there to stop typing into a plugin-driven
                                field; the native control validates its own input.

                                SAFE ON THE SERVER: a native input posts Y-m-d\TH:i.
                                saveDip() tries uf_date() first and falls back to
                                Carbon::parse() when that returns null - and Carbon
                                parses ISO. The existing save path handles it
                                without change.
                            --}}
                            {!! Form::input('datetime-local', 'date_and_time', date('Y-m-d\TH:i'), [
                                'class' => 'form-control',
                                'required',
                            ]) !!}
                        </div>
                    </div>


                    <div class="col-md-3 pg-dip-report-date-col">
                        <div class="form-group">
                            {!! Form::label('daily_report_date', __('petrogeneral::lang.daily_report_date') . ':*') !!}
                            {{--
                                LA-1187: the date picker did not open on this field.

                                It had no id, so nothing could target it, and it
                                carried the class "date_and_time" - the same class as
                                the Dip Date & Time field above, which is driven by a
                                datetimepicker. That made this field look wired up
                                when it was not.

                                It now has its own id and class. 'readonly' is kept:
                                the value must come from the picker, not be typed,
                                and the picker below is initialised with
                                ignoreReadonly so it still opens on click.
                            --}}
                            {{-- IS2153: native date input - see the note on Dip Date & Time above. --}}
                            {!! Form::input('date', 'daily_report_date', date('Y-m-d'), [
                                'class' => 'form-control pg-daily-report-date',
                                'id' => 'daily_report_date',
                                'required',
                            ]) !!}
                        </div>
                    </div>


                    <div class="col-md-4 pg-dip-location-col">
                        <div class="form-group">
                            {!! Form::label('location_id', __('petrogeneral::lang.location') . ':*') !!}
                            {!! Form::select('location_id', $business_locations, !empty($default_location) ? $default_location : null, [
                                'class' => 'form-control select2
                                                        fuel_tank_location',
                                'required',
                                'placeholder' => __('petrogeneral::lang.please_select'),
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('tank_id', __('petrogeneral::lang.tanks') . ':') !!}
                            {!! Form::select('tank_id', $tanks, null, [
                                'class' => 'form-control select2',
                                'placeholder' => __('petrogeneral::lang.please_select'),
                                'id' => 'add_dip_tank_id',
                                'style' => 'width:100%',
                            ]) !!}
                        </div>
                    </div>

                    {{--
                        Dip Reading - manual entry, next to Tanks.

                        This is a TYPED value, not the old chart-driven dropdown.
                        That dropdown is still in the hidden block below (it feeds
                        the chart lookup the controller expects); this field posts
                        as manual_dip_reading and the controller stores it as the
                        dip reading.
                    --}}
                    <div class="col-md-2 pg-dip-reading-col">
                        <div class="form-group">
                            {!! Form::label('manual_dip_reading', __('petrogeneral::lang.dip_reading') . ':') !!}
                            {!! Form::text('manual_dip_reading', null, [
                                'class' => 'form-control manual_dip_reading',
                                'id' => 'manual_dip_reading',
                                'inputmode' => 'decimal',
                                'placeholder' => __('petrogeneral::lang.dip_reading'),
                            ]) !!}
                        </div>
                    </div>

                    {{--
                        TEMPORARY: Dip Reading and Dip Reading Value in Lts are
                        hidden so a dip can be saved without them.

                        Hidden with a wrapper rather than deleted, so restoring
                        them later is a matter of removing the two wrappers and
                        the four temporary blocks marked "TEMPORARY" in this file
                        and in DipManagementController::saveNewDip().

                        The inputs are still rendered - just not visible - so the
                        existing JavaScript that reads and clears them keeps
                        working untouched. Their 'required' attribute is removed
                        below, because a hidden field marked required blocks the
                        browser from submitting the form at all, with no visible
                        message explaining why.
                    --}}
                    {{--
                        The wrapper is display:none AND width:0, and carries no
                        col-* class.

                        A plain <div> inside a Bootstrap row still occupies the
                        flow, which is what produced the blank boxes between the
                        fields. Taking it out of the layout entirely removes them
                        while keeping the hidden inputs in the form so the existing
                        JavaScript and the controller keep working.
                    --}}
                    <div class="pg-temp-hidden-dip-fields"
                         style="display:none !important; width:0; height:0; overflow:hidden; position:absolute;">
                    @if ($tank_dip_chart_permission)
                        <div class="form-group col-sm-2">
                            {!! Form::label('dip_reading', __('petrogeneral::lang.dip_reading') . ':*') !!}
                            <div class="input-group">

                                {{-- TEMPORARY: 'required' removed - the field is hidden. --}}
                                {!! Form::select('dip_reading', [], null, [
                                    'class' => 'form-control dip_reading select2',
                                    'placeholder' => __('petrogeneral::lang.please_select'),
                                ]) !!}

                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-default bg-white btn-flat btn-modal"
                                        data-href="{{ action('\Modules\PetroGeneral\Http\Controllers\DipManagementController@addDipChart') }}?quick_add=true"
                                        data-container=".modal_dip_modal">
                                        <i class="fa fa-plus-circle text-primary fa-lg"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="col-md-2">
                            <div class="form-group">
                                {!! Form::label('dip_reading', __('petrogeneral::lang.dip_reading') . ':*') !!}
                                {!! Form::text('dip_reading', null, [
                                    'class' => 'form-control dip_reading',
                                    'placeholder' => __('petrogeneral::lang.please_select'),
                                ]) !!}
                            </div>
                        </div>
                    @endif

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('fuel_balance_dip_reading', __('petrogeneral::lang.tank_fuel_balance_dip_reading') . ':*') !!}
                            {!! Form::text('fuel_balance_dip_reading', null, [
                                'class' => 'form-control tank_fuel_balance_dip_reading',
                                'id' => 'fuel_balance_dip_reading',
                                'readonly',
                                'placeholder' => __('petrogeneral::lang.tank_fuel_balance_dip_reading'),
                            ]) !!}
                        </div>
                    </div>
                    </div>{{-- /.pg-temp-hidden-dip-fields (TEMPORARY) --}}

                    <div class="col-md-2 pg-dip-current-qty-col">
                        <div class="form-group">
                            {!! Form::label('current_qty', __('petrogeneral::lang.current_qty') . ':') !!}
                            {{--
                                Current Qty is entered by hand - it is the quantity
                                actually measured in the tank.

                                It used to be readonly and filled with the system
                                figure, which made Difference always 0 and left
                                nowhere to record the real reading.

                                System Current Qty beside it stays readonly: that is
                                the system's own figure and is not for editing.
                            --}}
                            {!! Form::text('current_qty', null, [
                                'class' => 'form-control current_qty',
                                'id' => 'current_qty',
                                'inputmode' => 'decimal',
                                'placeholder' => __('petrogeneral::lang.current_qty'),
                            ]) !!}
                        </div>
                    </div>

                    {{--
                        System Current Qty: the stock the system believes is in the
                        tank, filled automatically when a tank is chosen.

                        The value comes from the 'current_stock' key that
                        /get-tank-balance-by-id already returns - the same figure
                        the Dip Report compares a dip against - so no new endpoint
                        or query is needed. Read only: it is a system figure, not
                        something to type over.
                    --}}
                    <div class="col-md-2 pg-dip-system-qty-col">
                        <div class="form-group">
                            {!! Form::label('system_current_qty', __('petrogeneral::lang.system_current_qty') . ':') !!}
                            {!! Form::text('system_current_qty', null, [
                                'class' => 'form-control system_current_qty',
                                'id' => 'system_current_qty',
                                'readonly',
                                'placeholder' => __('petrogeneral::lang.system_current_qty'),
                            ]) !!}
                        </div>
                    </div>

                    {{-- Difference = System Current Qty - Current Qty. Read only. --}}
                    <div class="col-md-2 pg-dip-difference-col">
                        <div class="form-group">
                            {!! Form::label('dip_difference', __('petrogeneral::lang.difference') . ':') !!}
                            {!! Form::text('dip_difference', null, [
                                'class' => 'form-control dip_difference',
                                'id' => 'dip_difference',
                                'readonly',
                                'placeholder' => __('petrogeneral::lang.difference'),
                            ]) !!}
                        </div>
                    </div>

                    {{--
                        Note and the + button, flattened.

                        This used to be a nested row: a col-md-3 holding a col-md-2
                        and a col-md-1. Nested columns are a fraction of their
                        PARENT, so the Note field ended up around a sixth of the
                        already narrow column - squeezed to almost nothing, with the
                        button pushed out below it.

                        Now they are siblings on the main row: Note takes its own
                        col-md-2 and the button sits beside it.
                    --}}
                    {{--
                        Note: a button rather than a text box.

                        The textarea took a full column for a field that is often
                        left empty. The button opens a pop-up to type in, and the
                        value is held in the hidden input - so every script and the
                        controller still read #note exactly as before.

                        The button shows a tick once a note has been entered, so it
                        is obvious at a glance whether the row carries one.
                    --}}
                    <div class="col-md-1">
                        <div class="form-group">
                            {!! Form::label('note', __('petrogeneral::lang.note')) !!}
                            {!! Form::hidden('note', null, ['id' => 'note', 'class' => 'note']) !!}
                            <button type="button" id="pg_note_open" class="btn btn-default btn-block pg-note-btn">
                                <i class="fa fa-pencil"></i> <span id="pg_note_label">Click to Enter</span>
                            </button>
                        </div>
                    </div>

                    {{-- The + button shares the Note column's line, so the row stays at 12. --}}
                    <div class="col-md-0 pg-add-btn-col">
                        <div class="form-group">
                            <label style="visibility:hidden;">&nbsp;</label>
                            <button type="button" id="add_tank_button" class="btn btn-success">+</button>
                        </div>
                    </div>

                    </div>

                </div>
                <div class="row">
                    <table width="100%" id="tank_table">
                        <thead>
                            <tr>
                                <th>@lang('petrogeneral::lang.tanks')</th>
                                {{--
                                    TEMPORARY: the Dip Reading and Dip Reading Value
                                    in Lts columns are removed, because those two
                                    fields are hidden on the form above and would
                                    only ever show 0.

                                    System Current Qty added before Current Qty.

                                    The row markup further down must keep the same
                                    number of cells in the same order, or the values
                                    land under the wrong headings.
                                --}}
                                {{--
                                    Column order: Tanks, Dip Reading, Current Qty,
                                    System Current Qty, Difference, Note, details.

                                    The three headings marked pg-two-line are given
                                    an explicit line break so they render on two
                                    rows. A <br> is used rather than relying on the
                                    column being narrow enough to wrap, so the break
                                    falls in the same place every time.
                                --}}
                                {{--
                                    These three use the existing full language keys.
                                    The break is produced by CSS - .pg-two-line caps
                                    the width so the label wraps onto two rows - so
                                    no new translation keys are invented and the
                                    headings stay correct in every language.
                                --}}
                                <th class="pg-two-line">@lang('petrogeneral::lang.dip_reading')</th>
                                <th class="pg-two-line">@lang('petrogeneral::lang.current_qty')</th>
                                <th class="pg-two-line">@lang('petrogeneral::lang.system_current_qty')</th>
                                <th>@lang('petrogeneral::lang.difference')</th>
                                <th>@lang('petrogeneral::lang.note')</th>
                                <th>*</th>
                            </tr>
                        </thead>

                        <tbody>
                        </tbody>

                    </table>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary add_new_dip_reading_btn">@lang('messages.save')</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>

            {!! Form::close() !!}
        </div><!-- /.modal-content -->


    </div><!-- /.modal-dialog -->


    <div class="modal fade modal_dip_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <script>
        /*
         | LA-1187: initialise the Daily Report Date picker.
         |
         | This used to target #transaction_date, which does not exist anywhere in
         | this form - so no picker was ever created and clicking the field did
         | nothing.
         |
         | datetimepicker is used, matching Dip Date & Time above, with
         | ignoreReadonly so it opens even though the input is readonly. The format
         | is date-only, because the value is parsed as a date on save.
         */
        /*
         | IS2140: the calendar would not open on either field.
         |
         | Both pickers are configured with `moment_date_format`, a GLOBAL set by
         | the main layout. This modal is injected by ajax, and if that global is
         | not defined at the moment the script runs, datetimepicker receives
         | format: undefined and silently fails to initialise - no error, no
         | calendar, and the field keeps whatever text it was rendered with.
         | That matches the report exactly: dates visible, calendar dead.
         |
         | Resolved locally with a fallback, so the picker always gets a usable
         | format string. The Blade values are the business's own settings, so
         | the fallback matches what the rest of the system uses rather than
         | being an arbitrary guess.
         */
        var pgDateFormat = (typeof moment_date_format !== 'undefined' && moment_date_format)
            ? moment_date_format
            : '{{ strtoupper(str_replace(["d","m","Y"], ["DD","MM","YYYY"], session("business.date_format", "m/d/Y"))) }}';

        var pgTimeFormat = (typeof moment_time_format !== 'undefined' && moment_time_format)
            ? moment_time_format
            : 'HH:mm';

        /*
         | IS2147: the calendar opened but was CUT OFF at the top of the modal.
         |
         | The reported screenshot shows only the last two rows of dates - the
         | month header and the first weeks are clipped. That is not the picker
         | failing to start; it starts fine and is then cropped.
         |
         | The widget is drawn inside the field's own container, so the modal's
         | bounds clip it. Attaching it to <body> takes it out of that box, and
         | the plugin then positions it against the viewport instead.
         |
         | widgetPositioning is set to open DOWNWARD from the field. The default
         | flips upward when the field sits low in the window, which is exactly
         | how it ended up half outside the modal here.
         */
        /*
         * IS2150: the calendar is anchored to its FIELD again.
         *
         * IS2147 attached the widget to <body> to stop the modal clipping it.
         * That fixed the clipping - the calendar now renders in full - but broke
         * its position: detached from the field, the plugin laid it out against
         * the page and it appeared at the far left of the screen, nowhere near
         * the input.
         *
         * widgetParent is dropped so the widget sits next to its field again.
         * The clipping is prevented a different way: vertical 'bottom' opens it
         * DOWNWARD, and the modal panels are set to overflow: visible in the
         * stylesheet, so nothing crops it on the way out.
         *
         * That keeps both properties at once - correctly placed AND fully
         * visible - which attaching to body could not.
         */
        var pgPickerOptions = {
            ignoreReadonly: true,
            useCurrent: false,
            widgetPositioning: {
                horizontal: 'auto',
                vertical: 'bottom'
            }
        };

        /*
         * IS2153: the plugin is no longer attached to this field.
         *
         * It is a native date input now. Leaving datetimepicker bound to it
         * would put TWO calendars on one field - the browser's and the
         * plugin's - which is exactly the fault reported on the F22 form when
         * that field was converted and its picker left in place.
         */

        /*
         |----------------------------------------------------------------------
         | S670: a picked date did not appear in either field.
         |----------------------------------------------------------------------
         |
         | Both inputs are readonly. ignoreReadonly lets the picker OPEN on them,
         | but some builds of bootstrap-datetimepicker still will not write into a
         | readonly input - the date is held internally and the box stays as it was.
         |
         | Writing it explicitly on dp.change removes the doubt: whatever the picker
         | resolves is put straight into the field in the display format, and the
         | difference is recalculated for the report date.
         */
        $(document).off('dp.change.pgDipDates')
            .on('dp.change.pgDipDates', '#daily_report_date, #date_and_time', function (e) {
                if (! e.date) {
                    return;
                }

                var isReportDate = this.id === 'daily_report_date';
                var fmt = isReportDate
                    ? pgDateFormat
                    : pgDateFormat + ' ' + pgTimeFormat;

                $(this).val(e.date.format(fmt));
            });

        /*
         * IS2153: default to today in the NATIVE input's format.
         *
         * This asked the removed plugin for its instance. A native input takes
         * a plain Y-m-d string - any other format is silently rejected and the
         * field stays blank.
         */
        if (! $('#daily_report_date').val()) {
            $('#daily_report_date').val(moment().format('YYYY-MM-DD'));
        }

        /*
         * IS2153: removed - the native input opens its own calendar on click.
         *
         * This existed to force the plugin open on a readonly field. It was
         * guarded, so it did no harm, but leaving dead code that references a
         * removed plugin invites someone to "fix" it later by putting the plugin
         * back.
         */

        /*
         | S670: the calendar opened on the wrong month.
         |
         | With no defaultDate and useCurrent left at its default, the picker
         | opened on whatever it last parsed from the field - and the field held a
         | value written in a different format, so the month was wrong.
         |
         | defaultDate pins it to now, and the value is set through the picker
         | itself so the field and the calendar always agree.
         */
        /*
         * IS2153: likewise - native datetime input, so no plugin here either.
         */

        /*
         | Write today's date into the input itself.
         |
         | defaultDate and useCurrent set the picker's internal state, but with
         | a readonly input bootstrap-datetimepicker does not always write that
         | through to the field - which left it empty on screen.
         |
         | Using the picker's own API, in the picker's own format, so this
         | cannot repeat the earlier conflict where a second datepicker wrote
         | the date in a different order and produced "11/03/2026".
         |
         | The user can still change it; this only supplies the starting value.
         */
        /*
         * IS2153: same for the datetime field.
         *
         * This one called .data('DateTimePicker').date(...) with NO guard, so
         * with the plugin gone it would throw TypeError and stop every line of
         * script after it - including the select2 setup on the next line. A
         * native datetime-local needs YYYY-MM-DDTHH:mm exactly.
         */
        if (!$('#date_and_time').val()) {
            $('#date_and_time').val(moment().format('YYYY-MM-DDTHH:mm'));
        }

        $('.select2').select2();
        $('#location_id').select2();

        /*
         | The datepicker("setDate") call that used to be here is REMOVED.
         |
         | #date_and_time is already managed by datetimepicker a few lines above,
         | with format moment_date_format + ' ' + moment_time_format. Calling
         | datepicker() on the SAME field started a second, competing picker that
         | wrote the date in ITS own format.
         |
         | The two disagreed about which part is the day and which the month, so
         | the field ended up holding dates like "11/03/2026" on 15 August. That
         | value was then saved to dip_readings.date_and_time and put the dip far
         | outside the report's date range.
         |
         | The field is populated server-side already - see the Form::text above,
         | which fills it with @format_datetime(date('Y-m-d H:i')) - so nothing is
         | lost by removing this.
         */

        $('#location_id option:eq(1)').attr('selected', true).trigger('change');

        @if (!$tank_dip_chart_permission)
            $('#fuel_balance_dip_reading').attr("readonly", false);
        @endif
        /*
         | Delegated binding, not $('#add_dip_tank_id').change(...).
         |
         | This modal is fetched with .load() and its select2 is initialised a
         | few lines above. A handler bound directly to the element can be lost
         | when select2 re-renders the control, or when the modal is closed and
         | reopened - which is why System Current Qty sometimes stayed empty
         | after choosing a tank.
         |
         | Delegating from document survives both.
         */
        /*
         | Difference = System Current Qty - Current Qty.
         |
         | Recalculated whenever either value changes. Current Qty is read only
         | today, but it is watched anyway so the field stays correct if that
         | ever changes.
         */
        /*
         |----------------------------------------------------------------------
         | Number formatting with thousands separators.
         |----------------------------------------------------------------------
         |
         | Displayed values carry commas (16,500.000). Every calculation must
         | strip them first - parseFloat("16,500") returns 16, which would make
         | Difference wildly wrong.
         |
         | pgParseNumber() before any arithmetic; pgFormatNumber() when writing a
         | value back for display. Current Qty is formatted on BLUR only, so
         | commas do not appear while the operator is still typing.
         */
        /*
         | Note pop-up.
         |
         | The value lives in the hidden #note input, so nothing else in this form
         | had to change - the row builder and the controller still read #note.
         |
         | Delegated, because the modal is re-loaded on every open.
         */
        $(document).off('click.pgNoteOpen').on('click.pgNoteOpen', '#pg_note_open', function(e) {
            e.preventDefault();

            var current = $('#note').val() || '';

            if (typeof Swal !== 'undefined' && Swal.fire) {
                Swal.fire({
                    title: '{{ __('petrogeneral::lang.note') }}',
                    input: 'textarea',
                    inputValue: current,
                    inputAttributes: { rows: 4 },
                    showCancelButton: true,
                    confirmButtonText: 'Save',
                }).then(function (result) {
                    if (result && typeof result.value === 'string') {
                        pgSetNote(result.value);
                    }
                });
            } else {
                // Plain prompt when SweetAlert is unavailable - never leave the
                // operator with no way to enter a note.
                var typed = window.prompt('{{ __('petrogeneral::lang.note') }}', current);
                if (typed !== null) {
                    pgSetNote(typed);
                }
            }
        });

        function pgSetNote(value) {
            value = (value === null || value === undefined) ? '' : String(value);

            $('#note').val(value);

            if (value.trim() !== '') {
                $('#pg_note_label').text('Note added');
                $('#pg_note_open').addClass('has-note').attr('title', value);
            } else {
                $('#pg_note_label').text('Click to Enter');
                $('#pg_note_open').removeClass('has-note').removeAttr('title');
            }
        }

        function pgParseNumber(value) {
            if (value === null || value === undefined) {
                return NaN;
            }
            return parseFloat(String(value).replace(/,/g, '').trim());
        }

        /*
         | Escapes a value before it goes into HTML or an attribute.
         |
         | This definition was lost during an earlier edit while the 14 CALLS to it
         | remained. JavaScript throws a ReferenceError on the first call, so the
         | row was never built and nothing reached the table - the Add button
         | appeared to do nothing at all.
         |
         | Defined at the same top level as the other pg* helpers so every handler
         | can see it. A note containing a quote or angle bracket would otherwise
         | break the row markup.
         */
        function pgEscapeHtml(value) {
            return $('<div>').text(value === null || value === undefined ? '' : value).html()
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function pgFormatNumber(value, decimals) {
            var n = pgParseNumber(value);
            if (!isFinite(n)) {
                return '';
            }
            return n.toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            });
        }

        function updateDipDifference() {
            var systemQty = pgParseNumber($('#system_current_qty').val());
            var currentQty = pgParseNumber($('#current_qty').val());

            if (!isFinite(systemQty) && !isFinite(currentQty)) {
                $('#dip_difference').val('');
                return;
            }

            systemQty = isFinite(systemQty) ? systemQty : 0;
            currentQty = isFinite(currentQty) ? currentQty : 0;

            $('#dip_difference').val(pgFormatNumber(systemQty - currentQty, 3));
        }

        $(document).off('input.pgDipDiff change.pgDipDiff')
            .on('input.pgDipDiff change.pgDipDiff', '#system_current_qty, #current_qty', updateDipDifference);

        /*
         | Current Qty is typed, so commas are applied on BLUR and removed again on
         | FOCUS. Formatting while typing would fight the operator - the caret
         | jumps every time a separator is inserted.
         */
        $(document).off('focus.pgCurrentQty', '#current_qty')
            .on('focus.pgCurrentQty', '#current_qty', function() {
                var raw = pgParseNumber($(this).val());
                $(this).val(isFinite(raw) ? raw : '');
            });

        $(document).off('blur.pgCurrentQty', '#current_qty')
            .on('blur.pgCurrentQty', '#current_qty', function() {
                var raw = pgParseNumber($(this).val());
                $(this).val(isFinite(raw) ? pgFormatNumber(raw, 3) : '');
                updateDipDifference();
            });

        /*
         | Fire once on open too, not only on change.
         |
         | If a tank is already selected when the form loads - a single-tank
         | location, or a value restored by the browser - no change event ever
         | fires and System Current Qty stays empty. Triggering it after setup
         | covers that.
         */
        $(document).off('change.pgAddDipTank').on('change.pgAddDipTank', '#add_dip_tank_id', function() {
            let tank_id = $(this).val();
            
            // Only make AJAX call if tank_id is selected
            if (!tank_id || tank_id === '' || tank_id === null) {
                return;
            }

            /*
             | Immediate feedback while the balance is fetched.
             |
             | The lookup sums purchases, sales and testing for the tank, so it is
             | not instant. Showing "Loading..." tells the operator it is working
             | instead of leaving the field blank and looking broken.
             */
            $('#system_current_qty').val('Loading...');
            $('#dip_difference').val('');

            /*
             | Two things keep this responsive.
             |
             | 1. A result already fetched for this tank is reused, so switching
             |    back to a tank is instant instead of hitting the server again.
             | 2. A request still in flight is aborted when another tank is
             |    picked. Without that, choosing three tanks quickly left three
             |    requests racing and whichever finished LAST won - which could be
             |    the wrong tank's figure.
             */
            window.pgTankBalanceCache = window.pgTankBalanceCache || {};

            if (window.pgTankBalanceCache[tank_id]) {
                pgApplyTankBalance(window.pgTankBalanceCache[tank_id]);
                return;
            }

            if (window.pgTankBalanceRequest && window.pgTankBalanceRequest.readyState !== 4) {
                window.pgTankBalanceRequest.abort();
            }

            window.pgTankBalanceRequest = $.ajax({
                method: 'get',
                url: "/petro-general/get-tank-balance-by-id/" + tank_id,
                data: {},
                success: function(result) {
                    // Remember it so re-selecting this tank is instant.
                    window.pgTankBalanceCache[tank_id] = result;
                    pgApplyTankBalance(result);
                },
                error: function(xhr, status) {
                    // An aborted request is not a failure - it was replaced.
                    if (status === 'abort') {
                        return;
                    }
                    $('#system_current_qty').val('');
                    toastr.error('Error fetching tank balance. Please try again.');
                }
            });
        });

        /*
         | Applies a tank balance result to the form. Shared by the live response
         | and the cached one, so both behave identically.
         */
        function pgApplyTankBalance(result) {
                    $('#tank_manufacturer').val(result.details.tank_manufacturer);
                    $('#tank_capacity').val(result.details.tank_capacity);
                    // System Current Qty - the system's figure, read only, comma separated.
                    $('#system_current_qty').val(pgFormatNumber(result.current_stock, 3)).trigger('change');

                    /*
                     | Current Qty is deliberately CLEARED, not prefilled.
                     |
                     | Prefilling it with the system figure made Difference always
                     | read 0.000, which hides the very discrepancy this form
                     | exists to record. The operator types the measured quantity.
                     */
                    $('#current_qty').val('').trigger('change');

                    updateDipDifference();

                    let html = '';
                    let dip_readings = result.dip_readings;
                    for (const [key, value] of Object.entries(dip_readings)) {
                        html += '<option value="' + value + '">' + key + '</option>';
                    }
                    $('#dip_reading').empty().append(html);

                    /*
                     | The trigger('change') that used to be here is REMOVED.
                     |
                     | It fired the Superadmin lookup
                     |     /superadmin/tank-dip-chart-details/get-reading-value/{id}
                     | which uses findOrFail(). With the Dip Reading field hidden
                     | there is no id to send, so it threw
                     |     No query results for model
                     |     [Modules\Superadmin\Entities\TankDipChartDetail] null
                     | - the red toast - and aborted the rest of this handler,
                     | which is why System Current Qty stayed at 0.000.
                     |
                     | Guarding the receiving handler was not enough: select2 can
                     | raise its own change when the options are replaced above.
                     | Not triggering at all is the reliable fix - the request can
                     | no longer be made from here under any circumstances.
                     |
                     | To restore when Dip Reading comes back: put the trigger back.
                     */
        }

        @if ($tank_dip_chart_permission)
            $(document).off('change.pgDipReading').on('change.pgDipReading', '#dip_reading', function() {
                let tank_dip_reading = $(this).val();

                /*
                 | TEMPORARY: skip the lookup when no dip reading is chosen.
                 |
                 | The Dip Reading field is hidden now, so it is always empty -
                 | but the tank handler above still ends with
                 |     $('#dip_reading').trigger('change')
                 | which fired this request with an empty id. The Superadmin
                 | endpoint uses findOrFail(), so it threw
                 |     No query results for model
                 |     [Modules\Superadmin\Entities\TankDipChartDetail] null
                 | and that is the red toast on screen.
                 |
                 | It also aborted the rest of the tank handler's work, which is
                 | why System Current Qty never filled in.
                 */
                if (!tank_dip_reading || tank_dip_reading === '' || tank_dip_reading === null) {
                    $('#fuel_balance_dip_reading').val('');
                    return;
                }

                $.ajax({
                    method: 'get',
                    url: "/superadmin/tank-dip-chart-details/get-reading-value/" + tank_dip_reading,
                    data: {},
                    success: function(result) {
                        $('#fuel_balance_dip_reading').val(result.dip_reading_value).trigger('change');
                    },
                    error: function() {
                        // Never block the form for an optional lookup.
                        $('#fuel_balance_dip_reading').val('');
                    },
                });
            })
        @endif







        // Function to toggle save button based on whether there are tanks in the table
        function toggleSaveButton() {
            var saveButton = $('.add_new_dip_reading_btn');
            var rowCount = $('#tank_table tbody tr').length;
            
            if (rowCount > 0) {
                saveButton.prop('disabled', false);
            } else {
                saveButton.prop('disabled', true);
            }
        }

        $(document).ready(function() {
            /*
             | Delegated, namespaced binding - was $('#add_tank_button').click(...).
             |
             | The modal is emptied and re-loaded each time it opens. A direct
             | binding is lost with the old markup, and re-running this script
             | stacked a second handler on the new one - so the button either did
             | nothing or added the row twice. Delegating from document with an
             | .off() first gives exactly one live handler however many times the
             | form is opened.
             */
            $(document).off('click.pgAddTank').on('click.pgAddTank', '#add_tank_button', function() {
                // Get input values
                var tankId = $('#add_dip_tank_id').val();
                var dipReading = $('#dip_reading').val();
                var fuelBalanceDipReading = $('#fuel_balance_dip_reading').val();
                var currentQty = $('#current_qty').val();
                var systemCurrentQty = $('#system_current_qty').val();
                var dipDifference = $('#dip_difference').val();
                var note = $('#note').val();
                var tankName = $('#add_dip_tank_id option:selected').text();


                // Validate required fields
                /*
                 | TEMPORARY: dipReading and fuelBalanceDipReading are no longer
                 | required - those fields are hidden. Only the tank still has to
                 | be chosen. Current Qty is filled automatically when a tank is
                 | selected, so it is not demanded either.
                 */
                /*
                 | Catches null and undefined too, not just an empty string.
                 |
                 | A select2 with a placeholder and nothing chosen returns NULL from
                 | .val(), and 'null === ""' is false - so the old test passed, the
                 | handler carried on and added a blank row.
                 */
                if (!tankId) {
                    toastr.error('Please select a tank.');
                    $('#add_dip_tank_id').focus();
                    return;
                }

                // Hidden fields submit as empty strings; send 0 so the row is valid.
                dipReading = (dipReading === '' || dipReading === null) ? 0 : dipReading;
                fuelBalanceDipReading = (fuelBalanceDipReading === '' || fuelBalanceDipReading === null)
                    ? 0
                    : fuelBalanceDipReading;

                /*
                 |------------------------------------------------------------------
                 | Raw values for submission, formatted values for display.
                 |------------------------------------------------------------------
                 |
                 | manualDipReading and the *Raw variables were USED further down but
                 | never declared here - the block that created them was lost in an
                 | earlier edit. That threw a ReferenceError the moment the row was
                 | built, so nothing was ever appended to the table and the button
                 | looked dead even though its handler was running.
                 |
                 | Commas must be stripped before anything is submitted: the display
                 | fields read "19,000.000" and MySQL would store that as 19.
                 */
                var manualDipReading = $('#manual_dip_reading').val();

                var currentQtyRaw = pgParseNumber(currentQty);
                var systemCurrentQtyRaw = pgParseNumber(systemCurrentQty);
                var dipDifferenceRaw = pgParseNumber(dipDifference);
                var manualDipReadingRaw = pgParseNumber(manualDipReading);

                currentQtyRaw = isFinite(currentQtyRaw) ? currentQtyRaw : 0;
                systemCurrentQtyRaw = isFinite(systemCurrentQtyRaw) ? systemCurrentQtyRaw : 0;
                dipDifferenceRaw = isFinite(dipDifferenceRaw) ? dipDifferenceRaw : 0;
                manualDipReadingRaw = isFinite(manualDipReadingRaw) ? manualDipReadingRaw : 0;

                // Displayed text, comma separated.
                currentQty = pgFormatNumber(currentQtyRaw, 3);
                systemCurrentQty = pgFormatNumber(systemCurrentQtyRaw, 3);
                dipDifference = pgFormatNumber(dipDifferenceRaw, 3);
                manualDipReading = String(manualDipReadingRaw);


                // Create a new row
                /*
                 | Cells must match the header: Tanks, System Current Qty,
                 | Current Qty, Note, actions.
                 |
                 | dip_readings[] and fuel_balance_dip_readings[] are still
                 | submitted as hidden inputs even though their columns are gone -
                 | the controller still reads those arrays, and dropping them would
                 | shift every row's data. They carry 0.
                 */
                /*
                 | Cells follow the header exactly:
                 |   Tanks, Dip Reading, Current Qty, System Current Qty,
                 |   Difference, Note, actions
                 |
                 | The details button carries the row's values in data- attributes.
                 | title= gives the hover summary; the click handler reads
                 | data-note for the pop-up.
                 |
                 | Values are escaped before going into an attribute - a note
                 | containing a quote would otherwise break the markup.
                 */
                var rowSummary =
                    'Tank: ' + tankName + '\n' +
                    'Dip Reading: ' + manualDipReading + '\n' +
                    'Current Qty: ' + currentQty + '\n' +
                    'System Current Qty: ' + systemCurrentQty + '\n' +
                    'Difference: ' + dipDifference +
                    (note ? '\nNote: ' + note : '');

                var newRow = '<tr>' +
                    '<td>' + pgEscapeHtml(tankName) + '</td>' +
                    '<td>' + pgEscapeHtml(manualDipReading) + '</td>' +
                    '<td>' + pgEscapeHtml(currentQty) + '</td>' +
                    '<td>' + pgEscapeHtml(systemCurrentQty) + '</td>' +
                    '<td>' + pgEscapeHtml(dipDifference) + '</td>' +
                    '<td>' + pgEscapeHtml(note) + '</td>' +
                    '<td>' +
                    '<input type="hidden" name="tank_ids[]" value="' + pgEscapeHtml(tankId) + '">' +
                    '<input type="hidden" name="dip_readings[]" value="' + pgEscapeHtml(dipReading) + '">' +
                    '<input type="hidden" name="manual_dip_readings[]" value="' + pgEscapeHtml(manualDipReadingRaw) + '">' +
                    '<input type="hidden" name="fuel_balance_dip_readings[]" value="' +
                    pgEscapeHtml(fuelBalanceDipReading) + '">' +
                    '<input type="hidden" name="system_current_qtys[]" value="' + pgEscapeHtml(systemCurrentQtyRaw) + '">' +
                    '<input type="hidden" name="current_qtys[]" value="' + pgEscapeHtml(currentQtyRaw) + '">' +
                    '<input type="hidden" name="dip_differences[]" value="' + pgEscapeHtml(dipDifferenceRaw) + '">' +
                    '<input type="hidden" name="notes[]" value="' + pgEscapeHtml(note) + '">' +
                    '<button type="button" class="btn btn-info btn-xs pg_dip_row_details" ' +
                        'title="' + pgEscapeHtml(rowSummary) + '" ' +
                        'data-note="' + pgEscapeHtml(note) + '" ' +
                        'data-summary="' + pgEscapeHtml(rowSummary) + '">' +
                        '<i class="fa fa-eye"></i>' +
                    '</button> ' +
                    '<button class="btn btn-danger remove_tank_button">-</button>' +
                    '</td>' +
                    '</tr>';

                // Append the new row to the table body
                $('#tank_table tbody').append(newRow);

                $('#add_dip_tank_id option:selected').remove();

                // Clear input values
                $('#dip_reading').val('').trigger('change');
                $('#fuel_balance_dip_reading').val('').trigger('change');
                /*
                 | S670: the typed Dip Reading was never cleared, so the previous
                 | value stayed in the box and looked like it belonged to the next
                 | entry. #dip_reading above is the hidden chart field - this is the
                 | visible one the operator types into.
                 */
                $('#manual_dip_reading').val('');
                $('#current_qty').val('').trigger('change');
                $('#system_current_qty').val('').trigger('change');
                $('#dip_difference').val('');
                // Resets the hidden value AND the button label back to "Click to Enter".
                pgSetNote('');
                
                // Clear tank selection without triggering change event to avoid AJAX call
                $('#add_dip_tank_id').val('').trigger('change.select2');

                // Toggle save button state
                toggleSaveButton();
            });

            // Remove row button click event
            $('#tank_table').on('click', '.remove_tank_button', function() {
                // Remove the corresponding row
                $(this).closest('tr').remove();

                // Toggle save button state
                toggleSaveButton();
            });
            
            // Initialize save button state on page load
            toggleSaveButton();
        });

        $(document).ready(function() {
            /*
             |------------------------------------------------------------------
             | The + button is NEVER disabled.
             |------------------------------------------------------------------
             |
             | It used to start disabled and be re-enabled by checkRequiredFields.
             | That had two ways to fail, and between them the button spent most of
             | its life dead:
             |
             |   - addTankButton was captured ONCE into a const. After the modal is
             |     emptied and re-loaded that reference points at a button that is
             |     no longer in the page, so enabling it had no visible effect.
             |
             |   - if anything threw before checkRequiredFields ran, the button was
             |     left disabled with nothing to re-enable it.
             |
             | A disabled button also gives the operator no clue WHY it will not
             | work. It is now always clickable and the click handler checks the
             | tank, showing a clear message when one has not been chosen. There is
             | no hidden state left to go wrong.
             */
            $('#add_tank_button').prop('disabled', false);

            function checkRequiredFields() {
                // Kept as a no-op: other code still calls it. The button no longer
                // depends on it, so a failure here cannot disable the button.
                $('#add_tank_button').prop('disabled', false);
            }

            // Run check whenever relevant fields change (note field excluded as it's optional)
            /*
             | Delegated - was a direct binding on the elements.
             |
             | The modal is emptied and re-loaded each time it opens, so a direct
             | binding died with the old markup. checkRequiredFields then never ran
             | again, the + button kept the disabled state it was given on first
             | load, and selecting a tank did nothing to re-enable it. That is why
             | the button appeared dead.
             */
            $(document).off('change.pgDipReq keyup.pgDipReq')
                .on('change.pgDipReq keyup.pgDipReq',
                    '#add_dip_tank_id, #manual_dip_reading, #current_qty',
                    checkRequiredFields);

            // Also run it once initially in case some values are prefilled
            checkRequiredFields();

            /*
             | Load the tank's current stock straight away when a tank is already
             | selected on open - see the note on the change handler above.
             */
            if ($('#add_dip_tank_id').val()) {
                $('#add_dip_tank_id').trigger('change');
            }
        });
    </script>

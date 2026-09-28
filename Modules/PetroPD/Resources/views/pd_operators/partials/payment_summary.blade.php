
<style>
/* IS1527/IS1523: hide legacy/debug CSS text section if it was printed by an older cached view. */
.petropd-payment-summary-debug,
.payment-summary-debug,
#payment_summary_debug,
#petropd_payment_summary_debug { display:none !important; }

/* IS1668: Slip No must remain compact even when older nth-child rules or
   DataTables calculated widths are present. Class-based rules work in both
   the standalone and PD Operators tab layouts. */
/*
 |-----------------------------------------------------------------------------
 | IS2062 #3: Slip No column widened; Action, Date and Note reduced.
 |-----------------------------------------------------------------------------
 |
 | The slip number appeared blank, and this was why: the column was pinned to
 | 34px with a max-width, so a five digit slip such as 46208 had nowhere to go
 | and was clipped out of sight.
 |
 | The data was never missing. Verified in the database - the join resolves and
 | returns the slip correctly:
 |
 |     payment 105  resolved_slip 46208  from daily_cards id 64
 |
 | Widths are set as percentages so they hold whatever the table's overall size,
 | rather than fixed pixels that break on a narrower screen.
 */
#pump_operators_payment_summary_table th.pd-col-slip,
#pump_operators_payment_summary_table td.pd-col-slip,
#pump_operators_payment_summary_table th[data-column-name="slip_no"],
#pump_operators_payment_summary_table td[data-column-name="slip_no"] {
    width: 60% !important;
    min-width:90px !important;
    max-width: none !important;
    padding-left: 6px !important;
    padding-right: 6px !important;
    text-align: center !important;
    white-space: nowrap !important;
    overflow: visible !important;
    text-overflow: clip !important;
}

/*
 | Collection Form No: half its previous width.
 |
 | The renderer turns anything too long into a button, so nothing is clipped -
 | which is what hid the slip numbers on this same table before.
 */
/* Shift No: halved, it only ever holds a single digit. */
#pump_operators_payment_summary_table th.pd-col-shift,
#pump_operators_payment_summary_table td.pd-col-shift {
    width: 6px !important;
    min-width: 6px !important;
    max-width: 6px !important;
    padding-left: 1px !important;
    padding-right: 1px !important;
}

#pump_operators_payment_summary_table th[data-column-name="collection_form_no"],
#pump_operators_payment_summary_table td[data-column-name="collection_form_no"] {
    width: 41px !important;
    min-width: 41px !important;
    max-width: 41px !important;
    padding-left: 2px !important;
    padding-right: 2px !important;
    text-align: center !important;
    white-space: normal !important;
    overflow-wrap: break-word !important;
}

.pd-collection-form-no-btn {
    padding: 0 4px !important;
    font-size: 11px !important;
    line-height: 16px !important;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/*
 |-----------------------------------------------------------------------------
 | Column headings: Calibri, one point smaller.
 |-----------------------------------------------------------------------------
 |
 | calc(1em - 1pt) takes a point off whatever the table inherits, rather than
 | fixing a pixel size - so the headings stay in proportion if the base size is
 | changed later, and on a screen using a different default.
 |
 | Carlito is metrically identical to Calibri and is what Linux browsers have,
 | so the fallback list keeps the same appearance where Calibri is absent.
 |
 | Headings only. The data cells are untouched.
 */
#pump_operators_payment_summary_table thead th {
    font-family: Calibri, Carlito, "Segoe UI", "Helvetica Neue", Arial, sans-serif !important;
    font-size: calc(1em - 1pt) !important;
}

/*
 | #8: data half a point smaller. Headings keep their own size, set above.
 */
#pump_operators_payment_summary_table tbody td {
    font-size: calc(1em - 0.5pt) !important;
}

/*
 | #1: the Action column hugs its button - no padding either side.
 |
 | The first-child rule further down sets a 110px minimum, so that is overridden
 | here or the column would keep its old width whatever this says.
 */
#pump_operators_payment_summary_table th.pd-col-action,
#pump_operators_payment_summary_table td.pd-col-action,
#pump_operators_payment_summary_table thead th:first-child,
#pump_operators_payment_summary_table tbody td:first-child {
    /* +25% from the original 36px. */
    width: 45px !important;
    min-width: 45px !important;
    max-width: 45px !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    text-align: center !important;
}

/*
 | #5: Customer button. The name shows on hover and on click.
 */
.pd-customer-name-btn {
    padding: 0 4px !important;
    font-size: 11px !important;
    line-height: 16px !important;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}



/*
 | Date column: 138px, with the time on a second line.
 |
 | Raised from 92px because the date was being clipped and unreadable. The
 | separate Time column is gone, so the table is narrower overall despite this.
 */
#pump_operators_payment_summary_table th.pd-col-date,
#pump_operators_payment_summary_table td.pd-col-date {
    width: 138px !important;
    min-width: 138px !important;
    max-width: 138px !important;
    white-space: nowrap !important;
    text-align: center !important;
}

.pd-time-sub {
    font-size: 10px !important;
    display: block;
    line-height: 1.1;
}

/* IS2062 #3: the three columns that give up the room. */
#pump_operators_payment_summary_table th[data-column-name="action"],
#pump_operators_payment_summary_table td[data-column-name="action"] {
    width: 20% !important;
    max-width: none !important;
}

#pump_operators_payment_summary_table th[data-column-name="date"],
#pump_operators_payment_summary_table td[data-column-name="date"],
#pump_operators_payment_summary_table th[data-column-name="payment_date"],
#pump_operators_payment_summary_table td[data-column-name="payment_date"] {
    width: 20% !important;
    max-width: none !important;
}

#pump_operators_payment_summary_table th[data-column-name="note"],
#pump_operators_payment_summary_table td[data-column-name="note"] {
    width: 15% !important;
    max-width: none !important;
    white-space: normal !important;
    overflow-wrap: break-word !important;
}

/*
 | IS2062 #4: money reads right aligned, as figures should.
 |
 | The heading is left as it is - only the values move, so the decimal points
 | line up down the column.
 */
#pump_operators_payment_summary_table td[data-column-name="payment_amount"],
#pump_operators_payment_summary_table td[data-column-name="amount"],
#pump_operators_payment_summary_table td.pd-col-amount {
    text-align: right !important;
    padding-right: 10px !important;
}
</style>

<script>
/*
 | Clicking a Collection Form No button shows the number in full.
 |
 | Delegated, so it keeps working after DataTables redraws - a direct binding
 | would be lost on the first paging or filter.
 |
 | Hovering already shows it through the title attribute; this is for touch,
 | where there is no hover.
 */
$(document).off('click.pdCollectionFormNo')
    .on('click.pdCollectionFormNo', '.pd-collection-form-no-btn', function () {
        var value = $(this).data('collection-form-no');

        if (!value) {
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Collection Form No',
                text: String(value),
                confirmButtonText: 'OK'
            });
        } else {
            alert('Collection Form No: ' + value);
        }
    });

/*
 |-----------------------------------------------------------------------------
 | Note button: a DELEGATED click handler, independent of the inline onclick.
 |-----------------------------------------------------------------------------
 |
 | Reported: hovering shows the note, but clicking does nothing.
 |
 | Hover works because it uses the title attribute, which needs no JavaScript at
 | all. The click relied on an inline
 |     onclick="return window.petropdShowPaymentNote(this);"
 | and an inline handler fails silently if that function is not defined at the
 | moment of the click - which is what happens when the rows are injected by
 | DataTables before this script has run, or when the table is rendered on a page
 | that does not carry this partial.
 |
 | This delegated handler is bound to the document, so it works for rows added at
 | any time and does not depend on the inline attribute at all. The note is read
 | from the button's own data attribute, so nothing external is needed.
 |
 | The inline onclick is harmless if it does fire - it opens the same modal - and
 | is left in place so nothing regresses.
 */
$(document).off('click.pdPaymentNote')
    .on('click.pdPaymentNote', '.pd-note-btn, .pd-payment-note-view', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var encoded = $(this).attr('data-note-b64') || '';
        var note = '';

        try {
            note = encoded ? decodeURIComponent(escape(window.atob(encoded))) : '';
        } catch (err) {
            // Fall back to the title, which carries the same text.
            note = $(this).attr('title') || '';
        }

        if (!note) {
            note = $(this).attr('title') || '';
        }

        if (!note) {
            return false;
        }

        var $modal = $('#pd_payment_note_modal');

        if ($modal.length) {
            if (!$modal.parent().is('body')) {
                $modal.appendTo('body');
            }

            $('#pd_payment_note_modal_body').text(note);
            $modal.modal('show');

            return false;
        }

        // No modal on this page - show it anyway rather than doing nothing.
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: 'Note', text: note, confirmButtonText: 'OK' });
        } else {
            alert(note);
        }

        return false;
    });

/*
 |-----------------------------------------------------------------------------
 | Details popup - the columns taken off the table.
 |-----------------------------------------------------------------------------
 |
 | Collection Form No, Slip No, Date & Time, Order No, Note and Edited By were
 | consuming most of the table width. They are now hidden and shown here on
 | demand, from the Details item at the end of the Action menu.
 |
 | Date and Time are shown MERGED on one line, as requested.
 |
 | Every value travels on the button as a data attribute, so nothing is fetched
 | when it opens - it appears immediately and works on any page of results.
 |
 | Delegated from the document so it keeps working after DataTables redraws.
 */
$(document).off('click.pdPaymentDetails')
    .on('click.pdPaymentDetails', '.pd-payment-details-btn', function (e) {
        e.preventDefault();

        var $btn = $(this);

        function val(name) {
            var v = $btn.attr('data-' + name);
            return (v === undefined || v === null || v === '') ? '\u2014' : v;
        }

        // Date and Time arrive as one value from date_and_time.
        var dateTime = val('date-time');

        var rows = [
            ['Collection Form No', val('collection-form-no')],
            ['Slip No',            val('slip-no')],
            ['Date & Time',        dateTime],
            ['Order No',           val('order-no')],
            ['Note',               val('note')],
            ['Edited By',          val('edited-by')]
        ];

        var body = '<table class="table table-condensed" style="margin-bottom:0;">';

        $.each(rows, function (i, pair) {
            body += '<tr>'
                 +  '<th style="width:40%; white-space:nowrap;">' + pair[0] + '</th>'
                 +  '<td style="word-break:break-word;">' + $('<div>').text(pair[1]).html() + '</td>'
                 +  '</tr>';
        });

        body += '</table>';

        var $modal = $('#pd_payment_details_modal');

        if (!$modal.length) {
            $modal = $(
                '<div class="modal fade" id="pd_payment_details_modal" tabindex="-1" role="dialog">' +
                '  <div class="modal-dialog" role="document">' +
                '    <div class="modal-content">' +
                '      <div class="modal-header">' +
                '        <button type="button" class="close" data-dismiss="modal">&times;</button>' +
                '        <h4 class="modal-title">Payment Details</h4>' +
                '      </div>' +
                '      <div class="modal-body" id="pd_payment_details_body"></div>' +
                '      <div class="modal-footer">' +
                '        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
                '      </div>' +
                '    </div>' +
                '  </div>' +
                '</div>'
            ).appendTo('body');
        }

        $('#pd_payment_details_body').html(body);
        $modal.modal('show');

        return false;
    });

// #5: the same treatment for the Customer button.
$(document).off('click.pdCustomerName')
    .on('click.pdCustomerName', '.pd-customer-name-btn', function () {
        var name = $(this).data('customer-name');

        if (!name) {
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Customer',
                text: String(name),
                confirmButtonText: 'OK'
            });
        } else {
            alert('Customer: ' + name);
        }
    });
</script>

<style>
#pump_operators_payment_summary_table td:first-child,
#pump_operators_payment_summary_table th:first-child {
    min-width: 110px !important;
    width: 110px !important;
    overflow: visible !important;
}
#pump_operators_payment_summary_table_wrapper,
#pump_operators_payment_summary_table,
.payment-summary-horizontal-scroll {
    overflow: visible !important;
}
/*
 |-----------------------------------------------------------------------------
 | The Action menu opens instantly.
 |-----------------------------------------------------------------------------
 |
 | Reported as slow to expand. There is no javascript delay on it, so the pause
 | comes from the browser: the menu is laid out and painted only at the moment
 | it is shown, and any inherited transition or fade is animated first.
 |
 |   transition/animation: none  - nothing to wait for; Bootstrap and several
 |                                 themes fade dropdowns in by default
 |   will-change: transform      - the browser prepares a layer in advance
 |                                 rather than at click time
 |   contain: layout             - the menu's layout is worked out on its own,
 |                                 not by re-measuring the whole table, which is
 |                                 what makes it slow on a long grid
 |
 | Appearance is unchanged; only the delay before it appears is removed.
 */
.pd-payment-action-dropdown .dropdown-menu,
.pd-payment-action-dropdown.open .dropdown-menu {
    transition: none !important;
    animation: none !important;
    will-change: transform;
    contain: layout;
}

.pd-payment-action-dropdown .dropdown-menu {
    z-index: 99999 !important;
}

#pump_operators_payment_summary_table th:nth-child(9),
#pump_operators_payment_summary_table td:nth-child(9){
    width:170px !important;
    min-width:170px !important;
    max-width:260px !important;
}
#pump_operators_payment_summary_table th:nth-child(11),
#pump_operators_payment_summary_table td:nth-child(11){
    width:60px !important;
    min-width:60px !important;
    max-width:70px !important;
}
#pump_operators_payment_summary_table th:nth-child(13),
#pump_operators_payment_summary_table td:nth-child(13){
    width:68px !important;
    min-width:68px !important;
    max-width:74px !important;
}
.pd-note-btn,
.pd-payment-note-view{
    background:#f59e0b !important;
    border-color:#d97706 !important;
    color:#ffffff !important;
    font-weight:700 !important;
    border-radius:12px !important;
}
#footer_payment_summary_breakdown{
    margin-top:4px !important;
    font-size:12px !important;
    line-height:1.6 !important;
    color:#7c2d12 !important;
    white-space:normal !important;
}
#footer_payment_summary_breakdown .pd-summary-breakdown-item{
    display:inline-block !important;
    margin-left:10px !important;
}

</style>


<style>

#payment_summarys .box-body,
#payment_summarys .box-body .row {
    overflow: visible !important;
}
#payment_summarys .form-group {
    margin-bottom: 10px !important;
}
#payment_summarys label {
    font-weight: 700 !important;
    color: #334155 !important;
}
#pump_operators_payment_summary_table_wrapper {
    overflow: visible !important;
}
#pump_operators_payment_summary_table th,
#pump_operators_payment_summary_table td {
    white-space: nowrap !important;
    vertical-align: middle !important;
}
#pump_operators_payment_summary_table .btn[disabled],
#pump_operators_payment_summary_table .disabled {
    opacity: .55 !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
}
@media (max-width: 768px) {
    #payment_summarys .col-md-2,
    #payment_summarys .col-md-3 {
        width: 100% !important;
        float: none !important;
    }
}

/* IS1471-001: compact payment summary columns and stable total row */
#pump_operators_payment_summary_table{
    table-layout: fixed !important;
    width: 100% !important;
}
#pump_operators_payment_summary_table th:nth-child(1),
#pump_operators_payment_summary_table td:nth-child(1){width:78px !important; max-width:78px !important;}
#pump_operators_payment_summary_table th:nth-child(5),
#pump_operators_payment_summary_table td:nth-child(5){width:105px !important; max-width:105px !important;}
#pump_operators_payment_summary_table th:nth-child(6),
#pump_operators_payment_summary_table td:nth-child(6){width:70px !important; max-width:70px !important;}
#pump_operators_payment_summary_table th:nth-child(7),
#pump_operators_payment_summary_table td:nth-child(7){width:92px !important; max-width:92px !important;}
#pump_operators_payment_summary_table th:nth-child(8),
#pump_operators_payment_summary_table td:nth-child(8){width:248px !important; max-width:248px !important;}
#pump_operators_payment_summary_table th:nth-child(12),
#pump_operators_payment_summary_table td:nth-child(12){width:110px !important; max-width:110px !important; text-align:right !important;}
#pump_operators_payment_summary_table th,
#pump_operators_payment_summary_table td{
    overflow:hidden !important;
    text-overflow:ellipsis !important;
}

/* =====================================================================
   MA-002 (IS-1929): the Action dropdown was being clipped.

   The rule above sets overflow:hidden on EVERY cell so long values
   ellipsis instead of stretching the column. That is right for text - and
   it also clips the Action dropdown inside its own 170px cell, which is
   why Edit appears "almost hidden behind".

   z-index cannot help: a menu is clipped by its overflow ancestor no
   matter how high it is stacked. The rule has to be lifted from the cell
   that holds the menu.

   Lifted only for the ACTION cell, and only while the dropdown is OPEN,
   so every other cell keeps its ellipsis behaviour and the action column
   does not stretch when the menu is closed.
   ===================================================================== */
#pump_operators_payment_summary_table td:has(.pd-payment-action-dropdown.open),
#pump_operators_payment_summary_table td.pd-action-open{
    overflow: visible !important;
}

/* The menu itself sits above the table and its sticky header. */
.pd-payment-action-dropdown .dropdown-menu{
    z-index: 99999 !important;
}

/* Make the trigger itself unmistakable rather than a faint icon. */
.pd-payment-action-dropdown > .dropdown-toggle{
    opacity: 1 !important;
    visibility: visible !important;
}

/* A locked row still shows the item, greyed, so it is obvious the action
   exists and why it cannot be used. */
.pd-payment-action-dropdown .dropdown-menu > li.disabled > a{
    color: #9aa5b1 !important;
    cursor: not-allowed;
}
#pump_operators_payment_summary_table tfoot tr.footer-total td{
    background:#fff7ed !important;
    border-top:2px solid #fb923c !important;
    font-weight:900 !important;
}
#footer_payment_summary_amount{
    display:inline-block !important;
    min-width:100px !important;
    text-align:right !important;
}

.payment-summary-horizontal-scroll{overflow-x:auto !important; -webkit-overflow-scrolling:touch;}


/* IS1472 consolidated: two-line headers, compact shift no, and bottom horizontal scroll */
#pump_operators_payment_summary_table thead th{
    white-space:normal !important;
    text-align:center !important;
    line-height:1.15 !important;
    vertical-align:middle !important;
}
#pump_operators_payment_summary_table th:nth-child(6),
#pump_operators_payment_summary_table td:nth-child(6){
    width:42px !important;
    max-width:42px !important;
    min-width:42px !important;
}
.payment-summary-horizontal-scroll{
    overflow-x:auto !important;
    overflow-y:visible !important;
    -webkit-overflow-scrolling:touch !important;
    padding-bottom:10px !important;
}
.payment-summary-horizontal-scroll table{
    min-width:1180px !important;
}



/* PETROPD_PAY_SUM_TABLE_001: Payment Summary table column redesign */
#pump_operators_payment_summary_table{
    table-layout:auto !important;
    width:auto !important;
    min-width:1320px !important;
}
#pump_operators_payment_summary_table th,
#pump_operators_payment_summary_table td{
    white-space:nowrap !important;
    vertical-align:middle !important;
}
/* 2nd, 3rd and 4th columns auto expand with data */
#pump_operators_payment_summary_table th:nth-child(2),
#pump_operators_payment_summary_table td:nth-child(2),
#pump_operators_payment_summary_table th:nth-child(3),
#pump_operators_payment_summary_table td:nth-child(3),
#pump_operators_payment_summary_table th:nth-child(4),
#pump_operators_payment_summary_table td:nth-child(4){
    width:auto !important;
    min-width:max-content !important;
    max-width:none !important;
    overflow:visible !important;
    text-overflow:clip !important;
}
/* Shift number: reduce width */
#pump_operators_payment_summary_table th:nth-child(6),
#pump_operators_payment_summary_table td:nth-child(6){
    width:28px !important;
    min-width:28px !important;
    max-width:28px !important;
    text-align:center !important;
}
/* Payment Types/Terms: reduce width */
#pump_operators_payment_summary_table th:nth-child(8),
#pump_operators_payment_summary_table td:nth-child(8){
    width:54px !important;
    min-width:54px !important;
    max-width:54px !important;
}
/* Slip No, Order No and Amount auto expand with data */
#pump_operators_payment_summary_table th:nth-child(10),
#pump_operators_payment_summary_table td:nth-child(10),
#pump_operators_payment_summary_table th:nth-child(11),
#pump_operators_payment_summary_table td:nth-child(11),
#pump_operators_payment_summary_table th:nth-child(12),
#pump_operators_payment_summary_table td:nth-child(12){
    width:auto !important;
    min-width:86px !important;
    max-width:none !important;
    overflow:visible !important;
    text-overflow:clip !important;
}
#pump_operators_payment_summary_table th:nth-child(12),
#pump_operators_payment_summary_table td:nth-child(12){
    text-align:right !important;
}
/* Note column: compact button only */
#pump_operators_payment_summary_table th:nth-child(13),
#pump_operators_payment_summary_table td:nth-child(13){
    width:55px !important;
    min-width:55px !important;
    max-width:55px !important;
    text-align:center !important;
    overflow:visible !important;
}
.pd-note-btn{
    padding:3px 8px !important;
    font-size:11px !important;
    line-height:1.2 !important;
}
/* Edited By column: increase width */
#pump_operators_payment_summary_table th:nth-child(14),
#pump_operators_payment_summary_table td:nth-child(14){
    width:150px !important;
    min-width:150px !important;
    max-width:none !important;
    overflow:visible !important;
    text-overflow:clip !important;
}
.payment-summary-horizontal-scroll{
    overflow-x:auto !important;
    overflow-y:visible !important;
}

/* PETROPD-PAYMENT-SUMMARY-UI-029: requested final column widths and date visibility */
#pump_operators_payment_summary_table th.pd-col-date,
#pump_operators_payment_summary_table td.pd-col-date,
#pump_operators_payment_summary_table th:nth-child(2),
#pump_operators_payment_summary_table td:nth-child(2){
    min-width:115px !important;
    width:115px !important;
    max-width:130px !important;
    overflow:visible !important;
    text-overflow:clip !important;
    color:#1f2937 !important;
}
#pump_operators_payment_summary_table th.pd-col-customer,
#pump_operators_payment_summary_table td.pd-col-customer{
    min-width:270px !important;
    width:270px !important;
    max-width:360px !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
}
#pump_operators_payment_summary_table th.pd-col-slip,
#pump_operators_payment_summary_table td.pd-col-slip{
    min-width:14px !important;
    width:14px !important;
    max-width:16px !important;
    text-align:center !important;
}
#pump_operators_payment_summary_table th.pd-col-order,
#pump_operators_payment_summary_table td.pd-col-order{
    min-width:65px !important;
    width:65px !important;
    max-width:78px !important;
    text-align:center !important;
}
#pump_operators_payment_summary_table th.pd-col-note,
#pump_operators_payment_summary_table td.pd-col-note{
    min-width:42px !important;
    width:42px !important;
    max-width:46px !important;
    text-align:center !important;
}
#pump_operators_payment_summary_table .pd-note-btn,
#pump_operators_payment_summary_table .pd-payment-note-view{
    padding:5px 11px !important;
    font-size:12px !important;
    line-height:1.25 !important;
    border-radius:14px !important;
}
#pump_operators_payment_summary_table{
    min-width:1250px !important;
}



#pump_operators_payment_summary_table th.pd-col-customer,
#pump_operators_payment_summary_table td.pd-col-customer,
#pump_operators_payment_summary_table th:nth-child(9),
#pump_operators_payment_summary_table td:nth-child(9){
    min-width:270px !important;
    width:270px !important;
    max-width:420px !important;
}
#pump_operators_payment_summary_table th.pd-col-slip,
#pump_operators_payment_summary_table td.pd-col-slip,
#pump_operators_payment_summary_table th:nth-child(10),
#pump_operators_payment_summary_table td:nth-child(10){
    min-width:14px !important;
    width:14px !important;
    max-width:16px !important;
    text-align:center !important;
}
#pump_operators_payment_summary_table th.pd-col-order,
#pump_operators_payment_summary_table td.pd-col-order,
#pump_operators_payment_summary_table th:nth-child(11),
#pump_operators_payment_summary_table td:nth-child(11){
    min-width:56px !important;
    width:56px !important;
    max-width:64px !important;
    text-align:center !important;
}
#pump_operators_payment_summary_table th.pd-col-note,
#pump_operators_payment_summary_table td.pd-col-note,
#pump_operators_payment_summary_table th:nth-child(13),
#pump_operators_payment_summary_table td:nth-child(13){
    min-width:44px !important;
    width:44px !important;
    max-width:50px !important;
    text-align:center !important;
}
#pump_operators_payment_summary_table .pd-note-btn,
#pump_operators_payment_summary_table .pd-payment-note-view{
    padding:6px 14px !important;
    font-size:12px !important;
    line-height:1.25 !important;
    border-radius:16px !important;
    background:#f59e0b !important;
    border-color:#f59e0b !important;
    color:#ffffff !important;
    font-weight:700 !important;
}
#pump_operators_payment_summary_table th.pd-col-amount,
#pump_operators_payment_summary_table td.pd-col-amount,
#pump_operators_payment_summary_table td .amount{
    text-align:right !important;
}
#pump_operators_payment_summary_table tfoot tr.footer-total td,
#pump_operators_payment_summary_table tfoot tr.footer-breakdown td{
    background:#fff7ed !important;
    border-top:2px solid #fb923c !important;
    font-weight:900 !important;
}
#footer_payment_summary_amount{
    display:block !important;
    min-width:120px !important;
    text-align:right !important;
    color:#7f1d1d !important;
}
#footer_payment_summary_breakdown{
    display:block !important;
    margin-top:8px !important;
    font-size:14px !important;
    line-height:1.6 !important;
    color:#0284c7 !important;
    white-space:normal !important;
}
#footer_payment_summary_breakdown .pd-summary-breakdown-row{
    display:grid !important;
    grid-template-columns:70px 1fr !important;
    column-gap:10px !important;
    max-width:230px !important;
    margin-left:auto !important;
}
#footer_payment_summary_breakdown .pd-summary-breakdown-label{
    text-align:right !important;
    font-weight:800 !important;
}
#footer_payment_summary_breakdown .pd-summary-breakdown-value{
    text-align:right !important;
    font-weight:800 !important;
}



#pump_operators_payment_summary_table tfoot tr.footer-total td,
#pump_operators_payment_summary_table tfoot tr.footer-breakdown td{
    background:#fff7ed !important;
    border-top:2px solid #fb923c !important;
    font-weight:900 !important;
}
#footer_payment_summary_amount{
    display:inline-block !important;
    min-width:120px !important;
    text-align:right !important;
    color:#7f1d1d !important;
}
#footer_payment_summary_breakdown{
    display:block !important;
    width:100% !important;
    text-align:right !important;
    color:#0284c7 !important;
    white-space:nowrap !important;
    line-height:1.8 !important;
}
#footer_payment_summary_breakdown .pd-summary-breakdown-item{
    display:inline-block !important;
    margin-left:24px !important;
}

</style>

<style>
/* IS1666: Slip No column reduced by 60%. */
#pump_operators_payment_summary_table th:nth-child(10),
#pump_operators_payment_summary_table td:nth-child(10) {
    width: 34px !important;
    min-width: 34px !important;
    max-width: 34px !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    text-align: center !important;
}



/*
 | Customer column.
 |
 | Placed LAST in this stylesheet on purpose. Earlier rules pin columns by
 | POSITION - nth-child(9) forces the customer column to 170px with a 260px
 | max-width - and a positional selector carries the same specificity as a class
 | one, so the later rule wins. Written before them, this would have had no
 | effect at all.
 */
#pump_operators_payment_summary_table th.pd-col-customer,
#pump_operators_payment_summary_table td.pd-col-customer,
#pump_operators_payment_summary_table th:nth-child(9),
#pump_operators_payment_summary_table td:nth-child(9) {
    /* +200% on the original 90px. Six columns are hidden, so the width is
       available, and the customer name is what users most need to read. */
    width: 270px !important;
    min-width: 270px !important;
    max-width: 270px !important;
    text-align: center !important;
    white-space: nowrap !important;
    overflow: hidden !important;
}
</style>

<!-- Main content -->
<section class="content">
    @component('components.filters', ['title' => __('report.filters'), 'id' => 'payment_summarys'])
        <div class="row">
            <div class="col-md-12">
                @if(empty($only_pumper))
                    <div class="col-md-2">
                        <div class="form-group">
                            {!! Form::label('payment_summary_location_id', __('purchase.business_location') . ':') !!}
                            {!! Form::select('payment_summary_location_id', $business_locations, null, [
                                'class' => 'form-control select2',
                                'placeholder' => __('petropd::lang.all'),
                                'id' => 'payment_summary_location_id',
                                'style' => 'width:100%'
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('payment_summary_date_range', __('report.date_range') . ':') !!}
                            {!! Form::text('payment_summary_date_range', @format_date('today') . ' ~ ' . @format_date('today'), [
                                'class' => 'form-control',
                                'id' => 'payment_summary_date_range',
                                'readonly'
                            ]) !!}
                        </div>
                    </div>
                @endif

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_pump_operators', __('petropd::lang.pump_operator') . ':') !!}
                        {!! Form::select('payment_summary_pump_operators', $pump_operators, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('petropd::lang.all'),
                            'id' => 'payment_summary_pump_operators',
                            'style' => 'width:100%'
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_shift_id', __('petropd::lang.shift_number') . ':') !!}
                        <select class="form-control select2" style='width:100%' id="payment_summary_shift_id">
                            @if(empty($only_pumper))
                                <option value="">@lang('lang_v1.all')</option>
                            @endif

                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}">
                                    {{ $shift->assignment_shift_number ?? $shift->shift_number ?? $shift->id }} -
                                    ({{ @format_date($shift->assignment_date ?? $shift->shift_date ?? $shift->created_at) }} to 
                                    {{ !empty($shift->closed_time) ? @format_datetime($shift->closed_time) : 'Open' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_payment_method', __('petropd::lang.payment_method') . ':') !!}
                        {!! Form::select('payment_summary_payment_method', $payment_types, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('petropd::lang.all'),
                            'id' => 'payment_summary_payment_method',
                            'style' => 'width:100%'
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_customer', __('petropd::lang.customer') . ':') !!}
                        {!! Form::select('payment_summary_customer', $customers, null, [
                            'class' => 'form-control select2',
                            'placeholder' => __('petropd::lang.all'),
                            'id' => 'payment_summary_customer',
                            'style' => 'width:100%'
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_slip_no', __('petropd::lang.slip_no') . ':') !!}
                        {!! Form::text('payment_summary_slip_no', null, [
                            'class' => 'form-control',
                            'placeholder' => 'Enter Slip No',
                            'id' => 'payment_summary_slip_no'
                        ]) !!}
                    </div>
                </div>

                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('payment_summary_order_no', __('petropd::lang.order_no') . ':') !!}
                        {!! Form::text('payment_summary_order_no', null, [
                            'class' => 'form-control',
                            'placeholder' =>'Enter order no',
                            'id' => 'payment_summary_order_no'
                        ]) !!}
                    </div>
                </div>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' =>
    __('petropd::lang.all_your_payments')])
    <div class="table-responsive payment-summary-horizontal-scroll">
        <table class="table table-bordered table-striped" id="pump_operators_payment_summary_table"
            style="width: 100%;">
            <thead>
                <tr>
                    <th>@lang('messages.action')</th>
                    <th>@lang('petropd::lang.date')</th>
                    @if(empty($only_pumper))
                    <th>@lang('petropd::lang.location')</th>
                    @endif
                    <th>@lang('petropd::lang.time')</th>
                    <th>Pump<br>Operator</th>
                    <th>Shift<br>No</th>
                    <th>Collection<br>Form No</th>
                    <th>Payment<br>Types</th>
                    <th>Customer</th>
                    <th>Slip<br>No</th>                    
                    <th>Order<br>No</th>
                    <th class="pd-col-amount text-right">@lang('petropd::lang.amount')</th>
                    @if(empty($only_pumper))
                    <th>@lang('petropd::lang.note')</th>
                    <th>Edited<br>By</th>
                    @endif
                </tr>
            </thead>

            <tfoot>
                <tr class="bg-gray font-17 footer-total">
                    <td colspan="{{ empty($only_pumper) ? 11 : 10 }}" class="text-right" style="color:brown">
                        <strong>@lang('sale.total'):</strong></td>
                    <td class="text-right pd-col-amount" id="footer_payment_summary_amount_cell" style="color:brown; min-width:120px;">
                        <span id="footer_payment_summary_amount" data-currency_symbol="true" data-orig-value="0">Rs 0.00</span>
                    </td>
                    @if(empty($only_pumper))
                    <td></td>
                    <td></td>
                    @endif
                </tr>
                <tr class="bg-gray font-17 footer-breakdown">
                    <td colspan="{{ empty($only_pumper) ? 14 : 12 }}" class="text-right">
                        <div id="footer_payment_summary_breakdown">
                            <span class="pd-summary-breakdown-item"><strong>Cash:</strong> Rs 0.00</span>
                            <span class="pd-summary-breakdown-item"><strong>Cards:</strong> Rs 0.00</span>
                            <span class="pd-summary-breakdown-item"><strong>Credit:</strong> Rs 0.00</span>
                            <span class="pd-summary-breakdown-item"><strong>Cheques:</strong> Rs 0.00</span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endcomponent


<div class="modal fade" id="pd_payment_note_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">@lang('petropd::lang.note')</h4>
            </div>
            <div class="modal-body">
                <div id="pd_payment_note_modal_body" style="white-space:pre-wrap; word-break:break-word;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>

{{--
    LA-1162 / IS1980: the Note button did nothing.

    This script used to be wrapped in @once. @once renders a block only the FIRST
    time it is reached in a single response - it is meant for a partial included
    repeatedly on one page.

    That is not the situation here. This partial is included by two different
    pages:
        pd_operators/index.blade.php:627      (the PD Operators tab)
        pd_operators/payment_summary.blade.php:31
    but the Note button markup is produced by the CONTROLLER, in
    PDPumpOperatorPaymentController::index() and ::summarypaymnetdashboard(),
    and injected into the DataTable over AJAX.

    So on any page where some earlier partial had already consumed the @once for
    this Blade block, the whole <script> - the window.petropdShowPaymentNote
    definition, the delegated handler and the modal reset - was skipped, while
    the buttons still arrived from the AJAX response with
    onclick="return window.petropdShowPaymentNote(this);" pointing at a function
    that did not exist. Clicking did nothing at all.

    This is also the only @once in the entire pd_operators view tree, which is
    what made it stand out.

    The guard below replaces it and is the correct one for this situation: it is
    evaluated by the BROWSER at run time, so the definitions are emitted whenever
    this partial renders, and simply not re-registered if they already exist. The
    handlers were already written to be re-entrant - note the .off() before each
    .on() - so a second execution was always safe.
--}}
<script type="text/javascript">
(function ($) {
    'use strict';

    // Already installed by another render of this partial on the same page.
    if (window.petropdPaymentNoteHandlerInstalled) {
        return;
    }
    window.petropdPaymentNoteHandlerInstalled = true;

    function decodePetroPdNote(encoded) {
        if (!encoded) {
            return '';
        }

        try {
            var binary = window.atob(encoded);
            if (window.TextDecoder) {
                var bytes = Uint8Array.from(binary, function (character) {
                    return character.charCodeAt(0);
                });
                return new TextDecoder('utf-8').decode(bytes);
            }

            return decodeURIComponent(Array.prototype.map.call(binary, function (character) {
                return '%' + ('00' + character.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
        } catch (error) {
            return '';
        }
    }

    /*
     * IS1752-6: expose one direct function as well as the delegated handler.
     * DataTables replaces tbody rows after every draw and some global scripts
     * stop delegated click propagation.  The inline call guarantees that the
     * saved note opens, while text() keeps the note XSS-safe.
     */
    window.petropdShowPaymentNote = function (button) {
        var $button = $(button);
        var encodedNote = $button.attr('data-note-b64') || '';
        var note = encodedNote
            ? decodePetroPdNote(encodedNote)
            : ($button.attr('data-note') || '');
        var $modal = $('#pd_payment_note_modal');

        /*
         | If the modal is not on the page, the note is still SHOWN.
         |
         | This returned false silently, so clicking Note did nothing at all and
         | there was no clue why - reported as "the Note added in the Payment Edit
         | form cannot be viewed".
         |
         | The modal markup lives in this same partial, but the Note BUTTON is
         | produced by the controller and injected over AJAX. On any page that
         | renders the table without this partial's markup, the button arrives and
         | the modal does not.
         |
         | Falling back to SweetAlert, or a plain alert, means the note is always
         | readable whatever the page. Nothing is lost when the modal is present -
         | that path is unchanged and still preferred.
         */
        if (!$modal.length) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Note',
                    text: note,
                    confirmButtonText: 'OK'
                });
            } else {
                alert(note);
            }

            return false;
        }

        if (!$modal.parent().is('body')) {
            $modal.appendTo('body');
        }

        $('#pd_payment_note_modal_body').text(note);
        $modal.modal('show');
        return false;
    };

    $(document)
        .off('click.petropdPaymentNote', '.pd-payment-note-view')
        .on('click.petropdPaymentNote', '.pd-payment-note-view', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return window.petropdShowPaymentNote(this);
        });

    $(document)
        .off('hidden.bs.modal.petropdPaymentNote', '#pd_payment_note_modal')
        .on('hidden.bs.modal.petropdPaymentNote', '#pd_payment_note_modal', function () {
            $('#pd_payment_note_modal_body').text('');
        });
})(jQuery);
</script>

</section>
<!-- /.content -->

<style>
/*
 |-----------------------------------------------------------------------------
 | Credit Sale form width.
 |-----------------------------------------------------------------------------
 |
 | Requirement: halve the width of the form, without fields overlapping, and fix
 | the right-hand side being cut off on a tablet.
 |
 | The fields sit in a CSS grid (.credit-sale-grid). Simply setting a narrower
 | width on the panel is what caused the clipping: the grid kept its original
 | column count, so the columns were squeezed past their minimum and the last one
 | ran outside the panel.
 |
 | So width and column count are changed TOGETHER:
 |   large screens   - panel at 50%, grid drops to 2 columns
 |   tablet          - panel at 100%, grid at 2 columns
 |   phone           - panel at 100%, single column
 |
 | Each column is sized with minmax(0, 1fr) rather than the default 1fr. The
 | default minimum for a grid column is auto, which refuses to shrink below the
 | widest item inside it - a long customer name or a wide select2 - and that
 | overflow is what pushed content off the right edge on a tablet. minmax(0, 1fr)
 | lets the column shrink and the field scale down with it.
 |
 | Scoped to .pumper-credit-sale-page so no other screen is affected. The rules
 | live here, in the view they style, because the original grid CSS is not in
 | this module and this file must not depend on it.
 */
/* Sizing for .pumper-credit-sale-page is set further down, in the
   "Fit the screen on ANY device" block. */
.pumper-credit-sale-page .credit-sale-form-panel {
    box-sizing: border-box;
    max-width: 100%;
}

.pumper-credit-sale-page .credit-sale-grid {
    display: grid;
    /* Column count is set further down by the auto-fit rule. */
    gap: 12px 16px;
    align-items: start;
    width: 100%;
}

/* Nothing inside may force the grid wider than its column. */
.pumper-credit-sale-page .credit-sale-grid > .credit-sale-field,
.pumper-credit-sale-page .credit-sale-field .form-group,
.pumper-credit-sale-page .credit-sale-select-control {
    min-width: 0;
    max-width: 100%;
}

.pumper-credit-sale-page .credit-sale-field .form-control,
.pumper-credit-sale-page .credit-sale-field .select2-container {
    max-width: 100%;
    box-sizing: border-box;
}

/* Long customer names must wrap rather than widen the column. */
.pumper-credit-sale-page .credit-sale-field .select2-container .select2-selection__rendered {
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
}

.pumper-credit-sale-page .credit-sale-field label {
    overflow-wrap: break-word;
    word-break: break-word;
}

/*
 |-----------------------------------------------------------------------------
 | Fit the screen on ANY device - no fixed percentage per device size.
 |-----------------------------------------------------------------------------
 |
 | Chasing this with a percentage per breakpoint kept failing, because 50% of a
 | 9" tablet is still narrower than the controls need, while 50% of a desktop is
 | far wider than necessary. The rules below size the form from the space that is
 | actually available instead of from an assumed device.
 |
 | Two things do the work:
 |
 | 1. max-width: min(100%, 460px)
 |    min() takes whichever is SMALLER. On a narrow screen that is 100% - so the
 |    form can never be wider than the screen, and sideways scrolling becomes
 |    impossible by construction. On a wide screen it is 460px, a comfortable
 |    reading width and well under half the screen.
 |
 | 2. grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr))
 |    auto-fit decides the column count from the room available: one column when
 |    narrow, two when there is genuinely space for two. Nothing is assumed about
 |    the device. The inner min(100%, 220px) stops a column ever being asked to
 |    be wider than its container, which is what caused the earlier overflow.
 |
 | The result: a 9" tablet gets a single column that fits, a phone the same, and
 | a desktop two columns at 460px - roughly 40% narrower than the previous
 | tablet layout, and it cannot overflow on a device size I have not thought of.
 */
.pumper-credit-sale-page {
    width: 100%;
    max-width: min(100%, 460px);
}

/*
 |-----------------------------------------------------------------------------
 | THE ACTUAL CAUSE OF THE SIDEWAYS SCROLLING - the modal, not the form.
 |-----------------------------------------------------------------------------
 |
 | This form is rendered inside a Bootstrap modal (see
 | actions/payments.blade.php - .pumper-credit-sale-modal / -dialog). Nothing in
 | this module sets a width on that dialog, so it falls back to Bootstrap's
 | default .modal-dialog, which is a FIXED PIXEL width - 600px, and 900px once
 | .modal-lg applies at 992px and up.
 |
 | On a 9" tablet the viewport is around 1024px, so the dialog claims 900px plus
 | its margins. That is wider than the space available once page padding is taken
 | into account, and the modal itself overflows - which is why narrowing the form
 | inside it changed nothing. The scrollbar belonged to the dialog all along.
 |
 | Constraining the dialog is what actually fixes it. calc(100vw - 24px) keeps it
 | inside the viewport on every device, and min() caps it at a sensible reading
 | width on a large screen.
 */
.pumper-credit-sale-modal .modal-dialog,
.pumper-credit-sale-dialog {
    width: auto;
    max-width: min(calc(100vw - 24px), 520px);
    margin: 12px auto;
}

.pumper-credit-sale-modal .modal-content,
.pumper-credit-sale-content {
    max-width: 100%;
    overflow-x: hidden;
}

.pumper-credit-sale-modal .modal-body,
.pumper-credit-sale-body {
    max-width: 100%;
    overflow-x: hidden;
}

/*
 |-----------------------------------------------------------------------------
 | The added-lines TABLE must fit the form, not force a sideways scroll.
 |-----------------------------------------------------------------------------
 |
 | Reported: on a tablet the form is wide enough to need the horizontal slider,
 | which means scrolling sideways to reach fields - double work for the operator.
 |
 | The form controls were already constrained. What was pushing the width out is
 | the credit sale lines TABLE below them: eight columns at their natural widths
 | are wider than the modal, so the panel scrolled and dragged the eye with it.
 |
 | table-layout:fixed makes the columns share the available width instead of
 | sizing to their content, and the cells wrap rather than demanding one line.
 | Everything stays readable - it simply uses two lines where it needs to.
 |
 | Applied up to 1199px, which covers phones and tablets. Desktop keeps the
 | natural layout, where there is room for it.
 */
@media (max-width: 1199px) {
    .pumper-credit-sale-page .credit-sale-table-scroll {
        overflow-x: hidden;
    }

    .pumper-credit-sale-page #credit_sale_table {
        table-layout: fixed;
        width: 100%;
        min-width: 0;
    }

    .pumper-credit-sale-page #credit_sale_table th,
    .pumper-credit-sale-page #credit_sale_table td {
        white-space: normal;
        overflow-wrap: break-word;
        word-break: break-word;
        padding-left: 4px;
        padding-right: 4px;
        font-size: 12px;
    }

    /* The customer name is the longest value, so it gets the most room. */
    .pumper-credit-sale-page #credit_sale_table .credit-sale-customer-column {
        width: 22%;
    }
}

/* Desktop: a little more room, still never wider than the screen. */
@media (min-width: 1200px) {
    .pumper-credit-sale-modal .modal-dialog,
    .pumper-credit-sale-dialog {
        max-width: min(calc(100vw - 48px), 760px);
    }
}

.pumper-credit-sale-page .credit-sale-grid {
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 220px), 1fr));
}

/* Desktop has room for a slightly wider form and two comfortable columns. */
@media (min-width: 1200px) {
    .pumper-credit-sale-page {
        max-width: min(50%, 720px);
    }
}

/*
 | Fallback for older browsers without min() support: a plain 100% ceiling still
 | prevents overflow, just without the pixel cap.
 */
@supports not (max-width: min(100%, 460px)) {
    .pumper-credit-sale-page {
        max-width: 100%;
    }

    .pumper-credit-sale-page .credit-sale-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}

/*
 | Last line of defence against sideways scrolling.
 |
 | select2 sets an inline PIXEL width on its container when it initialises, taken
 | from the element's width at that moment. If the form was wider then - for
 | example initialised while hidden on another tab - that stale pixel width stays
 | and pushes the layout past the screen edge no matter what the grid says.
 | Forcing it back to 100% overrides that inline value.
 */
.pumper-credit-sale-page .select2-container {
    width: 100% !important;
    max-width: 100% !important;
}

.pumper-credit-sale-page input,
.pumper-credit-sale-page select,
.pumper-credit-sale-page textarea,
.pumper-credit-sale-page table {
    max-width: 100%;
    box-sizing: border-box;
}

/* Wide tables inside the form scroll on their own rather than widening the page. */
.pumper-credit-sale-page .table-responsive {
    max-width: 100%;
    overflow-x: auto;
}
</style>

<div class="pumper-credit-sale-page">
    <section class="credit-sale-form-panel" aria-label="@lang('pumperdashboard::lang.credit_sale')">
        <div class="credit-sale-grid">
            <div class="credit-sale-field" data-credit-field="customer">
                <div class="form-group">
                    {!! Form::label('credit_sale_customer_id', __('pumperdashboard::lang.customer') . ':') !!}
                    <div class="credit-sale-select-control">
                        {!! Form::select('credit_sale_customer_id', $customers, null, [
                            'class' => 'form-control credit_sale_fields credit-sale-select2',
                            'style' => 'width: 100%;',
                            'required' => true,
                            'placeholder' => __('pumperdashboard::lang.please_select'),
                        ]) !!}
                    </div>
                    <div id="customer_reconfirmed_container" class="credit-sale-confirmation" hidden>
                        <span class="credit-sale-reconfirmed-badge">Reconfirmed</span>
                    </div>
                </div>
            </div>

            <div class="credit-sale-field" data-credit-field="order-number">
                <div class="form-group">
                    {!! Form::label('order_number', __('pumperdashboard::lang.order_number')) !!}
                    @if ($business->duplicate_orders_allowed === 1)
                        {!! Form::text('order_number', 0, [
                            'class' => 'form-control credit_sale_fields order_number',
                            'placeholder' => __('pumperdashboard::lang.order_number'),
                        ]) !!}
                    @else
                        {!! Form::text('order_number', null, [
                            'class' => 'form-control credit_sale_fields order_number',
                            'placeholder' => __('pumperdashboard::lang.order_number'),
                        ]) !!}
                    @endif
                    <div id="order_reconfirmed_container" class="credit-sale-confirmation" hidden>
                        <span class="credit-sale-reconfirmed-badge">Reconfirmed</span>
                    </div>
                </div>
            </div>

            <div class="credit-sale-field" data-credit-field="customer-vehicle">
                <div class="form-group">
                    {!! Form::label('customer_reference', __('pumperdashboard::lang.select_customer_vehicle_no')) !!}
                    <div class="credit-sale-select-control">
                        {!! Form::select('customer_reference', ['No Vehicle No' => 'No Vehicle No'], 'No Vehicle No', [
                            'class' => 'form-control credit_sale_fields customer_reference credit-sale-select2',
                            'required',
                            'id' => 'customer_reference',
                            'style' => 'width: 100%',
                            'placeholder' => __('pumperdashboard::lang.please_select'),
                        ]) !!}
                    </div>
                    <div id="customer_vehicle_reconfirmed_container" class="credit-sale-confirmation" hidden>
                        <span class="credit-sale-reconfirmed-badge">Reconfirmed</span>
                    </div>
                </div>
            </div>

            <div class="credit-sale-field" data-credit-field="product">
                <div class="form-group">
                    {!! Form::label('credit_sale_product_id', __('pumperdashboard::lang.credit_sale_product') . ':') !!}
                    <div class="credit-sale-select-control">
                        {!! Form::select('credit_sale_product_id', $products, null, [
                            'class' => 'form-control credit_sale_fields credit-sale-select2',
                            'style' => 'width: 100%;',
                            'placeholder' => __('pumperdashboard::lang.please_select'),
                            'required' => true,
                        ]) !!}
                    </div>
                    <input type="hidden" id="manual_discount" value="{{ auth()->user()->can('manual_discount') ? 1 : 0 }}">
                </div>
            </div>

            <div class="credit-sale-field" data-credit-field="order-date">
                <div class="form-group">
                    {!! Form::label('order_date', __('pumperdashboard::lang.order_date')) !!}
                    {!! Form::text('order_date', null, [
                        'class' => 'form-control order_date',
                        'placeholder' => __('pumperdashboard::lang.order_date'),
                    ]) !!}
                </div>
            </div>

            <div class="credit-sale-field" data-credit-field="manual-vehicle">
                <div class="form-group">
                    {!! Form::label('customer_reference_one_time', __('pumperdashboard::lang.enter_customer_vehicle_no')) !!}
                    {!! Form::text('customer_reference_one_time', null, [
                        'class' => 'form-control customer_reference_one_time',
                        'id' => 'customer_reference_one_time',
                        'placeholder' => __('pumperdashboard::lang.enter_customer_vehicle_no'),
                    ]) !!}
                    <div id="customer_vehicle_manual_reconfirmed_container" class="credit-sale-confirmation" hidden>
                        <span class="credit-sale-reconfirmed-badge">Reconfirmed</span>
                    </div>
                </div>
            </div>

            <div class="credit-sale-field" data-credit-field="unit-price">
                <div class="form-group">
                    {!! Form::label('unit_price', __('pumperdashboard::lang.unit_price')) !!}
                    {!! Form::text('unit_price', null, [
                        'class' => 'form-control input_number unit_price',
                        'readonly',
                        'placeholder' => __('pumperdashboard::lang.unit_price'),
                    ]) !!}
                </div>
            </div>

            <div class="credit-sale-field" data-credit-field="unit-discount">
                <div class="form-group">
                    {!! Form::label('unit_discount', __('pumperdashboard::lang.unit_discount')) !!}
                    {!! Form::text('unit_discount', null, [
                        'class' => 'form-control input_number unit_discount',
                        'disabled' => true,
                        'placeholder' => __('pumperdashboard::lang.unit_discount'),
                    ]) !!}
                </div>
            </div>

            <div class="credit-sale-field" data-credit-field="quantity">
                <div class="form-group">
                    {!! Form::label('credit_sale_qty', __('pumperdashboard::lang.credit_sale_qty')) !!}
                    {!! Form::text('credit_sale_qty', null, [
                        'class' => 'form-control credit_sale_fields input_number credit_sale_qty',
                        'required' => true,
                        'placeholder' => __('pumperdashboard::lang.credit_sale_qty'),
                        'disabled' => true,
                    ]) !!}
                    <input type="hidden" name="credit_sale_qty_hidden" value="0" id="credit_sale_qty_hidden">
                </div>
            </div>

            <div class="credit-sale-field" data-credit-field="amount-before-discount">
                <div class="form-group">
                    {!! Form::label('credit_total_amount', __('pumperdashboard::lang.amount') . __('pumperdashboard::lang.before_discount_cr')) !!}
                    {!! Form::text('credit_total_amount', null, [
                        'id' => 'credit_total_amount',
                        'class' => 'form-control credit_sale_fields cust_input_number credit_total_amount',
                        'required',
                        'disabled' => true,
                        'placeholder' => __('pumperdashboard::lang.credit_total_amount'),
                    ]) !!}
                </div>
            </div>

            <div class="credit-sale-field" data-credit-field="discount-amount">
                <div class="form-group">
                    {!! Form::label('credit_discount_amount', __('pumperdashboard::lang.credit_discount_amount')) !!}
                    {!! Form::text('credit_discount_amount', null, [
                        'class' => 'form-control credit_sale_fields cust_input_number credit_discount_amount',
                        'required',
                        'disabled' => true,
                        'placeholder' => __('pumperdashboard::lang.credit_discount_amount'),
                    ]) !!}
                </div>
            </div>

            <div class="credit-sale-add-cell">
                <button type="button" class="btn credit-sale-add-button credit_sale_add">
                    <i class="fa fa-plus" aria-hidden="true"></i>
                    <span>@lang('messages.add')</span>
                </button>
            </div>

            <div class="credit-sale-hidden-fields" aria-hidden="true">
                {!! Form::text('credit_sale_amount', null, [
                    'id' => 'credit_sale_amount',
                    'class' => 'form-control credit_sale_fields cust_input_number credit_sale_amount',
                    'required',
                    'disabled' => true,
                    'placeholder' => __('pumperdashboard::lang.amount'),
                    'tabindex' => '-1',
                ]) !!}
                <input type="hidden" name="credit_sale_amount_hidden" value="0" id="credit_sale_amount_hidden">
            </div>

            <div class="credit-sale-field credit-sale-note-field">
                <div class="form-group">
                    {!! Form::label('credit_note', __('lang_v1.payment_note') . ':') !!}
                    {!! Form::textarea('credit_note', null, [
                        'class' => 'form-control cash_fields',
                        'rows' => 3,
                        'placeholder' => __('lang_v1.payment_note'),
                    ]) !!}
                </div>
            </div>
        </div>
    </section>

    <section class="credit-sale-status" aria-label="Credit status">
        <div class="credit-sale-status-field">
            <label for="credit_sale_current_outstanding">@lang('pumperdashboard::lang.current_outstanding'):</label>
            <input type="text" id="credit_sale_current_outstanding"
                class="form-control current_outstanding credit-sale-status-input"
                value="0.00" readonly aria-readonly="true">
        </div>
        <div class="credit-sale-status-field">
            <label for="credit_sale_credit_limit">@lang('pumperdashboard::lang.credit_limit'):</label>
            <input type="text" id="credit_sale_credit_limit"
                class="form-control credit_limit credit-sale-status-input"
                value="0.00" readonly aria-readonly="true">
        </div>
    </section>

    <section class="credit-sale-table-panel">
        <div class="credit-sale-table-scroll">
            <table class="table" id="credit_sale_table">
                <thead>
                    <tr>
                        <th class="credit-sale-customer-column">@lang('pumperdashboard::lang.customer')</th>
                        <th>@lang('pumperdashboard::lang.order_no')</th>
                        <th>@lang('pumperdashboard::lang.order_date')</th>
                        <th>Vehicle No</th>
                        <th>@lang('pumperdashboard::lang.product')</th>
                        <th>@lang('pumperdashboard::lang.unit_price')</th>
                        <th>@lang('pumperdashboard::lang.qty')</th>
                        <th>Sub Total</th>
                        <th class="credit-sale-discount-column">@lang('pumperdashboard::lang.discount_total')</th>
                        <th>Total</th>
                        <th>@lang('pumperdashboard::lang.action')</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" class="credit-sale-total-label">@lang('pumperdashboard::lang.total') :</td>
                        <td class="credit_sale_total credit-sale-number">0.00</td>
                        <td class="credit_tb_discount_total credit-sale-number">0.00</td>
                        <td class="credit_tbl_amount_total credit-sale-number">0.00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <input type="hidden" value="0" name="credit_sale_total" id="credit_sale_total">
    </section>

    <div class="credit-sale-final-actions">
        <button type="button" class="btn credit-sale-final-button credit_sale_finalize">
            Save
        </button>
        <button type="button" class="btn credit-sale-final-button credit-sale-print-button credit_sale_finalize_print">
            <i class="fa fa-print" aria-hidden="true"></i>
            <span>Print and Save</span>
        </button>
    </div>
</div>

<div class="modal fade" id="print_copy_selection_modal" role="dialog"
    aria-labelledby="printCopySelectionLabel" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="printCopySelectionLabel">Select Print Options</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Choose which copy to print:</label>
                    <div class="radio">
                        <label>
                            <input type="radio" name="print_copy_option" value="customer" checked>
                            Customer Copy (Default)
                        </label>
                    </div>
                    <div class="radio">
                        <label>
                            <input type="radio" name="print_copy_option" value="both">
                            Two Copies (Customer &amp; Merchant)
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm_print_btn">Print</button>
            </div>
        </div>
    </div>
</div>

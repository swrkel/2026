<div class="modal-dialog" role="document" style="width: 50%;">
    <div class="modal-content" id="ma004_transfer_modal_content" style="position: relative;">

        @php
            // When opened from Tank Management, keep the save request under the
            // Tank Management permission. The standalone List Tank Transfer page
            // keeps its own route and its own separate page permission.
            $embeddedTankTransfer = request()->boolean('petrogeneral_embedded_tank_transfer_create');
            $transferStoreUrl = $embeddedTankTransfer
                ? url('/petro-general/tank-management')
                : route('petrogeneral.tank_transfer.store');
        @endphp

        {!! Form::open(['url' => $transferStoreUrl, 'method' => 'post', 'id' =>
        'transfer_add_form' ]) !!}
        @if($embeddedTankTransfer)
            {!! Form::hidden('petrogeneral_embedded_tank_transfer_store', 1) !!}
        @endif

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petrogeneral::lang.add_tank_transfer' )</h4>
        </div>

        <div class="modal-body">

            {{--
                IS1996: back to the six fields in the requirement document -
                Transfer No, From Tank, From Qty, Quantity, To Tank, Date.

                MA-004 had added Location and Product dropdowns above these to
                narrow the tank lists. IS1996 asks for them to go, so they have
                been removed from the form.

                The rules they enforced have NOT gone. store() still rejects a
                transfer whose two tanks sit at different locations or hold
                different products, and still refuses one larger than the source
                tank holds. Those checks were always server-side; the dropdowns
                only stopped an invalid pair being offered in the first place.
                With every tank now listed, an invalid pair is selectable again -
                so instead of silently moving stock across locations, the save is
                refused with a message naming the reason.
            --}}
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('transfer_no', __( 'petrogeneral::lang.transfer_no' ) . ':*') !!}
                        {{-- Display only, and deliberately not a posting field: the stored
                             number is generated inside the save transaction. --}}
                        {!! Form::text('transfer_no_display', $transfer_no, ['class' => 'form-control', 'readonly', 'disabled', 'style' => 'width: 100%;']); !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('from_tank', __( 'petrogeneral::lang.from_tank' ) . ':*') !!}
                        {!! Form::select('from_tank', $tank_numbers, null , ['class' => 'form-control add_from_tank select2', 'id' => 'petrogeneral_transfer_from_tank', 'required', 'placeholder' => __(
                        'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('from_qty', __( 'petrogeneral::lang.from_qty' ) . ':*') !!}
                        {!! Form::text('from_qty', null, ['class' => 'form-control', 'id' => 'from_qty', 'disabled',
                        'placeholder' => __(
                        'petrogeneral::lang.from_qty' ) ]); !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('quantity', __( 'petrogeneral::lang.quantity' ) . ':*') !!}
                        {!! Form::text('quantity', null, ['class' => 'form-control', 'id' => 'ma004_transfer_quantity', 'required',
                        'placeholder' => __(
                        'petrogeneral::lang.quantity' ) ]); !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('to_tank', __( 'petrogeneral::lang.to_tank' ) . ':*') !!}
                        {!! Form::select('to_tank', $tank_numbers, null , ['class' => 'form-control add_to_tank select2', 'id' => 'petrogeneral_transfer_to_tank', 'required', 'placeholder' => __(
                        'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('date', __( 'petrogeneral::lang.date' ) . ':*') !!}
                        {!! Form::text('date', date('m/d/Y'), ['class' => 'form-control fuel_tank_date',
                        'required', 'placeholder' => __(
                        'petrogeneral::lang.date' ) ]); !!}
                    </div>
                </div>
            </div>

            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary add_fuel_tank_btn">@lang( 'messages.save' )</button>

                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}

        </div><!-- /.modal-body -->

        {{-- MA004: confirmation step, kept - the requirement document asked for a
             Yes/No review popup and IS1996 changes only which fields the form
             shows. Rendered as an overlay inside this modal rather than a second
             Bootstrap modal, because nesting modals in this theme leaves an
             orphaned backdrop. --}}
        {{-- Confirmation popup, HALF the previous width (11 Aug 2026).
     It used to stretch the full width of the Add modal (left:0/right:0).
     It is now a centred panel at 50% of that, so the two columns inside it
     are half as wide. Height still fills the modal so the panel keeps
     covering the form behind it. --}}
        <div id="ma004_transfer_confirm" style="display: none; position: absolute !important; top: 0 !important; bottom: 0 !important; left: 25% !important; right: 25% !important; width: 50% !important; background: #fff !important; z-index: 1060 !important; box-shadow: 0 0 24px rgba(0,0,0,.25) !important;">
            <div class="modal-header">
                <h4 class="modal-title">@lang( 'petrogeneral::lang.confirm_tank_transfer' )</h4>
            </div>
            <div class="modal-body">
                <table class="table table-condensed">
                    <tbody>
                        <tr><th style="width: 40%;">@lang( 'petrogeneral::lang.from_tank' )</th><td id="ma004_confirm_from"></td></tr>
                        <tr><th>@lang( 'petrogeneral::lang.to_tank' )</th><td id="ma004_confirm_to"></td></tr>
                        <tr><th>@lang( 'petrogeneral::lang.quantity' )</th><td id="ma004_confirm_qty"></td></tr>
                        <tr><th>@lang( 'petrogeneral::lang.date' )</th><td id="ma004_confirm_date"></td></tr>
                    </tbody>
                </table>
            </div>
            {{-- Yes bottom-left, No bottom-right, per the requirement document. This
                 is the reverse of the usual order here, and the global stylesheets
                 float modal-footer buttons right, so the placement is forced inline. --}}
            <div class="modal-footer" style="display: flex !important; justify-content: space-between !important; text-align: left !important;">
                <button type="button" class="btn btn-primary" id="ma004_confirm_yes" style="float: none !important;">@lang( 'petrogeneral::lang.yes' )</button>
                <button type="button" class="btn btn-default" id="ma004_confirm_no" style="float: none !important;">@lang( 'petrogeneral::lang.no' )</button>
            </div>
        </div>

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

    <script id="s575-petrogeneral-tank-transfer-dropdowns">
        (function ($) {
            'use strict';

            var $form = $('#transfer_add_form');
            var $from = $form.find('#petrogeneral_transfer_from_tank');
            var $to = $form.find('#petrogeneral_transfer_to_tank');

            var tanks = @json($tank_numbers);
            var tankBalances = @json($tank_bals);
            var placeholder = @json(__('petrogeneral::lang.please_select'));

            // Every tank is listed. To Tank simply omits whichever tank is
            // currently chosen as From Tank, so the same tank cannot be both.
            function addToOptions(excludedId, selectedId) {
                $to.empty().append($('<option>', { value: '', text: placeholder }));
                $.each(tanks, function (id, label) {
                    if (String(id) === String(excludedId || '')) return;
                    $to.append($('<option>', { value: id, text: label }));
                });

                if (selectedId && $to.find('option[value="' + selectedId + '"]').length) {
                    $to.val(String(selectedId));
                } else {
                    $to.val('');
                }
                $to.trigger('change.select2');
            }

            function showFromBalance() {
                var fromId = $from.val();
                var bal = fromId ? (tankBalances[fromId] || 0) : '';
                if (typeof __write_number === 'function' && bal !== '') {
                    __write_number($form.find('#from_qty'), bal);
                } else {
                    $form.find('#from_qty').val(bal);
                }
            }

            $form.find('.select2').each(function () {
                var $select = $(this);
                if ($select.data('select2')) $select.select2('destroy');
                $select.select2({ width: '100%', dropdownParent: $form.closest('.modal') });
            });
            $form.find('.fuel_tank_date').datepicker();

            $from.off('change.s575PetroGeneral').on('change.s575PetroGeneral', function () {
                showFromBalance();
                addToOptions($(this).val(), $to.val());
            });

            addToOptions($from.val(), $to.val());
            showFromBalance();

            /*
             * Warn while typing, not on save.
             *
             * store() still refuses an over-transfer server-side - that guard is the
             * real protection and is unchanged. This only brings the SAME message
             * forward to the moment the quantity is entered, so the user is not told
             * after pressing Save.
             *
             * The message text comes from the same lang key the server uses, so the
             * two cannot drift apart.
             */
            var $qty = $form.find('#ma004_transfer_quantity');
            var $save = $form.find('button[type="submit"]');
            var insufficientTemplate = @json(__('petrogeneral::lang.transfer_insufficient_balance', ['tank' => '__TANK__', 'available' => '__AVAILABLE__']));
            var lastWarned = null;

            function availableForSelectedTank() {
                var fromId = $from.val();

                if (!fromId) {
                    return null;
                }

                var bal = parseFloat(tankBalances[fromId]);

                return isNaN(bal) ? null : bal;
            }

            function checkQuantity(showToast) {
                var available = availableForSelectedTank();
                var entered = parseFloat(String($qty.val() || '').replace(/,/g, ''));

                if (available === null || isNaN(entered) || entered <= 0) {
                    $qty.closest('.form-group').removeClass('has-error');
                    $save.prop('disabled', false);
                    lastWarned = null;
                    return true;
                }

                if (entered > available) {
                    $qty.closest('.form-group').addClass('has-error');
                    $save.prop('disabled', true);

                    if (showToast && lastWarned !== entered) {
                        lastWarned = entered;
                        toastr.error(
                            insufficientTemplate
                                .replace('__TANK__', $from.find('option:selected').text())
                                .replace('__AVAILABLE__', available)
                        );
                    }

                    return false;
                }

                $qty.closest('.form-group').removeClass('has-error');
                $save.prop('disabled', false);
                lastWarned = null;
                return true;
            }

            // keyup covers typing; change covers paste and spinner clicks.
            $qty.off('input.ma004 change.ma004').on('input.ma004 change.ma004', function () {
                checkQuantity(true);
            });

            // Re-check when the source tank changes - the same quantity may now be too much.
            $from.on('change.ma004Qty', function () {
                lastWarned = null;
                checkQuantity(true);
            });

            var $confirm = $('#ma004_transfer_confirm');
            var confirmed = false;

            $form.off('submit.ma004').on('submit.ma004', function (e) {
                if (confirmed) return true;

                if (this.checkValidity && !this.checkValidity()) return true;

                e.preventDefault();

                // Belt and braces: the Save button is already disabled, but a quantity
                // pasted straight before submit must not slip through to the popup.
                if (!checkQuantity(true)) {
                    return false;
                }

                $('#ma004_confirm_from').text($from.find('option:selected').text());
                $('#ma004_confirm_to').text($to.find('option:selected').text());
                $('#ma004_confirm_qty').text($form.find('#ma004_transfer_quantity').val());
                $('#ma004_confirm_date').text($form.find('.fuel_tank_date').val());

                $confirm.css('display', 'block');
                return false;
            });

            $('#ma004_confirm_no').off('click.ma004').on('click.ma004', function () {
                $confirm.css('display', 'none');
            });

            $('#ma004_confirm_yes').off('click.ma004').on('click.ma004', function () {
                confirmed = true;
                $(this).prop('disabled', true);
                $form.submit();
            });
        })(jQuery);
    </script>

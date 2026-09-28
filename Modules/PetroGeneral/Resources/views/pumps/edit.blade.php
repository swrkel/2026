{{-- Narrower pop-up (11 Aug 2026).
     The theme sizes this modal from erp-global-modal-system.css. This pins it
     to the theme's own compact width instead, which is roughly half of what it
     was rendering at. Because the two columns inside are still col-md-6, every
     field halves in width along with the dialog. --}}
<div class="modal-dialog" role="document" style="width: min(480px, calc(100vw - 36px)) !important; max-width: calc(100vw - 36px) !important; margin: 28px auto !important;">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\PumpController@update', $pump->id), 'method' =>
        'put',
        'id' =>
        'add_pumps_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petrogeneral::lang.add_pump' )</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('pump_no', __( 'petrogeneral::lang.pump_no' ) . ':*') !!}
                            {!! Form::text('pump_no', $pump->pump_no, ['class' => 'form-control pump_no', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.pump_no' ) ]); !!}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('pump_name', __( 'petrogeneral::lang.pump_name' ) . ':*') !!}
                            {!! Form::text('pump_name', $pump->pump_name, ['class' => 'form-control pump_name',
                            'required',
                            'placeholder' => __(
                            'petrogeneral::lang.pump_name' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('location_id', __( 'petrogeneral::lang.branch' ) . ':*') !!}
                            {!! Form::select('location_id', $locations, $pump->location_id , ['class' => 'form-control
                            select2
                            fuel_tank_location', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('product_id', __( 'petrogeneral::lang.product' ) . ':*') !!}
                            {!! Form::select('product_id', $products, $pump->product_id , ['class' => 'form-control
                            select2
                            ', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('installation_date', __( 'petrogeneral::lang.installation_date' ) . ':*') !!}
                            {!! Form::text('installation_date',  \Carbon::parse($pump->transaction_date)->format('m/d/Y'),
                            ['class' => 'form-control
                            fuel_tank_date',
                            'required', 'placeholder' => __(
                            'petrogeneral::lang.installation_date' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('transaction_date', __( 'petrogeneral::lang.transaction_date' ) . ':*') !!}
                            {{-- Defaults to TODAY (11 Aug 2026), not the date stored on the pump.
                                 An edit is a change being made today, so the transaction date
                                 starts at today and the user overrides it if they need to. The
                                 pump's original date is still shown in Pump Installed above. --}}
                            {!! Form::text('transaction_date', date('m/d/Y'),
                            ['class' => 'form-control fuel_tank_date',
                            'required', 'placeholder' => __(
                            'petrogeneral::lang.transaction_date' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('bulk_sale_meter', __( 'petrogeneral::lang.bulk_sale_meter' ) . ':*') !!}
                            {!! Form::select('bulk_sale_meter', ['0' => 'No', '1' => 'Yes'], $pump->bulk_sale_meter ,
                            ['class' =>
                            'form-control select2
                            bulk_sale_meter', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('meter_value', __( 'petrogeneral::lang.meter_value' ) . ':*') !!}
                            {!! Form::text('meter_value', $pump->starting_meter, ['class' => 'form-control meter_value input_number', 'readonly',
                            'required', 'placeholder' => __(
                            'petrogeneral::lang.meter_value' ) ]); !!}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            {!! Form::label('fuel_tank_id', __( 'petrogeneral::lang.fuel_tank' ) . ':*') !!}
                            {!! Form::select('fuel_tank_id', $tanks, $pump->fuel_tank_id , ['class' => 'form-control select2
                            ', 'required',
                            'placeholder' => __(
                            'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                        </div>
                    </div>
                    {{-- MA-002 (S-609 #4): Petro PD only. Markup copied from
                         Petro's pumps/create.blade.php so the three modules
                         present this flag identically. --}}
                    <div class="col-md-12">
                        <div class="checkbox">
                            <label>{!! Form::checkbox('is_petro_pd_only', 1, old('is_petro_pd_only', !empty($pump->is_petro_pd_only))) !!} <strong>Petro PD only</strong></label>
                            <p class="help-block">Exclude this pump and its data from Petro Direct, Petro and Settlement SW settlements.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary add_fuel_tank_btn">@lang( 'messages.save' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

    <script>
        /*
         * IS1993 (10 Aug 2026): same defect as pumps/create.blade.php - see the
         * full note there.
         *
         * The Product select here carries 'form-control select2' and not
         * .fuel_tank_product, so the line that used to initialise it matched
         * nothing. Only Add was reported because on Edit the saved product is
         * already shown in the closed field, so nobody needs to open the list -
         * but the dropdown was just as unusable once opened.
         */
        (function () {
            var $form = $('#add_pumps_form');
            var $modal = $form.closest('.modal');

            // Scoped to this form - see the note in pumps/create.blade.php.
            function initPumpSelect2(selector) {
                var $fields = $form.find(selector);

                $fields.each(function () {
                    var $field = $(this);

                    if ($field.data('select2')) {
                        $field.select2('destroy');
                    }

                    /*
                     * Anchor the panel to the field's OWN form-group (11 Aug 2026).
                     *
                     * It was anchored to the whole .modal. Select2 positions its panel
                     * absolutely against the offset parent of whatever it is appended
                     * to, so with the modal as the parent the panel was measured
                     * against the dialog rather than the field - which is why it
                     * rendered detached near the top of the modal, full width, instead
                     * of directly under the box that was clicked.
                     *
                     * The form-group is given position:relative so it becomes that
                     * offset parent, and the panel then opens flush under its own
                     * field at the field's own width, like every other dropdown.
                     */
                    var $dropdownParent = $field.closest('.form-group');

                    if (!$dropdownParent.length) {
                        $dropdownParent = $field.parent();
                    }

                    if ($dropdownParent.length && $dropdownParent.css('position') === 'static') {
                        $dropdownParent.css('position', 'relative');
                    }

                    $field.select2({
                        width: '100%',
                        dropdownAutoWidth: false,
                        dropdownParent: $dropdownParent
                    });
                });
            }

            function initEditPumpSelect2() {
                initPumpSelect2('select[name="location_id"]');
                initPumpSelect2('select[name="product_id"]');
                initPumpSelect2('select[name="bulk_sale_meter"]');
                initPumpSelect2('select[name="fuel_tank_id"]');
            }

            initEditPumpSelect2();

            if ($modal.length) {
                $modal.off('shown.bs.modal.pumpEditSelect2')
                      .on('shown.bs.modal.pumpEditSelect2', initEditPumpSelect2);
            }
        })();

        $('.fuel_tank_date').datepicker();
    </script>
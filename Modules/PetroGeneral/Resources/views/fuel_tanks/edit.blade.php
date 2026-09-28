{{-- Pop-up halved (11 Aug 2026).
     Was width:50% of the viewport; this is roughly half of that, and matches
     the Add/Edit Pump modal so the two screens are consistent. The fields are
     still three-per-row (col-md-4), so every field column halves in width along
     with the dialog rather than only the dialog shrinking. --}}
<div class="modal-dialog" role="document" style="width: min(480px, calc(100vw - 36px)) !important; max-width: calc(100vw - 36px) !important; margin: 28px auto !important;">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\FuelTankController@update', $fuel_tank->id), 'method' => 'put', 'id' =>
        'customer_reference_add_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petrogeneral::lang.add_fuel_tank' )</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('fuel_tank_number', __( 'petrogeneral::lang.fuel_tank_number' ) . ':*') !!}
                    {!! Form::text('fuel_tank_number', $fuel_tank->fuel_tank_number, ['class' => 'form-control
                    fuel_tank_number', 'required', 'placeholder' => __(
                    'petrogeneral::lang.fuel_tank_number' ) ]); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('location_id', __( 'petrogeneral::lang.branch' ) . ':*') !!}
                    {!! Form::select('location_id', $locations, $fuel_tank->location_id , ['class' => 'form-control
                    select2 fuel_tank_location', 'required',
                    'placeholder' => __(
                    'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('product_id', __( 'petrogeneral::lang.product' ) . ':*') !!}
                    {!! Form::select('product_id', $products, $fuel_tank->product_id , ['class' => 'form-control select2
                    fuel_tank_product', 'required',
                    'placeholder' => __(
                    'petrogeneral::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                </div>
            </div>
            <div class="clearfix"></div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('storage_volume', __( 'petrogeneral::lang.storage_volume' ) . ':*') !!}
                    {!! Form::text('storage_volume', $fuel_tank->storage_volume, ['class' => 'form-control input_number
                    storage_volume', 'required', 'placeholder' => __(
                    'petrogeneral::lang.storage_volume' ) ]); !!}
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('current_balance', __( 'petrogeneral::lang.opening_balance' ) . ':*') !!}
                    {!! Form::text('current_balance', $opening_stock, ['class' => 'form-control current_balance input_number', 'required',
                    'placeholder' => __(
                    'petrogeneral::lang.current_balance' ) ]); !!}
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('transaction_date', __( 'petrogeneral::lang.transaction_date' ) . ':*') !!}
                    {!! Form::text('transaction_date', date('m/d/Y', strtotime($fuel_tank->transaction_date)), ['class'
                    => 'form-control fuel_tank_date', 'required', 'placeholder' => __(
                    'petrogeneral::lang.transaction_date' ) ]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('bulk_tank', __( 'petrogeneral::lang.bulk_tank' ) . ':*') !!}
                    {!! Form::select('bulk_tank', ['1' => 'Yes', '0' => 'No'], $fuel_tank->bulk_tank,['class' => 'form-control bulk_tank',
                    'required', 'placeholder' => __(
                    'petrogeneral::lang.please_select' ) ]); !!}
                </div>
            </div>
            @if($tank_dip_chart_permission)
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('tank_dip_chart_id', __( 'petrogeneral::lang.sheet_name' ) . ':') !!}
                    {!! Form::select('tank_dip_chart_id', $sheet_names, $fuel_tank->tank_dip_chart_id,['class' => 'form-control tank_dip_chart_id',
                     'placeholder' => __(
                    'petrogeneral::lang.please_select' ) ]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('tank_manufacturer', __( 'petrogeneral::lang.tank_manufacturer' ) . ':') !!}
                    {!! Form::text('tank_manufacturer', $fuel_tank->tank_manufacturer, ['class' => 'form-control',
                     'placeholder' => __(
                    'petrogeneral::lang.tank_manufacturer' ) ]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('tank_capacity', __( 'petrogeneral::lang.tank_capacity' ) . ':') !!}
                    {!! Form::text('tank_capacity', $fuel_tank->tank_capacity, ['class' => 'form-control input_number',
                     'placeholder' => __(
                    'petrogeneral::lang.tank_capacity' ) ]); !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('unit_name', __( 'petrogeneral::lang.unit' ) . ':') !!}
                    {!! Form::text('unit_name', $fuel_tank->unit_name, ['class' => 'form-control input_number', 'readonly',
                     'placeholder' => __(
                    'petrogeneral::lang.unit_name' ) ]); !!}
                </div>
            </div>
            @endif
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
         * Anchor each dropdown to its OWN field (11 Aug 2026).
         *
         * These two lines called select2() with no dropdownParent, so the panel was
         * appended to <body> and positioned against the page rather than the field.
         * Inside a modal that makes the panel open detached from the box that was
         * clicked - the same fault reported on Add Pump - and it becomes more obvious
         * now the dialog is narrower.
         *
         * Each panel is now appended to the field's own .form-group, which is given
         * position:relative so it becomes the offset parent. The panel then opens
         * flush under its own field, at the field's width.
         *
         * Scoped to this modal: the plain '.fuel_tank_location' / '.fuel_tank_product'
         * selectors also match fields on other screens, so the lookup starts from this
         * form rather than the whole document.
         */
        (function () {
            var $form = $('.modal.in form, .modal form').filter(function () {
                return $(this).find('.fuel_tank_location, .fuel_tank_product').length > 0;
            }).last();

            if (!$form.length) {
                $form = $('.fuel_tank_location, .fuel_tank_product').closest('form').last();
            }

            function anchorSelect($field) {
                if ($field.data('select2')) {
                    $field.select2('destroy');
                }

                var $parent = $field.closest('.form-group');

                if (!$parent.length) {
                    $parent = $field.parent();
                }

                if ($parent.length && $parent.css('position') === 'static') {
                    $parent.css('position', 'relative');
                }

                $field.select2({
                    width: '100%',
                    dropdownAutoWidth: false,
                    dropdownParent: $parent
                });
            }

            var $scope = $form.length ? $form : $(document);

            $scope.find('.fuel_tank_location, .fuel_tank_product, select.select2').each(function () {
                anchorSelect($(this));
            });
        })();
        $('.fuel_tank_date').datepicker();

        $('#tank_dip_chart_id').change(function(){
            let id = $(this).val();

            $.ajax({
                method: 'get',
                url: '/superadmin/tank-dip-chart/get-by-id/'+id,
                data: {  },
                success: function(result) {
                    $('#tank_manufacturer').val(result.tank_manufacturer);
                    $('#tank_capacity').val(result.tank_capacity);
                    $('#unit_name').val(result.actual_name);
                },
            });
        })
    </script>
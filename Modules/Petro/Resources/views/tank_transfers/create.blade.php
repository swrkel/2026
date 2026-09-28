<div class="modal-dialog" role="document" style="width: 50%;">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\Petro\Http\Controllers\TankTransferController@store'), 'method' => 'post', 'id' =>
        'transfer_add_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petro::lang.add_tank_transfer' )</h4>
        </div>

        <div class="modal-body">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('transfer_no', __( 'petro::lang.transfer_no' ) . ':*') !!}
                        {!! Form::text('transfer_no', $transfer_no , ['class' => 'form-control', 'required','readonly', 'placeholder' => __(
                        'petro::lang.transfer_no' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('from_tank', __( 'petro::lang.from_tank' ) . ':*') !!}
                        {!! Form::select('from_tank', $tank_numbers, null , ['class' => 'form-control add_from_tank select2', 'id' => 'petro_transfer_from_tank', 'required', 'placeholder' => __(
                        'petro::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
            </div>
            <div class="row">
                
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('from_qty', __( 'petro::lang.from_qty' ) . ':*') !!}
                        {!! Form::text('from_qty', null, ['class' => 'form-control', 'disabled',
                        'placeholder' => __(
                        'petro::lang.from_qty' ) ]); !!}
                    </div>
                </div>
    
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('quantity', __( 'petro::lang.quantity' ) . ':*') !!}
                        {!! Form::text('quantity', null, ['class' => 'form-control', 'required',
                        'placeholder' => __(
                        'petro::lang.quantity' ) ]); !!}
                    </div>
                </div>
             </div>
            <div class="row">
                 <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('to_tank', __( 'petro::lang.to_tank' ) . ':*') !!}
                        {!! Form::select('to_tank', $tank_numbers, null , ['class' => 'form-control add_to_tank select2', 'id' => 'petro_transfer_to_tank', 'required', 'placeholder' => __(
                        'petro::lang.please_select' ), 'style' => 'width: 100%;']); !!}
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('date', __( 'petro::lang.date' ) . ':*') !!}
                        {!! Form::text('date', date('m/d/Y'), ['class' => 'form-control fuel_tank_date',
                        'required', 'placeholder' => __(
                        'petro::lang.date' ) ]); !!}
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

	    <script id="s575-petro-tank-transfer-dropdowns">
            (function ($) {
                'use strict';

                var $form = $('#transfer_add_form');
                var $from = $form.find('#petro_transfer_from_tank');
                var $to = $form.find('#petro_transfer_to_tank');
                var tanks = @json($tank_numbers);
                var tankBalances = @json($tank_bals);
                var placeholder = @json(__('petro::lang.please_select'));

                function addOptions($select, excludedId, selectedId) {
                    $select.empty().append($('<option>', { value: '', text: placeholder }));
                    $.each(tanks, function (id, label) {
                        if (String(id) === String(excludedId || '')) return;
                        $select.append($('<option>', { value: id, text: label }));
                    });

                    if (selectedId && $select.find('option[value="' + selectedId + '"]').length) {
                        $select.val(String(selectedId));
                    } else {
                        $select.val('');
                    }
                    $select.trigger('change.select2');
                }

                $form.find('.select2').each(function () {
                    var $select = $(this);
                    if ($select.data('select2')) $select.select2('destroy');
                    $select.select2({ width: '100%', dropdownParent: $form.closest('.modal') });
                });
                $form.find('.fuel_tank_date').datepicker();

                $from.off('change.s575Petro').on('change.s575Petro', function () {
                    var fromId = $(this).val();
                    var previousTo = $to.val();
                    if (typeof __write_number === 'function') {
                        __write_number($form.find('#from_qty'), tankBalances[fromId] || 0);
                    } else {
                        $form.find('#from_qty').val(tankBalances[fromId] || 0);
                    }
                    addOptions($to, fromId, previousTo);
                });

                addOptions($from, null, $from.val());
                addOptions($to, $from.val(), $to.val());
            })(jQuery);
	    </script>

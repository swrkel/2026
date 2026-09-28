<style>
    .pump_operator_modal .modal-dialog {
        width: min(860px, calc(100vw - 32px)) !important;
        margin: 6vh auto !important;
    }
    .pd-receive-modal {
        border-radius: 18px;
        border: 0;
        overflow: hidden;
        box-shadow: 0 22px 60px rgba(15, 23, 42, .25);
    }
    .pd-receive-modal .modal-header {
        background: linear-gradient(135deg, #2874A6 0%, #1F5FAA 100%) !important;
        color: #fff !important;
        border-bottom: 0;
        padding: 18px 22px;
    }
    .pd-receive-modal .modal-title {
        color: #fff !important;
        font-size: 22px;
        font-weight: 800;
        letter-spacing: .2px;
    }
    .pd-receive-modal .close { color: #fff; opacity: .95; text-shadow: none; }
    .pd-receive-modal .modal-body { padding: 22px; background: #f8fafc; }
    .pd-receive-panel {
        background: #fff;
        border: 1px solid #e5edf5;
        border-radius: 16px;
        padding: 16px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .08);
    }
    .pd-receive-info h4 { font-weight: 700; margin-top: 0; line-height: 1.35; }
    .pd-receive-modal .form-control.input-lg {
        height: 48px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        font-weight: 700;
        font-size: 18px;
    }
    .pd-receive-modal label { font-weight: 800; color: #334155; }
    #key_pad input { border: none; }
    #key_pad button {
        height: 68px;
        width: 68px;
        font-size: 24px;
        margin: 3px 2px;
        border: none !important;
        border-radius: 12px !important;
        box-shadow: 0 5px 12px rgba(15, 23, 42, .12);
    }
    .pd-receive-modal .modal-footer {
        border-top: 0;
        background: #fff;
        padding: 16px 22px;
    }
    .pd-receive-modal .confirm_meter_reading_btn {
        background: #2874A6 !important;
        border-color: #2874A6 !important;
        border-radius: 10px;
        font-weight: 800;
        padding: 10px 18px;
    }
    .pd-receive-modal .btn-default { border-radius: 10px; padding: 10px 18px; }
    :focus { outline: 0 !important; }
</style>
<div class="modal-dialog" role="document">
    <div class="modal-content pd-receive-modal">

        {!! Form::open(['url' =>
        route('petropd.receive-pump.confirm.store', [$pump->id]), 'method' =>
        'post',
        'id' =>
        'receive_pump_form' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petropd::lang.receive_pump' )</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12 pd-receive-panel">
                <div class="row">
                    <div class="col-md-8">

                        <div class="col-md-4 text-red pd-receive-info">
                            <h4>@lang('petropd::lang.date_and_time'): {{@format_datetime(date('Y-m-d H:i:s'))}}</h4>
                        </div>
                        <div class="col-md-4 text-red pd-receive-info">
                            <h4>@lang('petropd::lang.pump_name'): {{$pump->pump_name}}</h4>
                        </div>
                        <div class="col-md-4 text-red pd-receive-info">
                            <h4>@lang('petropd::lang.product'): {{$pump->name}}</h4>
                        </div>
                        <div class="clearfix"></div>
                        <br>

                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('starting_meter', __( 'petropd::lang.starting_meter' ) . ':*') !!}
                                {!! Form::text('starting_meter',  number_format($pump->starting_meter,3,".",""), ['class' =>
                                'form-control input-lg
                                starting_meter', 'required', 'readonly',
                                'placeholder' => __(
                                'petropd::lang.starting_meter' ) ]); !!}
                            </div>
                        </div>


                        <div class="col-md-12">
                            <div class="form-group">
                                {!! Form::label('closing_meter', __( 'petropd::lang.reconfirm_meter' ) . ':*') !!}
                                {!! Form::text('closing_meter', null, ['class' => 'form-control input-lg
                                closing_meter', 'min' => $pump->starting_meter, 'required',
                                'placeholder' => __(
                                'petropd::lang.closing_meter' ) ]); !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('status', __( 'petropd::lang.status' ) . ':*') !!} <br>
                                <input type="checkbox" checked name="status" id="toggle-two" data-toggle="toggle">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="row">
                            <div id="key_pad" tabindex="1">
                                <div class="row text-center" id="calc">
                                    <div class="calcBG col-md-12 text-center">
                                        <div class="row">
                                            <button id="7" type="button" class="btn btn-primary btn-sm"
                                                onclick="enterVal(this.id)">7</button>
                                            <button id="8" type="button" class="btn btn-primary btn-sm"
                                                onclick="enterVal(this.id)">8</button>
                                            <button id="9" type="button" class="btn btn-primary btn-sm"
                                                onclick="enterVal(this.id)">9</button>

                                        </div>
                                        <div class="row">
                                            <button id="4" type="button" class="btn btn-primary btn-sm"
                                                onclick="enterVal(this.id)">4</button>
                                            <button id="5" type="button" class="btn btn-primary btn-sm"
                                                onclick="enterVal(this.id)">5</button>
                                            <button id="6" type="button" class="btn btn-primary btn-sm"
                                                onclick="enterVal(this.id)">6</button>
                                        </div>
                                        <div class="row">
                                            <button id="1" type="button" class="btn btn-primary btn-sm"
                                                onclick="enterVal(this.id)">1</button>
                                            <button id="2" type="button" class="btn btn-primary btn-sm"
                                                onclick="enterVal(this.id)">2</button>
                                            <button id="3" type="button" class="btn btn-primary btn-sm"
                                                onclick="enterVal(this.id)">3</button>
                                        </div>
                                        <div class="row">
                                            <button id="backspace" type="button" class="btn btn-danger"
                                                onclick="enterVal(this.id)">⌫</button>
                                            <button id="0" type="button" class="btn"
                                                onclick="enterVal(this.id)">0</button>
                                            <button id="precision" type="button" class="btn btn-success"
                                                onclick="enterVal(this.id)">.</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                </div>
            </div>
            <br>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary confirm_meter_reading_btn">@lang(
                    'petropd::lang.confirm_meter_reading' )</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

            {!! Form::close() !!}
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->

    <script>
        $('#toggle-two').bootstrapToggle({
            on: 'Open',
            off: 'Close',
            width: 100,
            onstyle: 'success',
            offstyle: 'danger'
        });

        
    </script>

    <script>
        $('.confirm_meter_reading_btn').attr('disabled', true);
        $('.pump_operator_modal ').on('shown.bs.modal', function () {
            $('#closing_meter').focus();
        }) 
        $('.confirm_meter_reading_btn').click(function(){
            $('#receive_pump_form').validate();
        })
        $('#receive_pump_form').validate();
        function enterVal(val) {
            if(val === 'precision'){
                str = $('#closing_meter').val();
                str = str + '.';
                $('#closing_meter').val(str);
                return;
            }
            if(val === 'backspace'){
                str = $('#closing_meter').val().replace(',', '');
                str = str.substring(0, str.length - 1);
                $('#closing_meter').val(str);
                return;
            }
            let closing_meter = $('#closing_meter').val().replace(',', '') + val;
            $('#closing_meter').val(closing_meter);
            $('#closing_meter').focus();
            $('#closing_meter').keyup();
        };

        $('#closing_meter').keyup(function () {
            if(parseFloat($(this).val()) === parseFloat($('#starting_meter').val())){
                $('.confirm_meter_reading_btn').attr('disabled', false);
            }else{
                $('.confirm_meter_reading_btn').attr('disabled', true);
            }
        })

        $('#toggle-two').change(function () {
            if($(this).prop("checked") === false){
                $('.confirm_meter_reading_btn').attr('disabled', false);
            }else{
                $('.confirm_meter_reading_btn').attr('disabled', true);
                $('#closing_meter').trigger('change');
            }
        })

    </script>
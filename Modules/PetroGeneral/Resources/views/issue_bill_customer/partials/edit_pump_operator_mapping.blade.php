<style>
    .action-buttons-fixed {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: #fff;
        padding: 10px 30px;
        margin: -15px -15px 15px -15px;
    }

    .product-container {
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 10px;
        min-height: 50px;
        max-height: 150px;
        overflow-y: auto;
        background-color: #fff;
    }

    .product-container .badge {
        margin: 2px 3px 2px 0;
        font-size: 0.85em;
    }

    #operators-container {
        border: 1px solid #e0e0e0;
        padding: 10px;
        margin-bottom: 10px;
        max-height: 40vh;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .operator-block .remove-block {
        font-size: 20px;
        text-decoration: none;
    }

    #operators-container .operator-block {
        border-bottom: 1px solid #ddd;
        padding-top: 8px;
    }

    #operators-container .operator-block:last-child {
        border-bottom: none;
    }

    .operator-block:first-child .remove-block {
        display: none;
    }

    .modal-body {
        height: calc(100vh - 300px);
        max-height: calc(100vh - 200px);
        overflow-y: auto;
        padding-bottom: 20px;
        padding-top: 0;
    }

    .form-control#date {
        width: 150px;
    }
</style>
<div class="modal-dialog" role="document" style="width: 80%;">
    <div class="modal-content">
        {!! Form::open(['url' => action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorMappingController@update', ['pump_operator_mapping' => $pumpMaterialMapping->first()->operator_id]), 'method' =>
        'post', 'id' => 'edit_pump_operator_mapping_form' ])
        !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petrogeneral::lang.edit_pump_to_operator' )</h4>
        </div>

        <div class="modal-body">
            <div class="action-buttons-fixed">
                <div class="form-group">
                    <button type="button" id="reset-all" class="btn btn-warning">Reset</button>
                    <button type="button" id="add-operator-edit" class="btn btn-success">Add</button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('date', __( 'petrogeneral::lang.date' )) !!}
                    {!! Form::text('date', null, ['class' => 'form-control', 'id' => 'datepicker', 'placeholder' =>
                    __( 'petrogeneral::lang.date'), 'readonly']);
                    !!}
                </div>
            </div>
            <div id="operators-container" class="col-md-12">
                <div class="operator-block row" style="display:none;">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('operator_id', __( 'petrogeneral::lang.pump_operator' )) !!}
                            {!! Form::select('operator_id[]', $pump_operators, null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'placeholder' =>
                            __( 'petrogeneral::lang.please_select')]);
                            !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('pump_id', __( 'petrogeneral::lang.pump' )) !!}
                            {!! Form::select('pump_id[]', $pumps, null, ['class' => 'form-control select-multiple', 'style' => 'width:100%;', 'data-placeholder' =>
                            __( 'petrogeneral::lang.please_select'), 'multiple' => 'multiple']);
                            !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group" id="pump-products-container">
                            <label>@lang('petrogeneral::lang.product')</label>
                            <div id="pump-products-list">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-link remove-block">
                            ×
                        </button>
                    </div>
                </div>
                <div class="operator-block row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('operator_id', __( 'petrogeneral::lang.pump_operator' )) !!}
                            {!! Form::select('operator_id', $pump_operators, $firstMapping->operator_id ?? null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'placeholder' =>
                            __( 'petrogeneral::lang.please_select'), 'disabled' => 'disabled' , 'id' => 'operator_id', 'readonly']);
                            !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('pump_id', __( 'petrogeneral::lang.pump' )) !!}
                            {!! Form::select('pump_id[]', $pumps, $pumpIds, ['class' => 'form-control select-multiple', 'style' => 'width:100%;', 'data-placeholder' =>
                            __( 'petrogeneral::lang.please_select'), 'multiple' => 'multiple']);
                            !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group" id="pump-products-container">
                            <label>@lang('petrogeneral::lang.product')</label>
                            <div id="pump-products-list">
                                <div class="product-badges">
                                    @foreach($products as $product)
                                        <span class="badge badge-primary">{{ $product->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-link remove-block">
                            ×
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary"
                    id="save_issue_bill_customer_btn">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

        {!! Form::close() !!}

    </div>
</div>

<script>
    $('#datepicker').datetimepicker({
        defaultDate: moment("{{ \Carbon\Carbon::parse($firstMapping->assigned_at)->format('Y/m/d H:i') }}", "YYYY/MM/DD HH:mm")
    });
    $('.select2').select2();
    $('.select-multiple').select2({
        placeholder: function () {
            return $(this).data('placeholder');
        },
        allowClear: true,
        closeOnSelect: false
    });

    function addOperator() {
        var template = $('.operator-block:first');
        template.find('select.select2, select.select-multiple').select2('destroy');

        var block = template.clone().show();
        block.find('#pump-products-list').empty();
        $('#operators-container').prepend(block);

        block.find('select.select2').select2({placeholder: 'Please select'});
        block.find('select.select-multiple').select2({
            placeholder: function () {
                return $(this).data('placeholder');
            },
            allowClear: false,
            closeOnSelect: false
        });

        template.find('select.select2').select2({placeholder: 'Please select'});
        template.find('select.select-multiple').select2({
            placeholder: function () {
                return $(this).data('placeholder');
            },
            allowClear: false,
            closeOnSelect: false
        });
        refreshPumpOptions();
    }

    $('#add-operator-edit').on('click', addOperator);

    $('#edit_pump_operator_mapping_form').on('submit', function (e) {
        e.preventDefault();

        var hasError = false;
        var errorMsg = '';

        $('.operator-block:visible').each(function(index) {
            var operatorId = $(this).find('select.select2').val();
            var pumpIds = $(this).find('select.select-multiple').val();

            if (!operatorId || operatorId === '') {
                hasError = true;
                errorMsg = 'Please select Pump Operator for block ' + (index + 1) + '!';
                $(this).find('select.select2').next('.select2-container').addClass('border-danger');
                return false;
            }

            if (!pumpIds || pumpIds.length === 0) {
                hasError = true;
                errorMsg = 'Please select at least one Pump for block ' + (index + 1) + '!';
                $(this).find('select.select-multiple').next('.select2-container').addClass('border-danger');
                return false;
            }
        });

        if (hasError) {
            toastr.error(errorMsg);
            return false;
        }

        var data = {
            date: $('#datepicker').val(),
            mappings: []
        };

        $('.operator-block:visible').each(function() {
            var operatorId = $(this).find('select.select2').val();
            var pumpIds = $(this).find('select.select-multiple').val();

            data.mappings.push({
                operator_id: operatorId,
                pump_ids: pumpIds
            });
        });
        var url = $(this).attr('action');

        $('#save_issue_bill_customer_btn').prop('disabled', true).text('Saving...');

        $.ajax({
            url: url,
            method: 'PUT',
            data: JSON.stringify(data),
            contentType: 'application/json',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message || 'Saved successfully!');
                    $('#edit_pump_operator_mapping_form').closest('.modal').modal('hide');
                    pump_operator_mapping_table.ajax.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            complete: function() {
                $('#save_issue_bill_customer_btn').prop('disabled', false).text('@lang("messages.save")');
            }
        });
    });

    $('#reset-all').on('click', function() {
        refreshPumpOptions();
        $('.select-multiple').each(function() {
            $(this).val(null).trigger('change');
        });
    });

    function refreshPumpOptions() {
        let selectedPumps = [];

        $('.operator-block:visible').each(function () {
            let pumps = $(this).find('.select-multiple').val();
            if (pumps) {
                selectedPumps = selectedPumps.concat(pumps);
            }
        });

        $('.operator-block:visible .select-multiple').each(function () {
            let select = $(this);
            let current = select.val() ?? [];
            let allOptions = @json($pumps);
            select.empty();

            $.each(allOptions, function (pump_id, pump_name) {
                if (!current.includes(pump_id.toString()) && selectedPumps.includes(pump_id.toString())) {
                    return;
                }
                select.append(new Option(pump_name, pump_id, false, current.includes(pump_id.toString())));
            });

            select.select2('destroy');
            select.select2({
                closeOnSelect: false,
                allowClear: true
            });
        });
    }
</script>
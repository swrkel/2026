<div class="modal fade" id="mechanical_meter_modal" role="dialog" aria-labelledby="mechanicalMeterModalLabel">
    <div class="modal-dialog modal-sm" style="width: 380px; max-width: 95%;">
        <div class="modal-content">
            <div class="modal-body" style="padding: 12px;">
                <div class="form-group row" style="margin-bottom: 10px;">
                    <label class="col-xs-8 control-label" for="mechanical_last_meter_input" style="padding-top: 7px;">
                        Last Meter - Mechanical
                    </label>
                    <div class="col-xs-4">
                        <input type="text" class="form-control input_number" id="mechanical_last_meter_input" step="0.001">
                    </div>
                </div>
                <div class="form-group row" style="margin-bottom: 10px;">
                    <label class="col-xs-8 control-label" for="mechanical_digital_last_meter_input" style="padding-top: 7px;">
                        Entered Last Digital Meter
                    </label>
                    <div class="col-xs-4">
                        <input type="text" class="form-control input_number" id="mechanical_digital_last_meter_input" step="0.001" readonly>
                    </div>
                </div>
                <div class="form-group row" style="margin-bottom: 14px;">
                    <label class="col-xs-8 control-label" for="mechanical_meter_difference_input" style="padding-top: 7px;">
                        Difference Mechanical Meter to Digital Meter
                    </label>
                    <div class="col-xs-4">
                        <input type="text" class="form-control input_number" id="mechanical_meter_difference_input" step="0.001" readonly>
                    </div>
                </div>
                <div class="text-right">
                    <button type="button" class="btn btn-info" id="add_mechanical_meter_btn">
                        @lang('messages.add')
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var $doc = $(document);

        function mechanicalNumber(value) {
            value = (value || '').toString().replace(/,/g, '');
            var parsed = parseFloat(value);
            return isNaN(parsed) ? null : parsed;
        }

        function formatMechanicalNumber(value) {
            return (parseFloat(value) || 0).toFixed(3);
        }

        function getCurrentDigitalMeterValue() {
            var digital = mechanicalNumber($('#pump_closing_meter').val());
            return digital === null ? null : digital;
        }

        function requiresMechanicalMeterSave() {
            return $('#mechanical_meter_btn').length > 0 && $('.btn_meter_sale').length > 0;
        }

        function setMeterSaleAddState() {
            var $button = $('.btn_meter_sale');
            if (!$button.length || window.isMeterSaleSubmitting) {
                return;
            }

            // LA-1091 urgent correction: the optional Mechanical Meter entry
            // must not disable or block the main Meter Sale Add button.
            $button.prop('disabled', false).removeAttr('disabled').removeClass('disabled');
        }

        window.refreshPetroMeterSaleAddState = setMeterSaleAddState;

        function markMechanicalMeterUnsaved() {
            if (!requiresMechanicalMeterSave()) {
                return;
            }

            $('#mechanical_meter_saved').val('0');
            setMeterSaleAddState();
        }

        function updateMechanicalDifference() {
            var mechanical = mechanicalNumber($('#mechanical_last_meter_input').val());
            var digital = getCurrentDigitalMeterValue();

            $('#mechanical_digital_last_meter_input').val(digital === null ? '' : formatMechanicalNumber(digital));

            if (mechanical !== null) {
                $('#mechanical_last_meter_input').val(formatMechanicalNumber(mechanical));
            }

            $('#mechanical_meter_difference_input').val(
                mechanical === null || digital === null ? '' : formatMechanicalNumber(mechanical - digital)
            );
        }

        $doc.off('click.direct_mechanical_meter', '#mechanical_meter_btn')
            .on('click.direct_mechanical_meter', '#mechanical_meter_btn', function () {
                var digital = getCurrentDigitalMeterValue();

                $('#mechanical_last_meter_input').val($('#mechanical_last_meter').val());
                $('#mechanical_digital_last_meter_input').val(digital === null ? '' : formatMechanicalNumber(digital));
                $('#mechanical_meter_difference_input').val('');
                updateMechanicalDifference();
                $('#mechanical_meter_modal').modal('show');
            });

        $doc.off('input.direct_mechanical_meter', '#mechanical_last_meter_input')
            .on('input.direct_mechanical_meter', '#mechanical_last_meter_input', function () {
                var mechanical = mechanicalNumber($(this).val());
                var digital = getCurrentDigitalMeterValue();

                $('#mechanical_digital_last_meter_input').val(digital === null ? '' : formatMechanicalNumber(digital));
                $('#mechanical_meter_difference_input').val(
                    mechanical === null || digital === null ? '' : formatMechanicalNumber(mechanical - digital)
                );
            });

        $doc.off('blur.direct_mechanical_meter', '#mechanical_last_meter_input')
            .on('blur.direct_mechanical_meter', '#mechanical_last_meter_input', updateMechanicalDifference);

        $doc.off('input.direct_mechanical_meter change.direct_mechanical_meter', '#pump_closing_meter')
            .on('input.direct_mechanical_meter change.direct_mechanical_meter', '#pump_closing_meter', function () {
                var digital = getCurrentDigitalMeterValue();
                var mechanical = mechanicalNumber($('#mechanical_last_meter').val());

                markMechanicalMeterUnsaved();
                $('#mechanical_digital_last_meter').val(digital === null ? '' : formatMechanicalNumber(digital));
                $('#mechanical_meter_difference').val(
                    mechanical === null || digital === null ? '' : formatMechanicalNumber(mechanical - digital)
                );

                if ($('#mechanical_meter_modal').hasClass('in')) {
                    $('#mechanical_digital_last_meter_input').val(digital === null ? '' : formatMechanicalNumber(digital));
                    $('#mechanical_meter_difference_input').val($('#mechanical_meter_difference').val());
                }
            });

        $doc.off('click.direct_mechanical_meter', '#add_mechanical_meter_btn')
            .on('click.direct_mechanical_meter', '#add_mechanical_meter_btn', function () {
                var mechanical = mechanicalNumber($('#mechanical_last_meter_input').val());
                var digital = getCurrentDigitalMeterValue();

                if (digital === null) {
                    toastr.error('Please enter the Pump Closing Meter first.');
                    return;
                }

                if (mechanical === null) {
                    toastr.error('Please enter Last Meter - Mechanical.');
                    return;
                }

                var difference = mechanical - digital;
                $('#mechanical_last_meter').val(formatMechanicalNumber(mechanical));
                $('#mechanical_digital_last_meter').val(formatMechanicalNumber(digital));
                $('#mechanical_meter_difference').val(formatMechanicalNumber(difference));
                $('#mechanical_meter_saved').val('1');
                $('#mechanical_last_meter_input').val(formatMechanicalNumber(mechanical));
                $('#mechanical_digital_last_meter_input').val(formatMechanicalNumber(digital));
                $('#mechanical_meter_difference_input').val(formatMechanicalNumber(difference));
                setMeterSaleAddState();
                $('#mechanical_meter_modal').modal('hide');
            });

        $(function () {
            setMeterSaleAddState();
            setTimeout(setMeterSaleAddState, 0);
            setTimeout(setMeterSaleAddState, 300);
        });
        $(window).off('pageshow.la1091_meter_sale_partial')
            .on('pageshow.la1091_meter_sale_partial', setMeterSaleAddState);
    })();
</script>

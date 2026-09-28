<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="@lang('messages.close')">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">Mechanical Meter</h4>
        </div>
        <div class="modal-body">
            @if($meter_sales->isNotEmpty())
                <div class="btn-group" style="margin-bottom: 15px;">
                    @foreach($meter_sales as $meter_sale)
                        <button type="button"
                            class="btn btn-info btn-sm settlement-mechanical-pump"
                            data-mechanical="{{ !is_null($meter_sale->mechanical_last_meter) ? number_format((float) $meter_sale->mechanical_last_meter, 3, '.', '') : '' }}"
                            data-digital="{{ !is_null($meter_sale->mechanical_digital_last_meter) ? number_format((float) $meter_sale->mechanical_digital_last_meter, 3, '.', '') : '' }}"
                            data-difference="{{ !is_null($meter_sale->mechanical_meter_difference) ? number_format((float) $meter_sale->mechanical_meter_difference, 3, '.', '') : '' }}">
                            {{ optional($meter_sale->pump)->pump_name ?? optional($meter_sale->pump)->pump_no ?? __('petro::lang.pump') }}
                        </button>
                    @endforeach
                </div>

                <div class="form-group row">
                    <label class="col-sm-7 control-label">Last Meter - Mechanical</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="saved_mechanical_last_meter" readonly>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-7 control-label">Entered Last Digital Meter</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="saved_mechanical_digital_last_meter" readonly>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-7 control-label">Difference Mechanical Meter to Digital Meter</label>
                    <div class="col-sm-5">
                        <input type="text" class="form-control" id="saved_mechanical_meter_difference" readonly>
                    </div>
                </div>
            @else
                <p class="text-muted">No meter sales found for this settlement.</p>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>

<script>
    (function () {
        function showMechanicalMeterDetails($button) {
            $('#saved_mechanical_last_meter').val($button.data('mechanical') || '');
            $('#saved_mechanical_digital_last_meter').val($button.data('digital') || '');
            $('#saved_mechanical_meter_difference').val($button.data('difference') || '');
        }

        $('.settlement-mechanical-pump').off('click').on('click', function () {
            showMechanicalMeterDetails($(this));
        });

        var $firstPump = $('.settlement-mechanical-pump').first();
        if ($firstPump.length) {
            showMechanicalMeterDetails($firstPump);
        }
    })();
</script>

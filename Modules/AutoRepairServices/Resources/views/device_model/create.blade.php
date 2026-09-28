<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('\Modules\AutoRepairServices\Http\Controllers\DeviceModelController@store'), 'method' => 'post', 'id' => 'device_model' ]) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title" id="myModalLabel">
                @lang('repair::lang.add_device_model')
            </h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">
                   <div class="form-group">
                        {!! Form::label('name', __('repair::lang.model_name') . ':*' )!!}
                        {!! Form::text('name', null, ['class' => 'form-control', 'required' ]) !!}
                   </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group position-relative">
                        {!! Form::label('brand_id', __('product.brand') . ':') !!}
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                {!! Form::select('brand_id', $brands, null, [
                                    'class' => 'form-control select2',
                                    'placeholder' => __('messages.please_select'),
                                    'id' => 'model_brand_id'
                                ]) !!}
                            </div>
                            <div class="ml-2">
                                <button type="button" class="btn btn-default" id="toggleAddBrandPopup" title="{{ __('brand.add_brand') }}" style="height:37px; align-items: center; display: flex;">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Hidden popup panel --}}
                            <div id="addBrandPopup"
                                class="bg-white rounded p-3"
                                style="
                                    display: none;
                                    position: absolute;
                                    left: 100%;
                                    transform: translateX(-50%);
                                    width: 300px;
                                    z-index: 1050;
                                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                                    border: 1px solid #dee2e6;
                                    padding: 6px;
                                ">
                                <div class="form-group mb-2">
                                    <label class="small">{{ __('brand.brand_name') }}</label>
                                    <input type="text" id="brand_name_input" class="form-control form-control-sm">
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small">{{ __('lang_v1.description') }}</label>
                                    <textarea id="brand_description_input" class="form-control form-control-sm" rows="2"></textarea>
                                </div>
                                <div class="d-flex justify-content-between mt-3">
                                    <button type="button" class="btn btn-sm btn-primary" id="submitBrandButton">
                                        {{ __('messages.save') }}
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border" id="cancelAddBrand">
                                        {{ __('messages.close') }}
                                    </button>
                                </div>
                            </div>


                    </div>


                </div>

                <div class="col-md-6">
                    <div class="form-group position-relative">
                        {!! Form::label('device_id', __('repair::lang.device') .':') !!}
                        <div class="d-flex align-items-start">
                            <div class="flex-grow-1">
                                {!! Form::select('device_id', $devices, null, [
                                    'class' => 'form-control select2',
                                    'placeholder' => __('messages.please_select'),
                                    'style' => 'width: 100%;',
                                    'id' => 'model_device_id'
                                ]) !!}
                            </div>
                            <div class="ml-2">
                                <button type="button" class="btn btn-default" id="toggleAddDevicePopup" title="{{ __('product.add') }}" style="height:37px; align-items: center; display: flex;">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Hidden Device Add Popup -->
                        <div id="addDevicePopup"
                            class="bg-white rounded p-3"
                            style="display: none; position: absolute; left: 100%; transform: translateX(-50%); width: 300px; z-index: 1050; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); border: 1px solid #dee2e6; padding: 6px;">
                            <div class="form-group mb-2">
                                <label class="small">Device Name</label>
                                <input type="text" id="device_name_input" class="form-control form-control-sm">
                            </div>
                            <div class="form-group mb-2">
                                <label class="small">Device Type</label>
                                <input type="text" id="device_type_input" class="form-control form-control-sm">
                            </div>
                            <div class="form-group mb-2">
                                <label class="small">Filter</label>
                                <input type="text" id="device_filter_input" class="form-control form-control-sm">
                            </div>
                            <div class="d-flex justify-content-between mt-3">
                                <button type="button" class="btn btn-sm btn-primary" id="submitDeviceButton">
                                    Save
                                </button>
                                <button type="button" class="btn btn-sm btn-light border" id="cancelAddDevice">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('repair_checklist', __('repair::lang.repair_checklist') . ':') !!} @show_tooltip(__('repair::lang.repair_checklist_tooltip'))
                        {!! Form::textarea('repair_checklist', null, ['class' => 'form-control ', 'id' => 'repair_checklist', 'rows' => '3']); !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">
                @lang('messages.close')
            </button>
            <button type="submit" class="btn btn-primary">
                @lang('messages.save')
            </button>
        </div>
        {!! Form::close() !!}
    </div>
</div>


<script>
$(document).ready(function () {
    // Show the popup
    $('#toggleAddBrandPopup').on('click', function () {
        $('#addBrandPopup').toggle();
    });

    // Hide the popup
    $('#cancelAddBrand').on('click', function () {
        $('#addBrandPopup').hide();
    });

    // AJAX submit
    $('#submitBrandButton').on('click', function () {
        let brandName = $('#brand_name_input').val().trim();
        let brandDescription = $('#brand_description_input').val().trim();

        if (!brandName) {
            toastr.error("{{ __('brand.brand_name') }} is required");
            return;
        }

        $.ajax({
            method: 'POST',
            url: "{{ action('\Modules\AutoRepairServices\Http\Controllers\AutorepairBrandController@store') }}",
            data: {
                name: brandName,
                description: brandDescription,
                _token: "{{ csrf_token() }}"
            },
            success: function (result) {
                if (result.success) {
                    $('#addBrandPopup').hide();
                    $('#brand_name_input').val('');
                    $('#brand_description_input').val('');
                    toastr.success(result.msg);

                    var newOption = new Option(result.data.name, result.data.id, true, true);
                    $('#model_brand_id').append(newOption).trigger('change');
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function () {
                toastr.error("{{ __('messages.something_went_wrong') }}");
            }
        });
    });

    // Re-initialize select2 if needed
    $('#model_brand_id').select2({
        placeholder: "{{ __('messages.please_select') }}",
        width: 'resolve'
    }).autocomplete({
        autoFocus: true
    });
});


$(document).ready(function () {
    // Toggle Device popup
    $('#toggleAddDevicePopup').on('click', function () {
        $('#addDevicePopup').toggle();
    });

    // Hide Device popup
    $('#cancelAddDevice').on('click', function () {
        $('#addDevicePopup').hide();
    });

    // Submit new device via AJAX (without relying on JSON)
    $('#submitDeviceButton').on('click', function () {
        let deviceName = $('#device_name_input').val().trim();
        let deviceDescription = $('#device_type_input').val().trim(); // maps to description

        if (!deviceName) {
            toastr.error("{{ __('product.device_name') }} is required");
            return;
        }

        $.ajax({
            method: 'POST',
            url: "{{ action('\Modules\AutoRepairServices\Http\Controllers\DeviceModelController@store_device') }}",
            data: {
                name: deviceName,
                description: deviceDescription,
                _token: "{{ csrf_token() }}"
            },
            success: function () {
                // Assume success because we got a 200 response (not a JSON response)
                $('#addDevicePopup').hide();
                $('#device_name_input').val('');
                $('#device_type_input').val('');
                toastr.success("{{ __('lang_v1.success') }}");

                // Use the submitted name as fallback (no ID, so we can't preselect it safely)
                let newOption = new Option(deviceName, deviceName, false, true);
                $('#model_device_id').append(newOption).trigger('change');
            },
            error: function () {
                toastr.error("{{ __('messages.something_went_wrong') }}");
            }
        });
    });
});


</script>
@section('javascript')
    <script>
        $(document).ready(function () {

            $("#model_device_id").autocomplete({
                data : []
            })
        })
    </script>
@endsection

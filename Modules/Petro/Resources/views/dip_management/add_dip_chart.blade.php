<style>
    .bootstrap-tagsinput {
        width: 100% !important;
    }
</style>

<div class="modal-dialog" role="document" style="width: 60%;">

    <div class="modal-content">



        {!! Form::open([
            'url' => action('\Modules\Petro\Http\Controllers\DipManagementController@saveDipChart'),
        
            'method' => 'post',
        
            'id' => 'dip_chart_add_form',
        ]) !!}



        <div class="modal-header">

            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>

            <h4 class="modal-title">@lang('petro::lang.add_dip_chart') </h4>

        </div>



        <div class="modal-body">
            <div class="col-md-12">

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            {!! Form::label('date_time', __('petro::lang.date_time') . ':*') !!}

                            {!! Form::text('date_time', @format_datetime(date('Y-m-d H:i')), [
                                'class' => 'form-control date_time',
                                'required',
                            
                                'placeholder' => __('petro::lang.date_time'),
                                'readonly',
                            ]) !!}

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="form-group">

                            {!! Form::label('tank_id', __('petro::lang.tanks') . ':') !!}

                            <select class="form-control select2" id="add_dip_chart_tank_id" name="tank_id">
                                <option value="">@lang('petro::lang.please_select')</option>

                                @foreach ($tanks as $tank)
                                    <option value="{{ $tank->id }}" data-tankname="{{ $tank->fuel_tank_number }}"
                                        data-manufacturer="{{ $tank->tank_manufacturer }}"
                                        data-manufacturerphone="{{ $tank->tank_manufacturer_phone }}"
                                        data-capacity="{{ $tank->storage_volume }}">
                                        {{ $tank->fuel_tank_number }}
                                    </option>
                                @endforeach
                            </select>


                        </div>

                    </div>

                    <div class="col-md-4">

                        <div class="form-group">

                            {!! Form::label('sheet_name', __('petro::lang.sheet_name') . ':*') !!}

                            {!! Form::text('sheet_name', null, [
                                'class' => 'form-control sheet_name',
                                'required',
                            
                                'placeholder' => __('petro::lang.sheet_name'),
                            ]) !!}

                        </div>

                    </div>



                    <div class="col-md-6">

                        <div class="form-group">

                            {!! Form::label('tank_capacity', __('petro::lang.tank_capacity') . ':*') !!}

                            {!! Form::text('tank_capacity', null, [
                                'class' => 'form-control tank_capacity',
                                'required',
                            
                                'placeholder' => __('petro::lang.tank_capacity'),
                                'readonly',
                            ]) !!}

                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="form-group">

                            {!! Form::label('tank_manufacturer', __('petro::lang.tank_manufacturer') . ':*') !!}

                            {!! Form::text('tank_manufacturer', null, [
                                'class' => 'form-control tank_manufacturer',
                                'required',
                            
                                'placeholder' => __('petro::lang.tank_manufacturer'),
                            ]) !!}

                        </div>

                    </div>

                    <div class="col-md-8">

                        <div class="form-group">

                            {!! Form::label(
                                'tank_manufacturer_contact',
                                __('petro::lang.tank_manufacturer_contact') . '(separate by comma):*',
                            ) !!}

                            {!! Form::text('tank_manufacturer_contact', null, [
                                'class' => 'form-control tank_manufacturer_contact',
                                'required',
                            
                                'placeholder' => __('petro::lang.tank_manufacturer_contact'),
                                'style' => 'width: 100% !important',
                            ]) !!}

                        </div>

                    </div>

                    <div class="clearfix"></div>


                    <div class="col-md-4">

                        <div class="form-group">

                            {!! Form::label('user_added', __('petro::lang.user_added') . ':*') !!}

                            {!! Form::text('user_added', auth()->user()->username, [
                                'class' => 'form-control user_added',
                                'required',
                            
                                'placeholder' => __('petro::lang.user_added'),
                                'readonly',
                            ]) !!}

                        </div>

                    </div>

                </div>

            </div>

            <div class="clearfix"></div>

            <div class="col-md-12">
                <h4>@lang('petro::lang.dip_readings')</h4>
                {{-- <table class="table table-bordered" id="dip_readings_table">
                    <thead>
                        <tr>
                            <th>@lang('petro::lang.dip_reading')</th>
                            <th>@lang('petro::lang.dip_reading_value')</th>
                            <th><button type="button" class="btn btn-sm btn-primary" id="add_row">Add</button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="text" name="dip_reading[]" class="form-control" required></td>
                            <td><input type="text" name="dip_reading_value[]" class="form-control" required></td>
                            <td><button type="button" class="btn btn-sm btn-danger remove_row"><i
                                        class="fa fa-trash"></i></button></td>
                        </tr>
                    </tbody>
                </table> --}}

                <table class="table table-bordered" id="dip_readings_table">
                    <thead>
                        <tr>
                            <th>@lang('petro::lang.dip_reading')</th>
                            <th>@lang('petro::lang.dip_reading_value')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Entry row at the top -->
                        <tr id="entry_row">
                            <td><input type="text" id="new_dip_reading" class="form-control"
                                    placeholder="Enter dip reading"></td>
                            <td><input type="text" id="new_dip_value" class="form-control" placeholder="Enter value">
                            </td>
                            <td><button type="button" class="btn btn-sm btn-primary" id="add_row">Add</button></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Data rows will be shown here -->
                <table class="table table-bordered" id="dip_readings_list">
                    <tbody></tbody>
                </table>

            </div>


            <div class="modal-footer">

                <button type="submit" class="btn btn-primary add_dip_resetting_btn">@lang('messages.save')</button>

                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>

            </div>



            {!! Form::close() !!}

        </div><!-- /.modal-content -->

    </div><!-- /.modal-dialog -->



    <script>
        $(document).ready(function() {
            function addRow() {
                let reading = $("#new_dip_reading").val().trim();
                let value = $("#new_dip_value").val().trim();

                if (reading !== "" && value !== "") {
                    let newRow = `<tr>
                <td><input type="text" name="dip_reading[]" value="${reading}" class="form-control" readonly></td>
                <td><input type="text" name="dip_reading_value[]" value="${value}" class="form-control" readonly></td>
                <td><button type="button" class="btn btn-sm btn-danger remove_row"><i class="fa fa-trash"></i></button></td>
            </tr>`;

                    $("#dip_readings_list tbody").prepend(newRow);

                    // Reset input fields for next entry
                    $("#new_dip_reading").val("").focus();
                    $("#new_dip_value").val("");
                }
            }

            // Add row when user clicks "Add"
            $("#add_row").on("click", function() {
                addRow();
            });

            // Pressing Enter in the "value" field will trigger Add
            $("#new_dip_value").on("keypress", function(e) {
                if (e.which === 13) { // Enter key
                    e.preventDefault(); // prevent form submit
                    addRow();
                }
            });

            // Remove row
            $(document).on("click", ".remove_row", function() {
                $(this).closest("tr").remove();
            });
        });
    </script>

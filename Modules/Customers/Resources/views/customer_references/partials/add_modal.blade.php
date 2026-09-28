{{--
    Task 8046 - the Add Customer Reference popup.

    Two-stage flow, which is what the spec describes:

      "Add"  stages a row into the table inside the popup, where it can still
             be edited or deleted, and resets the form for the next entry.
      "Save" commits every staged row to the database in one request.

    After Add, the customer selection is deliberately kept while every other
    field resets and the date/time jumps to the current moment - the spec's
    "once Added, need to show the last entered customer name. All other fields
    to reset and date and time to show the real time."
--}}
<div class="modal fade" id="cus_ref_add_modal" tabindex="-1" role="dialog" aria-labelledby="cus_ref_add_modal_label">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="cus_ref_add_modal_label">Add Customer Reference</h4>
            </div>

            <div class="modal-body">

                <div class="alert alert-danger" id="cus_ref_form_errors" style="display: none;"></div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cus_ref_datetime">Date &amp; Time <span class="text-danger">*</span></label>
                            {{--
                                datetime-local rather than the ERP date picker:
                                this field needs a time as well as a date, and
                                it is pre-filled with "now" on every reset.
                            --}}
                            <input type="datetime-local" name="cus_ref_datetime" id="cus_ref_datetime"
                                   class="form-control" style="width: 100%;">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cus_ref_customer_id">Customer <span class="text-danger">*</span></label>
                            {!! Form::select('cus_ref_customer_id', $customers, null, [
                                'class' => 'form-control cus-ref-select2',
                                'id' => 'cus_ref_customer_id',
                                'style' => 'width: 100%;',
                                'placeholder' => 'Select a customer',
                            ]) !!}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="cus_ref_is_vehicle">Reference is a Vehicle <span class="text-danger">*</span></label>
                            {!! Form::select('cus_ref_is_vehicle', ['1' => 'Yes', '0' => 'No'], '0', [
                                'class' => 'form-control cus-ref-select2',
                                'id' => 'cus_ref_is_vehicle',
                                'style' => 'width: 100%;',
                            ]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cus_ref_reference_no">
                                Customer Reference <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="cus_ref_reference_no" id="cus_ref_reference_no"
                                   class="form-control" style="width: 100%;" maxlength="191"
                                   placeholder="Enter the customer reference">
                            <p class="help-block" id="cus_ref_reference_hint" style="margin-bottom: 0;">
                                Enter the reference for this customer.
                            </p>
                        </div>
                    </div>

                    {{--
                        Fuel Type is hidden until "Reference is a Vehicle" is
                        set to Yes, per the spec. The wrapper is toggled rather
                        than the control itself so the label goes with it.
                    --}}
                    <div class="col-md-6" id="cus_ref_fuel_type_wrapper" style="display: none;">
                        <div class="form-group">
                            <label for="cus_ref_fuel_type">Fuel Type <span class="text-danger">*</span></label>
                            {!! Form::select('cus_ref_fuel_type', $fuelTypes, \Modules\Customers\Entities\CustomerReferenceFuelType::NOT_KNOWN_VALUE, [
                                'class' => 'form-control cus-ref-select2',
                                'id' => 'cus_ref_fuel_type',
                                'style' => 'width: 100%;',
                            ]) !!}
                            <p class="help-block" style="margin-bottom: 0;">
                                Product sub-categories under the Fuel product category.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 text-right">
                        <button type="button" class="btn btn-success" id="cus_ref_add_row">
                            <i class="fa fa-plus"></i> Add
                        </button>
                        <button type="button" class="btn btn-warning" id="cus_ref_cancel_edit" style="display: none;">
                            <i class="fa fa-times"></i> Cancel Edit
                        </button>
                    </div>
                </div>

                <hr>

                {{-- Rows staged in this popup, not yet saved. --}}
                <div class="table-responsive">
                    <table class="table table-bordered table-striped cus-ref-staged-table" id="cus_ref_staged_table">
                        <thead>
                            <tr>
                                <th style="width: 150px;">Date &amp; Time</th>
                                <th>Customer</th>
                                <th style="width: 90px;">Is a Vehicle</th>
                                <th>Reference No</th>
                                <th style="width: 130px;">Fuel Type</th>
                                <th style="width: 110px;" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="cus-ref-empty-row">
                                <td colspan="6" class="text-center text-muted">
                                    No references added yet. Complete the fields above and press Add.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="modal-footer">
                <span class="pull-left text-muted" id="cus_ref_staged_count" style="margin-top: 8px;"></span>
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="btn btn-primary" id="cus_ref_save_all">
                    <i class="fa fa-save"></i> @lang('messages.save')
                </button>
            </div>

        </div>
    </div>
</div>

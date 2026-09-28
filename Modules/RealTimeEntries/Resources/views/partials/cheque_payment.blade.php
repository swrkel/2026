{!! Form::open(['url' => action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@saveChequePayment'), 'method' =>'post', 'id' => 'cheques-form']) !!}
<div class="modal-body">
    <div class="row">

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('customer', 'Customer:') !!}
                {!! Form::select('customer', $customers ?? [], null, [
                    'class' => 'form-control select2',
                    'placeholder' => 'Select Customer',
                    'id' => 'cheques_customer',
                    'style' => 'width:100%',
                ]) !!}
            </div>
            <p id="customer-balance" class="text-info"></p>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('amount', 'Amount:') !!}
                <input type="text" class="form-control" id="cheques_amount" placeholder="Enter Amount">
            </div>
        </div>


        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('cheque_no', 'Cheque No:') !!}
                <input type="text" class="form-control" id="cheques_cheque_no" placeholder="Enter Cheque Number">
            </div>
        </div>

        <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('cheque_date', 'Cheque Date:') !!}
                <input type="date" class="form-control" id="cheques_cheque_date">
            </div>
        </div>

        <div class="col-md-4 text-left" style="margin-top: 25px;">
            <button type="button" class="btn btn-success" id="add-cheque">
                <i class="fa fa-plus"></i> Add
            </button>
        </div>
    </div>

    <div class="row" style="margin-top:20px;">
        <div class="col-md-12">
            <table class="table table-bordered" id="cheques-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Cheque No</th>
                        <th>Cheque Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Footer -->
<div class="modal-footer">
    <button type="submit" class="btn btn-primary" id="submit-cheques" disabled>Submit</button>
    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
</div>
{!! Form::close() !!}

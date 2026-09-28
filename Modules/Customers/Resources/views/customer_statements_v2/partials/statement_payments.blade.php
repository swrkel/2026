<div class="cs-v2-panel">
    <div class="row cs-v2-filter-row">
        <div class="col-md-3"><label>Customer</label>{!! Form::select('payment_customer_id', $customers, null, ['id'=>'cs-v2-payment-customer','class'=>'form-control select2','placeholder'=>__('messages.please_select')]) !!}</div>
        <div class="col-md-3"><label>Statement Date</label><input id="cs-v2-payment-range" class="form-control cs-v2-range" readonly></div>
        <div class="col-md-2"><label>Payment Method</label><select id="cs-v2-payment-method" class="form-control"><option value="">Please Select</option><option value="cash">Cash</option><option value="card">Card</option><option value="bank">Bank</option></select></div>
        <div class="col-md-2"><label>Statement No</label><input id="cs-v2-payment-statement-no" class="form-control" type="text"></div>
        <div class="col-md-2"><label>Search</label><input id="cs-v2-payment-search" class="form-control" type="search"></div>
    </div>
    <div class="cs-v2-toolbar-row text-right">
        <button type="button" class="btn btn-default cs-v2-payment-columns"><i class="fa fa-columns"></i> Column Visibility</button>
        <button type="button" class="btn btn-default cs-v2-payment-excel"><i class="fa fa-file-excel-o"></i> Export Excel</button>
    </div>
    <div class="table-responsive"><table class="table table-bordered" id="cs-v2-payments-table"><thead><tr><th>Action</th><th>Date Printed</th><th>Date From</th><th>Date To</th><th>Customer</th><th>Statement No</th><th>Statement Amount</th><th>Payment Status</th><th>Added By</th><th>Description</th></tr></thead><tbody></tbody></table></div>
</div>

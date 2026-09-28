<div class="cs-v2-panel">
    <div class="row cs-v2-filter-row">
        <div class="col-md-2"><label>Business Location</label>{!! Form::select('list_location_id', $locations, $defaultLocationId, ['class'=>'form-control select2','placeholder'=>__('messages.please_select')]) !!}</div>
        <div class="col-md-2"><label>Customer</label>{!! Form::select('list_customer_id', $customers, null, ['class'=>'form-control select2','placeholder'=>__('messages.please_select')]) !!}</div>
        <div class="col-md-2"><label>Customer Type</label><select class="form-control" id="cs-v2-list-type"><option value="all">All</option><option value="customer">Customers</option><option value="both">Both (Supplier & Customer)</option></select></div>
        <div class="col-md-2"><label>Statement Date</label><input class="form-control cs-v2-range" id="cs-v2-statement-range" readonly></div>
        <div class="col-md-2"><label>Printed Date</label><input class="form-control cs-v2-range" id="cs-v2-printed-range" readonly></div>
        <div class="col-md-2"><label>Search</label><input class="form-control" id="cs-v2-list-search" type="search"></div>
    </div>
    <div class="cs-v2-toolbar-row text-right"><button class="btn btn-default"><i class="fa fa-columns"></i> Column Visibility</button> <button class="btn btn-default"><i class="fa fa-file-text-o"></i> Export CSV</button> <button class="btn btn-default"><i class="fa fa-file-excel-o"></i> Export Excel</button></div>
    <div class="table-responsive"><table class="table table-bordered table-striped" id="cs-v2-list-table"><thead><tr><th>Action</th><th>Date Printed</th><th>Date From</th><th>Date To</th><th>Customer</th><th>Statement No</th><th>Statement Amount</th><th>Payment Status</th><th>Added By</th><th>Description</th></tr></thead><tbody></tbody></table></div>
</div>

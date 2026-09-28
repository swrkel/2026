<div class="cs-v2-panel">
    <form id="cs-v2-create-form" autocomplete="off">
        <div class="row cs-v2-filter-row">
            <div class="col-md-2"><label>Business Location</label>{!! Form::select('location_id', $locations, $defaultLocationId, ['class'=>'form-control select2','required','placeholder'=>__('messages.please_select')]) !!}</div>
            <div class="col-md-2"><label>Customer</label>{!! Form::select('customer_id', $customers, null, ['class'=>'form-control select2','required','id'=>'cs-v2-customer','placeholder'=>__('messages.please_select')]) !!}</div>
            <div class="col-md-2"><label>Customer Type</label><select class="form-control" name="customer_type"><option value="all">All</option><option value="customer">Customers</option><option value="both">Both (Supplier & Customer)</option></select></div>
            <div class="col-md-2"><label>Date Range</label><input type="text" class="form-control" id="cs-v2-date-range" readonly><input type="hidden" name="start_date" id="cs-v2-start-date"><input type="hidden" name="end_date" id="cs-v2-end-date"></div>
            <div class="col-md-2"><label>Statement Logo <small>(optional)</small></label>{!! Form::select('logo', $logos, null, ['class'=>'form-control select2','placeholder'=>__('messages.please_select')]) !!}</div>
            <div class="col-md-2"><label>Vehicle No <small>(optional)</small></label><input class="form-control" name="vehicle_no" type="text"></div>
        </div>
        <div class="row cs-v2-toolbar-row">
            <div class="col-md-4"><input type="search" id="cs-v2-create-search" class="form-control" placeholder="Search statements/invoices..."></div>
            <div class="col-md-8 text-right">
                <button type="button" class="btn btn-default cs-v2-columns"><i class="fa fa-columns"></i> Column Visibility</button>
                <button type="button" class="btn btn-default cs-v2-csv"><i class="fa fa-file-text-o"></i> Export CSV</button>
                <button type="button" class="btn btn-default cs-v2-excel"><i class="fa fa-file-excel-o"></i> Export Excel</button>
                <button type="submit" class="btn btn-primary" id="cs-v2-save"><i class="fa fa-save"></i> Save</button>
            </div>
        </div>
    </form>
    <div class="alert alert-info cs-v2-min-date-message" style="display:none"></div>
    <div class="table-responsive"><table class="table table-bordered table-striped" id="cs-v2-create-table"><thead><tr><th>Action</th><th>Date</th><th>Customer</th><th>Customer P/O No</th><th>Invoice No</th><th>Route No/Vehicle No</th><th>Voucher Order Date</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Invoice Amount</th></tr></thead><tbody></tbody></table></div>
</div>

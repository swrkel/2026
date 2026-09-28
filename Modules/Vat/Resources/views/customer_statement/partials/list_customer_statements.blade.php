<section class="content vat-statement-list-section">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid vat-statement-filter-box">
                <div class="box-header with-border vat-filter-header" data-widget="collapse" role="button">
                    <h3 class="box-title">
                        <i class="fa fa-filter"></i> @lang('report.filters')
                    </h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>

                <div class="box-body">
                    <div class="row vat-filter-row">
                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                {!! Form::label('list_customer_statement_location_id', __('purchase.business_location') . ':') !!}
                                {!! Form::select(
                                    'list_customer_statement_location_id',
                                    $business_locations,
                                    null,
                                    [
                                        'class' => 'form-control select2 vat-list-filter',
                                        'id' => 'list_customer_statement_location_id',
                                        'placeholder' => __('lang_v1.all'),
                                        'style' => 'width:100%;',
                                    ]
                                ) !!}
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                {!! Form::label('list_customer_statement_customer_id', __('contact.customer') . ':') !!}
                                {!! Form::select(
                                    'list_customer_statement_customer_id',
                                    $customers,
                                    null,
                                    [
                                        'class' => 'form-control select2 vat-list-filter',
                                        'id' => 'list_customer_statement_customer_id',
                                        'placeholder' => __('lang_v1.all'),
                                        'style' => 'width:100%;',
                                    ]
                                ) !!}
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                {!! Form::label('list_customer_statement_customer_type', __('lang_v1.customer_type') . ':') !!}
                                {!! Form::select(
                                    'list_customer_statement_customer_type',
                                    $customer_types,
                                    'all',
                                    [
                                        'class' => 'form-control select2 vat-list-filter',
                                        'id' => 'list_customer_statement_customer_type',
                                        'style' => 'width:100%;',
                                    ]
                                ) !!}
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                {!! Form::label('list_customer_statement_search', __('lang_v1.search') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                    {!! Form::text('list_customer_statement_search', null, [
                                        'class' => 'form-control vat-list-filter',
                                        'id' => 'list_customer_statement_search',
                                        'placeholder' => 'Search Customer Statement',
                                        'autocomplete' => 'off',
                                    ]) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row vat-filter-row">
                        <div class="col-md-3 col-sm-6 col-md-offset-6">
                            <div class="form-group">
                                {!! Form::label('printed_list_customer_statement_date_range', __('report.date_range') . ' (printed):') !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    {!! Form::text(
                                        'printed_list_customer_statement_date_range',
                                        @format_date(date('Y-01-01')) . ' - ' . @format_date(date('Y-12-31')),
                                        [
                                            'class' => 'form-control vat-list-date-filter',
                                            'id' => 'printed_list_customer_statement_date_range',
                                            'autocomplete' => 'off',
                                        ]
                                    ) !!}
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 col-sm-6">
                            <div class="form-group">
                                {!! Form::label('list_customer_statement_date_range', __('report.date_range') . ' (statement date):') !!}
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                                    {!! Form::text(
                                        'list_customer_statement_date_range',
                                        @format_date(date('Y-01-01')) . ' - ' . @format_date(date('Y-12-31')),
                                        [
                                            'class' => 'form-control vat-list-date-filter',
                                            'id' => 'list_customer_statement_date_range',
                                            'autocomplete' => 'off',
                                        ]
                                    ) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary vat-statement-list-box">
                <div class="box-body">
                    <div class="table-responsive vat-statement-table-wrap">
                        <table class="table table-bordered table-striped table-hover" id="customer_statement_list_table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th class="notexport noColvis">@lang('lang_v1.action')</th>
                                    <th>@lang('contact.date_printed')</th>
                                    <th>@lang('contact.date_from')</th>
                                    <th>@lang('contact.date_to')</th>
                                    <th>@lang('contact.customer')</th>
                                    <th>@lang('contact.statement_no')</th>
                                    <th class="text-right">@lang('contact.statement_amount')</th>
                                    <th>@lang('contact.payment_status')</th>
                                    <th>@lang('contact.added_by')</th>
                                    <th>@lang('contact.description')</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th class="text-right">@lang('sale.total'):</th>
                                    <th class="text-right"><span id="grand_total">0.00</span></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.vat-statement-list-section {
    padding: 0 !important;
}
.vat-statement-filter-box,
.vat-statement-list-box {
    border-radius: 10px;
    border-top: 0 !important;
    box-shadow: 0 3px 14px rgba(15, 23, 42, .08);
    overflow: visible;
}
.vat-statement-filter-box .vat-filter-header {
    background: #f4f7fa;
    border-bottom: 1px solid #e5e9ef;
    padding: 10px 14px;
}
.vat-statement-filter-box .box-title {
    font-size: 14px;
    font-weight: 600;
}
.vat-statement-filter-box .box-body {
    padding: 14px 16px 8px;
}
.vat-filter-row .form-group {
    margin-bottom: 12px;
}
.vat-filter-row label {
    font-size: 12px;
    font-weight: 600;
    color: #344054;
}
.vat-statement-list-box .box-body {
    padding: 14px;
}
.vat-statement-table-wrap {
    overflow: visible !important;
}
#customer_statement_list_table {
    margin-bottom: 0 !important;
}
#customer_statement_list_table thead th {
    white-space: nowrap;
    vertical-align: middle;
    font-size: 12px;
}
#customer_statement_list_table tbody td {
    vertical-align: middle;
    font-size: 12px;
}
#customer_statement_list_table td:nth-child(5),
#customer_statement_list_table td:nth-child(10) {
    white-space: normal;
    min-width: 160px;
}
#customer_statement_list_table td:nth-child(7),
#customer_statement_list_table th:nth-child(7) {
    text-align: right;
    white-space: nowrap;
}
.vat-statement-status {
    display: inline-block;
    min-width: 48px;
    padding: 5px 9px;
    border-radius: 12px;
    color: #fff !important;
    font-size: 11px;
    font-weight: 700;
    text-align: center;
}
.vat-statement-status-due {
    background: #f39c12 !important;
}
.vat-statement-status-paid {
    background: #28a745 !important;
}
.vat-customer-statement-action-group .dropdown-toggle {
    margin: 0 !important;
}
#customer_statement_list_table_wrapper .dt-buttons {
    margin-bottom: 10px;
}
#customer_statement_list_table_wrapper .dataTables_length,
#customer_statement_list_table_wrapper .dataTables_filter {
    padding-top: 5px;
}
@media (max-width: 991px) {
    .vat-filter-row .col-md-offset-6 {
        margin-left: 0;
    }
}
</style>

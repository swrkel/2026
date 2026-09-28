<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('report.prod_trans_report_store_wise') }}</h1>
</section>

<!-- Main content -->
<section class="content">
    {{-- IMPORTANT:
        Do NOT add filters here.
        Filters exist in product_transaction_report tab.
        This prevents duplicate IDs which breaks select2/daterangepicker.
    --}}

    @include('report.partials.report_summary_section')

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div id="table_div">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="prod_trans_report_store_wise_table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th class="notexport">@lang('messages.action')</th>
                                    <th>@lang('report.date_and_time')</th>
                                    <th>@lang('report.sku')</th>
                                    <th>@lang('report.product')</th>
                                    <th>@lang('report.description')</th>
                                    <th>@lang('report.starting_qty')</th>
                                    <th>@lang('report.purchase_qty')</th>
                                    <th>@lang('report.bonus_qty')</th>
                                    <th>@lang('report.sold_qty')</th>
                                    <th>@lang('report.balance_qty')</th>
                                    <th>@lang('report.balance_qty_value')</th>
                                    <th>@lang('lang_v1.product_added_date')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    <input type="hidden" id="prod_trans_report_store_wise_ajax_url"
       value="{{ action('ReportController@getProductTransactionReportStoreWise') }}">

</section>
<!-- /.content -->

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <ul class="breadcrumbs pull-left" style="margin-top: 20px; margin-bottom: 20px;">
                    <li><a href="#">@lang('disstocktransfer::lang.list_dis_stock_transfers')</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@component('components.widget', ['class' => 'box-primary', 'title' => __('disstocktransfer::lang.all_dis_stock_transfers')])
    @slot('tool')
        <div class="box-tools pull-right ">
            <button type="button" class="btn  btn-primary btn-modal"
                data-href="{{ action('\Modules\DisStockTransfer\Http\Controllers\DisStockTransferController@create') }}"
                data-container=".pump_modal">
                <i class="fa fa-plus"></i> @lang('messages.add')</button>

        </div>
    @endslot
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="dis_stock_transfer_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Reference No</th>
                    <th>Location (From)</th>
                    <th>From Store</th>
                    <th>To Store</th>
                    <th>Total Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th colspan="5" class="text-right"><strong>Page Total:</strong></th>
                    <th id="footer_total_amount" class="text-right"></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
@endcomponent

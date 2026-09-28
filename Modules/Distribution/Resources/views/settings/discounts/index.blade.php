<section class="content">
    @component('distribution::components.widget', ['class' => 'box-primary', 'title' => 'Discounts'])
        @slot('tool')
            <div class="box-tools">
                <button type="button"
                    class="btn btn-primary btn-modal pull-right"
                    data-href="{{ action('\Modules\Distribution\Http\Controllers\DiscountController@create') }}"
                    data-container="#discountModal" onclick="if(window.openDistributionSettingsModal){return window.openDistributionSettingsModal(this,event);}">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </button>
            </div>
        @endslot

            <div class="table-responsive" style="overflow-x: auto;">
                <table class="table table-bordered table-striped table-hover" id="discount_table" style="width: 100%;">
                    <thead style="background-color: #f4f4f4;">
                        <tr>
                            <th style="min-width: 150px;">Date & Time</th>
                            <th style="min-width: 158px;">Product Category</th>
                            <th style="min-width: 169px;">Product Sub Category</th>
                            <th style="min-width: 225px;">Products</th>
                            <th style="min-width: 120px;">Unit</th>
                            <th style="min-width: 80px; text-align: right;">Qty</th>
                            <th style="min-width: 120px;">Discount Type</th>
                            <th style="min-width: 120px; text-align: right;">Max Discount</th>
                        </tr>
                    </thead>
                </table>
            </div>
    @endcomponent

    <div class="modal fade" id="discountModal" tabindex="-1" role="dialog"></div>
</section>

<style>
    #discount_table {
        font-size: 14px;
    }
    #discount_table thead th {
        font-weight: 600;
        text-align: left;
        padding: 12px 8px;
    }
    #discount_table tbody td {
        padding: 10px 8px;
        vertical-align: middle;
    }
    #discount_table tbody tr:hover {
        background-color: #f9f9f9;
    }
    /* Right align numeric columns */
    #discount_table tbody td:nth-child(6),
    #discount_table tbody td:nth-child(8) {
        text-align: right;
    }
</style>

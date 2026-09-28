<section class="content">

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __(
            'petro::lang.all_issue_bill_customer')])
                @can('issue_customer_bill.add')
                    @slot('tool')
                        <div class="box-tools">
                            <button type="button" class="btn btn-primary btn-modal pull-right" id="add_issue_bill_customer_btn"
                                    data-href="{{action('\Modules\Petro\Http\Controllers\IssueCustomerBillController@create')}}"
                                    data-container=".issue_bill_customer_model">
                                <i class="fa fa-plus"></i> @lang( 'petro::lang.add' )</button>
                        </div>
                    @endslot
                @endcan
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-striped table-bordered" id="issue_bill_customer_table" style="width: 100%;">
                            <thead>
                            <tr>
                                <th>@lang( 'petro::lang.date_time' )</th>
                                <th>@lang( 'petro::lang.customer_bill_no' )</th>
                                <th>@lang( 'petro::lang.bill_amount' )</th>
                                <th>@lang( 'petro::lang.pump' )</th>
                                <th>@lang( 'petro::lang.pump_operator' )</th>
                                <th>@lang( 'petro::lang.customer' )</th>
                                <th>@lang( 'petro::lang.vehicle_no' )</th>
                                <th>@lang( 'petro::lang.order_no' )</th>
                                <th>@lang( 'petro::lang.user' )</th>
                                <th>@lang( 'messages.action' )</th>
                            </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
    <div id="issue_bill_customer_modal" class="modal fade issue_bill_customer_model" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
</section>
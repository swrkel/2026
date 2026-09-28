<section class="content">

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __(
            'petrogeneral::lang.all_issue_bill_customer')])
                @can('issue_customer_bill.add')
                    @slot('tool')
                        <div class="box-tools">
                            <button type="button" class="btn btn-primary btn-modal pull-right" id="add_issue_bill_customer_btn"
                                    data-href="{{action('\Modules\PetroGeneral\Http\Controllers\IssueCustomerBillController@create')}}"
                                    data-container=".issue_bill_customer_model">
                                <i class="fa fa-plus"></i> @lang( 'petrogeneral::lang.add' )</button>
                        </div>
                    @endslot
                @endcan
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-striped table-bordered" id="issue_bill_customer_table" style="width: 100%;">
                            <thead>
                            <tr>
                                <th>@lang( 'petrogeneral::lang.date_time' )</th>
                                <th>@lang( 'petrogeneral::lang.customer_bill_no' )</th>
                                <th>@lang( 'petrogeneral::lang.bill_amount' )</th>
                                <th>@lang( 'petrogeneral::lang.pump' )</th>
                                <th>@lang( 'petrogeneral::lang.pump_operator' )</th>
                                <th>@lang( 'petrogeneral::lang.customer' )</th>
                                <th>@lang( 'petrogeneral::lang.vehicle_no' )</th>
                                <th>@lang( 'petrogeneral::lang.order_no' )</th>
                                <th>@lang( 'petrogeneral::lang.user' )</th>
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
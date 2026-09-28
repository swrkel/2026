<section class="content">

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __(
            'petrogeneral::lang.map_pump_to_operator')])
                @can('issue_customer_bill.add')
                    @slot('tool')
                        <div class="box-tools">
                            <button type="button" class="btn btn-primary btn-modal pull-right" id="add_pump_operator_mapping_btn"
                                    data-href="{{action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorMappingController@create')}}"
                                    data-container=".pump_to_operator_modal">
                                <i class="fa fa-plus"></i> @lang( 'petrogeneral::lang.add' )</button>
                        </div>
                    @endslot
                @endcan
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-striped table-bordered" id="pump_operator_mapping_table" style="width: 100%;">
                            <thead>
                            <tr>
                                <th>@lang( 'petrogeneral::lang.action' )</th>
                                <th>@lang( 'petrogeneral::lang.date' )</th>
                                <th>@lang( 'petrogeneral::lang.assigned_time' )</th>
                                <th>@lang( 'petrogeneral::lang.operator' )</th>
                                <th>@lang( 'petrogeneral::lang.pumps' )</th>
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
    <div class="modal fade pump_to_operator_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade edit_pump_to_operator_modal" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
</section>
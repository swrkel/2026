<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __(
            'tasksmanagement::lang.add_status')])
            @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-primary btn-modal pull-right" id="add_status_btn"
                    data-href="{{action('\Modules\TasksManagement\Http\Controllers\StatusController@create')}}"
                    data-container=".status_model">
                    <i class="fa fa-plus"></i> @lang( 'tasksmanagement::lang.add_status' )</button>
            </div>
            @endslot

            <div class="row">
                <div class="col-md-12">
                    <table class="table table-striped table-bordered" id="status_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>@lang( 'tasksmanagement::lang.name' )</th>
                                <th>@lang( 'tasksmanagement::lang.color' )</th>
                                <th>@lang( 'tasksmanagement::lang.default' )</th>
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

</section>
<!-- /.content -->
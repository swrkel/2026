<!-- Main content -->
<section class="content">
    

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __(
            'leadsnew::lang.all_labels')])
            @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-primary btn-modal pull-right" id="add_label_btn"
                    data-href="{{action('\Modules\LeadsNew\Http\Controllers\LabelController@create')}}"
                    data-container=".category_model">
                    <i class="fa fa-plus"></i> @lang( 'leadsnew::lang.add_label' )</button>
            </div>
            @endslot

            <div class="row">
                <div class="col-md-12">
                    <table class="table table-striped table-bordered" id="leads_new_labels_table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>@lang( 'leadsnew::lang.label_1' )</th>
                                <th>@lang( 'leadsnew::lang.label_2' )</th>
                                <th>@lang( 'leadsnew::lang.label_3' )</th>
                                <th>@lang( 'leadsnew::lang.user' )</th>
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
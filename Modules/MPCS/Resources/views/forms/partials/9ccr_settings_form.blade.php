<!-- Main content -->
<section class="content" style="padding-top:10px">


    <div class="row">
        <div class="box-tools pull-right" style="margin: 14px 20px 14px 0;">
            <button type="button" id="add_form_9ccr_settings_button" disabled class="btn btn-primary btn-modal form-9c-button"
                data-href="{{ action('\Modules\MPCS\Http\Controllers\Form9CCRSettingsController@create') }}"
                data-container=".form_9_ccr_settings_modal">
                <i class="fa fa-plus"></i> @lang('mpcs::lang.add_form_9_ccr_settings')</button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="col-md-12">
                    <div class="box-body" style="margin-top: 20px;">
                        <div class="row">
                            <div class="col-md-12">

                                <div id="msg"></div>
                                <table id="form_9ccr_settings_table" class="table table-striped table-bordered"
                                    cellspacing="0" width="100%">
                                    <thead>
                                        <tr>
                                            <th>@lang('mpcs::lang.action')</th>
                                            <th>@lang('mpcs::lang.opening_date')</th>
                                            <th>@lang('mpcs::lang.form_starting_number')</th>
                                            <th>@lang('mpcs::lang.total_previous_page')</th>
                                            <th>@lang('mpcs::lang.user_added')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            @endcomponent
        </div>
    </div>

</section>


<!-- /.content -->

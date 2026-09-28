  
            
            
<div class="row">
    <div class="col-xs-12">
        <button type="button" class="btn btn-primary pull-right" id="add_settings_btn" data-href="{{action('\Modules\ReportsCustomized\Http\Controllers\ReportsCustomizedSettingsController@create')}}"  data-container=".settings_modal">
            <i class="fa fa-plus"></i> @lang('messages.add')
        </button>
    </div>
</div>
<br>

{{-- Modified by Engr. Alex -- task 7882: Issue 3 - add Description Constant Details and Added User columns to Prefix & Numbers DataTable --}}
@component('components.widget', ['class' => 'box-primary', 'title' => __('Prefix & Numbers')])
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
        <table class="table table-bordered table-striped" id="lioc_customized_report_table" style="width: 100%;">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Date &amp; Time</th>
                    <th>Prefix</th>
                    <th>Statement Starting Number</th>
                    <th>Constant Value</th>
                    <th>Description Constant Details</th>
                    <th>Added User</th>
                </tr>
            </thead>
        </table>
            </div>
        </div>
    </div>
@endcomponent
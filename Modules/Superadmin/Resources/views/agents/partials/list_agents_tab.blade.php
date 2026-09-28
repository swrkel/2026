<style>
    #agents_table_wrapper .dt-buttons,
    #agents_table_wrapper .dt-buttons.btn-group {
        float: none !important;
        text-align: center !important;
        display: block !important;
        width: 100% !important;
        margin: 10px auto !important;
    }
    #agents_table_wrapper .dt-buttons .btn {
        float: none !important;
        display: inline-block !important;
    }
</style>

<!-- Main content -->
<section class="content">
    @php
        $countries = $countries ?? [];
        $agent_names = $agent_names ?? [];
        $agent_codes = $agent_codes ?? [];
        $cities = $cities ?? [];
        $usernames = $usernames ?? [];
        $nic_numbers = $nic_numbers ?? [];
        $referral_codes = $referral_codes ?? [];
        $added_bys = $added_bys ?? [];
        $mobile_numbers = $mobile_numbers ?? [];
    @endphp
    @component('components.filters', ['title' => __('report.filters')])
    <div class="row">
        <!-- Row 1 -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('date_range_tab', __('report.date_range_tab') . ':') !!}
                {!! Form::text('date_range_tab', null, ['placeholder' => __('lang_v1.select_a_date_range_tab'),
                'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('agent_name', __('superadmin::lang.name') . ':') !!}
                {!! Form::select('agent_name', $agent_names, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('superadmin::lang.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('agent_code', __('superadmin::lang.agent_code') . ':') !!}
                {!! Form::select('agent_code', $agent_codes, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('superadmin::lang.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('country_id', __('superadmin::lang.country') . ':') !!}
                {!! Form::select('country_id', $countries, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('superadmin::lang.all')]); !!}
            </div>
        </div>

        <!-- Row 2 -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('city', __('superadmin::lang.city') . ':') !!}
                {!! Form::select('city', $cities, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('superadmin::lang.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('mobile_number', __('superadmin::lang.mobile_number') . ':') !!}
                {!! Form::select('mobile_number', $mobile_numbers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('superadmin::lang.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('username', __('superadmin::lang.username') . ':') !!}
                {!! Form::select('username', $usernames, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('superadmin::lang.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('nic_number', __('superadmin::lang.nic_number') . ':') !!}
                {!! Form::select('nic_number', $nic_numbers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('superadmin::lang.all')]); !!}
            </div>
        </div>

        <!-- Row 3 -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('referral_code', __('superadmin::lang.referral_code') . ':') !!}
                {!! Form::select('referral_code', $referral_codes, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('superadmin::lang.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('added_by', __('superadmin::lang.added_by') . ':') !!}
                {!! Form::select('added_by', $added_bys, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('superadmin::lang.all')]); !!}
            </div>
        </div>
    </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'superadmin::lang.all_your_agents')])
    @slot('tool')
    <div class="box-tools">
        <button type="button" class="btn btn-primary btn-modal"
            data-href="{{action('\Modules\Superadmin\Http\Controllers\AgentController@create')}}"
            data-container=".view_modal">
            <i class="fa fa-plus"></i> @lang('messages.add')
        </button>
    </div>
    @endslot

    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="agents_table" width="100%">
            <thead>
                <tr>
                    <th>@lang('superadmin::lang.date')</th>
                    <th>@lang('superadmin::lang.referral_code')</th>
                    <th>@lang('superadmin::lang.name')</th>
                    <th>@lang('superadmin::lang.mobile_number')</th>
                    <th>@lang('superadmin::lang.email')</th>
                    <th>@lang('superadmin::lang.referral_group')</th>
                    <th>@lang('superadmin::lang.total_orders')</th>
                    <th>@lang('superadmin::lang.active_subscription')</th>
                    <th>@lang('superadmin::lang.income')</th>
                    <th>@lang('superadmin::lang.paid')</th>
                    <th>@lang('superadmin::lang.due')</th>
                    <th>@lang('superadmin::lang.action')</th>

                </tr>
            </thead>
            <tfoot>

            </tfoot>
        </table>
    </div>

    @endcomponent
</section>
<!-- /.content -->

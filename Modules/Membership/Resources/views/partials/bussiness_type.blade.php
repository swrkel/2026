<div class="row">
    <div class="col-xs-12">
        @can('add_membership_settings')
        <button type="button" class="btn btn-primary btn-modal pull-right"
                data-href="{{ url('/membership/setting/business-types/create') }}"
                data-container=".business_type_modal">
            <i class="fa fa-plus"></i> @lang('membership::lang.add_business_type')
        </button>
        @endcan
    </div>
</div>
<br>
<div class="row">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="membership_business_types_table" style="width: 100%">
                <thead>
                <tr>
                    <th>@lang('membership::lang.date_time')</th>
                    <th>@lang('membership::lang.business_type')</th>
                    <th>@lang('membership::lang.user')</th>
                    <th>@lang('messages.action')</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade business_type_modal" tabindex="-1" role="dialog"></div>

@include('membership::settings.business_type.users_modal')
@include('membership::settings.business_type.scripts')

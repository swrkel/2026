<div class="row">
    <div class="col-xs-12">
        @can('add_membership_settings')
            <button type="button" class="btn btn-primary membership-point-setting-modal pull-right"
                    data-href="{{ url('/membership/setting/point-settings/create') }}"
                    data-container=".point_setting_modal">
                <i class="fa fa-plus"></i> @lang('membership::lang.add_point_setting')
            </button>
        @endcan
    </div>
</div>
<br>
<div class="row">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="membership_point_settings_table" style="width: 100%">
                <thead>
                <tr>
                    <th>@lang('messages.action')</th>
                    <th>@lang('membership::lang.date_time')</th>
                    <th>@lang('membership::lang.business_type')</th>
                    <th>@lang('membership::lang.reward_point_percent')</th>
                    <th>@lang('membership::lang.min_bill_total_to_earn')</th>
                    <th>@lang('membership::lang.max_points_per_bill')</th>
                    <th>@lang('membership::lang.min_bill_total_to_redeem')</th>
                    <th>@lang('membership::lang.min_redeem_point')</th>
                    <th>@lang('membership::lang.max_redeem_point_per_bill')</th>
                    <th>@lang('membership::lang.expiry_period')</th>
                    <th>@lang('membership::lang.added_by')</th>
                </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade point_setting_modal" tabindex="-1" role="dialog"></div>

<script>
    window.MembershipPointSettingsConfig = {
        tableUrl: "{{ url('/membership/setting/point-settings') }}",
        csrfToken: "{{ csrf_token() }}"
    };
</script>
{{-- IS1618: handled by public/js/membership/membership_settings_is1618.js --}}

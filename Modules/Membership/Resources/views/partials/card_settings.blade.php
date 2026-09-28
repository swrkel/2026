<div class="row">
    <div class="col-xs-12">
        @can('add_membership_settings')
            <button type="button" class="btn btn-primary pull-right membership-card-setting-modal-trigger" id="add_card_setting_btn" data-href="{{ url('/membership/setting/card-settings/create') }}" data-container=".card_setting_modal">
                <i class="fa fa-plus"></i> @lang('messages.add') @lang('membership::lang.card_setting')
            </button>
        @endcan
    </div>
</div>
<br>
<div class="row">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="membership_card_settings_table" style="width: 100%">
                <thead>
                    <tr>
                        <th style="width: 120px; min-width: 120px;">@lang('messages.action')</th>
                        <th style="width: 12%">@lang('membership::lang.date_time')</th>
                        <th style="width: 10%" class="text-right">@lang('membership::lang.length') (mm)</th>
                        <th style="width: 10%" class="text-right">@lang('membership::lang.width') (mm)</th>
                        <th style="width: 16%">@lang('membership::lang.size_details')</th>
                        <th style="width: 18%">@lang('membership::lang.card_sample')</th>
                        <th style="width: 12%">@lang('membership::lang.added_by')</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade card_setting_modal" tabindex="-1" role="dialog" aria-labelledby="membershipCardSettingModalLabel">
    <div id="card_setting_modal_content"></div>
</div>

@include('membership::partials.card_settings.scripts')

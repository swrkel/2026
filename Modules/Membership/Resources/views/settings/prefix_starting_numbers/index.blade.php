<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('membership::lang.prefix_and_starting_numbers') }}</h3>
        @can('add_membership_settings')
            <button type="button" class="btn btn-primary pull-right" id="mem_prefix_add_btn">
                <i class="fa fa-plus"></i> {{ __('messages.add') }}
            </button>
        @endcan
    </div>

    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="mem_prefix_table" style="width:100%;">
                <thead>
                    <tr>
                        <th style="width:130px;">@lang('messages.action')</th>
                        <th>@lang('membership::lang.region')</th>
                        <th>@lang('membership::lang.prefix')</th>
                        <th class="text-right">@lang('membership::lang.starting_number')</th>
                        <th>@lang('membership::lang.added_by')</th>
                        <th>@lang('membership::lang.date_time')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($settings as $setting)
                        <tr data-id="{{ $setting->id }}">
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        {{ __('messages.actions') }} <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-left" role="menu">
                                        <li>
                                            <a href="#" class="btn-modal" data-href="{{ url('/membership/setting/membership-settings/' . $setting->id) }}" data-container=".membership_setting_modal">
                                                <i class="glyphicon glyphicon-eye-open"></i> {{ __('messages.view') }}
                                            </a>
                                        </li>
                                        @can('edit_membership_settings')
                                            <li>
                                                <a href="#" class="mem_prefix_edit_btn" data-id="{{ $setting->id }}">
                                                    <i class="glyphicon glyphicon-edit"></i> {{ __('messages.edit') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="mem_prefix_delete_btn" data-id="{{ $setting->id }}">
                                                    <i class="glyphicon glyphicon-trash"></i> {{ __('messages.delete') }}
                                                </a>
                                            </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                            <td>{{ $setting->region }}</td>
                            <td>{{ $setting->prefix }}</td>
                            <td class="text-right">{{ number_format((float) $setting->starting_number, 0, '.', ',') }}</td>
                            <td>{{ optional($setting->createdBy)->username ?? '-' }}</td>
                            <td>{{ $setting->created_at ? $setting->created_at->format('Y-m-d H:i') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade membership_setting_modal" tabindex="-1" role="dialog"></div>
@include('membership::settings.prefix_starting_numbers.form_modal')

<script>
    window.MembershipPrefixStartingNumberConfig = {
        csrfToken: @json(csrf_token()),
        storeUrl: @json(url('/membership/setting/membership-settings')),
        baseUrl: @json(url('/membership/setting/membership-settings')),
        texts: {
            add: @json(__('messages.add')),
            edit: @json(__('messages.edit')),
            sure: @json(__('messages.sure')),
            somethingWrong: @json(__('messages.something_went_wrong'))
        }
    };
</script>
<script src="{{ asset('modules/membership/js/settings/prefix_starting_number.js') }}?v={{ file_exists(public_path('modules/membership/js/settings/prefix_starting_number.js')) ? filemtime(public_path('modules/membership/js/settings/prefix_starting_number.js')) : time() }}"></script>

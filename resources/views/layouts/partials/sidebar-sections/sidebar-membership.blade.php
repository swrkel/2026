{{-- Membership Module Sidebar --}}

@if (
    $show_membership_menu &&
        ($is_privileged_sidebar_user ||
            auth()->user()->can('add_membership_settings') ||
            auth()->user()->can('edit_membership_settings') ||
            auth()->user()->can('membership.settings_page') ||
            auth()->user()->can('add_member') ||
            auth()->user()->can('edit_member') ||
            auth()->user()->can('membership.add_points_page') ||
            auth()->user()->can('membership.point_activities_page') ||
            auth()->user()->can('membership.add_member_page') ||
            auth()->user()->can('membership.list_members_page') ||
            auth()->user()->can('membership.membership_activities_page') ||
            auth()->user()->can('add_dividends') ||
            auth()->user()->can('edit_dividends') ||
            auth()->user()->can('membership.add_dividends_page') ||
            auth()->user()->can('membership.list_dividends_page') ||
            auth()->user()->can('approved_by') ||
            auth()->user()->can('checked_by'))
)
    <li class="nav-item {{ in_array($request->segment(1), ['membership']) ? 'active active-sub' : '' }}">
        <a class="nav-link collapsed"
           href="#"
           data-toggle="collapse"
           data-target="#membership-menu"
           aria-expanded="true"
           aria-controls="membership-menu">
            <i class="ti-id-badge"></i>
            <span>@lang('membership::lang.membership')</span>
        </a>

        <div id="membership-menu"
             class="collapse {{ in_array($request->segment(2), ['setting', 'dividends']) ? 'show' : '' }}"
             aria-labelledby="headingPages"
             data-parent="#accordionSidebar">

            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">@lang('membership::lang.membership'):</h6>

                @if ($is_privileged_sidebar_user || auth()->user()->can('membership.add_points_page'))
                    <a class="collapse-item {{ $request->segment(2) == 'add-points' ? 'active' : '' }}"
                       href="{{ action('\Modules\Membership\Http\Controllers\MembershipPointController@addPointForm') }}">
                        @lang('membership::lang.add_points')
                    </a>
                @endif

                @if ($is_privileged_sidebar_user || auth()->user()->can('membership.point_activities_page'))
                    <a class="collapse-item {{ $request->segment(2) == 'list-points' ? 'active' : '' }}"
                       href="{{ action('\Modules\Membership\Http\Controllers\MembershipPointController@getListPoints') }}">
                        @lang('membership::lang.point_activities')
                    </a>
                @endif

                @if ($is_privileged_sidebar_user || auth()->user()->can('add_membership_settings') || auth()->user()->can('edit_membership_settings') || auth()->user()->can('membership.settings_page'))
                    <a class="collapse-item {{ $request->segment(2) == 'setting' ? 'active' : '' }}"
                       href="{{ action('\Modules\Membership\Http\Controllers\MembershipSettingController@index') }}">
                        @lang('membership::lang.membership_settings')
                    </a>
                @endif

                @if ($is_privileged_sidebar_user || auth()->user()->can('add_member') || auth()->user()->can('membership.add_member_page'))
                    <a class="collapse-item {{ $request->segment(2) == 'members' && $request->segment(3) == 'create' ? 'active' : '' }}"
                       href="{{ action('\Modules\Membership\Http\Controllers\MembershipController@createMember') }}">
                        @lang('membership::lang.add_member')
                    </a>
                @endif

                @if ($is_privileged_sidebar_user || auth()->user()->can('membership.list_members_page') || auth()->user()->can('edit_member'))
                    <a class="collapse-item {{ $request->segment(2) == 'members' && ! in_array((string) $request->segment(3), ['create', 'activities']) ? 'active' : '' }}"
                       href="{{ action('\Modules\Membership\Http\Controllers\MembershipController@getMembers') }}">
                        @lang('membership::lang.list_membership')
                    </a>
                @endif

                @if ($is_privileged_sidebar_user || auth()->user()->can('membership.membership_activities_page'))
                    <a class="collapse-item {{ $request->segment(2) == 'members' && $request->segment(3) == 'activities' ? 'active' : '' }}"
                       href="{{ action('\Modules\Membership\Http\Controllers\MembershipController@getMembershipActivities') }}">
                        @lang('membership::lang.membership_activities')
                    </a>
                @endif

                @if ($is_privileged_sidebar_user || auth()->user()->can('add_dividends') || auth()->user()->can('membership.add_dividends_page'))
                    <a class="collapse-item {{ $request->segment(2) == 'dividends' && $request->segment(3) == 'add' ? 'active' : '' }}"
                       href="{{ action('\Modules\Membership\Http\Controllers\DividendController@addDividends') }}">
                        @lang('membership::lang.add_dividends')
                    </a>
                @endif

                @if ($is_privileged_sidebar_user || auth()->user()->can('edit_dividends') || auth()->user()->can('membership.list_dividends_page'))
                    <a class="collapse-item {{ $request->segment(2) == 'dividends' && $request->segment(3) == 'list' ? 'active' : '' }}"
                       href="{{ action('\Modules\Membership\Http\Controllers\DividendController@listDividends') }}">
                        @lang('membership::lang.list_dividends')
                    </a>
                @endif
            </div>
        </div>
    </li>
@endif
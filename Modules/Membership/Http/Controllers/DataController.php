<?php

namespace Modules\Membership\Http\Controllers;

use Illuminate\Routing\Controller;

class DataController extends Controller
{
    public function user_permissions()
    {
        $s = __('membership::lang.role_section_settings');
        $m = __('membership::lang.role_section_members_points');
        $d = __('membership::lang.role_section_dividends');

        return [
            [
                'section' => $s,
                'value' => 'add_membership_settings',
                'label' => __('membership::lang.add_membership_settings_permission'),
                'default' => false,
            ],
            [
                'section' => $s,
                'value' => 'edit_membership_settings',
                'label' => __('membership::lang.edit_membership_settings_permission'),
                'default' => false,
            ],
            [
                'section' => $s,
                'value' => 'membership.settings_page',
                'label' => __('membership::lang.membership_settings_page_permission'),
                'default' => false,
            ],
            [
                'section' => $m,
                'value' => 'add_member',
                'label' => __('membership::lang.add_member_permission'),
                'default' => false,
            ],
            [
                'section' => $m,
                'value' => 'edit_member',
                'label' => __('membership::lang.edit_member_permission'),
                'default' => false,
            ],
            [
                'section' => $m,
                'value' => 'membership.add_member_page',
                'label' => __('membership::lang.add_member_page_permission'),
                'default' => false,
            ],
            [
                'section' => $m,
                'value' => 'membership.list_members_page',
                'label' => __('membership::lang.list_members_page_permission'),
                'default' => false,
            ],
            [
                'section' => $m,
                'value' => 'membership.membership_activities_page',
                'label' => __('membership::lang.membership_activities_page_permission'),
                'default' => false,
            ],
            [
                'section' => $m,
                'value' => 'membership.add_points_page',
                'label' => __('membership::lang.add_points_page_permission'),
                'default' => false,
            ],
            [
                'section' => $m,
                'value' => 'membership.point_activities_page',
                'label' => __('membership::lang.point_activities_page_permission'),
                'default' => false,
            ],
            [
                'section' => $d,
                'value' => 'add_dividends',
                'label' => __('membership::lang.add_dividends_permission'),
                'default' => false,
            ],
            [
                'section' => $d,
                'value' => 'edit_dividends',
                'label' => __('membership::lang.edit_dividends_permission'),
                'default' => false,
            ],
            [
                'section' => $d,
                'value' => 'membership.add_dividends_page',
                'label' => __('membership::lang.add_dividends_page_permission'),
                'default' => false,
            ],
            [
                'section' => $d,
                'value' => 'membership.list_dividends_page',
                'label' => __('membership::lang.list_dividends_page_permission'),
                'default' => false,
            ],
            [
                'section' => $d,
                'value' => 'approved_by',
                'label' => __('membership::lang.approved_by_permission'),
                'default' => false,
            ],
            [
                'section' => $d,
                'value' => 'checked_by',
                'label' => __('membership::lang.checked_by_permission'),
                'default' => false,
            ],
        ];
    }
}

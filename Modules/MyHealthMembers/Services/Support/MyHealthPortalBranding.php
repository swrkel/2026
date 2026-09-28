<?php

namespace Modules\MyHealthMembers\Services\Support;

use App\Business;
use Illuminate\Support\Facades\Schema;
use Modules\MyHealthMembers\Entities\MyHealthBusinessPermission;
use Modules\MyHealthMembers\Entities\MyHealthMember;

class MyHealthPortalBranding
{
    public static function defaults(): array
    {
        return [
            'portal_name' => 'My Health Member Portal',
            'browser_title' => 'My Health Member Portal',
            'logo' => '',
            'login_banner' => '',
            'welcome_message' => 'Your secure health dashboard shows only your own records.',
            'footer_text' => 'My Health Member Portal • Secure access to your own records only • Powered by standalone MyHealthMembers',
            'copyright_text' => '© ' . date('Y') . ' My Health Member Portal. All rights reserved.',
            'primary_theme_color' => '#0d6efd',
            'secondary_theme_color' => '#00a6a6',
            'support_email' => '',
            'support_telephone' => '',
        ];
    }

    public static function forBusiness(?int $businessId): array
    {
        $defaults = self::defaults();

        if (empty($businessId)) {
            return $defaults;
        }

        try {
            $permission = MyHealthBusinessPermission::where('business_id', $businessId)->first();
            $branding = [];

            if ($permission && isset($permission->portal_branding) && !empty($permission->portal_branding)) {
                $branding = is_array($permission->portal_branding)
                    ? $permission->portal_branding
                    : (json_decode($permission->portal_branding, true) ?: []);
            }

            if (empty($branding['portal_name'])) {
                $business = Business::find($businessId);
                if ($business && !empty($business->name)) {
                    $branding['portal_name'] = $business->name . ' My Health Portal';
                }
            }

            return array_merge($defaults, array_filter($branding, function ($value) {
                return $value !== null && $value !== '';
            }));
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    public static function forCurrentPortalMember(): array
    {
        try {
            $memberId = session('myhealth_member_id');
            if (empty($memberId)) {
                return self::defaults();
            }

            $member = MyHealthMember::find($memberId);
            $businessId = $member->registered_business_id ?? null;

            return self::forBusiness($businessId ? (int) $businessId : null);
        } catch (\Throwable $e) {
            return self::defaults();
        }
    }
}

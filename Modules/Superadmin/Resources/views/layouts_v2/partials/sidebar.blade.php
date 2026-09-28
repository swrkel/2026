@php
    $__show_superadmin_module_sidebar = !empty($isCentralSuperAdminSidebar);
    try {
        if (!$__show_superadmin_module_sidebar) {
            if (method_exists(\App\Utils\SidebarPermissionUtil::class, 'shouldShowSuperAdminMenu')) {
                $__show_superadmin_module_sidebar =
                    \App\Utils\SidebarPermissionUtil::shouldShowSuperAdminMenu();
            } else {
                $__superadmin_context_business_id = (int) (
                    session('business.id')
                    ?: session('user.business_id')
                    ?: optional(auth()->user())->business_id
                );
                $__show_superadmin_module_sidebar = auth()->check()
                    && (bool) auth()->user()->can('superadmin')
                    && $__superadmin_context_business_id === 1;
            }
        }
    } catch (\Throwable $__superadmin_sidebar_exception) {
        $__show_superadmin_module_sidebar = false;
    }
@endphp
@if ($__show_superadmin_module_sidebar && auth()->check() && auth()->user()->can('superadmin'))
<li class="nav-item {{ in_array($request->segment(1), ['superadmin', 'sample-medical-product-import', 'site-settings', 'pay-online']) ? 'active active-sub' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#superadmin-menu"
        aria-expanded="true" aria-controls="superadmin-menu">
        <i class="ti-settings"></i>
        <span>@lang('superadmin::lang.superadmin')</span>
    </a>
    <div id="superadmin-menu" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">@lang('superadmin::lang.superadmin'):</h6>
            <a class="collapse-item {{ empty($request->segment(2)) && $request->segment(1) != 'site-settings' ? 'active active-sub' : '' }}" href="{{action('\Modules\Superadmin\Http\Controllers\SuperadminController@index')}}">@lang('superadmin::lang.superadmin')</a>

            <a class="collapse-item {{ $request->segment(2) == 'business' ? 'active active-sub' : '' }}" href="{{action('\Modules\Superadmin\Http\Controllers\BusinessController@index')}}">@lang('superadmin::lang.all_business')</a>

            <a class="collapse-item {{ $request->segment(2) == 'business-location-overview' ? 'active active-sub' : '' }}" href="{{ route('superadmin.business-locations-overview') }}">Business &amp; Locations</a>

            <a class="collapse-item {{ $request->segment(2) == 'tenant-management' ? 'active active-sub' : '' }}" href="{{action('\Modules\Superadmin\Http\Controllers\TenantManagementController@index')}}">@lang('superadmin::lang.tenant_management')</a>

            <a class="collapse-item {{ $request->segment(2) == 'packages' ? 'active active-sub' : '' }}" href="{{action('\Modules\Superadmin\Http\Controllers\PackagesController@index')}}">@lang('superadmin::lang.subscription_packages')</a>

            <a class="collapse-item {{ $request->segment(2) == 'referrals' ? 'active active-sub' : '' }}" href="{{action('\Modules\Superadmin\Http\Controllers\ReferralController@index')}}">@lang('superadmin::lang.referrals')</a>

            <a class="collapse-item {{ $request->segment(2) == 'settings' ? 'active active-sub' : '' }}" href="{{ url('/superadmin/settings') }}">@lang('superadmin::lang.super_admin_settings')</a>

            <a class="collapse-item {{ $request->segment(2) == 'master-super-admin' ? 'active active-sub' : '' }}" href="{{ route('superadmin.master-super-admin.index') }}"><i class="fa fa-shield"></i> Master Super Admin</a>

            {{-- 8051: Standalone idle-banner management page --}}
            <a class="collapse-item {{ $request->segment(2) == 'banner-management' ? 'active active-sub' : '' }}" href="{{ route('superadmin.banner-management.index') }}">Banners Management</a>

            <a class="collapse-item {{ $request->segment(2) == 'imports-exports' ? 'active active-sub' : '' }}" href="{{action('\Modules\Superadmin\Http\Controllers\ImportExportController@index')}}">@lang('superadmin::lang.import_export')</a>

            <a class="collapse-item {{ $request->segment(2) == 'help-explanation' ? 'active active-sub': '' }}" href="{{action('\Modules\Superadmin\Http\Controllers\HelpExplanationController@index')}}">@lang('superadmin::lang.help_explanation')</a>

            <a class="collapse-item {{ $request->segment(2) == 'communicator' ? 'active active-sub' : '' }}" href="{{action('\Modules\Superadmin\Http\Controllers\CommunicatorController@index')}}">@lang('superadmin::lang.communicator')</a>

            <a class="collapse-item {{ $request->segment(1) == 'site-settings'? 'active' : '' }}" href="{{route('site_settings.view')}}">@lang('site_settings.settings')</a>

            <a class="collapse-item {{ $request->segment(1) == 'system_administration'? 'active' : '' }}" href="{{route('site_settings.help_view')}}">@lang('site_settings.help')</a>

            <a class="collapse-item {{ $request->segment(2) == 'pages' ? 'active' : '' }}" href="{{action('\Modules\Superadmin\Http\Controllers\SuperadminSettingsController@pages')}}">Landing Page Content</a>

            <a class="collapse-item {{ $request->segment(2) == 'landing-pages' ? 'active' : '' }}" href="{{action('\Modules\Superadmin\Http\Controllers\SuperadminSettingsController@landingSettings')}}">Enable Landing Pages</a>

            <a class="collapse-item {{ $request->segment(2) == 'landing-settings' ? 'active' : '' }}" href="{{action('\Modules\Superadmin\Http\Controllers\SuperadminSettingsController@landAdminSettings')}}">Landing Page Settings</a>

            <a class="collapse-item {{ $request->segment(2) == 'landing-languages' ? 'active' : '' }}" href="{{action('\Modules\Superadmin\Http\Controllers\SuperadminSettingsController@landing_languages')}}">Landing Page Languages</a>

            <a class="collapse-item {{ $request->segment(1) == 'sample-medical-product-import' ? 'active' : '' }}" href="{{action('ImportMedicalProductController@index')}}">@lang('lang_v1.sample_medical_product_import')</a>

            {{--
                8050: Help Guide pages, in the SUPERADMIN sidebar.

                These live under /superadmin/, and layouts/app.blade.php renders
                sidebar-superadmin for any URL whose first segment is
                'superadmin'. So entries added to the business sidebar never
                appeared on them - the pages simply had no menu of their own.

                Placed here, alongside every other superadmin page, and written
                in exactly the same shape as the entries above: a collapse-item
                whose active test compares $request->segment(2).

                Segment 2 is 'help-guide-modules' for all three; segment 3
                distinguishes them, which is why the settings entry additionally
                checks that segment 3 is empty. Without that it would highlight
                on the article pages too.
            --}}
            <a class="collapse-item {{ $request->segment(2) == 'help-guide-modules' && empty($request->segment(3)) ? 'active active-sub' : '' }}" href="{{ url('/superadmin/help-guide-modules') }}">Help GuideSettings</a>

            <a class="collapse-item {{ $request->segment(2) == 'help-guide-modules' && $request->segment(3) == 'articles' && empty($request->segment(4)) ? 'active active-sub' : '' }}" href="{{ url('/superadmin/help-guide-modules/articles') }}">Create Article</a>

            <a class="collapse-item {{ $request->segment(2) == 'help-guide-modules' && $request->segment(3) == 'articles' && $request->segment(4) == 'list' ? 'active active-sub' : '' }}" href="{{ url('/superadmin/help-guide-modules/articles/list') }}">List Articles</a>

            <a class="collapse-item {{ $request->segment(1) == 'petro-quota-setting' ? 'active' : '' }}" href="{{action('\Modules\Petro\Http\Controllers\VehicleController@petro_qouta_setting')}}">@lang('vehicle.petro_qouta_setting')</a>

            <a class="collapse-item {{ $request->segment(1) == 'smsrefill-package' ? 'active' : '' }}" href="{{action('\Modules\Superadmin\Http\Controllers\SmsRefillPackageController@index')}}">@lang('superadmin::lang.sms_refill')</a>

            <a class="collapse-item {{ $request->segment(1) == 'user-locations' ? 'active active-sub' :'' }}" href="{{ route('userlocations.index') }}">@lang('superadmin::lang.user_locations_sidebar')</a>

        </div>
    </div>
</li>
<li class="nav-item {{ $request->segment(1) == 'default-notification-templates' ? 'active active-sub' :'' }}">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#default-notification-template" aria-expanded="true" aria-controls="default-notification-template">
            <i class="fa fa-envelope"></i>
            <span>@lang('lang_v1.default_notification_templates')</span>
        </a>
        <div id="default-notification-template" class="collapse" aria-labelledby="headingPages" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">
                    @lang('lang_v1.default_notification_templates'):
                </h6>
                <a class="collapse-item {{ $request->segment(1) == 'notification-template' && $request->segment(2) == 'email' ? 'active' : '' }}" href="{{ url('superadmin/default-notification-templates') }}?type=email">@lang('lang_v1.email')</a>
                <a class="collapse-item {{ $request->segment(1) == 'notification-template' && $request->segment(2) == 'sms' ? 'active' : '' }}" href="{{ url('superadmin/default-notification-templates') }}?type=sms">@lang('lang_v1.sms')
                    &
                    @lang('lang_v1.whatsapp')
                </a>

            </div>
        </div>
    </li>
@endif

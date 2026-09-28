{{--
    Lightweight Superadmin header.
    It avoids tenant subscription, SMS, day-end, currency and notification
    relation queries that are not required while administering businesses.
--}}
@php
    $superadminHeaderUser = auth()->user();
@endphp

<div class="header-area superadmin-fast-header no-print">
    <div class="row align-items-center" style="display:flex;align-items:center;margin:0;min-height:64px;">
        <div class="col-sm-5" style="padding-left:20px;">
            <strong style="font-size:18px;">@lang('superadmin::lang.superadmin')</strong>
        </div>

        <div class="col-sm-7 text-right" style="padding-right:20px;">
            <a href="{{ action('BusinessController@clearCache') }}" class="btn btn-danger btn-sm clear_cache_btn">
                <i class="fa fa-refresh"></i> @lang('lang_v1.clear_cache')
            </a>

            <a target="_blank"
                href="{{ \Illuminate\Support\Facades\Route::has('frontend') ? route('frontend') : url('/helpguide') }}"
                class="btn btn-default btn-sm">
                <i class="fa fa-question-circle"></i> Help Guide
            </a>

            @unless (isset($__is_superadmin_business_index) && $__is_superadmin_business_index)
                <button type="button" id="btnLock" class="btn btn-default btn-sm" title="@lang('lang_v1.lock_screen')">
                    <i class="fa fa-lock"></i>
                </button>
            @endunless

            <div class="btn-group">
                <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-user"></i>
                    {{ \Illuminate\Support\Str::limit($superadminHeaderUser->first_name ?? 'Super Admin', 27) }}
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li class="text-center" style="padding:10px 15px;font-weight:700;">
                        {{ trim(($superadminHeaderUser->first_name ?? '') . ' ' . ($superadminHeaderUser->last_name ?? '')) }}
                    </li>
                    <li role="separator" class="divider"></li>
                    <li>
                        <a href="{{ action('UserController@getProfile') }}">
                            <i class="fa fa-user"></i> @lang('lang_v1.profile')
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('/logout') }}">
                            <i class="fa fa-sign-out"></i> @lang('lang_v1.sign_out')
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.superadmin-fast-header {
    margin:18px 18px 0;
    border-radius:16px;
    background:#fff;
    border:1px solid rgba(0,0,0,.06);
    box-shadow:0 4px 18px rgba(0,0,0,.07);
    overflow:visible !important;
}
.superadmin-fast-header .btn {
    margin-left:7px;
    border-radius:9px;
    font-weight:600;
}
.superadmin-fast-header .dropdown-menu {
    z-index:8000;
}
</style>

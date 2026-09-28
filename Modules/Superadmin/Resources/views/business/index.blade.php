<style>
/* Manage New sits in the card header; colour now comes from btn-warning. */
.sa-header-manage-new { margin-bottom: 6px; font-weight: 600; }

/* Purple, previously on Manage New, now on Manage Side Bar. */
.sa-btn-purple {
    background: #6d28d9;
    border: 1px solid #6d28d9;
    color: #fff;
}
.sa-btn-purple:hover,
.sa-btn-purple:focus { background: #5b21b6; border-color: #5b21b6; color: #fff; }
.sa-btn-purple:active { background: #4c1d95; border-color: #4c1d95; color: #fff; }
</style>

{{-- Manage New button. Purple, so it is not confused with the existing
     green Manage or amber Manage Side Bar. --}}
<style>
.sa-btn-manage-new {
    background: #6d28d9;
    border: 1px solid #6d28d9;
    color: #fff;
}
.sa-btn-manage-new:hover,
.sa-btn-manage-new:focus {
    background: #5b21b6;
    border-color: #5b21b6;
    color: #fff;
}
.sa-btn-manage-new:active { background: #4c1d95; border-color: #4c1d95; color: #fff; }
</style>
@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | Business')

@section('content')
<style>
/* All Businesses - professional responsive company cards */
.sa-business-page {
    --sa-blue: #2563eb;
    --sa-blue-soft: #eff6ff;
    --sa-ink: #0f172a;
    --sa-muted: #64748b;
    --sa-border: #e2e8f0;
    --sa-surface: #ffffff;
    --sa-page: #f4f7fb;
}

.sa-page-heading {
    margin-bottom: 18px;
}

.sa-business-page .sa-filter-panel {
    background: var(--sa-surface);
    border: 1px solid var(--sa-border);
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
    margin-bottom: 24px;
    padding: 18px 20px;
}

.sa-business-page .sa-filter-panel .form-group {
    margin-bottom: 0;
}

.sa-business-page .sa-filter-actions {
    align-items: flex-end;
    display: flex;
    justify-content: flex-end;
    min-height: 59px;
}

.sa-business-page .sa-add-business-btn {
    border-radius: 10px;
    box-shadow: 0 8px 18px rgba(37, 99, 235, .18);
    font-weight: 700;
    min-width: 118px;
    padding: 10px 18px;
}

.sa-business-grid {
    display: flex;
    flex-wrap: wrap;
    margin-left: -10px;
    margin-right: -10px;
}

.sa-business-grid > .sa-business-grid__column {
    display: flex;
    justify-content: center;
    padding-left: 10px;
    padding-right: 10px;
    width: 33.333333%;
}

.sa-company-card {
    background: var(--sa-surface);
    border: 1px solid rgba(148, 163, 184, .28);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
    display: flex;
    flex-direction: column;
    margin-bottom: 20px;
    max-width: 650px;
    min-width: 0;
    overflow: hidden;
    position: relative;
    transition: box-shadow .2s ease, transform .2s ease;
    width: 100%;
}

.sa-company-card:hover {
    box-shadow: 0 16px 38px rgba(15, 23, 42, .13);
    transform: translateY(-3px);
}

.sa-company-card__header {
    border-bottom: 1px solid #eef2f7;
    min-height: 94px;
    padding: 17px 20px;
    position: relative;
}

.sa-company-card__identity {
    align-items: center;
    display: flex;
    min-width: 0;
    padding-right: 76px;
}

.sa-company-card__logo {
    align-items: center;
    background: var(--sa-blue-soft);
    border: 1px solid #dbeafe;
    border-radius: 50%;
    color: var(--sa-blue);
    display: flex;
    flex: 0 0 60px;
    font-size: 26px;
    height: 60px;
    justify-content: center;
    overflow: hidden;
    width: 60px;
}

.sa-company-card__logo img {
    height: 100%;
    object-fit: cover;
    width: 100%;
}

.sa-company-card__titles {
    min-width: 0;
    padding-left: 14px;
}

.sa-company-card__name {
    color: var(--sa-ink);
    font-size: 20px;
    font-weight: 700;
    line-height: 1.25;
    margin: 0 0 4px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sa-company-card__subtitle {
    color: var(--sa-muted);
    font-size: 13px;
    line-height: 1.4;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sa-company-card__number {
    background: #dc2626;
    border-radius: 30px;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    max-width: 105px;
    overflow: hidden;
    padding: 6px 11px;
    position: absolute;
    right: 17px;
    text-overflow: ellipsis;
    top: 17px;
    white-space: nowrap;
}

.sa-company-card__number.is-empty {
    background: #94a3b8;
}

.sa-company-card__details-toggle {
    align-items: center;
    background: #fff;
    border: 0;
    border-bottom: 1px solid #eef2f7;
    color: #334155;
    display: flex;
    font-size: 13px;
    font-weight: 700;
    justify-content: space-between;
    min-height: 39px;
    outline: none;
    padding: 8px 20px;
    text-align: left;
    width: 100%;
}

.sa-company-card__details-toggle:hover,
.sa-company-card__details-toggle:focus {
    background: #f8fbff;
    color: var(--sa-blue);
    outline: none;
}

.sa-company-card__details-toggle-label {
    align-items: center;
    display: flex;
    gap: 8px;
}

.sa-company-card__details-toggle-chevron {
    transition: transform .2s ease;
}

.sa-company-card__details-toggle:not(.collapsed) .sa-company-card__details-toggle-chevron {
    transform: rotate(180deg);
}

.sa-company-card__details-collapse {
    border-bottom: 1px solid #eef2f7;
}

.sa-company-card__details {
    padding: 14px 20px 3px;
}

.sa-info-row {
    align-items: flex-start;
    display: flex;
    margin-bottom: 10px;
    min-width: 0;
}

.sa-info-row__icon {
    align-items: center;
    background: var(--sa-blue-soft);
    border-radius: 9px;
    color: var(--sa-blue);
    display: flex;
    flex: 0 0 36px;
    font-size: 14px;
    height: 36px;
    justify-content: center;
    margin-right: 11px;
    width: 36px;
}

.sa-info-row__content {
    color: #334155;
    font-size: 13px;
    line-height: 1.4;
    min-width: 0;
    overflow-wrap: anywhere;
    padding-top: 0;
}

.sa-info-row__label {
    color: var(--sa-ink);
    display: block;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 1px;
}

.sa-company-card__subscription-wrap {
    padding: 14px 20px;
}

.sa-subscription-panel {
    background: #f8fbff;
    border: 1px solid var(--sa-border);
    border-radius: 12px;
    padding: 5px 13px;
}

.sa-subscription-row {
    align-items: flex-start;
    border-bottom: 1px solid #e9eef5;
    display: flex;
    gap: 12px;
    justify-content: space-between;
    padding: 8px 0;
}

.sa-subscription-row:last-child {
    border-bottom: 0;
}

.sa-subscription-row__label {
    color: var(--sa-ink);
    flex: 0 0 42%;
    font-size: 13px;
    font-weight: 700;
}

.sa-subscription-row__label i {
    color: var(--sa-blue);
    margin-right: 7px;
    text-align: center;
    width: 16px;
}

.sa-subscription-row__value {
    color: #334155;
    font-size: 13px;
    overflow-wrap: anywhere;
    text-align: right;
}

.sa-status-text {
    font-weight: 700;
}

.sa-status-text.is-enabled {
    color: #15803d;
}

.sa-status-text.is-disabled {
    color: #b91c1c;
}

.sa-company-card__actions {
    border-top: 1px solid #eef2f7;
    margin-top: auto;
    padding: 13px 15px 3px;
}

.sa-action-grid {
    display: flex;
    flex-wrap: wrap;
    margin-left: -5px;
    margin-right: -5px;
}

.sa-action-grid__item {
    padding: 0 4px 8px;
    width: 33.333333%;
}

.sa-action-grid .btn {
    align-items: center;
    border-radius: 10px;
    display: flex;
    font-size: 12px;
    font-weight: 700;
    justify-content: center;
    min-height: 36px;
    padding: 7px 6px;
    white-space: normal;
    width: 100%;
}

.sa-action-grid .btn i {
    margin-right: 6px;
}

.sa-business-empty {
    background: #fff;
    border: 1px dashed #cbd5e1;
    border-radius: 16px;
    color: #64748b;
    padding: 48px 20px;
    text-align: center;
    width: 100%;
}

.sa-business-pagination {
    text-align: center;
    width: 100%;
}

@media (max-width: 1199px) {
    .sa-business-grid > .sa-business-grid__column {
        width: 50%;
    }

    .sa-action-grid__item {
        width: 50%;
    }
}

@media (max-width: 991px) {
    .sa-business-grid > .sa-business-grid__column {
        width: 50%;
    }
}

@media (max-width: 767px) {
    .sa-business-page .sa-filter-actions {
        justify-content: flex-start;
        min-height: 0;
        padding-top: 12px;
    }

    .sa-business-grid > .sa-business-grid__column {
        display: block;
        width: 100%;
    }

    .sa-company-card {
        margin-left: auto;
        margin-right: auto;
    }
}

@media (max-width: 479px) {
    .sa-company-card__identity {
        align-items: flex-start;
        padding-right: 0;
    }

    .sa-company-card__logo {
        flex-basis: 54px;
        height: 54px;
        width: 54px;
    }

    .sa-company-card__number {
        display: inline-block;
        margin-bottom: 15px;
        max-width: 100%;
        position: static;
    }

    .sa-company-card__header {
        padding-top: 20px;
    }

    .sa-action-grid__item {
        width: 100%;
    }
}
</style>

<section class="content-header sa-business-page sa-page-heading">
    <h1>
        @lang('superadmin::lang.all_business')
        <small>@lang('superadmin::lang.manage_business')</small>
    </h1>
</section>

<section class="content sa-business-page">
    <div class="sa-filter-panel">
        <div class="row">
            <div class="col-md-4 col-sm-7">
                <form action="{{ route('filter.business') }}" method="post" id="form">
                    @csrf
                    <div class="form-group">
                        {!! Form::label('filter_business', __('lang_v1.all_business') . ':') !!}
                        <select name="filter_business" id="filter_business" class="form-control select2 filter_business">
                            <option value="all">@lang('lang_v1.all')</option>
                            @foreach ($business as $busi)
                                <option value="{{ $busi->id }}" {{ (string) request('filter_business') === (string) $busi->id ? 'selected' : '' }}>
                                    {{ $busi->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="col-md-8 col-sm-5 sa-filter-actions">
                <a href="{{ action('\\Modules\\Superadmin\\Http\\Controllers\\BusinessController@create') }}" class="btn btn-primary sa-add-business-btn">
                    <i class="fa fa-plus"></i> @lang('messages.add')
                </a>
            </div>
        </div>
    </div>

    @can('superadmin')
        <div class="sa-business-grid">
            @forelse ($businesses as $business)
                @php
                    $cardData = $businessCardData[$business->id] ?? [];
                    $address = $cardData['address'] ?? null;
                    $currentSubscription = $cardData['current_subscription'] ?? null;
                    $activeSubscription = $cardData['active_subscription'] ?? null;
                    $packageName = $cardData['package_name'] ?? null;
                    $remainingDays = $cardData['remaining_days'] ?? null;

                    $ownerName = '';
                    if (!empty($business->owner)) {
                        $ownerName = trim(($business->owner->first_name ?? '') . ' ' . ($business->owner->last_name ?? ''));
                    }

                    $contactNumbers = [];
                    if (!empty($address)) {
                        foreach ([$address->mobile ?? null, $address->alternate_number ?? null] as $number) {
                            if (!empty($number) && !in_array($number, $contactNumbers, true)) {
                                $contactNumbers[] = $number;
                            }
                        }
                    }
                    if (empty($contactNumbers) && !empty($business->owner) && !empty($business->owner->contact_no)) {
                        $contactNumbers[] = $business->owner->contact_no;
                    }

                    $locationParts = [];
                    if (!empty($address)) {
                        foreach ([$address->city ?? null, $address->state ?? null, $address->country ?? null] as $part) {
                            if (!empty($part) && !in_array($part, $locationParts, true)) {
                                $locationParts[] = $part;
                            }
                        }
                    }

                    $addressLines = [];
                    if (!empty($address)) {
                        if (!empty($address->landmark)) {
                            $addressLines[] = $address->landmark;
                        }

                        $cityState = array_filter([$address->city ?? null, $address->state ?? null]);
                        if (!empty($cityState)) {
                            $addressLines[] = implode(', ', $cityState);
                        }

                        $countryZip = array_filter([
                            $address->country ?? null,
                            !empty($address->zip_code) ? __('business.zip_code') . ': ' . $address->zip_code : null,
                        ]);
                        if (!empty($countryZip)) {
                            $addressLines[] = implode(' - ', $countryZip);
                        }
                    }

                    $otpEnabled = !empty($business->owner)
                        && !empty($business->owner->setting)
                        && !empty($business->owner->setting->opt_verification_enabled);
                    $recaptchaEnabled = !empty($business->owner)
                        && !empty($business->owner->setting)
                        && !empty($business->owner->setting->re_captcha_enabled);
                @endphp

                <div class="sa-business-grid__column">
                    <article class="sa-company-card">
                        <header class="sa-company-card__header">
                            <div class="sa-company-card__number {{ empty($business->company_number) ? 'is-empty' : '' }}" title="{{ $business->company_number ?: 'Not available' }}">
                                {{ $business->company_number ?: 'N/A' }}
                            </div>

                            <div class="sa-company-card__identity">
                                <div class="sa-company-card__logo">
                                    @if (!empty($business->logo))
                                        <img src="{{ url('public/uploads/business_logos/' . $business->logo) }}" alt="{{ $business->name }} logo" loading="lazy">
                                    @else
                                        <i class="fa fa-building" aria-hidden="true"></i>
                                    @endif
                                </div>

                                <div class="sa-company-card__titles">
                                    <h2 class="sa-company-card__name" title="{{ $business->name }}">{{ $business->name }}</h2>
                                    {{-- IS2103: the business id, so it is possible to tell which
                                         business a Manage/Manage New URL refers to. The URL carries
                                         the id but the card did not show it, and several businesses
                                         share similar names across tenants. --}}
                                    <div class="sa-company-card__id" style="font-size:12px;color:#64748b;margin-top:2px;">
                                        ID {{ $business->id }}@if(!empty($business->tenant_id)) · {{ $business->tenant_id }}@endif
                                    </div>
                                    {{-- Manage New sits in the header, beside the
                                         business name, so testers find it without
                                         hunting through the action grid. --}}
                                    <a class="btn btn-xs btn-warning sa-header-manage-new"
                                       href="{{ action('\\Modules\\Superadmin\\Http\\Controllers\\BusinessController@manageModuleIndex', [$business->id]) }}">
                                        <i class="fa fa-th-list"></i> Manage New
                                    </a>
                                    <p class="sa-company-card__subtitle">
                                        {{--
                                          A business whose tenant_id is set does not live in this
                                          database - this row is only its registry entry, recording
                                          that it exists and where to find it.

                                          Its owner_id points at the central super-admin account,
                                          because the central schema requires owner_id to reference
                                          a user in this database. The REAL owner is a user in the
                                          tenant database. Showing that central name here would be
                                          actively misleading, so the tenant is shown instead.

                                          This matters: the card already fell back to the owner's
                                          name for a business with no name, which is how an
                                          unrelated user's name once appeared on screen.
                                        --}}
                                        @php
                                            // A registry row: the business lives in a different
                                            // database from the one this page is reading.
                                            $isRegistryRow = !empty($business->tenant_id)
                                                && (string) $business->tenant_id !== (string) ($currentTenantId ?? '');
                                        @endphp
                                        @if ($isRegistryRow)
                                            <i class="fa fa-database" aria-hidden="true"></i>
                                            Tenant: {{ $business->tenant_id }}
                                        @elseif (!empty($ownerName))
                                            {{ $ownerName }}
                                            @if (!empty($business->is_patient) && !empty($business->owner->username))
                                                &middot; {{ $business->owner->username }}
                                            @endif
                                        @else
                                            No Owner
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </header>

                        <button type="button"
                                class="sa-company-card__details-toggle collapsed"
                                data-toggle="collapse"
                                data-target="#sa-business-details-{{ $business->id }}"
                                aria-expanded="false"
                                aria-controls="sa-business-details-{{ $business->id }}">
                            <span class="sa-company-card__details-toggle-label">
                                <i class="fa fa-address-card-o"></i>
                                Business Details
                            </span>
                            <i class="fa fa-chevron-down sa-company-card__details-toggle-chevron" aria-hidden="true"></i>
                        </button>

                        <div id="sa-business-details-{{ $business->id }}" class="collapse sa-company-card__details-collapse">
                            <div class="sa-company-card__details">
                            <div class="sa-info-row">
                                <div class="sa-info-row__icon"><i class="fa fa-map-marker"></i></div>
                                <div class="sa-info-row__content">
                                    <span class="sa-info-row__label">Location</span>
                                    {{ !empty($locationParts) ? implode(', ', $locationParts) : 'Not available' }}
                                </div>
                            </div>

                            <div class="sa-info-row">
                                <div class="sa-info-row__icon"><i class="fa fa-envelope"></i></div>
                                <div class="sa-info-row__content">
                                    <span class="sa-info-row__label">Email</span>
                                    {{ !empty($business->owner) && !empty($business->owner->email) ? $business->owner->email : 'Not available' }}
                                </div>
                            </div>

                            <div class="sa-info-row">
                                <div class="sa-info-row__icon"><i class="fa fa-phone"></i></div>
                                <div class="sa-info-row__content">
                                    <span class="sa-info-row__label">Phone</span>
                                    {{ !empty($contactNumbers) ? implode(' / ', $contactNumbers) : 'Not available' }}
                                </div>
                            </div>

                            <div class="sa-info-row">
                                <div class="sa-info-row__icon"><i class="fa fa-address-card-o"></i></div>
                                <div class="sa-info-row__content">
                                    <span class="sa-info-row__label">Address</span>
                                    @if (!empty($addressLines))
                                        @foreach ($addressLines as $addressLine)
                                            {{ $addressLine }}@if (!$loop->last)<br>@endif
                                        @endforeach
                                    @else
                                        Not available
                                    @endif
                                </div>
                            </div>
                            </div>
                        </div>

                        <div class="sa-company-card__subscription-wrap">
                            <div class="sa-subscription-panel">
                                <div class="sa-subscription-row">
                                    <div class="sa-subscription-row__label"><i class="fa fa-credit-card"></i> @lang('superadmin::lang.subscription')</div>
                                    <div class="sa-subscription-row__value">
                                        {{ $packageName ?: __('superadmin::lang.no_active_subscription') }}
                                    </div>
                                </div>

                                <div class="sa-subscription-row">
                                    <div class="sa-subscription-row__label"><i class="fa fa-clock-o"></i> Remaining</div>
                                    <div class="sa-subscription-row__value">
                                        @if ($remainingDays !== null)
                                            {{ $remainingDays }} {{ $remainingDays == 1 ? 'Day' : 'Days' }}
                                        @elseif (!empty($activeSubscription))
                                            Unlimited
                                        @elseif (!empty($currentSubscription))
                                            Expired
                                        @else
                                            Not available
                                        @endif
                                    </div>
                                </div>

                                <div class="sa-subscription-row">
                                    <div class="sa-subscription-row__label"><i class="fa fa-shield"></i> @lang('business.opt')</div>
                                    <div class="sa-subscription-row__value sa-status-text {{ $otpEnabled ? 'is-enabled' : 'is-disabled' }}">
                                        {{ $otpEnabled ? __('business.enable') : __('business.disable') }}
                                    </div>
                                </div>

                                <div class="sa-subscription-row">
                                    <div class="sa-subscription-row__label"><i class="fa fa-refresh"></i> @lang('business.reCAPTCHA')</div>
                                    <div class="sa-subscription-row__value sa-status-text {{ $recaptchaEnabled ? 'is-enabled' : 'is-disabled' }}">
                                        {{ $recaptchaEnabled ? __('business.enable') : __('business.disable') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <footer class="sa-company-card__actions">
                            <div class="sa-action-grid">
                                <div class="sa-action-grid__item">
                                    <a href="{{ action('\\Modules\\Superadmin\\Http\\Controllers\\BusinessController@show', [$business->id]) }}" class="btn btn-info">
                                        <i class="fa fa-eye"></i> @lang('messages.view')
                                    </a>
                                </div>

                                <div class="sa-action-grid__item">
                                    <button type="button" class="btn btn-primary btn-modal" data-href="{{ action('\\Modules\\Superadmin\\Http\\Controllers\\SuperadminSubscriptionsController@create', ['business_id' => $business->id]) }}" data-container=".view_modal">
                                        <i class="fa fa-plus"></i> @lang('superadmin::lang.add_subscription')
                                    </button>
                                </div>

                                <div class="sa-action-grid__item">
                                    @if ($business->is_active == 1)
                                        <a href="{{ action('\\Modules\\Superadmin\\Http\\Controllers\\BusinessController@toggleActive', [$business->id, 0]) }}" class="btn btn-danger link_confirmation">
                                            <i class="fa fa-power-off"></i> @lang('messages.deactivate')
                                        </a>
                                    @else
                                        <a href="{{ action('\\Modules\\Superadmin\\Http\\Controllers\\BusinessController@toggleActive', [$business->id, 1]) }}" class="btn btn-success link_confirmation">
                                            <i class="fa fa-power-off"></i> @lang('messages.activate')
                                        </a>
                                    @endif
                                </div>

                                @if ($business_id != $business->id)
                                    <div class="sa-action-grid__item">
                                        <a href="{{ action('\\Modules\\Superadmin\\Http\\Controllers\\BusinessController@destroy', [$business->id]) }}" class="btn btn-danger delete_business_confirmation">
                                            <i class="fa fa-trash"></i> @lang('messages.delete')
                                        </a>
                                    </div>
                                @endif

                                <div class="sa-action-grid__item">
                                    <a href="{{ action('\\Modules\\Superadmin\\Http\\Controllers\\BusinessController@manage', [$business->id]) }}#mpcs_module"
                                       data-warm-url="{{ action('\\Modules\\Superadmin\\Http\\Controllers\\BusinessController@manage', [$business->id]) }}?warm=1"
                                       class="btn btn-success js-warm-manage-page">
                                        <i class="fa fa-pencil"></i> @lang('superadmin::lang.manage')
                                    </a>
                                </div>


                                <div class="sa-action-grid__item">
                                    <button type="button"
                                            class="btn sa-btn-purple js-manage-sidebar-modal"
                                            data-business-id="{{ $business->id }}"
                                            data-fallback-href="{{ action('\\Modules\\Superadmin\\Http\\Controllers\\BusinessController@manageSidebarModules', [$business->id]) }}">
                                        <i class="fa fa-bars"></i> Manage Side Bar
                                    </button>
                                </div>

                                <div class="sa-action-grid__item">
                                    <a href="{{ action('\\Modules\\Superadmin\\Http\\Controllers\\BusinessController@loginAsBusiness', [$business->id]) }}" class="btn btn-primary">
                                        <i class="fa fa-sign-in"></i> @lang('superadmin::lang.login')
                                    </a>
                                </div>
                            </div>
                        </footer>
                    </article>
                </div>
            @empty
                <div class="col-md-12">
                    <div class="sa-business-empty">
                        <i class="fa fa-building-o fa-3x"></i>
                        <h4>No businesses found</h4>
                    </div>
                </div>
            @endforelse
        </div>

        @if ($businesses->hasPages())
            <div class="sa-business-pagination">
                {{ $businesses->links() }}
            </div>
        @endif
    @endcan

    <div class="modal fade brands_modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
</section>

@endsection

@section('javascript')

<script type="application/json" id="manage-sidebar-modal-data">{!! json_encode($manageSidebarModalData ?? ['modules' => [], 'businesses' => []], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script type="text/javascript">
(function ($) {
    'use strict';

    var modalData = {modules: {}, businesses: {}};
    try {
        modalData = JSON.parse(document.getElementById('manage-sidebar-modal-data').textContent || '{}');
    } catch (error) {
        modalData = {modules: {}, businesses: {}};
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function openAjaxFallback(button) {
        var url = $(button).data('fallback-href');
        if (!url) {
            return;
        }

        var $modal = $('.view_modal');
        $modal.html('<div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center" style="padding:35px;"><i class="fa fa-spinner fa-spin fa-2x"></i></div></div></div>').modal('show');
        $.get(url).done(function (html) {
            $modal.html(html);
        }).fail(function () {
            $modal.modal('hide');
            if (typeof toastr !== 'undefined') {
                toastr.error('Unable to open Manage Side Bar.');
            }
        });
    }

    function ensureManageSidebarModal() {
        var modules = modalData.modules || {};
        if (!Object.keys(modules).length) {
            return false;
        }

        if ($('#manage_sidebar_modules_form').length) {
            return true;
        }

        var moduleHtml = '';
        Object.keys(modules).forEach(function (moduleKey) {
            var moduleName = modules[moduleKey];
            var searchValue = String(moduleName + ' ' + moduleKey).toLowerCase();
            moduleHtml += '<div class="col-md-4 sidebar-module-item" data-search="' + escapeHtml(searchValue) + '">' +
                '<label class="well well-sm" style="display:block;cursor:pointer;min-height:52px;">' +
                '<input type="checkbox" name="enabled_modules[]" value="' + escapeHtml(moduleKey) + '">' +
                '<strong style="margin-left:8px;">' + escapeHtml(moduleName) + '</strong>' +
                '</label></div>';
        });

        $('.view_modal').html(
            '<div class="modal-dialog modal-lg" role="document"><div class="modal-content">' +
            '<form method="post" action="" id="manage_sidebar_modules_form">' +
            '<input type="hidden" name="_token" value="' + escapeHtml($('meta[name="csrf-token"]').attr('content')) + '">' +
            '<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
            '<h4 class="modal-title">Manage Side Bar</h4></div>' +
            '<div class="modal-body"><div class="alert alert-info" style="font-weight:600;">Select the globally available modules that should be visible in this business sidebar. Installed modules with a missing/disabled global status are not auto-enabled or offered here. The normal Manage permission page remains the page/tab-level permission control.</div>' +
            '<div class="row" style="margin-bottom:15px;"><div class="col-md-8"><input type="text" class="form-control" id="sidebar_module_filter" placeholder="Search modules..."></div>' +
            '<div class="col-md-4 text-right"><button type="button" class="btn btn-default" id="sidebar_select_all">Select All</button> <button type="button" class="btn btn-default" id="sidebar_clear_all">Clear All</button></div></div>' +
            '<div class="row" id="sidebar_modules_grid">' + moduleHtml + '</div></div>' +
            '<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button><button type="submit" class="btn btn-primary">Save</button></div>' +
            '</form></div></div>'
        );

        return true;
    }

    function openPreparedManageSidebarModal(businessId) {
        var business = (modalData.businesses || {})[String(businessId)];
        if (!business || !ensureManageSidebarModal()) {
            return false;
        }

        var checked = {};
        (business.checked || []).forEach(function (key) {
            checked[String(key)] = true;
        });

        var $modal = $('.view_modal');
        var $form = $('#manage_sidebar_modules_form');
        $form.attr('action', business.save_url);
        $form.find('.modal-title').text('Manage Side Bar - ' + business.name);
        $form.find('input[name="enabled_modules[]"]').each(function () {
            this.checked = !!checked[String(this.value)];
        });
        $('#sidebar_module_filter').val('');
        $('#sidebar_modules_grid .sidebar-module-item').show();

        $modal.data('business-id', String(businessId)).modal('show');
        window.setTimeout(function () {
            $('#sidebar_module_filter').trigger('focus');
        }, 0);

        return true;
    }

    // Build the one reusable modal during browser idle time. Clicking a
    // business button then only updates checkbox states and opens it.
    if (window.requestIdleCallback) {
        window.requestIdleCallback(ensureManageSidebarModal, {timeout: 600});
    } else {
        window.setTimeout(ensureManageSidebarModal, 150);
    }

    var manageWarmRequests = {};
    function warmManageUrl(warmUrl) {
        warmUrl = String(warmUrl || '');
        if (!warmUrl || manageWarmRequests[warmUrl]) {
            return Promise.resolve();
        }
        manageWarmRequests[warmUrl] = true;
        return window.fetch(warmUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            keepalive: true
        }).catch(function () {
            delete manageWarmRequests[warmUrl];
        });
    }

    $(document).on('mouseenter focus touchstart', '.js-warm-manage-page', function () {
        warmManageUrl($(this).data('warm-url'));
    });

    // Pre-warm the first visible business cards sequentially during idle time.
    // A normal click therefore opens a compiled, cache-hot Manage page even
    // when the pointer moves directly to the button without a long hover.
    function prewarmVisibleManagePages() {
        var urls = [];
        $('.js-warm-manage-page:visible').slice(0, 4).each(function () {
            var url = String($(this).data('warm-url') || '');
            if (url) { urls.push(url); }
        });
        var chain = Promise.resolve();
        urls.forEach(function (url) {
            chain = chain.then(function () { return warmManageUrl(url); });
        });
    }
    if (window.requestIdleCallback) {
        window.requestIdleCallback(prewarmVisibleManagePages, {timeout: 1000});
    } else {
        window.setTimeout(prewarmVisibleManagePages, 300);
    }

    $(document).on('click', '.js-manage-sidebar-modal', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        var businessId = $(this).data('business-id');
        if (!openPreparedManageSidebarModal(businessId)) {
            openAjaxFallback(this);
        }
    });

    $(document).on('input', '#sidebar_module_filter', function () {
        var term = String($(this).val() || '').toLowerCase();
        $('#sidebar_modules_grid .sidebar-module-item').each(function () {
            $(this).toggle(String($(this).data('search') || '').indexOf(term) !== -1);
        });
    });

    $(document).on('click', '#sidebar_select_all', function () {
        $('#sidebar_modules_grid .sidebar-module-item:visible input[type="checkbox"]').prop('checked', true);
    });

    $(document).on('click', '#sidebar_clear_all', function () {
        $('#sidebar_modules_grid .sidebar-module-item:visible input[type="checkbox"]').prop('checked', false);
    });

    $(document).on('submit', '#manage_sidebar_modules_form', function (event) {
        event.preventDefault();
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var businessId = String($('.view_modal').data('business-id') || '');
        $button.prop('disabled', true).text('Saving...');

        $.ajax({
            method: 'POST',
            url: $form.attr('action'),
            data: $form.serialize()
        }).done(function (result) {
            if (modalData.businesses && modalData.businesses[businessId]) {
                modalData.businesses[businessId].checked = $form.find('input[name="enabled_modules[]"]:checked').map(function () {
                    return this.value;
                }).get();
            }
            $('.view_modal').modal('hide');
            if (typeof toastr !== 'undefined') {
                toastr.success((result && result.msg) ? result.msg : 'Manage Side Bar saved successfully.');
            }
        }).fail(function (xhr) {
            var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
            var message = response && (response.msg || response.message)
                ? (response.msg || response.message)
                : 'Not saved. Please check the log.';
            if (typeof toastr !== 'undefined') {
                toastr.error(message);
            } else {
                alert(message);
            }
        }).always(function () {
            $button.prop('disabled', false).text('Save');
        });
    });

    $('#filter_business').select2({width: '100%'});
    $('#filter_business').change(function () {
        $('#form').submit();
    });

    $(document).on('click', 'a.delete_business_confirmation', function (event) {
        event.preventDefault();
        var href = $(this).attr('href');
        swal({
            title: @json(__('messages.sure')),
            text: 'Once deleted, you will not be able to recover this business!',
            icon: 'warning',
            buttons: true,
            dangerMode: true
        }).then(function (confirmed) {
            if (confirmed) {
                window.location.href = href;
            }
        });
    });
})(jQuery);
</script>

@endsection

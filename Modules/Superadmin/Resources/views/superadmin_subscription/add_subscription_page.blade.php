@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | ' . __('superadmin::lang.add_subscription'))

@section('content')

<style>
    .sa-subscription-page {
        max-width: 1180px;
        margin: 0 auto;
    }

    .sa-page-hero {
        background: linear-gradient(135deg, #2563eb 0%, #0ea5e9 100%);
        border-radius: 18px;
        padding: 24px 28px;
        color: #ffffff;
        box-shadow: 0 14px 35px rgba(37, 99, 235, 0.22);
        margin-bottom: 22px;
    }

    .sa-page-hero h1 {
        margin: 0;
        font-size: 26px;
        font-weight: 800;
        color: #ffffff;
    }

    .sa-page-hero p {
        margin: 8px 0 0 0;
        opacity: 0.92;
        font-size: 14px;
    }

    .sa-info-card,
    .sa-form-card,
    .sa-summary-card {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #e8eef5;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.07);
        margin-bottom: 20px;
        overflow: hidden;
    }

    .sa-card-header {
        padding: 18px 22px;
        border-bottom: 1px solid #edf2f7;
        background: #f8fafc;
    }

    .sa-card-header h3 {
        margin: 0;
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
    }

    .sa-card-header small {
        display: block;
        margin-top: 5px;
        color: #64748b;
        font-size: 12px;
    }

    .sa-card-body {
        padding: 22px;
    }

    .sa-business-title {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .sa-business-avatar {
        width: 54px;
        height: 54px;
        border-radius: 16px;
        background: linear-gradient(135deg, #0ea5e9, #2563eb);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        font-weight: 800;
        flex: 0 0 auto;
    }

    .sa-business-title h2 {
        margin: 0;
        font-size: 21px;
        font-weight: 800;
        color: #0f172a;
    }

    .sa-business-title p {
        margin: 4px 0 0 0;
        color: #64748b;
        font-size: 13px;
    }

    .sa-detail-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
    }

    .sa-detail-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 13px 14px;
        min-height: 72px;
    }

    .sa-detail-box span {
        display: block;
        color: #64748b;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 5px;
    }

    .sa-detail-box strong {
        display: block;
        color: #0f172a;
        font-size: 14px;
        font-weight: 800;
        word-break: break-word;
    }

    .sa-section-title {
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
        margin: 0 0 14px 0;
        padding-bottom: 10px;
        border-bottom: 1px dashed #dbe3ec;
    }

    .sa-form-card label {
        color: #334155;
        font-weight: 800;
        font-size: 13px;
    }

    .sa-form-card .form-control,
    .sa-form-card .select2-selection {
        min-height: 44px !important;
        border-radius: 12px !important;
        border: 1px solid #dbe3ec !important;
    }

    .sa-package-preview {
        background: linear-gradient(135deg, #f8fafc 0%, #eef6ff 100%);
        border: 1px solid #dbeafe;
        border-radius: 16px;
        padding: 18px;
        min-height: 164px;
    }

    .sa-package-preview h4 {
        margin: 0 0 10px 0;
        color: #0f172a;
        font-weight: 800;
        font-size: 17px;
    }

    .sa-package-preview .muted {
        color: #64748b;
        font-size: 13px;
    }

    .sa-preview-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 13px;
    }

    .sa-preview-item {
        background: rgba(255, 255, 255, 0.8);
        border-radius: 12px;
        padding: 10px 12px;
        border: 1px solid rgba(219, 234, 254, 0.9);
    }

    .sa-preview-item span {
        display: block;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .sa-preview-item strong {
        display: block;
        color: #0f172a;
        font-size: 13px;
        font-weight: 800;
        margin-top: 3px;
    }

    .sa-action-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 18px 22px;
        background: #f8fafc;
        border-top: 1px solid #edf2f7;
    }

    .sa-action-bar .btn {
        min-height: 42px;
        padding: 10px 18px;
        font-weight: 800;
        border-radius: 12px !important;
    }

    .sa-help-text {
        color: #64748b;
        font-size: 12px;
        margin: 0;
    }

    @media (max-width: 991px) {
        .sa-detail-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .sa-page-hero {
            padding: 20px;
        }

        .sa-page-hero h1 {
            font-size: 22px;
        }

        .sa-card-body {
            padding: 16px;
        }

        .sa-detail-grid,
        .sa-preview-grid {
            grid-template-columns: 1fr;
        }

        .sa-action-bar {
            flex-direction: column;
            align-items: stretch;
        }

        .sa-action-bar .btn,
        .sa-action-bar a {
            width: 100%;
            margin-bottom: 8px;
        }
    }
</style>

<section class="content-header">
    <h1>@lang('superadmin::lang.add_subscription')</h1>
</section>

<section class="content">
    <div class="sa-subscription-page">

        <div class="sa-page-hero">
            <h1>Subscription Management</h1>
            <p>Create a new subscription package for the selected business without using the unstable popup window.</p>
        </div>

        @if (session('status'))
            <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }}">
                {{ session('status.msg') }}
            </div>
        @endif

        <div class="sa-info-card">
            <div class="sa-card-header">
                <h3>Selected Business</h3>
                <small>Confirm the business before saving the subscription.</small>
            </div>
            <div class="sa-card-body">
                <div class="sa-business-title">
                    <div class="sa-business-avatar">
                        {{ strtoupper(substr($business->name ?? 'B', 0, 1)) }}
                    </div>
                    <div>
                        <h2>{{ $business->name ?? '-' }}</h2>
                        <p>
                            Company No: <strong>{{ $business->company_number ?? '-' }}</strong>
                        </p>
                    </div>
                </div>

                <div class="sa-detail-grid">
                    <div class="sa-detail-box">
                        <span>Owner</span>
                        <strong>
                            @if(!empty($business->owner))
                                {{ trim(($business->owner->first_name ?? '') . ' ' . ($business->owner->last_name ?? '')) }}
                            @else
                                -
                            @endif
                        </strong>
                    </div>
                    <div class="sa-detail-box">
                        <span>Email</span>
                        <strong>{{ optional($business->owner)->email ?? '-' }}</strong>
                    </div>
                    <div class="sa-detail-box">
                        <span>Current Package</span>
                        <strong>{{ optional($current_package)->name ?? __('superadmin::lang.no_active_subscription') }}</strong>
                    </div>
                    <div class="sa-detail-box">
                        <span>Current Status</span>
                        <strong>{{ !empty($current_subscription) ? ucfirst($current_subscription->status) : '-' }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="sa-form-card">
            <div class="sa-card-header">
                <h3>New Subscription Details</h3>
                <small>Select the package and payment details below.</small>
            </div>

            {!! Form::open(['url' => action('\Modules\Superadmin\Http\Controllers\SuperadminSubscriptionsController@store'), 'method' => 'post', 'id' => 'superadmin_add_subscription_page_form']) !!}
                {!! Form::hidden('business_id', $business_id) !!}
                {!! Form::hidden('source', 'business_index') !!}

                <div class="sa-card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="sa-section-title">Subscription Package</h4>
                            <div class="form-group">
                                {!! Form::label('package_id', __('superadmin::lang.subscription_packages') . ':*') !!}
                                {!! Form::select('package_id', $packages, null, ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select'), 'id' => 'package_id']) !!}
                            </div>

                            <div class="sa-package-preview" id="package_preview">
                                <h4>No package selected</h4>
                                <p class="muted">Select a package to preview available details.</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <h4 class="sa-section-title">Payment Details</h4>
                            <div class="form-group">
                                {!! Form::label('paid_via', __('superadmin::lang.paid_via') . ':*') !!}
                                {!! Form::select('paid_via', $gateways, null, ['class' => 'form-control select2', 'required', 'placeholder' => __('messages.please_select')]) !!}
                            </div>

                            <div class="form-group">
                                {!! Form::label('payment_transaction_id', __('superadmin::lang.payment_transaction_id') . ':') !!}
                                {!! Form::text('payment_transaction_id', null, ['class' => 'form-control', 'placeholder' => __('superadmin::lang.payment_transaction_id')]) !!}
                                <small class="text-muted">Optional for offline payments, recommended for online/bank/reference payments.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sa-action-bar">
                    <p class="sa-help-text">
                        Please verify the business and package before saving.
                    </p>
                    <div>
                        <a href="{{ action('\Modules\Superadmin\Http\Controllers\BusinessController@index') }}" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> Back to Businesses
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Save Subscription
                        </button>
                    </div>
                </div>
            {!! Form::close() !!}
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script>
    $(document).ready(function () {
        $('.select2').select2({ width: '100%' });

        var packageDetails = @json($package_details ?? []);

        function valueOrDash(value) {
            if (value === null || value === undefined || value === '') {
                return '-';
            }
            return value;
        }

        function renderPackagePreview(packageId) {
            var item = packageDetails[packageId];
            var $preview = $('#package_preview');

            if (!item) {
                $preview.html(
                    '<h4>No package selected</h4>' +
                    '<p class="muted">Select a package to preview available details.</p>'
                );
                return;
            }

            var html = '';
            html += '<h4>' + valueOrDash(item.name) + '</h4>';
            if (item.description) {
                html += '<p class="muted">' + item.description + '</p>';
            } else {
                html += '<p class="muted">Package details preview.</p>';
            }

            html += '<div class="sa-preview-grid">';
            html += '<div class="sa-preview-item"><span>Price</span><strong>' + valueOrDash(item.price) + '</strong></div>';
            html += '<div class="sa-preview-item"><span>Interval</span><strong>' + valueOrDash(item.interval_count) + ' ' + valueOrDash(item.interval) + '</strong></div>';
            html += '<div class="sa-preview-item"><span>Trial Days</span><strong>' + valueOrDash(item.trial_days) + '</strong></div>';
            html += '<div class="sa-preview-item"><span>Users</span><strong>' + valueOrDash(item.user_count) + '</strong></div>';
            html += '<div class="sa-preview-item"><span>Locations</span><strong>' + valueOrDash(item.location_count) + '</strong></div>';
            html += '<div class="sa-preview-item"><span>Products</span><strong>' + valueOrDash(item.product_count) + '</strong></div>';
            html += '</div>';

            $preview.html(html);
        }

        $('#package_id').on('change', function () {
            renderPackagePreview($(this).val());
        });
    });
</script>
@endsection

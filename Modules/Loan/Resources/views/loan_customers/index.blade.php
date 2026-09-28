@extends('layouts.app')
@section('title', 'Loan Customers')

@section('content')
<section class="content" style="padding:0;">
    <div class="loan-customer-entry-page loan-customer-list-page">
        <div class="loan-page-top">
            <div>
                <h2><i class="fa fa-users"></i> Loan Customers</h2>
                <p><i class="fa fa-bank"></i> Loan Module &nbsp; | &nbsp; Standalone Loan Customer Register</p>
            </div>
            <div class="loan-page-actions">
                <a href="{{ route('loan.customers.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Loan Customer
                </a>
            </div>
        </div>

        <div class="loan-card loan-filter-card">
            {!! Form::open(['method' => 'get', 'url' => route('loan.customers.index')]) !!}
                <div class="row">
                    <div class="col-md-7">
                        <div class="form-group">
                            {!! Form::label('search', 'Search') !!}
                            {!! Form::text('search', request('search'), [
                                'class' => 'form-control',
                                'placeholder' => 'Search by customer no, name, NIC, mobile or email'
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('status', 'Status') !!}
                            {!! Form::select('status', [
                                '' => 'All Status',
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'blacklisted' => 'Blacklisted',
                                'deceased' => 'Deceased',
                                'closed' => 'Closed'
                            ], request('status'), ['class' => 'form-control select2', 'style' => 'width:100%']) !!}
                        </div>
                    </div>
                    <div class="col-md-2 loan-filter-buttons">
                        <button type="submit" class="btn btn-info"><i class="fa fa-search"></i> Search</button>
                        <a href="{{ route('loan.customers.index') }}" class="btn btn-default">Reset</a>
                    </div>
                </div>
            {!! Form::close() !!}
        </div>

        <div class="loan-card loan-table-card">
            <div class="loan-card-title"><i class="fa fa-list"></i> Customer List</div>
            <div class="table-responsive loan-customer-table-wrapper">
                <table class="table table-bordered table-striped loan-customer-table">
                    <thead>
                        <tr>
                            <th class="loan-action-col">Action</th>
                            <th>Loan Customer No</th>
                            <th>Name</th>
                            <th>NIC / ID</th>
                            <th>Mobile</th>
                            <th>Email</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr>
                                <td class="loan-action-cell">
                                    <button type="button" class="btn btn-info btn-xs loan-action-trigger" aria-expanded="false">
                                        Actions <span class="caret"></span>
                                    </button>
                                    <div class="loan-action-template" style="display:none;">
                                        <ul class="loan-action-menu" role="menu">
                                            <li><a href="{{ route('loan.customers.show', $customer->id) }}"><i class="fa fa-eye"></i> View Customer</a></li>
                                            <li><a href="{{ route('loan.customers.edit', $customer->id) }}"><i class="fa fa-pencil"></i> Edit Customer</a></li>
                                            <li class="divider"></li>
                                            <li><a href="{{ route('loan.customers.ledger', $customer->id) }}"><i class="fa fa-book"></i> Customer Ledger</a></li>
                                            <li><a href="{{ route('loan.customers.applications', $customer->id) }}"><i class="fa fa-file-text-o"></i> Loan Applications</a></li>
                                            <li><a href="{{ route('loan.customers.active_loans', $customer->id) }}"><i class="fa fa-check-circle"></i> Active Loans</a></li>
                                            <li><a href="{{ route('loan.customers.documents', $customer->id) }}"><i class="fa fa-folder-open"></i> Documents</a></li>
                                            <li><a href="{{ route('loan.customers.notes', $customer->id) }}"><i class="fa fa-sticky-note"></i> Notes</a></li>
                                            <li><a href="{{ route('loan.customers.audit_log', $customer->id) }}"><i class="fa fa-history"></i> Audit Log</a></li>
                                            <li class="divider"></li>
                                            <li>
                                                <a href="{{ route('loan.customers.destroy', $customer->id) }}" class="text-danger" onclick="return confirm('Are you sure you want to delete this loan customer?')">
                                                    <i class="fa fa-trash"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                                <td><strong>{{ $customer->customer_no }}</strong></td>
                                <td>{{ $customer->display_name }}</td>
                                <td>{{ $customer->nic ?: '-' }}</td>
                                <td>{{ $customer->mobile ?: '-' }}</td>
                                <td>{{ $customer->email ?: 'No Email ID' }}</td>
                                <td>
                                    @php $status = $customer->status ?: 'active'; @endphp
                                    <span class="label label-{{ $status == 'active' ? 'success' : ($status == 'blacklisted' ? 'danger' : 'default') }} loan-status-label">
                                        {{ ucfirst($status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center loan-empty-row">No loan customers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="loan-pagination-wrap">
                {{ $customers->links() }}
            </div>
        </div>
    </div>
</section>
@endsection

@section('css')
<style>
    /* LOAN-23: Scoped only to Loan Customer List/View. Does not affect sidebar or other modules. */
    .loan-customer-entry-page {
        padding: 12px 18px 85px 18px;
        background: #f4f8fb;
    }
    .loan-customer-entry-page .loan-page-top {
        background: #fff;
        border-radius: 18px;
        padding: 22px 28px;
        margin-bottom: 20px;
        box-shadow: 0 10px 28px rgba(15, 48, 80, 0.08);
        border-left: 5px solid #1d9de0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
    }
    .loan-customer-entry-page .loan-page-top h2 {
        margin: 0;
        color: #1f3349;
        font-size: 28px;
        font-weight: 700;
        line-height: 1.2;
    }
    .loan-customer-entry-page .loan-page-top p {
        margin: 7px 0 0 0;
        color: #6f8090;
        font-size: 14px;
    }
    .loan-customer-entry-page .loan-card {
        background: #fff;
        border-radius: 18px;
        padding: 24px 26px;
        margin-bottom: 22px;
        box-shadow: 0 10px 28px rgba(15, 48, 80, 0.08);
    }
    .loan-customer-entry-page .loan-card-title {
        font-size: 19px;
        font-weight: 700;
        color: #1f3349;
        margin-bottom: 20px;
        padding-bottom: 13px;
        border-bottom: 1px solid #e7eef5;
    }
    .loan-customer-entry-page .loan-card-title i { color: #1d9de0; margin-right: 7px; }
    .loan-customer-entry-page label {
        font-size: 13px;
        color: #2c3f50;
        font-weight: 600;
        margin-bottom: 7px;
    }
    .loan-customer-entry-page .form-control,
    .loan-customer-entry-page .select2-container .select2-selection--single {
        min-height: 44px;
        border-radius: 10px !important;
        border: 1px solid #d9e4ef;
        box-shadow: none;
        font-size: 14px;
        color: #25374a;
    }
    .loan-customer-entry-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px;
        padding-left: 14px;
    }
    .loan-filter-buttons { padding-top: 25px; display: flex; gap: 8px; flex-wrap: wrap; }
    .loan-filter-buttons .btn,
    .loan-page-actions .btn {
        border-radius: 10px;
        padding: 10px 18px;
        font-weight: 700;
    }
    .loan-table-card { overflow: visible !important; }
    .loan-customer-table-wrapper {
        overflow-x: auto !important;
        overflow-y: visible !important;
        border-radius: 14px;
        border: 1px solid #e7eef5;
    }
    .loan-customer-table { margin-bottom: 0; background: #fff; }
    .loan-customer-table thead th {
        background: #f6f9fc;
        color: #657489;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: 12px 10px;
        border-bottom: 1px solid #e7eef5 !important;
        white-space: nowrap;
    }
    .loan-customer-table tbody td {
        padding: 13px 10px;
        color: #25374a;
        vertical-align: middle !important;
    }
    .loan-action-col { width: 145px !important; min-width: 145px !important; }
    .loan-action-cell { white-space: nowrap; }
    .loan-action-trigger { min-width: 98px; border-radius: 10px; font-weight: 600; }
    .loan-action-trigger.is-open { box-shadow: 0 0 0 3px rgba(91, 192, 222, .18); }
    .loan-action-template { display: none !important; }
    .loan-action-portal {
        position: fixed !important;
        z-index: 2147483647 !important;
        display: block !important;
        min-width: 240px;
        max-height: calc(100vh - 24px);
        overflow-y: auto;
        overflow-x: hidden;
        padding: 6px 0;
        margin: 0;
        list-style: none;
        border: 0;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: 0 14px 38px rgba(20, 40, 70, .28);
    }
    .loan-action-portal > li > a {
        display: block;
        padding: 8px 14px;
        font-size: 13px;
        line-height: 18px;
        color: #34495e;
        white-space: nowrap;
        text-decoration: none;
    }
    .loan-action-portal > li > a i { width: 18px; margin-right: 8px; color: #2f536f; }
    .loan-action-portal > li > a:hover,
    .loan-action-portal > li > a:focus { background: #eef7fd; color: #1f6fb2; text-decoration: none; }
    .loan-action-portal .divider { height: 1px; margin: 5px 0; overflow: hidden; background-color: #e5e9ef; }
    .loan-pagination-wrap { margin-top: 15px; }
    .loan-empty-row { padding: 30px !important; color: #6f8090; }
    @media (max-width: 991px) {
        .loan-customer-entry-page .loan-page-top { display: block; }
        .loan-page-actions { margin-top: 15px; }
        .loan-page-actions .btn { width: 100%; }
        .loan-filter-buttons { padding-top: 0; }
        .loan-filter-buttons .btn { width: 100%; }
    }
</style>
@endsection

@section('javascript')
<script>
(function ($) {
    'use strict';

    var $activeMenu = null;
    var $activeButton = null;

    function closeLoanActionMenu() {
        if ($activeMenu) {
            $activeMenu.remove();
            $activeMenu = null;
        }
        if ($activeButton) {
            $activeButton.removeClass('is-open').attr('aria-expanded', 'false');
            $activeButton = null;
        }
    }

    function positionMenu($button, $menu) {
        var rect = $button[0].getBoundingClientRect();
        var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
        var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
        var menuWidth = Math.max($menu.outerWidth(), 240);
        var menuHeight = $menu.outerHeight();

        if (!menuHeight || menuHeight < 60) {
            menuHeight = 340;
        }

        var left = rect.left;
        var top = rect.bottom + 6;

        if ((left + menuWidth + 12) > viewportWidth) {
            left = viewportWidth - menuWidth - 12;
        }
        if (left < 8) {
            left = 8;
        }
        if ((top + menuHeight + 12) > viewportHeight) {
            top = rect.top - menuHeight - 6;
        }
        if (top < 8) {
            top = 8;
        }

        $menu.css({ top: top + 'px', left: left + 'px', minWidth: menuWidth + 'px' });
    }

    $(document).on('click', '.loan-action-trigger', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $button = $(this);
        if ($activeButton && $activeButton[0] === $button[0]) {
            closeLoanActionMenu();
            return;
        }
        closeLoanActionMenu();

        var $template = $button.closest('.loan-action-cell').find('.loan-action-template .loan-action-menu').first();
        if (!$template.length) { return; }

        var $menu = $template.clone(false, false).removeClass('loan-action-menu').addClass('loan-action-portal').appendTo('body');
        $activeMenu = $menu;
        $activeButton = $button.addClass('is-open').attr('aria-expanded', 'true');
        positionMenu($button, $menu);
    });

    $(document).on('click', '.loan-action-portal', function (e) { e.stopPropagation(); });
    $(document).on('click', closeLoanActionMenu);
    $(window).on('resize scroll', closeLoanActionMenu);
    $(document).on('keyup', function (e) { if (e.key === 'Escape') { closeLoanActionMenu(); } });
})(jQuery);
</script>
@endsection

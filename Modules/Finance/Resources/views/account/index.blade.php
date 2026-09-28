@extends('layouts.app')
@section('title', __('lang_v1.payment_accounts'))

@section('content')

@if(session('status') && data_get(session('status'), 'success'))
    <div id="finance-save-success" class="finance-save-success" role="alert">
        <i class="fa fa-check-circle" aria-hidden="true"></i>
        <span>{{ data_get(session('status'), 'msg', 'Successfully Saved') }}</span>
    </div>
@endif

@php
                    
    $business_id = request()
        ->session()
        ->get('user.business_id');
    
    $pacakge_details = [];
        
    $subscription = Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
    if (!empty($subscription)) {
        $pacakge_details = $subscription->package_details;
    }

@endphp

<link rel="stylesheet"
    href="{{ asset('plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.css?v='.$asset_v) }}">

<style>
    /* IS2258 #2: Finance save confirmation - deliberately local to this page
       so theme/toastr settings cannot hide or recolour the requested message. */
    .finance-save-success{
        display:flex;
        align-items:center;
        gap:10px;
        margin:12px 15px 16px;
        padding:13px 18px;
        border-radius:8px;
        background:#16a34a !important;
        color:#fff !important;
        font-weight:700;
        font-size:15px;
        box-shadow:0 5px 16px rgba(22,163,74,.22);
    }
    .finance-save-success i,
    .finance-save-success span{color:#fff !important;}

    /* S356: Finance Table Standard v1.0 - Account List
       Standalone Finance module only. One fixed column model for header/body/export, no main view dependency. */
    .finance-account-table-wrap{
        width:100%;
        overflow-x:auto !important;
        overflow-y:visible !important;
        padding-bottom:10px;
        /* One horizontal scroll owner only.  DataTables scrollX is disabled
           below; this wrapper handles narrow screens without splitting the
           header/body into separate moving regions. */
        overscroll-behavior-x:contain;
        overflow-anchor:none;
    }
    #other_account_table_wrapper{
        width:100% !important;
        overflow-anchor:none;
    }
    /* Compatibility only: if an old cached DataTables instance briefly
       creates scroll wrappers, never allow them to become a second vertical
       or horizontal scroll owner. */
    #other_account_table_wrapper .dataTables_scroll,
    #other_account_table_wrapper .dataTables_scrollBody{
        width:100% !important;
        overflow:visible !important;
    }
    /* S708: List Accounts owns a single visible THEAD.  If an older/global
       DataTables path tries to rebuild this table with scrollX it creates a
       second scrollHead clone.  Keep that compatibility clone hidden. */
    #other_account_table_wrapper .dataTables_scrollHead{
        display:none !important;
        width:100% !important;
        overflow:hidden !important;
    }
    #other_account_table,
    #other_account_table_wrapper .dataTables_scrollHeadInner table,
    #other_account_table_wrapper .dataTables_scrollBody table{
        table-layout:fixed !important;
        width:100% !important;
        min-width:1032px !important;
        max-width:none !important;
        margin:0 !important;
        border-collapse:collapse !important;
    }
    #other_account_table th,
    #other_account_table td,
    #other_account_table_wrapper .dataTables_scrollHead th,
    #other_account_table_wrapper .dataTables_scrollBody td{
        box-sizing:border-box !important;
        vertical-align:middle !important;
        overflow:hidden !important;
        text-overflow:ellipsis !important;
    }
    #other_account_table thead th,
    #other_account_table_wrapper .dataTables_scrollHead th{
        height:58px !important;
        padding:8px 8px !important;
        line-height:18px !important;
        text-align:center !important;
        font-weight:800 !important;
        white-space:normal !important;
        color:#526a86 !important;
    }
    #other_account_table tbody td,
    #other_account_table_wrapper .dataTables_scrollBody td{
        height:48px !important;
        padding:7px 10px !important;
        line-height:20px !important;
        white-space:nowrap !important;
        color:#152238;
        font-size:15px;
    }
    #other_account_table .finance-th-line{display:block; width:100%; text-align:center; white-space:nowrap;}
    #other_account_table .finance-th-sep{display:inline-block; padding:0 2px;}

    /* One source of truth for the Account List column widths */
    #other_account_table col.col-expand,
    #other_account_table th:nth-child(1),
    #other_account_table td:nth-child(1){width:52px !important; min-width:52px !important; max-width:52px !important; text-align:center !important;}
    #other_account_table col.col-name,
    #other_account_table th:nth-child(2),
    #other_account_table td:nth-child(2){width:235px !important; min-width:235px !important; max-width:235px !important; text-align:left !important;}
    #other_account_table col.col-location,
    #other_account_table th:nth-child(3),
    #other_account_table td:nth-child(3){width:105px !important; min-width:105px !important; max-width:105px !important; text-align:center !important;}
    #other_account_table col.col-type,
    #other_account_table th:nth-child(4),
    #other_account_table td:nth-child(4){width:270px !important; min-width:270px !important; max-width:270px !important; text-align:left !important; white-space:normal !important;}
    #other_account_table col.col-account-no,
    #other_account_table th:nth-child(5),
    #other_account_table td:nth-child(5){width:80px !important; min-width:80px !important; max-width:80px !important; text-align:center !important;}
    #other_account_table col.col-balance,
    #other_account_table th:nth-child(6),
    #other_account_table td:nth-child(6){width:150px !important; min-width:150px !important; max-width:150px !important; text-align:right !important;}
    #other_account_table col.col-action,
    #other_account_table th:nth-child(7),
    #other_account_table td:nth-child(7){width:140px !important; min-width:140px !important; max-width:140px !important; text-align:center !important; overflow:visible !important;}

    #other_account_table th:nth-child(2), #other_account_table th:nth-child(4){text-align:center !important;}
    #other_account_table td:nth-child(4){line-height:21px !important;}
    #other_account_table td.finance-amount-cell, #other_account_table th.finance-amount-cell{text-align:right !important;}
    #other_account_table .finance-account-plus{display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:7px; color:#fff!important; background:#06b981; box-shadow:0 5px 13px rgba(6,185,129,.22); cursor:pointer; font-size:15px!important;}
    #other_account_table .finance-account-plus.is-open{background:#2563eb;}
    #other_account_table .finance-account-details{display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:10px 18px; padding:13px 16px; background:#f8fbff; border:1px solid #e4edf8; border-radius:12px; color:#152238; font-size:14px;}
    #other_account_table .finance-account-details strong{color:#506985; font-weight:700;}

    .finance-account-action-wrap{position:relative; display:inline-block; width:124px; max-width:124px;}
    .finance-account-action-main{width:124px; min-height:44px; padding:10px 12px; border:0; border-radius:10px; background:linear-gradient(135deg,#2563eb,#38bdf8); color:#fff!important; font-weight:800; font-size:15px; line-height:20px; box-shadow:0 8px 18px rgba(37,99,235,.20);}
    .finance-account-action-main i{font-size:16px; margin-right:5px;}
    /*
     * IS2248 #2: the per-row source menu is never opened inside DataTables.
     * A single body-level fixed portal is used instead (see JS below). This
     * prevents the scroll body from changing its overflow height/scroll position
     * when Action is clicked, so the account list no longer jumps up/down.
     */
    .finance-account-action-menu{display:none!important;}
    #finance-account-action-portal{
        display:none;
        position:fixed;
        z-index:2147483000;
        width:190px;
        padding:10px;
        background:#fff;
        border:1px solid #e6edf6;
        border-radius:13px;
        box-shadow:0 16px 35px rgba(15,23,42,.20);
    }
    #finance-account-action-portal .btn,
    #finance-account-action-portal a,
    #finance-account-action-portal button{display:flex!important; align-items:center; justify-content:flex-start; width:100%!important; min-height:44px!important; margin:0 0 7px 0!important; padding:10px 13px!important; border:0!important; border-radius:8px!important; color:#fff!important; font-size:15px!important; font-weight:800!important; line-height:20px!important; text-align:left!important; white-space:nowrap!important; box-shadow:none!important; text-decoration:none!important;}
    #finance-account-action-portal .btn:last-child,
    #finance-account-action-portal a:last-child,
    #finance-account-action-portal button:last-child{margin-bottom:0!important;}
    #finance-account-action-portal i,
    #finance-account-action-portal .fa,
    #finance-account-action-portal .glyphicon{width:18px; margin-right:7px; font-size:16px!important; color:#fff!important;}
    .finance-action-edit{background:linear-gradient(135deg,#8b5cf6,#c026d3)!important;}
    .finance-action-book{background:linear-gradient(135deg,#f59e0b,#fb923c)!important;}
    .finance-action-close{background:linear-gradient(135deg,#ef4444,#f87171)!important;}
    .finance-action-transfer{background:linear-gradient(135deg,#0ea5e9,#2563eb)!important;}

    /* IS2246 #2: permanent Finance-owned Transfer control. Keep it independent
       from the generic .transfer_btn class because global ERP rules may hide it. */
    .finance-account-toolbar .finance-toolbar-transfer,
    .finance-account-toolbar .finance-toolbar-transfer:focus {
        display:inline-flex !important;
        visibility:visible !important;
        opacity:1 !important;
        align-items:center;
        justify-content:center;
        min-width:105px;
        background:#475569 !important;
        border-color:#475569 !important;
        color:#fff !important;
    }
    .finance-account-toolbar .finance-toolbar-transfer:hover,
    .finance-account-toolbar .finance-toolbar-transfer:active {
        background:#334155 !important;
        border-color:#334155 !important;
        color:#fff !important;
    }
    .finance-action-deposit{background:linear-gradient(135deg,#14b8a6,#22c55e)!important;}
    .finance-action-notes{background:linear-gradient(135deg,#06b6d4,#14b8a6)!important;}
    .finance-action-enabled{background:linear-gradient(135deg,#16a34a,#22c55e)!important;}
    .finance-action-default{background:linear-gradient(135deg,#64748b,#94a3b8)!important;}


    /* S357: Compact one-row filter bar for Finance Table Standard v1.0 */
    .finance-account-filter-row{
        display:grid !important;
        grid-template-columns:0.8fr 1.35fr 1.15fr 1.35fr 1.1fr;
        gap:12px 14px;
        align-items:end;
        margin:0 !important;
    }
    .finance-account-filter-row:before,
    .finance-account-filter-row:after{display:none !important; content:none !important;}
    .finance-account-filter-row .finance-filter-item{
        width:100% !important;
        padding:0 !important;
        float:none !important;
        min-width:0 !important;
    }
    .finance-account-filter-row .form-group{margin-bottom:0 !important;}
    .finance-account-filter-row label{
        display:block;
        margin-bottom:7px;
        color:#1f334d;
        font-weight:800;
        font-size:14px;
        line-height:17px;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .finance-account-filter-row .select2-container,
    .finance-account-filter-row .form-control{width:100% !important;}
    .finance-account-filter-row .select2-selection--single{
        min-height:44px !important;
        border-radius:10px !important;
        border-color:#dce8f5 !important;
    }
    .finance-account-filter-row .select2-selection__rendered{line-height:42px !important;}
    .finance-account-filter-row .select2-selection__arrow{height:42px !important;}
    .finance-account-filters-panel .box-body{padding-bottom:12px !important;}
    .finance-account-filters-panel{margin-bottom:14px !important;}
    @media (max-width:1199px){
        .finance-account-filter-row{grid-template-columns:repeat(3,minmax(160px,1fr));}
    }
    @media (max-width:767px){
        .finance-account-filter-row{grid-template-columns:1fr;}
    }

</style>


<!-- Content Header (Page header) -->

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('account.manage_your_account')</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">@lang('lang_v1.payment_accounts')</a></li>
                    <li><span>@lang('account.manage_your_account')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner">
    @can('account.access')
    <div class="row">
        <div class="col-sm-12">
            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="@if(empty(session('status.tab')) && !request()->has('ldt_tab')) active @endif">
                        <a href="#other_accounts" data-toggle="tab">
                            <i class="fa fa-book"></i> <strong>@lang('account.list_accounts')</strong>
                        </a>
                    </li>

                    <li class="@if(session('status.tab') == 'list_deposit_transfer' || request()->has('ldt_tab')) active @endif">
                        <a href="#list_deposit_transfer" data-toggle="tab">
                            <i class="fa fa-exchange"></i> <strong>@lang('lang_v1.list_deposit_transfer')</strong>
                        </a>
                    </li>

                    <li>
                        <a href="#account_types" data-toggle="tab">
                            <i class="fa fa-list"></i> <strong>
                                @lang('lang_v1.account_types') </strong>
                        </a>
                    </li>

                    <li>
                        <a href="#account_groups" data-toggle="tab">
                            <i class="fa fa-object-group"></i> <strong>
                                @lang('lang_v1.account_groups') </strong>
                        </a>
                    </li>
                    @can('account.settings')
                    <li>
                        <a href="#account_settings" data-toggle="tab">
                            <i class="fa fa-cogs"></i> <strong>
                                @lang('lang_v1.account_settings') </strong>
                        </a>
                    </li>
                    @endcan
                    <li class="@if(session('status.tab') == 'cheques_ob_details') active @endif">
                        <a href="#cheques_ob_details" data-toggle="tab">
                            <i class="fa fa-list"></i> <strong>
                                @lang('lang_v1.cheques_ob_details') </strong>
                        </a>
                    </li>
                    
                </ul>
                <div class="tab-content">
                    <div class="tab-pane @if(empty(session('status.tab')) && !request()->has('ldt_tab')) active @endif" id="other_accounts">
                        <div class="row">
                          
                            <div class="col-md-12">
                                <div class="row finance-account-toolbar">
                                    <button style="margin-right: 25px;" type="button" id="finance_add_account_button"
                                        class="btn btn-sm btn-primary finance-account-modal-trigger pull-right"
                                        data-container=".account_model"
                                        data-href="{{ url('/finance/account/create') }}">
                                        <i class="fa fa-plus"></i> @lang('messages.add')</button>

                                    <button style="margin-right: 25px;" type="button"
                                        data-href="{{ url('/finance/finance-fund-transfer') }}"
                                        class="btn btn-sm btn-default finance-account-modal-trigger pull-right finance-toolbar-transfer"
                                        data-container=".account_model">
                                        <i class="fa fa-exchange"></i> @lang('account.fund_transfer')</button>

                                    <button style="margin-right: 25px;"
                                        data-href="{{ url('/finance/finance-deposit/card') }}"
                                        class="btn btn-sm btn-warning finance-account-modal-trigger pull-right deposit_btn"
                                        data-container=".account_model"><i class="fa fa-money"></i>
                                        @lang("account.card_deposit")</button>

                                    <button style="margin-right: 25px;"
                                        data-href="{{ route('finance.list-accounts.live.cheque-deposit.form') }}"
                                        class="btn btn-sm btn-info finance-account-modal-trigger pull-right deposit_btn"
                                        data-container=".account_model"><i class="fa fa-address-card-o"></i>
                                        @lang("account.cheque_deposit")</button>
                                    
                                   @if(!empty($pacakge_details['realize_cheque']))
                                        @can('deposits.realize_cheque')    
                                            <button style="margin-right: 25px;"
                                                data-href="{{ url('/finance/finance-realize-cheque-deposit') }}"
                                                class="btn btn-sm btn-danger finance-account-modal-trigger pull-right deposit_btn"
                                                data-container=".account_model"><i class="fa fa-address-card-o"></i>
                                                @lang("account.realize_cheque")</button>
                                        @endcan
                                    @endif
                                        
                                        
                                    <button style="margin-right: 25px;"
                                        data-href="{{ url('/finance/finance-deposit/cash') }}"
                                        class="btn btn-sm btn-success finance-account-modal-trigger pull-right deposit_btn"
                                        data-container=".account_model"><i class="fa fa-money"></i>
                                        @lang("account.cash_deposit")</button>
                                </div>
                            </div>
                            <div class="col-md-12 finance-account-filters-panel">
                            @component('components.filters', ['title' => __('report.filters')])
                                <div class="finance-account-filter-row">
                                    <div class="finance-filter-item finance-filter-account-type">
                                        <div class="form-group">
                                            {!! Form::label('account_type',  __('Account Type') . ':') !!}
                                            {!! Form::select('account_type', $account_types_opts, null, ['id'=>'account_type','class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                                        </div>
                                    </div>
                                    <div class="finance-filter-item finance-filter-account-sub-type">
                                        <div class="form-group">
                                            {!! Form::label('account_sub_type',  __('Account Sub Type') . ':') !!}
                                            {!! Form::select('account_sub_type', $sub_acn_arr, null, ['id'=>'account_sub_type','class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                                        </div>
                                    </div>
                                    <div class="finance-filter-item finance-filter-account-group">
                                        <div class="form-group">
                                            {!! Form::label('account_group',  __('Account Group') . ':') !!}
                                            {!! Form::select('account_group', $account_groups, null, ['id'=>'account_group','class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                                        </div>
                                    </div>
                                    <div class="finance-filter-item finance-filter-account-name">
                                        <div class="form-group">
                                            {!! Form::label('account_name',  __('Account Name') . ':') !!}
                                            {!! Form::select('account_name', $accounts, null, ['id'=>'account_name','class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
                                        </div>
                                    </div>
                                    <div class="finance-filter-item finance-filter-location">
                                        <div class="form-group">
                                            {!! Form::label('location_id',  __('lang_v1.location') . ':') !!}
                                            {!! Form::select('location_id', $_business_locations, null, ['id'=>'list_accounts_location_id','class' => 'form-control select2', 'style' => 'width:100%']); !!}
                                        </div>
                                    </div>
                                </div>
                            @endcomponent
                            </div>
                            <div class="col-sm-12">
                                <div class="finance-account-table-wrap">
                                <table class="table table-bordered table-striped" id="other_account_table" style="width:100%; min-width:1032px;">
                                    <colgroup>
                                        <col class="col-expand">
                                        <col class="col-name">
                                        <col class="col-location">
                                        <col class="col-type">
                                        <col class="col-account-no">
                                        <col class="col-balance">
                                        <col class="col-action">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th><span class="finance-th-line">@lang('lang_v1.name')</span></th>
                                            <th><span class="finance-th-line">@lang('lang_v1.location')</span></th>
                                            <th><span class="finance-th-line">@lang('lang_v1.account_type')</span><span class="finance-th-line">/ @lang('lang_v1.account_sub_type')</span></th>
                                            <th><span class="finance-th-line">Account</span><span class="finance-th-line">Number</span></th>
                                            <th class="finance-amount-cell"><span class="finance-th-line">@lang('lang_v1.balance')</span></th>
                                            <th class="notexport"><span class="finance-th-line">@lang('messages.action')</span></th>
                                        </tr>
                                    </thead>
                                </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="account_types">
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary btn-modal pull-right" @if(!$account_access)
                                    disabled @endif data-href="{{action('AccountTypeController@create')}}"
                                    data-container="#account_type_modal">
                                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-12">
                                <table class="table table-striped table-bordered" id="account_types_table"
                                    style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>@lang( 'lang_v1.name' )</th>
                                            <th>@lang( 'messages.action' )</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($account_types as $account_type)
                                        <tr class="account_type_{{$account_type->id}}">
                                            <th>{{$account_type->name}}</th>
                                            <td>

                                                {!! Form::open(['url' => action('AccountTypeController@destroy',
                                                $account_type->id), 'method' => 'delete' ]) !!}
                                                <button type="button" class="btn btn-primary btn-modal btn-xs"
                                                    data-href="{{action('AccountTypeController@edit', $account_type->id)}}"
                                                    data-container="#account_type_modal">
                                                    <i class="fa fa-edit"></i> @lang( 'messages.edit' )</button>

                                                <button type="button" class="btn btn-danger btn-xs delete_account_type">
                                                    <i class="fa fa-trash"></i> @lang( 'messages.delete' )</button>
                                                {!! Form::close() !!}
                                            </td>
                                        </tr>
                                        @foreach($account_type->sub_types as $sub_type)
                                        <tr>
                                            <td>&nbsp;&nbsp;-- {{$sub_type->name}}</td>
                                            <td>


                                                {!! Form::open(['url' => action('AccountTypeController@destroy',
                                                $sub_type->id), 'method' => 'delete' ]) !!}
                                                <button type="button" class="btn btn-primary btn-modal btn-xs"
                                                    data-href="{{action('AccountTypeController@edit', $sub_type->id)}}"
                                                    data-container="#account_type_modal">
                                                    <i class="fa fa-edit"></i> @lang( 'messages.edit' )</button>
                                                <button type="button" class="btn btn-danger btn-xs delete_account_type">
                                                    <i class="fa fa-trash"></i> @lang( 'messages.delete' )</button>
                                                {!! Form::close() !!}
                                            </td>
                                        </tr>
                                        @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="account_groups">
                        <div class="row">
                            <!--<div class="col-md-12">-->
                            <!--    <button type="button" class="btn btn-primary btn-modal pull-right" @if(!$account_access)-->
                            <!--        disabled @endif id="add_acount_group_btn"-->
                            <!--        data-href="{{action('AccountGroupController@create')}}"-->
                            <!--        data-container="#account_groups_modal">-->
                            <!--        <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>-->
                            <!--</div>-->
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-12">
                                <table class="table table-striped table-bordered" id="account_groups_table"
                                    style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>@lang( 'lang_v1.name' )</th>
                                            <th>@lang( 'lang_v1.account_type_name' )</th>
                                            <th>@lang( 'lang_v1.note' )</th>
                                            <th>@lang( 'messages.action' )</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @can('account.settings')
                    <div class="tab-pane" id="account_settings">
                        @include('finance::account_settings.index')
                    </div>
                    @endcan
                    <div class="tab-pane @if(session('status.tab') == 'list_deposit_transfer' || request()->has('ldt_tab')) active @endif" id="list_deposit_transfer">
                        @include('finance::account.list_deposit_transfer')
                    </div>
                    
                    <div class="tab-pane @if(session('status.tab') == 'cheques_ob_details') active @endif" id="cheques_ob_details">
                        @include('account.cheques_opening_balance_details')
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    <div class="modal fade account_model"  role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade"  role="dialog" aria-labelledby="gridSystemModalLabel" id="account_type_modal">
    </div>
    <div class="modal fade"  role="dialog" aria-labelledby="gridSystemModalLabel"
        id="account_groups_modal">
    </div>
</section>
<!-- /.content -->

@endsection

@section('javascript')
<script src="{{ asset('plugins/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js?v=' . $asset_v) }}"></script>
<script>
    
    $(document).on('click','#add_amount',function(){
      var item_element = `
        <div class="form-group col-sm-4 added-amount">
            <div class="input-group">
              {!! Form::number('amount[]', null, ['class' => 'form-control input_amount', 'required','placeholder' => __(
                'sale.amount'),'step' => 'any']); !!}
              <span  class="input-group-addon bg-danger remove_amount"> - </span>
            </div>
          </div>
      `;
      
      $("#amounts_row").append(item_element);
  });
  
    /*
     * IS2252: Finance-owned modal loader.
     *
     * The ERP has a global `.btn-modal` click handler. List Accounts had become
     * half-migrated: row actions used `finance-account-modal-trigger`, but the
     * local handler had been lost and the toolbar still used `.btn-modal`.
     * Depending on which global delegated handler ran first, unrelated JSON
     * (notably dashboard chart payloads) could be written into `.account_model`
     * as visible text instead of the requested Finance form.
     *
     * Every Finance Account form now has one owner. The trigger deliberately
     * does NOT carry `.btn-modal`, and this handler stops the click before any
     * unrelated delegated handler can consume it.
     */
    function financeAccountModalContainer(selector) {
        selector = selector || '.account_model';
        var $container = $(selector).first();

        if (!$container.length) {
            if (selector.charAt(0) === '#') {
                $('body').append('<div class="modal fade" id="' + selector.substring(1) + '" role="dialog"></div>');
            } else {
                $('body').append('<div class="modal fade ' + selector.replace(/^\./, '') + '" role="dialog"></div>');
            }
            $container = $(selector).first();
        }

        // A modal inside DataTables/tab wrappers can inherit overflow/stacking
        // rules. Keep the reusable Finance modal directly under BODY.
        if (!$container.parent().is('body')) {
            $container.appendTo(document.body);
        }

        return $container;
    }

    function financeAccountModalError($container, message) {
        var safeMessage = $('<div/>').text(message || 'Unable to open the Finance form.').html();
        $container.html(
            '<div class="modal-dialog" role="document">' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        '<h4 class="modal-title">Finance</h4>' +
                    '</div>' +
                    '<div class="modal-body"><div class="alert alert-danger" style="margin-bottom:0">' + safeMessage + '</div></div>' +
                '</div>' +
            '</div>'
        ).modal('show');
    }

    function financeNormaliseModalHtml(responseText) {
        var html = responseText == null ? '' : String(responseText);
        var trimmed = $.trim(html);
        if (!trimmed) return '';

        // Be compatibility-safe if a controller wraps HTML in JSON, but never
        // print arbitrary JSON on the page. This is also the guard against the
        // exact raw-code symptom shown in IS2252.
        if (trimmed.charAt(0) === '{' || trimmed.charAt(0) === '[') {
            try {
                var parsed = JSON.parse(trimmed);
                if (parsed && typeof parsed.html === 'string') {
                    html = parsed.html;
                } else if (parsed && typeof parsed.data === 'string' && /<(?:div|form)[\s>]/i.test(parsed.data)) {
                    html = parsed.data;
                } else {
                    return '';
                }
            } catch (ignore) {
                return '';
            }
        }

        // A full application/error/login page is not a modal fragment. Never
        // inject it into the current page even if it happens to contain a form.
        if (/<!doctype\s+html|<html\b/i.test(html)) {
            return '';
        }

        // Finance modal responses are Blade fragments containing either a
        // modal-dialog or a form. Reject anything else instead of dumping it.
        if (!/(?:class=["'][^"']*modal-dialog\b|<form\b)/i.test(html)) {
            return '';
        }

        return html;
    }

    function financeOpenAccountModal($trigger) {
        var url = $trigger.attr('data-href') || $trigger.data('href');
        if (!url || url === '#') return;

        var $container = financeAccountModalContainer(
            $trigger.attr('data-container') || $trigger.data('container') || '.account_model'
        );
        if (!$container.length) return;

        if ($trigger.data('finance-modal-loading')) return;
        $trigger.data('finance-modal-loading', true).prop('disabled', true);

        $container.html(
            '<div class="modal-dialog" role="document">' +
                '<div class="modal-content">' +
                    '<div class="modal-body text-center" style="padding:32px">' +
                        '<i class="fa fa-spinner fa-spin"></i> Loading...' +
                    '</div>' +
                '</div>' +
            '</div>'
        ).modal({backdrop: 'static', keyboard: false, show: true});

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            cache: false,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).done(function(responseText) {
            var html = financeNormaliseModalHtml(responseText);
            if (!html) {
                console.error('Finance modal rejected a non-form response from:', url, responseText);
                financeAccountModalError($container, 'Unable to open the Finance form. The server returned an unexpected response.');
                return;
            }

            $container.html(html);
            $container.modal({backdrop: 'static', keyboard: false, show: true});
        }).fail(function(xhr) {
            var message = 'Unable to open the Finance form.';
            if (xhr && xhr.responseJSON) {
                message = xhr.responseJSON.msg || xhr.responseJSON.message || message;
            } else if (xhr && xhr.status) {
                message += ' Server returned ' + xhr.status + '.';
            }
            financeAccountModalError($container, message);
        }).always(function() {
            $trigger.data('finance-modal-loading', false).prop('disabled', false);
        });
    }

    $(document)
        .off('click.financeAccountOwnedModal', '.finance-account-modal-trigger[data-href]')
        .on('click.financeAccountOwnedModal', '.finance-account-modal-trigger[data-href]', function(event) {
            event.preventDefault();
            event.stopPropagation();
            if (event.stopImmediatePropagation) event.stopImmediatePropagation();
            $('#finance-account-action-portal').hide().empty();
            financeOpenAccountModal($(this));
        });

    $(document).ready(function(){
        /* Keep the global sidebar in the state chosen by the user/layout.
           Forcing sidebar-collapse here caused the content width to transition
           after render and made List Accounts move while the page was used. */
            $(document).on('click', 'button.close_account', function(){
                var rowData = other_account_table.row($(this).closest('tr')).data(); 
                var accountName = rowData.name; 
                swal({
                    title: "You are going to Close this " + accountName + " Account",
                    text: "This cannot be reversed again. Are you sure to close this account?",
                    icon: "warning",
                    buttons: {
                        cancel: {
                            text: "No",
                            value: false,
                            visible: true,
                            className: "btn btn-primary"
                        },
                        confirm: {
                            text: "Yes",
                            value: true,
                            visible: true,
                            className: "btn btn-danger"
                        }
                    },
                    dangerMode: true,
                }).then((willDelete)=>{
                    if(willDelete){
                        var url = $(this).data('url');
                        $.ajax({
                            method: "get",
                            url: url,
                            dataType: "json",
                            success: function(result){
                                if(result.success == true){
                                    toastr.success(result.msg);
                                    other_account_table.ajax.reload();
                                }else{
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

        $(document).on('click', 'button.disable_status_account', function(){
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete)=>{
                if(willDelete){
                     var url = $(this).data('url');

                     $.ajax({
                         method: "get",
                         url: url,
                         dataType: "json",
                         success: function(result){
                             if(result.success == true){
                                toastr.success(result.msg);
                                
                                other_account_table.ajax.reload();
                             }else{
                                toastr.error(result.msg);
                            }

                        }
                    });
                }
            });
        });

        /*
         * Add/Edit Account submit guard.
         *
         * Give the user immediate feedback and prevent accidental duplicate
         * submissions while the request is being saved.  This is intentionally
         * owned by the List Accounts page because both Add and Edit forms are
         * loaded into the same `.account_model` modal.
         */
        function financeAccountSetSaving($form, saving) {
            var $button = $form.find('button.finance-account-submit[type="submit"]').first();
            if (!$button.length) {
                $button = $form.find('button[type="submit"]').first();
            }

            if (saving) {
                if (typeof $button.data('finance-original-html') === 'undefined') {
                    $button.data('finance-original-html', $button.html());
                }
                $form.data('finance-account-saving', true);
                $button
                    .prop('disabled', true)
                    .attr('aria-disabled', 'true')
                    .attr('aria-busy', 'true')
                    .html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> ' + ($button.data('saving-text') || 'Saving...'));
            } else {
                $form.data('finance-account-saving', false);
                $button
                    .prop('disabled', false)
                    .removeAttr('aria-disabled')
                    .removeAttr('aria-busy');

                var originalHtml = $button.data('finance-original-html');
                if (typeof originalHtml !== 'undefined') {
                    $button.html(originalHtml);
                }
            }
        }

        window.financeSubmitAccountForm = function($form) {
            if (!$form || !$form.length || $form.data('finance-account-saving') === true) {
                return;
            }

            /*
             * 19 Sep 2026 - FIRST CLICK owns the complete save lifecycle.
             *
             * Give feedback before validation/lookup waiting, because the Add
             * Account form may still be finishing its Account Type dependent
             * AJAX lookups.  The previous order validated first; if Account
             * Number (or another auto-loaded value) had not arrived yet, the
             * first click was silently rejected and the second click appeared
             * to be the one that worked.
             */
            financeAccountSetSaving($form, true);

            var submitRequest = function() {
                // The same first click continues here after any pending lookup.
                // If the form is genuinely incomplete, restore Save immediately
                // and let the existing validation messages identify the field.
                if (typeof $form.valid === 'function' && !$form.valid()) {
                    financeAccountSetSaving($form, false);
                    var $firstInvalid = $form.find('.error:input, input.error, select.error, textarea.error').filter(':visible').first();
                    if ($firstInvalid.length) {
                        $firstInvalid.trigger('focus');
                    }
                    return;
                }

                var requestSucceeded = false;

                $.ajax({
                    method: "POST",
                    url: $form.attr("action"),
                    dataType: "json",
                    data: $form.serialize(),
                    success: function(result){
                        if(result.success == true){
                            requestSucceeded = true;
                            $('div.account_model').modal('hide');
                            toastr.success(result.msg);
                            other_account_table.ajax.reload(null, false);
                        }else{
                            toastr.error(result.msg || 'Unable to save the account.');
                        }
                    },
                    error: function(xhr){
                        var message = 'Unable to save the account. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.msg) {
                            message = xhr.responseJSON.msg;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        toastr.error(message);
                    },
                    complete: function(){
                        // On success the modal is closing, so keep the button locked.
                        // On any validation/server/network failure, restore it for retry.
                        if (!requestSucceeded) {
                            financeAccountSetSaving($form, false);
                        }
                    }
                });
            };

            var lookupPromise = $form.data('finance-account-lookup-promise');
            if ($form.data('finance-account-lookups-pending') === true && lookupPromise) {
                // Do not ask the user to click again. The first click remains
                // queued and automatically proceeds as soon as the current
                // Account Type dependent lookups finish (success or failure).
                $.when(lookupPromise).always(submitRequest);
                return;
            }

            submitRequest();
        };

        /*
         * 19 Sep 2026 - One-click Add/Edit Account save.
         *
         * Some installations have older/global delegated click/submit handlers
         * registered before the Finance module.  A normal delegated submit
         * listener therefore can be reached only after another handler has
         * consumed the first click.  Capture the primary Finance Account save
         * click before any bubbling/global handler can swallow it.  The first
         * click now owns the save, locks the button and shows Saving...
         * immediately.  The normal submit listener below remains as the
         * keyboard/Enter fallback.
         */
        if (!window.__financeAccountOneClickSaveCaptureBound) {
            document.addEventListener('click', function(event) {
                var target = event.target;
                if (!target || !target.closest) {
                    return;
                }

                var button = target.closest('button.finance-account-submit[type="submit"]');
                if (!button) {
                    return;
                }

                var form = button.closest('form#payment_account_form, form#edit_payment_account_form');
                if (!form) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();
                if (event.stopImmediatePropagation) {
                    event.stopImmediatePropagation();
                }

                window.financeSubmitAccountForm($(form));
            }, true);
            window.__financeAccountOneClickSaveCaptureBound = true;
        }

        $(document).on('submit', 'form#edit_payment_account_form', function(e){
            e.preventDefault();
            window.financeSubmitAccountForm($(this));
        });
        
        $(document).on('submit', 'form#edit_cheque_ob_form', function(e){
            e.preventDefault();
            var data = $(this).serialize();
            $.ajax({
                method: "POST",
                url: $(this).attr("action"),
                dataType: "json",
                data: data,
                success:function(result){
                    if(result.success == true){
                        $('#account_type_modal').modal('hide');
                        toastr.success(result.msg);
                        cheques_ob_details_table.ajax.reload();
                    }else{
                        toastr.error(result.msg);
                    }
                }
            });
        });

        var editIcon = function ( data, type, row ) {
        if ( type === 'display' ) {
        return data + '<i class="fa fa-plus-square" aria-hidden="true"></i>';
        }
        return data;
        };

        $(document).on('submit', 'form#payment_account_form', function(e){
            e.preventDefault();
            window.financeSubmitAccountForm($(this));
        });
        function financeAccountClassifyAction($item) {
            var text = $.trim($item.text()).toLowerCase();
            if (text.indexOf('edit') !== -1 || $item.hasClass('edit_btn')) return 'finance-action-edit';
            if (text.indexOf('account book') !== -1 || text.indexOf('main account book') !== -1) return 'finance-action-book';
            if (text.indexOf('close') !== -1 || $item.hasClass('close_account')) return 'finance-action-close';
            if (text.indexOf('fund transfer') !== -1 || $item.hasClass('transfer_btn')) return 'finance-action-transfer';
            if (text.indexOf('deposit') !== -1 || $item.hasClass('deposit_btn')) return 'finance-action-deposit';
            if (text.indexOf('notes') !== -1) return 'finance-action-notes';
            if (text.indexOf('enabled') !== -1 || $item.hasClass('disable_status_account')) return 'finance-action-enabled';
            return 'finance-action-default';
        }

        function financeAccountActionDropdown(data) {
            if (!data || $.trim(data) === '') return '';
            var $holder = $('<div/>').html(data.replace(/&nbsp;/g, ' '));
            var $items = $holder.find('a,button').filter(function(){ return $.trim($(this).text()).length > 0; });
            if (!$items.length) return data;
            var html = '<div class="finance-account-action-wrap">' +
                '<button type="button" class="finance-account-action-main"><i class="fa fa-cog"></i> Action <i class="fa fa-angle-down"></i></button>' +
                '<div class="finance-account-action-menu">';
            $items.each(function(){
                var $item = $(this).clone();
                $item.removeClass('btn-xs btn-primary btn-warning btn-danger btn-info btn-success btn-default')
                    .addClass(financeAccountClassifyAction($(this)));
                html += $('<div/>').append($item).html();
            });
            html += '</div></div>';
            return html;
        }

        function financeText(value) {
            return $('<div/>').html(value || '').text().trim() || '-';
        }

        function financeAccountChildDetails(row) {
            return '<div class="finance-account-details">' +
                '<div><strong>Parent Account:</strong> ' + financeText(row.parent_account_id) + '</div>' +
                '<div><strong>Account Group:</strong> ' + financeText(row.account_group) + '</div>' +
                '</div>';
        }

        var icon ='<i class="fa fa-plus-square" aria-hidden="true"></i>';
        // S708: Finance List Accounts must have exactly one DataTable instance.
        // The global Universal Search can cause page scripts/DOM hooks to fire
        // again; never initialise the same table twice.
        var financeAccountTableNode = document.querySelector('#other_account_table');
        if (!financeAccountTableNode) {
            return;
        }

        if ($.fn.DataTable.isDataTable(financeAccountTableNode)) {
            other_account_table = $(financeAccountTableNode).DataTable();
        } else {
        other_account_table = $(financeAccountTableNode).DataTable({
            'processing': true,
            'serverSide': false,
            'deferRender': true,
            'pageLength': 25,
            'searchDelay': 350,
            'stateSave': false,
            'ajax': {
                /*
                 | The account_type=other query string is gone.
                 |
                 | Hard-coded into the URL, so every load filtered the list to
                 | that one type whatever the user chose. The Account Type
                 | dropdown sends its own value as account_type_s below, which
                 | is the filter people can actually see.
                */
                url: @json(route('finance.list-accounts.live')),
                data: function(d){
                    d.location_id = $('#list_accounts_location_id').val();
                    d.account_type_s = $('#account_type').val();
                    d.account_sub_type = $('#account_sub_type').val();
                    d.account_group = $('#account_group').val();
                    d.account_name = $('#account_name').val();

                }
            },
            scrollX: false,
            scrollCollapse: false,
            autoWidth: false,
            columnDefs: [
                { targets: 0, orderable: false, searchable: false, width: '52px', className: 'finance-account-expand-cell text-center' },
                { targets: 1, width: '235px', className: 'finance-name-cell' },
                { targets: 2, width: '105px', className: 'finance-location-cell text-center' },
                { targets: 3, width: '270px', className: 'finance-account-type-cell' },
                { targets: 4, width: '80px', className: 'finance-account-number-cell text-center' },
                { targets: 5, width: '150px', className: 'finance-amount-cell text-right' },
                { targets: 6, orderable: false, searchable: false, width: '140px', className: 'finance-action-cell text-center' }
            ],
            columns: [

                // {data: null, render: editIcon,className: 'remove_name', },
                //     {
                //     data: null,
                //     defaultContent: '<i class="fa fa-plus-square" aria-hidden="true"></i>',
                //     className: 'edit_icon',
                    
                //     orderable: false
                // },
                {
                    data: 'id',
                    sortable: false,
                    className: 'finance-account-expand-cell',
                    render: function (data) {
                        return '<span class="finance-account-plus plus_singh" data-id="' + data + '"><i class="fa fa-plus"></i></span>';
                    }
                },
                    // {data: 'id', name: 'accounts.id'},
                {data: 'name', name: 'accounts.name'},                
                {data: 'account_location', name: 'account_location',searchable: false},
                {data: 'account_type', name: 'ats.name'},
                {data: 'account_number', name: 'accounts.account_number', className: 'finance-account-number-cell'},
                {data: 'balance', name: 'balance', searchable: false, className: 'finance-amount-cell'},
                // {data: 'added_by', name: 'u.first_name'},
                {
                    data: 'action',
                    name: 'action',
                    className: 'finance-action-cell',
                    render: function(data, type, row) {
                        if (type !== 'display') return data;
                        return financeAccountActionDropdown(data);
                    }
                },
               
            ],
            @include('layouts.partials.datatable_export_button')
            "fnInitComplete": function () {
                /* One width pass only.  draw(false) here used to trigger a
                   second table draw/reflow just after paint, which could move
                   the page while the user started scrolling. */
                this.api().columns.adjust();
            },
            "fnDrawCallback": function (oSettings) {
                financeCloseAccountActionPortal();
                __currency_convert_recursively($('#other_account_table'));
                $('#other_account_table tbody td.finance-amount-cell').css('text-align', 'right');

            },
            "rowCallback": function( row, data, index ) {
            
            }
           
        });
        }

        /*
         * S708 - Universal Search duplicate-column protection.
         * Keep the original Finance table node as the sole owner.  Some global
         * search/layout code can clone an already-initialised DataTable wrapper
         * or its THEAD.  That produced two export bars and two column-heading
         * rows.  Normalising the DOM is safe here because this Finance table has
         * one intentional THEAD row and one set of DataTables controls.
         */
        function financeNormaliseListAccountsDataTable() {
            var primaryTable = financeAccountTableNode;
            if (!primaryTable || !document.documentElement.contains(primaryTable)) return;

            // Remove cloned tables carrying the same id; never remove the live
            // DataTables-owned primary table.
            Array.prototype.slice.call(document.querySelectorAll('table#other_account_table')).forEach(function(table){
                if (table !== primaryTable) {
                    var duplicateWrapper = table.closest('[id="other_account_table_wrapper"]');
                    if (duplicateWrapper && !duplicateWrapper.contains(primaryTable)) {
                        duplicateWrapper.remove();
                    } else {
                        table.remove();
                    }
                }
            });

            // The List Accounts table intentionally has one heading row. Remove
            // only exact duplicate heading rows; do not touch tbody child rows.
            var head = primaryTable.tHead;
            if (head && head.rows.length > 1) {
                var firstSignature = $.trim($(head.rows[0]).text()).replace(/\s+/g, ' ').toLowerCase();
                Array.prototype.slice.call(head.rows, 1).forEach(function(row){
                    var signature = $.trim($(row).text()).replace(/\s+/g, ' ').toLowerCase();
                    if (signature === firstSignature) row.remove();
                });
            }

            var wrapper = primaryTable.closest('[id="other_account_table_wrapper"]');
            if (!wrapper) return;

            // A Universal Search refresh must never leave a second copy of the
            // DataTables toolbar/search/length/paging chrome in this wrapper.
            ['.dt-buttons', '.dataTables_length', '.dataTables_filter', '.dataTables_info', '.dataTables_paginate'].forEach(function(selector){
                var items = Array.prototype.slice.call(wrapper.querySelectorAll(selector));
                items.slice(1).forEach(function(item){ item.remove(); });
            });

            // Legacy scrollX initialisation clones THEAD into scrollHead.  This
            // Finance table is scrollX:false, so the clone is always duplicate.
            Array.prototype.slice.call(wrapper.querySelectorAll('.dataTables_scrollHead')).forEach(function(node){
                node.style.display = 'none';
            });
        }

        financeNormaliseListAccountsDataTable();
        $('#other_account_table')
            .off('draw.dt.financeS708 search.dt.financeS708 xhr.dt.financeS708')
            .on('draw.dt.financeS708 search.dt.financeS708 xhr.dt.financeS708', function(){
                window.requestAnimationFrame(financeNormaliseListAccountsDataTable);
            });

        // Catch DOM cloning performed by the global Universal Search after a
        // keypress/result refresh, without re-drawing the table or moving page
        // scroll position.
        var financeAccountSearchRoot = document.getElementById('other_accounts');
        if (financeAccountSearchRoot && window.MutationObserver && !financeAccountSearchRoot.__financeS708Observer) {
            var financeS708Queued = false;
            financeAccountSearchRoot.__financeS708Observer = new MutationObserver(function(){
                if (financeS708Queued) return;
                financeS708Queued = true;
                window.requestAnimationFrame(function(){
                    financeS708Queued = false;
                    financeNormaliseListAccountsDataTable();
                });
            });
            financeAccountSearchRoot.__financeS708Observer.observe(financeAccountSearchRoot, {childList:true, subtree:true});
        }

        $(".remove_name").remove();

        function financeCloseAccountActionPortal() {
            var $portal = $('#finance-account-action-portal');
            if (!$portal.length || !$portal.is(':visible')) return;
            $portal.hide().empty().removeData('finance-owner');
        }

        function financePositionAccountActionPortal($button) {
            var $portal = $('#finance-account-action-portal');
            if (!$portal.length || !$button.length) return;

            var rect = $button[0].getBoundingClientRect();
            var portalWidth = $portal.outerWidth() || 190;
            var portalHeight = $portal.outerHeight() || 0;
            var viewportWidth = window.innerWidth || document.documentElement.clientWidth;
            var viewportHeight = window.innerHeight || document.documentElement.clientHeight;
            var gutter = 8;
            var left = rect.right - portalWidth;
            var top = rect.bottom + 7;

            left = Math.max(gutter, Math.min(left, viewportWidth - portalWidth - gutter));
            if (top + portalHeight > viewportHeight - gutter && rect.top - portalHeight - 7 >= gutter) {
                top = rect.top - portalHeight - 7;
            }
            top = Math.max(gutter, Math.min(top, viewportHeight - portalHeight - gutter));

            $portal.css({left: Math.round(left) + 'px', top: Math.round(top) + 'px'});
        }

        $(document)
            .off('click.financeAccountAction', '.finance-account-action-main')
            .on('click.financeAccountAction', '.finance-account-action-main', function(e){
                e.preventDefault();
                e.stopImmediatePropagation();

                var $button = $(this);
                var $source = $button.siblings('.finance-account-action-menu');
                var $portal = $('#finance-account-action-portal');
                if (!$portal.length) {
                    $portal = $('<div id="finance-account-action-portal" role="menu"></div>').appendTo(document.body);
                }

                if ($portal.is(':visible') && $portal.data('finance-owner') === this) {
                    financeCloseAccountActionPortal();
                    return;
                }

                $portal.html($source.html()).data('finance-owner', this).show();
                financePositionAccountActionPortal($button);
            });

        $(document)
            .off('click.financeAccountActionClose')
            .on('click.financeAccountActionClose', function(){
                financeCloseAccountActionPortal();
            });

        $(document)
            .off('click.financeAccountActionPortal', '#finance-account-action-portal')
            .on('click.financeAccountActionPortal', '#finance-account-action-portal', function(e){
                e.stopPropagation();
            });

        $(document)
            .off('click.financeAccountActionItem', '#finance-account-action-portal a, #finance-account-action-portal button')
            .on('click.financeAccountActionItem', '#finance-account-action-portal a, #finance-account-action-portal button', function(){
                setTimeout(financeCloseAccountActionPortal, 0);
            });

        $(window)
            .off('resize.financeAccountAction scroll.financeAccountAction')
            .on('resize.financeAccountAction scroll.financeAccountAction', function(){
                financeCloseAccountActionPortal();
            });
        $('.finance-account-table-wrap')
            .off('scroll.financeAccountAction')
            .on('scroll.financeAccountAction', function(){
                financeCloseAccountActionPortal();
            });

        $('#other_account_table tbody').on('click', '.plus_singh' , function(){
            var $btn = $(this);
            var tr = $btn.closest('tr');
            var row = other_account_table.row(tr);

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                $btn.removeClass('is-open').html('<i class="fa fa-plus"></i>');
            } else {
                row.child(financeAccountChildDetails(row.data())).show();
                tr.addClass('shown');
                $btn.addClass('is-open').html('<i class="fa fa-minus"></i>');
            }
        });


        // filter Data

        var filter = JSON.parse(`<?php echo json_encode($filterdata) ?>`);
        
        $('#list_accounts_location_id').change(function(){
            other_account_table.ajax.reload();
        })

        $('#account_type').change(function(){
            
            let change_val ='subType_'+ $('#account_type').val();
            

            // $('#account_type').empty().trigger("change");
            $('#account_sub_type').select2('destroy').empty().select2(filter[change_val]).change();
            loadNamesOtion();

            // $("#account_sub_type").val("").change();
            other_account_table.ajax.reload();
        })

        $('#account_sub_type').change(function(){
            // console.log(filter,$('#account_sub_type').val());
            let change_val ='groupType_'+ $('#account_sub_type').val();
           
            if(change_val == 'groupType_All'){
                change_val = 'groupType_';
            }
            if(change_val=="groupType_"){
                data = [{'id':'','text':'All'}];
                $('#account_sub_type option').each(function(){
                    if($(this).attr('value') != ''){
                        let newChangeVal ='groupType_'+ $(this).val();
                        if(filter[newChangeVal] && filter[newChangeVal]['data']){

                            for(var i in filter[newChangeVal]['data']) {
                                data.push(filter[newChangeVal]['data'][i]);
                            }

                        }
                    }
                    $('#account_group').select2('destroy').empty().select2({'data':data}).change();

                })

            }else{
               $('#account_group').select2('destroy').empty().select2(filter[change_val]).change();
            }
            loadNamesOtion();
            other_account_table.ajax.reload();

        
        })

        $('#account_group').change(function(){
            loadNamesOtion();
            other_account_table.ajax.reload();
        })

        $('#account_name').change(function(){
            other_account_table.ajax.reload();
        })
       

        // account_groups_table
        account_groups_table = $('#account_groups_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('finance.list-accounts.live.account-groups.data') }}",
            // columnDefs:[{
            //         "targets": 4,
            //         "orderable": false,
            //         "searchable": false,
            //         "width" : "30%",
            //     }],
                
            columns: [
                
                {data: 'name', name: 'account_groups.name'},
                {data: 'account_type_name', name: 'ats.name'},
                {data: 'note', name: 'note'},
                {
                    data: 'action',
                    name: 'action',
                    className: 'finance-action-cell',
                    render: function(data, type, row) {
                        if (type !== 'display') return data;
                        return financeAccountActionDropdown(data);
                    }
                },
                
            ],
            "fnDrawCallback": function (oSettings) {
            }
        });


        $(document).on('click', '#save_account_group_btn', function(e){
            e.preventDefault();
            let name = $('#account_group_name_group').val();
            let account_type_id = $('#account_type_id_group').val();
            let note = $('#note_group').val();

            $.ajax({
                method: 'post',
                url: "{{ route('finance.list-accounts.live.account-groups.store') }}",
                data: { 
                    name,
                    account_type_id,
                    note,
                },
                success: function(result) {
                    if(result.success == 1){
                        toastr.success(result.msg);
                    }else{
                        toastr.error(result.msg);
                    }
                    $('#account_groups_modal').modal('hide');
                    account_groups_table.ajax.reload();
                },
            });

        });
        $(document).on('click', '#update_account_group_btn', function(e){
            e.preventDefault();
            let name = $('#account_group_name_group').val();
            let account_type_id = $('#account_type_id_group').val();
            let note = $('#note_group').val();
            let url = $('#account_group_form').attr('action');
            $.ajax({
                method: 'put',
                url: url,
                data: { 
                    name,
                    account_type_id,
                    note,
                },
                success: function(result) {
                    if(result.success == 1){
                        toastr.success(result.msg);
                    }else{
                        toastr.error(result.msg);
                    }
                    $('.account_model').modal('hide');
                    account_groups_table.ajax.reload();
                },
            });

        });

        $(document).on('click', 'button.account_group_delete', function(){
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete)=>{
                if(willDelete){
                    let href = $(this).data('href');

                    $.ajax({
                        method: 'delete',
                        url: href,
                        data: {  },
                        success: function(result) {
                            if(result.success == 1){
                                toastr.success(result.msg);
                            }else{
                                toastr.error(result.msg);
                            }
                            account_groups_table.ajax.reload();
                        },
                    });
                }
            });
        })
        
        $(document).on('click', '.cheque_ob_delete', function(){
            swal({
                title: LANG.sure,
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete)=>{
                if(willDelete){
                    let href = $(this).data('href');

                    $.ajax({
                        method: 'delete',
                        url: href,
                        data: {  },
                        success: function(result) {
                            if(result.success == 1){
                                toastr.success(result.msg);
                            }else{
                                toastr.error(result.msg);
                            }
                            cheques_ob_details_table.ajax.reload();
                        },
                    });
                }
            });
        })

        function loadNamesOtion(){
            postData ={"account_type_s" : $('#account_type').val(),
                "account_sub_type": $('#account_sub_type').val(),
                 "account_group" : $('#account_group').val(),
                 "_token": "{{ csrf_token() }}"
                 }

            //      postData = "account_type_s =" + $('#account_type').val();
            // postData +=    "&account_sub_type =" + $('#account_sub_type').val();
            // postData +=    "&account_group_type =" + $('#account_group').val(),
            // postData +=    "&_token = {{ csrf_token() }}";
            $.ajax({
                method: "post",
                url: '/finance/check_account_names',
                dataType: "json",
                data: postData,
                success:function(result){
                   
                    if(result.data){
                        $('#account_name').select2('destroy').empty().select2(result).change();
                    }
                }
            });
        }
        
    });

    $('.account_model').on('show.bs.modal', function(e) {
        // Cheque Deposit owns its initial request after its AJAX-loaded controls
        // are initialised (IS2258 #3). Do not race it from the parent page.
        if ($(this).find('#realize_cheque_list_table').length) {
            get_realize_cheques_list();
        }
    });

    // Finance account forms are opened by the module-owned modal loader below.
    // Do not also invoke the global ERP .btn-modal path: two modal owners on the
    // same click were the cause of IS2252 raw JSON/chart data appearing on screen.
    $('#add_acount_group_btn').click(function(){
        $('#account_groups_modal').modal({
            backdrop: 'static',
            keyboard: false
        })
    });
    $(document).on('click', 'button.delete_account_type', function(){
        swal({
            title: LANG.sure,
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willDelete)=>{
            if(willDelete){
                $(this).closest('form').submit();
            }
        });
    })

    $(document).on('change', '#account_number', function(){
        $.ajax({
            method: 'get',
            url: '/finance/check_account_number',
            data: {
                account_number: $(this).val(),
                account_id: $(this).data('account-id') || 0
            },
            success: function(result) {
                if(!result.success){
                    $("#account_number").val('');
                    toastr.error(result.msg);
                }
            },
        });
    })
    
    

    function get_realize_cheques_list(){
        
        if($('#realize_cheque_date').val()){
            start_date = $('input#realize_cheque_date').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#realize_cheque_date').data('daterangepicker').endDate.format('YYYY-MM-DD');
            
            // start_date_created = $('input#realize_date').data('daterangepicker').startDate.format('YYYY-MM-DD');
            // end_date_created = $('input#realize_date').data('daterangepicker').endDate.format('YYYY-MM-DD');
            
            cheque_no= $('#cheque_customer_cheque_no').val();
            amount = $('#cheque_customer_amount').val();
            realize_cheque_bank = $('#realize_cheque_bank').val();
            
            $.ajax({
                method: 'get',
                url: '{{ url('/finance/finance-realize-cheque-list') }}',
                data: { start_date, end_date, cheque_no,amount,realize_cheque_bank },
                contentType: 'html',
                success: function(result) {
                    $('.account_model').find('#realize_cheque_list_table tbody').empty().append(result);
                },
            });
        }
       
    }

    // Account Settings tab script.
    // Keep this table independent from the List Accounts table. In the old
    // code its Action renderer called financeAccountActionDropdown(), but that
    // function is local to a different document-ready closure. The AJAX call
    // completed successfully and then the first row draw threw a JavaScript
    // ReferenceError, leaving DataTables permanently on "Processing...".
    var account_setting_table = null;

    function financeReloadAccountSettingsTable() {
        if (account_setting_table && $.fn.DataTable.isDataTable('#account_setting_table')) {
            account_setting_table.ajax.reload(null, false);
        }
    }

    $(document).ready(function () {
        var $accountSettingsTable = $('#account_setting_table');
        if (!$accountSettingsTable.length) {
            return;
        }

        // S723 #2: initialise exactly one Account Settings DataTable and use the
        // Finance URL directly.  Duplicate route names exist in legacy route
        // files, while this endpoint must always return JSON to DataTables.
        if ($.fn.DataTable.isDataTable('#account_setting_table')) {
            $accountSettingsTable.DataTable().destroy();
        }

        $accountSettingsTable
            .off('error.dt.financeAccountSettings')
            .on('error.dt.financeAccountSettings', function () {
                $('#account_setting_table_processing').hide();
            });

        account_setting_table = $accountSettingsTable.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('finance.list-accounts.live.settings.data') }}",
                dataType: 'json',
                cache: false,
                timeout: 30000,
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
                data: function(d){
                    d.date = $('#date1').val();
                    d.account_type = $('#account_type1').val();
                    d.account_sub_type = $('#account_sub_type1').val();
                    d.account_id = $('#account_id2').val();
                    d.group_id = $('#group_id2').val();
                },
                error: function(xhr, status) {
                    $('#account_setting_table_processing').hide();
                    var msg = (xhr.responseJSON && (xhr.responseJSON.error || xhr.responseJSON.message))
                        ? (xhr.responseJSON.error || xhr.responseJSON.message)
                        : (status === 'timeout'
                            ? 'Account Settings took too long to load. Please retry.'
                            : 'Unable to load Account Settings.');
                    toastr.error(msg);
                }
            },
            columns: [
                {data: 'date', name: 'date'},
                {data: 'parent_account_type_name', name: 'pat.name'},
                {data: 'account_type_name', name: 'account_types.name'},
                {data: 'account_group', name: 'account_groups.name'},
                {data: 'name', name: 'accounts.name'},
                {data: 'amount', name: 'amount'},
                {data: 'created_by', name: 'users.username'},
                @if(!empty($can_edit_ob))
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'finance-action-cell'
                },
                @endif
                
               
            ],
            @include('layouts.partials.datatable_export_button')
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#account_setting_table'));
            },
            "initComplete": function () {
                $('#account_setting_table_processing').hide();
            },
            "rowCallback": function( row, data, index ) {
                
            }
        });

        $('#date1, #account_id2, #account_type1, #account_sub_type1')
            .off('change.financeAccountSettingsTable')
            .on('change.financeAccountSettingsTable', function () {
                financeReloadAccountSettingsTable();
            });

        $('#group_id2')
            .off('change.financeAccountSettingsTable')
            .on('change.financeAccountSettingsTable', function () {
                var groupId = $(this).val();
                var $accountFilter = $('#account_id2');

                financeReloadAccountSettingsTable();

                if (!groupId) {
                    $accountFilter.empty().append($('<option/>', {
                        value: '',
                        text: @json(__('lang_v1.all'))
                    })).trigger('change');
                    return;
                }

                $.ajax({
                    method: 'get',
                    url: '{{ url('/finance/get-account-by-group-id') }}/' + encodeURIComponent(groupId),
                    dataType: 'html',
                    success: function(result) {
                        $accountFilter.empty().append(result).trigger('change');
                    }
                });
            });

        // The opening-balance entry date defaults to today. The list filter is
        // deliberately left blank so the initial table shows all settings.
        $('#date').datepicker('setDate', new Date());

        $('a[data-toggle="tab"][href="#account_settings"]')
            .off('shown.bs.tab.financeAccountSettingsTable')
            .on('shown.bs.tab.financeAccountSettingsTable', function () {
                if (account_setting_table) {
                    account_setting_table.columns.adjust();
                }
            });
    });


    // list deposit and transfer account
    // Finance module only: restore the old working AJAX report behavior.
    // The date picker must not submit/redirect the whole account page; it only reloads this table.
    if($('#list_deposit_transfer_date_range').length) {
        var ldtDateSettings = $.extend({}, dateRangeSettings, { autoUpdateInput: false });
        $('#list_deposit_transfer_date_range').daterangepicker(
            ldtDateSettings,
            function (start, end) {
                $('#list_deposit_transfer_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                if (typeof list_deposit_transfer_table !== 'undefined') {
                    list_deposit_transfer_table.ajax.reload();
                }
            }
        );
        $('#list_deposit_transfer_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#list_deposit_transfer_date_range').val('');
            if (typeof list_deposit_transfer_table !== 'undefined') {
                list_deposit_transfer_table.ajax.reload();
            }
        });
    }
    
    if($('#cheques_ob_details_date_range').length) {
        $('#cheques_ob_details_date_range').daterangepicker(
            dateRangeSettings,
            function (start, end) {
                $('#cheques_ob_details_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                cheques_ob_details_table.ajax.reload();
            }
        );
        $('#cheques_ob_details_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#cheques_ob_details_date_range').val('');
            cheques_ob_details_table.ajax.reload();
        });
    }
    
    
    $(document).ready(function () {
        if ($.fn.DataTable.isDataTable('#list_deposit_transfer_table')) {
            $('#list_deposit_transfer_table').DataTable().destroy();
        }

        list_deposit_transfer_table = $('#list_deposit_transfer_table').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '/finance/list-deposit-transfer',
                data: function(d){
                    if($('#list_deposit_transfer_date_range').val()) {
                        var picker = $('#list_deposit_transfer_date_range').data('daterangepicker');
                        if (picker) {
                            d.start_date = picker.startDate.format('YYYY-MM-DD');
                            d.end_date = picker.endDate.format('YYYY-MM-DD');
                        }
                    }
                    d.sub_type = $('#list_deposit_transfer_type').val();
                    d.from_account_id = $('#from_account_id').val();
                    d.to_account_id = $('#to_account_id').val();
                    d.user_id = $('#user_id').val();
                },
                error: function(xhr) {
                    console.log('Finance List Deposit Transfer AJAX error', xhr.responseText);
                }
            },
            columnDefs:[{
                "targets": 0,
                "orderable": false,
                "searchable": false,
                "width" : "12%"
            }],
            columns: [
                {data: 'action', name: 'action'},
                {data: 'operation_date', name: 'operation_date'},
                {data: 'customer_name', name: 'customer_name'},
                {data: 'sub_type', name: 'sub_type'},
                {data: 'amount', name: 'amount'},
                {data: 'from_account', name: 'from_account'},
                {data: 'to_account', name: 'to_account'},
                {data: 'cheque_number', name: 'cheque_number'},
                {data: 'username', name: 'users.username'}
            ],
            @include('layouts.partials.datatable_export_button')
            "fnDrawCallback": function () {
                __currency_convert_recursively($('#list_deposit_transfer_table'));
                $('#list_deposit_transfer_record_count').text(this.api().rows({ search: 'applied' }).count());
            },
            "footerCallback": function () {
                var api = this.api();
                var total = 0;
                api.rows({ search: 'applied' }).every(function () {
                    var raw = $(this.node()).find('.finance-deposit-transfer-amount').data('orig-value');
                    if (raw === undefined) {
                        raw = $(this.node()).find('.display_currency').first().text().replace(/,/g, '');
                    }
                    total += parseFloat(raw) || 0;
                });
                $('#list_deposit_transfer_page_total').text(__number_f(total, false));
            }
        });

        $('#cheques_ob_details_table').off('xhr.dt.financeChequeOb').on('xhr.dt.financeChequeOb', function(e, settings, json) {
            if (json && json.finance_message && typeof toastr !== 'undefined') {
                toastr.error(json.finance_message);
            }
        });

        cheques_ob_details_table = $('#cheques_ob_details_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route('finance.list-accounts.live.cheques-ob-details') }}',
                data: function(d){
                    if($('#cheques_ob_details_date_range').val()) {
                        var start = $('#cheques_ob_details_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        var end = $('#cheques_ob_details_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                        d.start_date = start;
                        d.end_date = end;
                    }
                    d.amount = $('#cheques_ob_details_amount').val();
                    d.cheque_number = $('#cheques_ob_details_cheque_no').val();
                    d.bank_name = $('#cheques_ob_details_bank').val();
                    d.user_id = $('#cheques_ob_details_user_id').val();
                },
                error: function(xhr) {
                    var message = 'Unable to load Cheques in Hand opening details.';
                    if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.error(message);
                    }
                    console.log('Finance Cheques Opening Balance AJAX error', xhr ? xhr.responseText : '');
                }
            },
            columnDefs:[{
                    "targets": 5,
                    "orderable": false,
                    "searchable": false,
                    "width" : "30%",
                }],
            columns: [
                {data: 'transaction_date', name: 'transaction_date'},
                {data: 'customer', name: 'customer'},
                {data: 'cheque_number', name: 'cheque_number'},
                {data: 'amount', name: 'amount'},
                {data: 'cheque_date', name: 'cheque_date'},
                {data: 'bank_name', name: 'bank_name'},
                {data: 'action', name: 'action'}
            
               
            ],
            @include('layouts.partials.datatable_export_button')
            "fnDrawCallback": function (oSettings) {
                
                __currency_convert_recursively($('#cheques_ob_details_table'));
            },
            "rowCallback": function( row, data, index ) {
                
            }
        });
        
       
        

        $('#list_deposit_transfer_type, #from_account_id, #to_account_id, #user_id').change(function(){
            list_deposit_transfer_table.ajax.reload();
        });

        $('#list_deposit_transfer_filter_btn').on('click', function(){
            list_deposit_transfer_table.ajax.reload();
        });

        $('#list_deposit_transfer_clear_btn').on('click', function(){
            $('#list_deposit_transfer_date_range').val('');
            $('#list_deposit_transfer_type, #from_account_id, #to_account_id, #user_id').val('').trigger('change.select2');
            list_deposit_transfer_table.ajax.reload();
        });
        
        $('#cheques_ob_details_date_range, #cheques_ob_details_amount, #cheques_ob_details_cheque_no, #cheques_ob_details_bank, #cheques_ob_details_user_id').on('change', function() {
        
            cheques_ob_details_table.ajax.reload();
            
        });

    })
</script>
@endsection

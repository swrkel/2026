@extends('layouts.app')
@section('title', __('lang_v1.payment_accounts'))

@section('content')


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
    /* S356: Finance Table Standard v1.0 - Account List
       Standalone Finance module only. One fixed column model for header/body/export, no main view dependency. */
    .finance-account-table-wrap{
        width:100%;
        overflow-x:auto !important;
        overflow-y:visible !important;
        padding-bottom:10px;
        -webkit-overflow-scrolling:touch;
    }
    #other_account_table_wrapper,
    #other_account_table_wrapper .dataTables_scroll,
    #other_account_table_wrapper .dataTables_scrollHead,
    #other_account_table_wrapper .dataTables_scrollBody{
        width:100% !important;
    }
    #other_account_table_wrapper .dataTables_scrollBody{
        overflow-x:auto !important;
        overflow-y:visible !important;
        min-height:360px;
        padding-bottom:8px;
    }
    #other_account_table,
    #other_account_table_wrapper .dataTables_scrollHeadInner table,
    #other_account_table_wrapper .dataTables_scrollBody table{
        table-layout:fixed !important;
        width:1180px !important;
        min-width:1180px !important;
        max-width:1180px !important;
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
    .finance-account-action-menu{display:none; position:absolute; right:0; top:calc(100% + 7px); z-index:9999; width:190px; padding:10px; background:#fff; border:1px solid #e6edf6; border-radius:13px; box-shadow:0 16px 35px rgba(15,23,42,.16);}
    .finance-account-action-wrap.open .finance-account-action-menu{display:block;}
    .finance-account-action-menu .btn, .finance-account-action-menu a, .finance-account-action-menu button{display:flex!important; align-items:center; justify-content:flex-start; width:100%!important; min-height:44px!important; margin:0 0 7px 0!important; padding:10px 13px!important; border:0!important; border-radius:8px!important; color:#fff!important; font-size:15px!important; font-weight:800!important; line-height:20px!important; text-align:left!important; white-space:nowrap!important; box-shadow:none!important; text-decoration:none!important;}
    .finance-account-action-menu .btn:last-child, .finance-account-action-menu a:last-child, .finance-account-action-menu button:last-child{margin-bottom:0!important;}
    .finance-account-action-menu i, .finance-account-action-menu .fa, .finance-account-action-menu .glyphicon{width:18px; margin-right:7px; font-size:16px!important; color:#fff!important;}
    .finance-action-edit{background:linear-gradient(135deg,#8b5cf6,#c026d3)!important;}
    .finance-action-book{background:linear-gradient(135deg,#f59e0b,#fb923c)!important;}
    .finance-action-close{background:linear-gradient(135deg,#ef4444,#f87171)!important;}
    .finance-action-transfer{background:linear-gradient(135deg,#0ea5e9,#2563eb)!important;}
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
                                <div class="row">
                                    <button style="margin-right: 25px;" type="button" id="add_button"
                                        class="btn btn-sm btn-primary btn-modal pull-right"
                                        data-container=".account_model"
                                        data-href="{{action('AccountController@create')}}">
                                        <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
                                    <button style="margin-right: 25px;"
                                        data-href="{{action('AccountController@getDeposit', ['card'])}}"
                                        class="btn btn-sm btn-warning btn-modal  pull-right deposit_btn"
                                        data-container=".account_model"><i class="fa fa-money"></i>
                                        @lang("account.card_deposit")</button>

                                    <button style="margin-right: 25px;"
                                        data-href="{{action('AccountController@getChequeDeposit')}}"
                                        class="btn btn-sm btn-info btn-modal  pull-right deposit_btn"
                                        data-container=".account_model"><i class="fa fa-address-card-o"></i>
                                        @lang("account.cheque_deposit")</button>
                                    
                                   @if(!empty($pacakge_details['realize_cheque']))
                                        @can('deposits.realize_cheque')    
                                            <button style="margin-right: 25px;"
                                                data-href="{{action('AccountController@getRealizeChequeDeposit')}}"
                                                class="btn btn-sm btn-danger btn-modal  pull-right deposit_btn"
                                                data-container=".account_model"><i class="fa fa-address-card-o"></i>
                                                @lang("account.realize_cheque")</button>
                                        @endcan
                                    @endif
                                        
                                        
                                    <button style="margin-right: 25px;"
                                        data-href="{{action('AccountController@getDeposit', ['cash'])}}"
                                        class="btn btn-sm btn-success btn-modal  pull-right deposit_btn"
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
                                <table class="table table-bordered table-striped" id="other_account_table" style="width:970px; min-width:970px;">
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
                        @include('account_settings.index')
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
  
    $(document).ready(function(){
        var body = document.getElementsByTagName("body")[0];
        
        body.className += " sidebar-collapse";
        
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

        $(document).on('submit', 'form#edit_payment_account_form', function(e){
            e.preventDefault();
            var data = $(this).serialize();
            $.ajax({
                method: "POST",
                url: $(this).attr("action"),
                dataType: "json",
                data: data,
                success:function(result){
                    if(result.success == true){
                        $('div.account_model').modal('hide');
                        toastr.success(result.msg);
                        other_account_table.ajax.reload();
                    }else{
                        toastr.error(result.msg);
                    }
                }
            });
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
            var data = $(this).serialize();
            $.ajax({
                method: "post",
                url: $(this).attr("action"),
                dataType: "json",
                data: data,
                success:function(result){
                    if(result.success == true){
                        $('div.account_model').modal('hide');
                        toastr.success(result.msg);
                        
                        other_account_table.ajax.reload();
                    }else{
                        toastr.error(result.msg);
                    }
                }
            });
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
                '<div><strong>Deposit to Account Group:</strong> ' + financeText(row.account_group) + '</div>' +
                '</div>';
        }

        var icon ='<i class="fa fa-plus-square" aria-hidden="true"></i>';
        // other_account_table
        other_account_table = $('#other_account_table').DataTable({
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
                url: '/accounting-module/account',
                data: function(d){
                    d.location_id = $('#list_accounts_location_id').val();
                    d.account_type_s = $('#account_type').val();
                    d.account_sub_type = $('#account_sub_type').val();
                    d.account_group = $('#account_group').val();
                    d.account_name = $('#account_name').val();

                }
            },
            scrollX: true,
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
                var api = this.api();
                api.columns.adjust().draw(false);
                setTimeout(function(){ api.columns.adjust().draw(false); }, 200);
            },
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#other_account_table'));
                $('#other_account_table tbody td.finance-amount-cell').css('text-align', 'right');

            },
            "rowCallback": function( row, data, index ) {
            
            }
           
        });
        $(".remove_name").remove();

        $(document).on('click', '.finance-account-action-main', function(e){
            e.preventDefault();
            e.stopPropagation();
            var $wrap = $(this).closest('.finance-account-action-wrap');
            $('.finance-account-action-wrap').not($wrap).removeClass('open');
            $wrap.toggleClass('open');
        });

        $(document).on('click', function(){
            $('.finance-account-action-wrap').removeClass('open');
        });

        $(document).on('click', '.finance-account-action-menu', function(e){
            e.stopPropagation();
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
            ajax: '/account-groups',
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
                url: '/account-groups',
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
                url: '/accounting-module/check_account_names',
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
        get_cheques_list();
        get_realize_cheques_list();
    });

    $('#add_button').click(function(){
        $('.account_model').modal({
            backdrop: 'static',
            keyboard: false
        })
    });
    $('#add_acount_group_btn').click(function(){
        $('#account_groups_modal').modal({
            backdrop: 'static',
            keyboard: false
        })
    });
    $(document).on('click', '.edit_btn, .deposit_btn, .transfer_btn', function(){
        $('.account_model').modal({
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
            url: '/accounting-module/check_account_number',
            data: { account_number: $(this).val() },
            success: function(result) {
                if(!result.success){
                    $("#account_number").val('');
                    toastr.error(result.msg);
                }
            },
        });
    })
    
    

    function get_cheques_list(){
        if($('#transaction_date_range_cheque_deposit').val()){
            start_date = $('input#transaction_date_range_cheque_deposit').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date = $('input#transaction_date_range_cheque_deposit').data('daterangepicker').endDate.format('YYYY-MM-DD');
            
            start_date_created = $('input#transaction_date_range_cheque_deposit_created').data('daterangepicker').startDate.format('YYYY-MM-DD');
            end_date_created = $('input#transaction_date_range_cheque_deposit_created').data('daterangepicker').endDate.format('YYYY-MM-DD');
            
            cheque_no= $('#cheque_customer_cheque_no').val();
            amount = $('#cheque_customer_amount').val();
            
            $.ajax({
                method: 'get',
                url: '{{action("AccountController@getChequeList")}}',
                data: { start_date, end_date, cheque_no,amount,start_date_created,end_date_created },
                contentType: 'html',
                success: function(result) {
                    $('.account_model').find('#cheque_list_table tbody').empty().append(result);
                },
            });
        }
       
    }
    
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
                url: '{{action("AccountController@getRealizeChequeList")}}',
                data: { start_date, end_date, cheque_no,amount,realize_cheque_bank },
                contentType: 'html',
                success: function(result) {
                    $('.account_model').find('#realize_cheque_list_table tbody').empty().append(result);
                },
            });
        }
       
    }

    //account settings tab script
    $(document).ready(function () {
        account_setting_table = $('#account_setting_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{action('AccountSettingController@index')}}",
                data: function(d){
                    d.date = $('#date1').val();
                    d.account_type = $('#account_type1').val();
                    d.account_sub_type = $('#account_sub_type1').val();
                    d.account_id = $('#account_id2').val();
                    d.group_id = $('#group_id2').val();
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
                    className: 'finance-action-cell',
                    render: function(data, type, row) {
                        if (type !== 'display') return data;
                        return financeAccountActionDropdown(data);
                    }
                },
                @endif
                
               
            ],
            @include('layouts.partials.datatable_export_button')
            "fnDrawCallback": function (oSettings) {
                __currency_convert_recursively($('#account_setting_table'));
            },
            "rowCallback": function( row, data, index ) {
                
            }
        });

        
    })

    
    $('#group_id').change(function () {
        group_id = $(this).val();
        
        $.ajax({
            method: 'get',
            url: '/finance/get-account-by-group-id/'+group_id,
            data: {  },
            contentType: 'html',
            success: function(result) {
                $('#account_id').empty().append(result);
            },
        });
    })
    
    
    $('#date1').change(function () {
        account_setting_table.ajax.reload();
    })

    $('#account_id2').change(function () {
        account_setting_table.ajax.reload();
    })

    $('#account_type1').change(function () {
        account_setting_table.ajax.reload();
    })

    $('#account_sub_type1').change(function () {
        account_setting_table.ajax.reload();
    })

    


    $('#group_id2').change(function () {
        account_setting_table.ajax.reload();
        group_id = $(this).val();
        $.ajax({
            method: 'get',
            url: '/finance/get-account-by-group-id/'+group_id,
            data: {  },
            contentType: 'html',
            success: function(result) {
                $('#account_id2').empty().append(result);
            },
        });
    })
    $('#date').datepicker('setDate', new Date());
    $('#date1').datepicker('setDate');


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
                url: '/accounting-module/list-deposit-transfer',
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

        cheques_ob_details_table = $('#cheques_ob_details_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "/accounting-module/cheques-ob-details",
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
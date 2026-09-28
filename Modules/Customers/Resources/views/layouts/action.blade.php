@if(request()->ajax() || request()->wantsJson())
@php
    /*
     * 8049: opt-in narrow variant.
     *
     * Only the view that asks for it becomes narrow. The Ledger, Statement and
     * the other actions share this layout and need the full width for their
     * tables, so the default is unchanged.
     */
    $customersActionCompact = !empty($compact);
@endphp
<div class="modal-dialog modal-lg customers-action-modal-dialog{{ $customersActionCompact ? ' customers-action-modal-compact' : '' }}" role="document">
    <div class="modal-content customers-action-modal-content">

        <style>
            .customers-action-modal-dialog{
                width:95%;
                max-width:1400px;
                margin:30px auto;
            }
            /*
             * 8049: Pay Due Amount at half width.
             *
             * Both the percentage and the cap are halved - 95% to 48%, 1400px
             * to 700px - so the popup is genuinely half the size on a wide
             * screen as well as on a narrow one. Capping alone would have left
             * it unchanged below 1400px.
             *
             * The fields need no separate rule. They are col-md-4, so each is a
             * third of the dialog and halves with it.
             *
             * min-width stops the form collapsing into an unusable column on a
             * mid-size window; below that the mobile rule takes over anyway.
             */
            .customers-action-modal-dialog.customers-action-modal-compact{
                width:48%;
                max-width:700px;
                min-width:420px;
            }
            .customers-action-modal-content{
                border-radius:18px;
                overflow:hidden;
                border:none;
                box-shadow:0 16px 45px rgba(0,0,0,0.25);
            }
            .customers-action-modal-content .modal-header{
                background:#ffffff;
                border-bottom:1px solid #e5e7eb;
                padding:18px 22px;
            }
            .customers-action-modal-content .modal-title{
                font-weight:700;
                color:#1f2d3d;
                margin:0;
            }
            .customers-action-modal-content .modal-body{
                max-height:75vh;
                overflow-y:auto;
                padding:20px 22px;
            }
            .customers-action-modal-content .close{
                font-size:26px;
                opacity:.55;
            }
            @media (max-width:768px){
                .customers-action-modal-dialog,
                .customers-action-modal-dialog.customers-action-modal-compact{
                    width:98%;
                    min-width:0;
                    margin:10px auto;
                }
                .customers-action-modal-content .modal-body{
                    max-height:80vh;
                    padding:14px;
                }
            }
        </style>
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">{{ $title ?? 'Customer Action' }}</h4>
            @if(!empty($customer))
                <small class="text-muted" style="display:block; margin-top:6px;">{{ $customer->contact_id ?? '' }} {{ !empty($customer->name) ? ' - '.$customer->name : '' }}</small>
            @endif
        </div>
        <div class="modal-body">
            @yield('customer_action_body')
        </div>
    </div>
</div>
@else
@extends('layouts.app')
@section('title', $title ?? 'Customer Action')
@section('content')
<section class="content-header">
    <h1>{{ $title ?? 'Customer Action' }} <small>{{ $customer->name ?? '' }}</small></h1>
</section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $title ?? 'Customer Action' }}</h3>
            <div class="box-tools pull-right"><a href="{{ route('customers.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a></div>
        </div>
        <div class="box-body">
            @yield('customer_action_body')
        </div>
    </div>
</section>
@endsection
@endif

@extends('layouts.app')
@section('title', __('stocktransfernew::lang.freight_settlement'))
@section('content')
<section class="content-header stn-page-header">
    <h1>{{ __('stocktransfernew::lang.freight_settlement') }}</h1>
</section>
<section class="content stn-pos-page">
    <div class="box stn-card">
        <div class="box-header with-border stn-toolbar">
            <div class="stn-toolbar-left">
                <input type="text" id="stn_freight_search" class="form-control" placeholder="{{ __('stocktransfernew::lang.search') }}">
            </div>
            <div class="stn-toolbar-right">
                <a href="{{ route('stock-transfer-new.freight-settlement.export') }}" class="btn btn-primary btn-sm">CSV</a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped" id="stn_freight_settlement_table">
                <thead>
                    <tr>
                        <th>{{ __('stocktransfernew::lang.invoice_no') }}</th>
                        <th>{{ __('stocktransfernew::lang.carrier') }}</th>
                        <th>{{ __('stocktransfernew::lang.invoice_date') }}</th>
                        <th>{{ __('stocktransfernew::lang.amount') }}</th>
                        <th>{{ __('stocktransfernew::lang.approved_amount') }}</th>
                        <th>{{ __('stocktransfernew::lang.status') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</section>
@endsection
@section('javascript')
<script src="{{ asset('modules/stocktransfernew/js/freight_settlement.js') }}"></script>
@endsection

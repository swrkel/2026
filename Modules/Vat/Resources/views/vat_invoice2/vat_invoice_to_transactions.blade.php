@extends('layouts.app')
@section('title', __('vat::lang.vat_invoice_to_transactions'))

@section('content')
<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @include('vat::vat_invoice2.partials.nav')
            
            <div class="box box-primary">
                <div class="box-header">
                    <h3 class="box-title">@lang('vat::lang.vat_invoice_to_transactions')</h3>
                </div>
                <div class="box-body">
                    {!! Form::open(['url' => action('\Modules\Vat\Http\Controllers\VatInvoiceToTransactionController@store'), 'method' => 'post', 'id' => 'vat_invoice_to_transactions_form' ]) !!}
                    <div class="row">
                        <div class="col-md-6">
                            <div class="well well-sm">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <div class="checkbox">
                                        <label>
                                            {!! Form::checkbox('auto_update', 1, false, ['class' => 'input-icheck']) !!}
                                            <strong>@lang('vat::lang.auto_update_ledger_books_stock')</strong>
                                        </label>
                                    </div>
                                    <p class="help-block" style="margin-left: 20px;">
                                        <small><i>When the VAT Invoice is saved, need to Auto Update in Customer Ledger, Account Books and Stock Records.</i></small>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {!! Form::label('note', __('brand.note') . ':') !!}
                                {!! Form::textarea('note', null, ['class' => 'form-control', 'placeholder' => __('brand.note'), 'rows' => 3]); !!}
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-right">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa fa-save"></i> @lang('messages.save')
                            </button>
                        </div>
                    </div>
                    {!! Form::close() !!}
                    
                    <hr>
                    
                    <div class="box box-solid box-info">
                        <div class="box-header with-border">
                            <h3 class="box-title">@lang('vat::lang.option_status') @lang('vat::lang.list')</h3>
                            <div class="box-tools">
                                <button type="button" class="btn btn-info btn-sm btn-history">
                                    <i class="fa fa-history"></i> @lang('vat::lang.activity_history')
                                </button>
                            </div>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="vat_invoice_to_transaction_table" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th>@lang('messages.action')</th>
                                            <th>@lang('lang_v1.date_and_time')</th>
                                            <th>@lang('vat::lang.option_status')</th>
                                            <th>@lang('vat::lang.added_user')</th>
                                            <th>@lang('brand.note')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($settings as $setting)
                                            <tr>
                                                <td class="text-center">
                                                    <label class="switch">
                                                        <input type="checkbox" class="status-toggle" data-url="{{ action('\Modules\Vat\Http\Controllers\VatInvoiceToTransactionController@toggleStatus', [$setting->id]) }}" {{ $setting->status == 'Active' ? 'checked' : '' }}>
                                                        <span class="slider round"></span>
                                                    </label>
                                                    <br>
                                                    <small>{{ $setting->status == 'Active' ? __('vat::lang.deactivate') : __('vat::lang.activate') }}</small>
                                                </td>
                                                <td>{{ @format_datetime($setting->created_at) }}</td>
                                                <td class="text-center">
                                                    <span class="label {{ $setting->status == 'Active' ? 'label-success' : 'label-danger' }}">
                                                        {{ $setting->status == 'Active' ? __('vat::lang.active') : __('vat::lang.inactive') }}
                                                    </span>
                                                </td>
                                                <td>{{ $setting->created_by_user->user_full_name }}</td>
                                                <td>{{ $setting->note }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

</div>

<!-- History Modal -->
<div class="modal fade" id="history_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">@lang('vat::lang.activity_history')</h4>
            </div>
            <div class="modal-body">
                <div id="history_content" class="text-center">
                    <i class="fa fa-refresh fa-spin fa-3x fa-fw" style="margin: 30px 0;"></i>
                    <p>@lang('messages.loading')...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('css')
<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }
    .switch input { 
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }
    input:checked + .slider {
        background-color: #2196F3;
    }
    input:focus + .slider {
        box-shadow: 0 0 1px #2196F3;
    }
    input:checked + .slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }
    .slider.round {
        border-radius: 34px;
    }
    .slider.round:before {
        border-radius: 50%;
    }
</style>
@endsection

@section('javascript')
<script>
    $(document).ready(function(){
        $('#vat_invoice_to_transaction_table').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf', 'print', 'colvis'
            ],
        });

        $(document).on('change', '.status-toggle', function(){
            var url = $(this).data('url');
            window.location.href = url;
        });

        $(document).on('click', '.btn-history', function(e){
            e.preventDefault();
            $('#history_content').html('<div class="text-center"><i class="fa fa-refresh fa-spin fa-3x fa-fw" style="margin: 30px 0;"></i><p>@lang("messages.loading")...</p></div>');
            $('#history_modal').modal('show');
            
            $.ajax({
                url: "{{ action('\Modules\Vat\Http\Controllers\VatInvoiceToTransactionController@history') }}",
                dataType: "html",
                success: function(result){
                    $('#history_content').html(result);
                    $('#history_table').DataTable({
                        dom: 'Bfrtip',
                        buttons: [
                            'copy', 'csv', 'excel', 'pdf', 'print', 'colvis'
                        ],
                    });
                }
            });
        });
    });
</script>
@endsection

<div class="row">
    <div class="col-md-12">
        <div class="box box-solid" style="border-radius:14px; border:1px solid #e6eef8; box-shadow:0 8px 24px rgba(15,23,42,.06);">
            <div class="box-header with-border" style="background:#f3f7fb; border-radius:14px 14px 0 0;">
                <h3 class="box-title"><i class="fa fa-filter"></i> @lang('report.filters')</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('list_deposit_transfer_date_range', __('lang_v1.date_range').':') !!}
                            {!! Form::text('list_deposit_transfer_date_range', null, ['class' => 'form-control', 'style' => 'width:100%;', 'placeholder' => __('lang_v1.date_range')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('list_deposit_transfer_type', __('lang_v1.type').':') !!}
                            {!! Form::select('list_deposit_transfer_type', ['deposit' => 'Deposit', 'fund_transfer' => __('lang_v1.transfer')], null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('from_account_id', __('lang_v1.from_account').':') !!}
                            {!! Form::select('from_account_id', $accounts, null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('to_account_id', __('lang_v1.to_account').':') !!}
                            {!! Form::select('to_account_id', $accounts, null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('user_id', __('lang_v1.user').':') !!}
                            {!! Form::select('user_id', $users, null, ['class' => 'form-control select2', 'style' => 'width:100%;', 'placeholder' => __('lang_v1.all')]); !!}
                        </div>
                    </div>
                    <div class="col-md-3" style="padding-top:25px;">
                        <button type="button" class="btn btn-primary" id="list_deposit_transfer_filter_btn"><i class="fa fa-search"></i> Report Filter</button>{{-- S673: was @lang('report.filter'), a key that does not exist in this module's language files, so the raw key printed. --}}
                        <button type="button" class="btn btn-default" id="list_deposit_transfer_clear_btn"><i class="fa fa-times"></i> Clear Message</button>{{-- S673: was @lang('messages.clear'); wording also corrected from "Message Clear" per the ticket. --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <table class="table table-striped table-bordered" id="list_deposit_transfer_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('lang_v1.action')</th>
                    <th>@lang('lang_v1.date')</th>
                    <th>Customer Name</th>
                    <th>@lang('lang_v1.type')</th>
                    <th>@lang('lang_v1.amount')</th>
                    <th>@lang('lang_v1.from_account')</th>
                    <th>@lang('lang_v1.to_account')</th>
                    <th>@lang('lang_v1.cheque_number')</th>
                    <th>@lang('lang_v1.user')</th>
                </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
                <tr class="bg-gray">
                    <th colspan="4" class="text-right">Page Total</th>
                    <th><span id="list_deposit_transfer_page_total" class="display_currency" data-currency_symbol="false">0.00</span></th>
                    <th colspan="3" class="text-right">Record Count</th>
                    <th><span id="list_deposit_transfer_record_count">0</span></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

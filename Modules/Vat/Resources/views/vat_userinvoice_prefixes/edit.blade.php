<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('\\Modules\\Vat\\Http\\Controllers\\VatUserInvoicePrefixController@update', [$data->id]), 'method' => 'put', 'id' => 'vat_user_invoice_prefix_edit_form', 'class' => 'vat-modal-ajax-form', 'data-vat-reload-table' => '#userinvoice_prefixes_table']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title"><i class="fa fa-edit"></i> @lang('messages.edit')</h4>
        </div>
        <div class="modal-body">
            {{-- IS1571_DROPDOWN_WARNING: clearly show when master data is genuinely missing instead of showing an empty invisible dropdown. --}}
            @if((isset($business_locations) && count($business_locations) == 0) || (isset($users) && count($users) == 0) || (isset($prefixes) && count($prefixes) == 0) || (isset($prefixes2) && count($prefixes2) == 0))
                <div class="alert alert-warning" style="margin-bottom:15px;">
                    Some dropdown master data is missing. Please create Location, User, VAT Invoice Prefix and VAT Invoice 2 Prefix first.
                </div>
            @endif
            <div class="row">
                <div class="col-md-6"><div class="form-group">
                    {!! Form::label('date_time', __('vat::lang.date_time') . ':*') !!}
                    {!! Form::input('datetime-local', 'date_time', !empty($data->date_time) ? \Carbon\Carbon::parse($data->date_time)->format('Y-m-d\\TH:i') : \Carbon\Carbon::now()->format('Y-m-d\\TH:i'), ['class' => 'form-control', 'required', 'style' => 'width:100%;']) !!}
                </div></div>
                <div class="col-md-6"><div class="form-group">
                    {!! Form::label('location_id', __('vat::lang.location') . ':*') !!}
                    {!! Form::select('location_id', $business_locations ?? [], $data->location_id ?? null, ['class' => 'form-control select2 vat-required-dropdown', 'placeholder' => __('vat::lang.select_one'), 'required', 'style' => 'width:100%;']) !!}
                </div></div>
            </div>
            <div class="row">
                <div class="col-md-6"><div class="form-group">
                    {!! Form::label('user_id', __('vat::lang.user') . ':*') !!}
                    {!! Form::select('user_id', $users ?? [], $data->user_id ?? null, ['class' => 'form-control select2 vat-required-dropdown', 'placeholder' => __('vat::lang.select_one'), 'required', 'style' => 'width:100%;']) !!}
                </div></div>
                <div class="col-md-6"><div class="form-group">
                    {!! Form::label('prefix_id', __('vat::lang.vat_invoice_prefix') . ':*') !!}
                    {!! Form::select('prefix_id', $prefixes ?? [], $data->prefix_id ?? null, ['class' => 'form-control select2 vat-required-dropdown', 'placeholder' => __('vat::lang.select_one'), 'required', 'style' => 'width:100%;']) !!}
                </div></div>
            </div>
            <div class="row">
                <div class="col-md-6"><div class="form-group">
                    {!! Form::label('prefix_id2', __('vat::lang.vat_invoice2_prefix') . ':*') !!}
                    {!! Form::select('prefix_id2', $prefixes2 ?? [], $data->prefix_id2 ?? null, ['class' => 'form-control select2 vat-required-dropdown', 'placeholder' => __('vat::lang.select_one'), 'required', 'style' => 'width:100%;']) !!}
                </div></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>

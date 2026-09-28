<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => action('\\Modules\\Vat\\Http\\Controllers\\VatStatementPrefixController@store'), 'method' => 'post', 'id' => 'vat_statement_prefix_add_form', 'class' => 'vat-modal-ajax-form', 'data-vat-reload-table' => '#prefixes_table']) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('vat::lang.add_prefix')</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('prefix', __('vat::lang.prefix') . ':') !!}
                        {!! Form::text('prefix', null, ['class' => 'form-control', 'placeholder' => __('vat::lang.prefix'), 'style' => 'width: 100%;']) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('starting_no', __('vat::lang.starting_no') . ':*') !!}
                        {!! Form::text('starting_no', null, ['class' => 'form-control', 'required', 'inputmode' => 'numeric', 'pattern' => '[0-9]+', 'placeholder' => __('vat::lang.starting_no'), 'style' => 'width: 100%;']) !!}
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
        {!! Form::close() !!}
    </div>
</div>

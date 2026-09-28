<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\Distribution\Http\Controllers\DistributionNumberingPrefixController@update', [$prefix->id]), 'method' => 'PUT', 'id' => 'prefix_edit_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">
                <span>&times;</span>
            </button>
            <h4 class="modal-title">Edit Prefix & Starting Number</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('numbering_type', 'Numbering Type:*') !!}
                {!! Form::select('numbering_type', [
                    'sales_invoice' => 'Sales Invoice',
                    'sales_order' => 'Sales Orders',
                    'daily_summary_sheet' => 'Daily Summary Sheet',
                    'loading_sheet' => 'Loading Sheet',
                    'stock_transfer' => 'Stock Transfer',
                    'vat_distribution_invoice' => 'VAT Distribution Invoice'
                ], $prefix->numbering_type, ['class' => 'form-control select2', 'required']) !!}
            </div>

            <div class="form-group">
                {!! Form::label('prefix', 'Prefix') !!}
                {!! Form::text('prefix', $prefix->prefix, ['class' => 'form-control', 'placeholder' => 'Optional']) !!}
            </div>

            <div class="form-group">
                {!! Form::label('starting_no', 'Starting Number:*') !!}
                {!! Form::number('starting_no', $prefix->starting_no, ['class' => 'form-control', 'required', 'min' => 0]) !!}
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>

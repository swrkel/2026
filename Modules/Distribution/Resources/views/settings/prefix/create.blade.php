<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('\Modules\Distribution\Http\Controllers\DistributionNumberingPrefixController@store'), 'method' => 'post', 'id' => 'prefix_add_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">
                <span>&times;</span>
            </button>
            <h4 class="modal-title">Add Prefix & Starting Number</h4>
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
                ], null, ['class' => 'form-control select2', 'required']) !!}
            </div>

            <div class="form-group">
                {!! Form::label('prefix', 'Prefix') !!}
                {!! Form::text('prefix', null, ['class' => 'form-control', 'placeholder' => 'Optional']) !!}
            </div>

            <div class="form-group">
                {!! Form::label('starting_no', 'Starting Number:*') !!}
                {!! Form::number('starting_no', null, ['class' => 'form-control', 'required', 'min' => 0]) !!}
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>

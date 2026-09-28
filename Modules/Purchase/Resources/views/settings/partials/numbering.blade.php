{!! Form::open(['route' => 'purchase.settings.numbering.save', 'method' => 'post']) !!}
<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('purchase_order_prefix', __('purchase::lang.purchase_order_prefix')) !!}
            {!! Form::text('purchase_order_prefix', 'PO-', ['class' => 'form-control']) !!}
        </div>
    </div>
</div>
<button class="btn btn-primary">@lang('messages.save')</button>
{!! Form::close() !!}

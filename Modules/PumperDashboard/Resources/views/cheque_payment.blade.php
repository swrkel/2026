<form id="cheque_payment_form">
    @csrf
<div class="row">
    <div class="col-md-12">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('customer_id', __('pumperdashboard::lang.customer').':') !!}
                    {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2',
                    'style' => 'width: 100%;', 'required', 'id' => 'cheque_customer_id']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('cheque_number', __( 'pumperdashboard::lang.cheque_number' ) ) !!}
                    {!! Form::text('cheque_number', null, ['class' => 'form-control cheque_number',
                    'placeholder' => __('pumperdashboard::lang.cheque_number'), 'required']); !!}
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('cheque_date', __( 'pumperdashboard::lang.cheque_date' ) ) !!}
                    {!! Form::date('cheque_date', null, ['class' => 'form-control cheque_date',
                    'placeholder' => __('pumperdashboard::lang.cheque_date'), 'required']); !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('amount', __( 'pumperdashboard::lang.amount' ) ) !!}
                    {!! Form::text('amount', null, ['class' => 'form-control amount',
                    'placeholder' => __('pumperdashboard::lang.amount'), 'required']); !!}
                </div>
            </div>
            <input type="hidden" class="collection_form_no" name="collection_form_no" value="{{ $collection_form_no ?? '' }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-2 pull-right">
        <button type="submit" id="cheque_finalize_btn" class="btn btn-danger pull-right"
            style="margin-top: 23px;">@lang('pumperdashboard::lang.finalize')</button>
    </div>
</div>
</form>

<script>
$(document).off('submit', '#cheque_payment_form').on('submit', '#cheque_payment_form', function(e) {
    e.preventDefault();

    var $btn = $('#cheque_finalize_btn');
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');

    var formData = $(this).serialize();

    $.ajax({
        method: 'POST',
        url: '{{ action('\Modules\\PumperDashboard\\Http\\Controllers\\PumpOperatorPaymentController@saveChequePayment') }}',
        data: formData,
        dataType: 'json',
        success: function(result) {
            if (result.success) {
                toastr.success(result.msg || '@lang("lang_v1.success")');
                $('#cheque_payments').modal('hide');
                $('#cheque_payment_form')[0].reset();
                // Update collection form no if returned
                if (result.collection_form_no) {
                    $('#collection_form_no').val(result.collection_form_no);
                    $('.collection_form_no').val(result.collection_form_no);
                }
            } else {
                toastr.error(result.msg || '@lang("messages.something_went_wrong")');
            }
        },
        error: function() {
            toastr.error('@lang("messages.something_went_wrong")');
        },
        complete: function() {
            $btn.prop('disabled', false).html('@lang("pumperdashboard::lang.finalize")');
        }
    });
});
</script>





<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('\\Modules\\Finance\\Http\\Controllers\\Deposits\\DepositsController@postFundTransfer'), 'method' => 'post', 'id' =>
    'fund_transfer_form', 'enctype' => 'multipart/form-data' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'deposits.fund_transfer' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        {!! Form::label('from_account', __( 'deposits.transfer_from' ) .":*") !!}
        {!! Form::select('from_account', $to_accounts, null, ['class' => 'form-control select2', 'required', 'placeholder' => __('lang_v1.please_select') ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('to_account', __( 'deposits.transfer_to' ) .":*") !!}
        {!! Form::select('to_account', $to_accounts, null, ['class' => 'form-control select2', 'required', 'placeholder' => __('lang_v1.please_select') ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('amount', __( 'sale.amount' ) .":*") !!}
        {!! Form::text('amount', 0, ['class' => 'form-control input_number', 'required','placeholder' => __(
        'sale.amount' ) ]); !!}
      </div>

         
      <div class="form-group">
        {!! Form::label('cheque_number', __( 'lang_v1.cheque_number' )) !!}
        {!! Form::text('cheque_number', null, ['class' => 'form-control input_number', 'placeholder' => __(
        'lang_v1.cheque_number' ) ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('operation_date', __( 'messages.date' ) .":*") !!}
        <div class="input-group date" id='od_datetimepicker'>
          {!! Form::text('operation_date', @format_datetime('now'), ['class' => 'form-control', 'required', 'autocomplete' => 'off', 'placeholder' => __(
          'messages.date' ) ]); !!}
          <span class="input-group-addon">
            <span class="glyphicon glyphicon-calendar"></span>
          </span>
        </div>
      </div>

      <div class="form-group">
        {!! Form::label('note', __( 'brand.note' )) !!}
        {!! Form::textarea('note', null, ['class' => 'form-control', 'placeholder' => __( 'brand.note' ), 'rows' => 4]);
        !!}
      </div>

      <div class="form-group">
        {!! Form::label('attachment', __( 'lang_v1.add_image_document' )) !!}
        {!! Form::file('attachment', ['files' => true]); !!}
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary submit_btn">@lang( 'messages.submit' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
<style>
  .swal-title{
    color: red;
  }
</style>
<script type="text/javascript">
  $(document).ready( function(){
    /*
     * S650: scoped, and no second picker on one element.
     *
     * Cash Deposit, Cheque Deposit, Card Deposit and Transfer all load into the
     * same .account_model container, and deposit.blade.php / account/notes.blade.php
     * also render an element with id "od_datetimepicker". An unscoped
     * $('#od_datetimepicker') therefore bound whichever one happened to be first
     * in the DOM - so the calendar you could see belonged to a different element
     * than the one being updated. That is why the picker would not open on Cash
     * and Cheque Deposit, and why the date picked on Transfer never appeared in
     * the field.
     */
    var $transferDate = $('.account_model, .modal.in').find('#od_datetimepicker').first();

    if (!$transferDate.length) {
        $transferDate = $('#od_datetimepicker').first();
    }

    if ($transferDate.length && !$transferDate.data('DateTimePicker')) {
        $transferDate.datetimepicker({
            format: moment_date_format + ' ' + moment_time_format,
            ignoreReadonly: true
        });

        // Write the chosen value back, so the selected date is visible.
        $transferDate.off('dp.change.s650').on('dp.change.s650', function (e) {
            if (e.date && e.date.isValid && e.date.isValid()) {
                $(this).find('input').val(
                    e.date.format(moment_date_format + ' ' + moment_time_format)
                );
            }
        });
    }
    
    $(".select2").select2();
  });
  
  
  $('#account_group_id').change(function () {
    $.ajax({
      method: 'get',
      // S673: same empty-group-id guard as the accounting transfer page.
      url: '/deposits-module/get-account-by-group-id/' + encodeURIComponent($(this).val() || 0),
      data: {  },
      contentType: 'html',
      success: function(result) {
        $('#to_account').empty().append(result);
      },
    });
  })
</script>
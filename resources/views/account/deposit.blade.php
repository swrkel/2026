@php
 use Modules\Petro\Entities\PetroDailyShift;
 $business_id = request()->session()->get('user.business_id');
   $petroDailyShift = PetroDailyShift::where('business_id', $business_id)
            ->where('status', 0)
            ->pluck('shift_no')->toArray();
 @endphp
<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('AccountController@postDeposit'), 'method' => 'post', 'id' => 'deposit_form',
    'enctype' => 'multipart/form-data' ]) !!}
    {!! Form::hidden('deposit_type', !empty($sub_card_accounts) ? 'card' : (!empty($account) ? strtolower($account->name) : null)) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'account.deposit' )</h4>
    </div>

    <div class="modal-body">
      <div class="form-group">
        <strong>@lang('account.selected_account')</strong>:
        <span class="selected_account">
          @if(empty($sub_card_accounts))
          {{$account->name}}
          @endif
        </span>
        <span class="text-red pull-right account_balance"> @lang('account.balance'): @if(!empty($account_balance->balance))
          {{@num_format($account_balance->balance)}} @else {{0.00}} @endif </span>
        {!! Form::hidden('check_insufficient', $check_insufficient, ['id' => 'check_insufficient']) !!}
        @if(empty($sub_card_accounts))
        {!! Form::hidden('account_balance', !empty($account_balance->balance) ? round($account_balance->balance, 2) : 0, ['id' => 'account_balance']) !!}
        @else
        {!! Form::hidden('account_balance', 0, ['id' => 'account_balance']) !!}
        @endif
      </div>

      @if(!empty($sub_card_accounts))
      <div class="form-group">
        {!! Form::label('account_id', __( 'account.card_accounts' ) .":") !!}
        {!! Form::select('account_id', $sub_card_accounts, null, ['class' => 'form-control select2 account_id_deposit', 'placeholder' =>
        __('messages.please_select') ]); !!}
      </div>
      @else
      {!! Form::hidden('account_id', $account->id) !!}
      @endif

      <div class="row">
          <div class="form-group col-sm-4">
            {!! Form::label('account_group_id', __( 'account.account_group' ) .":*") !!}
            {!! Form::select('account_group_id', $account_groups, null, ['class' => 'form-control select2','required', 'placeholder' => __('lang_v1.please_select') ]); !!}
          </div>
          
          <div class="form-group col-sm-4">
            {!! Form::label('from_account', __( 'account.deposit_to' ) .":") !!}
            {!! Form::select('from_account', $from_accounts, null, ['class' => 'form-control select2','required', 'placeholder' =>
            __('messages.please_select') ]); !!}
          </div>
          
          <div class="form-group col-sm-4">
            {!! Form::label('operation_date', __( 'messages.date' ) .":*") !!}
            <div class="input-group date" id='od_datetimepicker'>
              {!! Form::text('operation_date', @format_datetime('now'), ['class' => 'form-control', 'required', 'readonly', 'placeholder' => __(
              'messages.date' ) ]); !!}
              <span class="input-group-addon">
                <span class="glyphicon glyphicon-calendar"></span>
              </span>
            </div>
          </div>
          
          <div class="form-group chequeDetails col-sm-4" >
            {!! Form::label('cheque_number', __( 'lang_v1.cheque_number' ) .":*") !!}
            {!! Form::text('cheque_number', null, ['class' => 'form-control input_number', 'placeholder' => __(
            'lang_v1.cheque_number' ) ]); !!}
          </div>
      </div>
      <hr>
      <div class="row" id="amounts_row">
          
          <div class="col-sm-12">
              {!! Form::label('amount', __( 'sale.amount' ) .":*") !!}
          </div>
          
          <div class="form-group col-sm-4">
            <div class="input-group">
              {!! Form::number('amount[]', null, ['class' => 'form-control input_amount', 'required','placeholder' => __(
                'sale.amount'),'step' => 'any']); !!}
              <span  class="input-group-addon bg-success" id="add_amount"> + </span>
            </div>
          </div>
          <div class="form-group col-sm-4">

              <div class="form-group">
                  {!! Form::label('daily_shift_no', 'Daily Shift No' ) !!}
                  {!! Form::select('daily_shift_no', array_combine($petroDailyShift,$petroDailyShift) ,null, ['class' => 'form-control daily_shift_no
                  daily_shift_no', 'id' => 'daily_shift_no',
                  'style' => 'width: 100%;',
                  'placeholder' => __(
                  'petro::lang.please_select' ) ]); !!}
              </div>
          </div>
      </div>
     
    <hr>
        @if($mpcs_module)
        <div class="row">
          <div class="col-md-12">
            <div class="checkbox">
              <label>
              {!! Form::checkbox('is_manager_cash_deposit', 1, false, ['class' => 'input-icheck']); !!}
               {{ __( 'account.cash_deposited_by_manager' ) }}
              </label>
            </div>
          </div>
        </div>
        @endif
        
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
  /* 006_S354: make transaction date calendar visible above Bootstrap modals for Cash/Card deposit */
  .bootstrap-datetimepicker-widget, .daterangepicker { z-index: 20000 !important; }
  #od_datetimepicker .input-group-addon { cursor:pointer; }
  .swal-title {
    color: red;
  }
</style>
<script type="text/javascript">
  
  $(document).ready( function(){
      $(".select2").select2();
      
      var isCash = "{{!empty($account) ? $account->name : ''}}";
      if(isCash == "Cash"){
          $(".chequeDetails").hide();
      }

      var operationDateFormat = moment_date_format + ' ' + moment_time_format;
      var $depositForm = $('#deposit_form');
      var $operationDateInput = $depositForm.find('input[name="operation_date"]');
      var $operationDatePicker = $depositForm.find('#od_datetimepicker');
      var selectedMoment = moment($operationDateInput.val(), operationDateFormat, true);
      if (!selectedMoment.isValid()) {
        selectedMoment = moment();
        $operationDateInput.val(selectedMoment.format(operationDateFormat));
      }

      $operationDatePicker.datetimepicker({
        format: operationDateFormat,
        date: selectedMoment,
        defaultDate: selectedMoment,
        useCurrent: true,
        ignoreReadonly: true,
      });
      $operationDatePicker.data('DateTimePicker').date(selectedMoment);
      $operationDatePicker.data('DateTimePicker').viewDate(selectedMoment);

      $operationDatePicker.on('dp.show', function() {
        var picker = $operationDatePicker.data('DateTimePicker');
        var current = picker && picker.date() ? picker.date() : moment();
        picker.viewDate(current);
      });

      $operationDatePicker.on('dp.change', function(e) {
        if (e.date) {
          $operationDateInput.val(e.date.format(operationDateFormat));
        }
      });

      $depositForm.on('submit', function() {
        var selectedDate = $operationDatePicker.data('DateTimePicker')
          ? $operationDatePicker.data('DateTimePicker').date()
          : null;
        if (selectedDate) {
          $operationDateInput.val(selectedDate.format(operationDateFormat));
          try {
            var accountIds = [
              $depositForm.find('[name="account_id"]').val(),
              $depositForm.find('[name="from_account"]').val()
            ].filter(Boolean);

            localStorage.setItem('account_book_last_deposit', JSON.stringify({
              date: selectedDate.format('YYYY-MM-DD'),
              account_ids: accountIds,
              deposit_type: $depositForm.find('[name="deposit_type"]').val(),
              stored_at: moment().valueOf()
            }));
          } catch (e) {}
        }
      });
  });

    function calculate_deposit_totals(){
        let amount = 0;
      
        $('.input_amount').each(function () {
            var value = parseFloat($(this).val()) || 0;
            amount += value;
        });
       account_balance = parseFloat($('#account_balance').val());
      
      if(false){ // Fix: Disable insufficient balance check for deposits
        swal({
            title: '@lang('account.insufficient_balance_msg')',
            icon: "error",
            buttons: true,
            dangerMode: true,
        })
        $('.submit_btn').prop('disabled', true);
      }else{
        $('.submit_btn').prop('disabled', false);
      }
    }
  
    $(document).on('click', '.remove_amount', function () {
        $(this).closest('.added-amount').remove();
        @if($group_name != 'Bank Account')
            calculate_deposit_totals();
        @endif
    });

  @if($group_name != 'Bank Account')
    $(document).on('change','.input_amount',function(){//@eng 13/2
        calculate_deposit_totals();
    })
  @endif

  @if(!empty($sub_card_accounts))
  // Show cheque_number field for card deposits (used as card number)
  $('.chequeDetails').show();
  $('.chequeDetails label').text('@lang("lang_v1.card_no"):');
  $('.chequeDetails input').attr('placeholder', '@lang("lang_v1.card_no")');
  
  $('.account_id_deposit').change(function(){
    account_id = $(this).val();
    $.ajax({
      method: 'get',
      url: '/finance/get-account-balance/'+account_id,
      data: {  },
      success: function(result) {
        $('.account_balance').text('Balance:' +__number_f(result.balance, false));
        $('.selected_account').text(result.name);
        $('#account_balance').val(result.balance);
      },
    });
  })
  @endif
  $('#account_group_id').change(function () {
    $.ajax({
      method: 'get',
      url: '/finance/get-account-by-group-id/' + $(this).val(),
      data: {  },
      contentType: 'html',
      success: function(result) {
        $('#from_account').empty().append(result);
      },
    });
  })
</script>

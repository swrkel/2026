@php
    // The controller is the single source for the optional Petro shift list.
    // Do not query Petro directly from the Finance view: a disabled/unavailable
    // Petro module must never make Cash/Card Deposit fail with HTTP 500.
    $petroDailyShift = isset($petroDailyShifts) && is_array($petroDailyShifts)
        ? array_values(array_filter($petroDailyShifts, static function ($value) {
            return $value !== null && $value !== '';
        }))
        : [];
    $petroDailyShiftOptions = !empty($petroDailyShift)
        ? array_combine($petroDailyShift, $petroDailyShift)
        : [];
@endphp
<div class="modal-dialog finance-deposit-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => url('/finance/finance-deposit'), 'method' => 'post', 'id' => 'deposit_form',
    'enctype' => 'multipart/form-data' ]) !!}
    {!! Form::hidden('finance_confirmation', 0, ['id' => 'finance_confirmation']) !!}
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

      <div class="alert alert-danger finance-insufficient-balance-alert" style="display:none; font-weight:700; margin-top:10px;"></div>

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
                  {!! Form::select('daily_shift_no', $petroDailyShiftOptions, null, ['class' => 'form-control daily_shift_no
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
  /* S769 follow-up: the Cash/Card Deposit dialog is intentionally 15% wider
     than Bootstrap's standard 600px modal (600 x 1.15 = 690px).  This gives
     the date control/calendar enough horizontal room without changing any
     other Finance pop-up.  On smaller screens it remains fully responsive. */
  @media (min-width: 768px) {
    .account_model .modal-dialog.finance-deposit-dialog {
      width: 690px !important;
      max-width: calc(100vw - 30px) !important;
    }
  }

  #od_datetimepicker .input-group-addon { cursor:pointer; }
  .swal-title {
    color: red;
  }
</style>

{{-- S769: use the Finance shared native date control for both Cash and Card
     Deposit.  The old bootstrap-datetimepicker was being drawn inside the
     compact modal/grid and appeared as an oversized/misaligned calendar. --}}
@include('finance::account.partials.date_picker_bind')
@include('finance::account.partials.transaction_confirm')

<script type="text/javascript">
  
  function financeDepositBalanceRestricted() {
      var raw = String($('#deposit_form #check_insufficient').val() || '').toLowerCase();
      return raw === '1' || raw === 'true';
  }

  function financeDepositAmountTotal() {
      var amount = 0;
      $('#deposit_form .input_amount').each(function () {
          amount += parseFloat($(this).val()) || 0;
      });
      return amount;
  }

  function financeDepositShowBalanceError(message) {
      var $form = $('#deposit_form');
      $form.find('.finance-insufficient-balance-alert').text(message).show();

      if (typeof swal === 'function') {
          swal({
              title: 'Insufficient Balance',
              text: message,
              icon: 'error',
              dangerMode: true,
              buttons: {
                  ok: {
                      text: 'OK',
                      value: true,
                      visible: true,
                      className: 'swal-button--danger'
                  }
              }
          });
      }
  }

  function financeDepositClearBalanceError() {
      $('#deposit_form .finance-insufficient-balance-alert').hide().text('');
  }

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

      // S769: Cash Deposit and Card Deposit share this same view.  Bind the
      // date once through the Finance native-date helper rather than creating a
      // bootstrap-datetimepicker widget inside the AJAX modal.  The helper keeps
      // a hidden operation_date field in the business date/time format, so the
      // controller receives exactly the same request value as before.
      var operationDateApi = (typeof window.financeBindDatePicker === 'function')
        ? window.financeBindDatePicker({
            picker: $operationDatePicker,
            input: $operationDateInput,
            format: operationDateFormat
          })
        : null;

      if (operationDateApi && !operationDateApi.date()) {
        operationDateApi.date(selectedMoment);
      }

      $depositForm.on('submit', function() {
        var selectedDate = operationDateApi && typeof operationDateApi.date === 'function'
          ? operationDateApi.date()
          : moment($depositForm.find('[name="operation_date"]').val(), operationDateFormat, true);

        if (selectedDate && selectedDate.isValid()) {
          // Re-apply through the API before submit so the hidden posting field
          // is synchronised even if the browser fires change late.
          if (operationDateApi && typeof operationDateApi.date === 'function') {
            operationDateApi.date(selectedDate);
          }

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

      // S735: mandatory reconfirmation plus a client-side source-balance
      // preview. The server independently re-checks the balance, so a stale
      // browser value can never permit an invalid transaction.
      $depositForm.off('submit.financeS735Confirm').on('submit.financeS735Confirm', function(e) {
          var $form = $(this);

          if ($form.data('finance-s735-confirmed')) {
              $form.removeData('finance-s735-confirmed');
              $form.find('[name="finance_confirmation"]').val('1');
              financeDepositClearBalanceError();
              return true;
          }

          e.preventDefault();
          e.stopImmediatePropagation();

          var amount = financeDepositAmountTotal();
          var sourceAccountId = $form.find('[name="account_id"]').val();
          var sourceName = $.trim($form.find('.selected_account').text()) || 'Selected account';
          var destinationName = $.trim($form.find('#from_account option:selected').text()) || 'Selected account';
          var operationDate = $form.find('[name="operation_date"]').val();

          function askForConfirmation() {
              var rows = [
                  {label: 'From Account', value: sourceName},
                  {label: 'To Account', value: destinationName},
                  {label: 'Amount', value: window.financeConfirmAmount ? window.financeConfirmAmount(amount) : amount, highlight: true},
                  {label: 'Date & Time', value: operationDate}
              ];

              var confirmation = (typeof window.financeConfirmTransaction === 'function')
                  ? window.financeConfirmTransaction({
                      title: 'Confirm Transaction',
                      rows: rows,
                      yesText: 'Yes, Process',
                      noText: 'No'
                    })
                  : Promise.resolve(window.confirm('Please confirm this transaction before it is processed.'));

              confirmation.then(function(confirmed) {
                  if (!confirmed) {
                      return;
                  }

                  $form.data('finance-s735-confirmed', true);
                  $form.find('[name="finance_confirmation"]').val('1');
                  $form.trigger('submit');
              });
          }

          if (!financeDepositBalanceRestricted() || !sourceAccountId || amount <= 0) {
              askForConfirmation();
              return false;
          }

          $.ajax({
              method: 'get',
              url: '/finance/get-account-balance/' + encodeURIComponent(sourceAccountId),
              cache: false,
              dataType: 'json',
              success: function(result) {
                  var available = parseFloat(result.balance) || 0;
                  $form.find('#account_balance').val(available);

                  if (amount > available + 0.000001) {
                      financeDepositShowBalanceError(
                          'Insufficient Balance: ' + sourceName + ' has only ' +
                          (window.financeConfirmAmount ? window.financeConfirmAmount(available) : available.toFixed(2)) +
                          ' available, but ' +
                          (window.financeConfirmAmount ? window.financeConfirmAmount(amount) : amount.toFixed(2)) +
                          ' is required. Please reduce the amount or add funds to this account and try again.'
                      );
                      return;
                  }

                  financeDepositClearBalanceError();
                  askForConfirmation();
              },
              error: function() {
                  // The server performs the authoritative S735 balance check.
                  askForConfirmation();
              }
          });

          return false;
      });
  });

    function calculate_deposit_totals(){
        var amount = financeDepositAmountTotal();
        var account_balance = parseFloat($('#deposit_form #account_balance').val()) || 0;
        var sourceAccountId = $('#deposit_form [name="account_id"]').val();

        if (financeDepositBalanceRestricted() && sourceAccountId && amount > account_balance + 0.000001) {
          $('#deposit_form .finance-insufficient-balance-alert')
              .text('Insufficient Balance: the selected account does not have enough balance for this transaction.')
              .show();
        } else {
          financeDepositClearBalanceError();
        }

        // Do not permanently disable the Save button based on a possibly stale
        // browser balance; the submit guard refreshes it and the server is final.
        $('#deposit_form .submit_btn').prop('disabled', false);
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
        $('#check_insufficient').val(result.check_insufficient_balance ? 1 : 0);
        calculate_deposit_totals();
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

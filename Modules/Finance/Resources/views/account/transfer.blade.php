

{{-- IS2254 (12 Sep 2026): Toolbar Transfer opens without a row account.
     Keep picker mode active when $from_account is empty; row Action -> Transfer
     continues to use the fixed-account mode below. --}}
<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => route('finance.account.fund-transfer.store'), 'method' => 'post', 'id' =>
    'fund_transfer_form', 'enctype' => 'multipart/form-data' ]) !!}
    {!! Form::hidden('finance_confirmation', 0, ['id' => 'finance_confirmation']) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">@lang( 'account.fund_transfer' )</h4>
    </div>

    <div class="modal-body">
      {{-- S-627 #4: opened from a row, the account is fixed and simply
           reported. Opened from the toolbar Transfer button there is no row,
           so it is chosen here - the same shape as the Card Deposit form. --}}
      @if(!empty($from_account))
      <div class="form-group">
        <strong>@lang('account.selected_account')</strong>:
        <span class="selected_account">{{$from_account->name}}</span>
        <span class="text-red pull-right account_balance"> @lang('account.balance'): @if(!empty($account_balance->balance))
          {{@num_format($account_balance->balance)}} @else {{0.00}} @endif </span>
        {!! Form::hidden('from_account', $from_account->id) !!}
        {!! Form::hidden('check_insufficient', $check_insufficient, ['id' => 'check_insufficient']) !!}
        {!! Form::hidden('account_balance', !empty($account_balance->balance) ? $account_balance->balance : 0, ['id' => 'account_balance']) !!}
      </div>
      @else
      <div class="form-group">
        {{-- S673: __('account.transfer_from') resolves to nothing in this module, so
             the raw key "account.transfer_from:*" was shown as the field label. --}}
        {!! Form::label('from_account', 'Transfer From:*') !!}
        <span class="text-red pull-right account_balance"></span>
        {!! Form::select('from_account', $from_accounts, null, [
          'class'       => 'form-control select2',
          'id'          => 'from_account',
          'required',
          'style'       => 'width: 100%;',
          'placeholder' => __('messages.please_select'),
        ]); !!}
        {!! Form::hidden('check_insufficient', 0, ['id' => 'check_insufficient']) !!}
        {!! Form::hidden('account_balance', 0, ['id' => 'account_balance']) !!}
      </div>
      @endif

      <div class="alert alert-danger finance-insufficient-balance-alert" style="display:none; font-weight:700; margin-top:10px;"></div>

      <!-- change account group to transfer to account group by virtual it professional referance docs number 7338 -->
      <div class="form-group">
        {!! Form::label('account_group_id', __( 'account.transfer_account_group' ) .":*") !!}
        {!! Form::select('account_group_id', $account_groups, null, ['class' => 'form-control select2', 'placeholder' => __('lang_v1.please_select') ]); !!}
      </div>
      
      <!-- replace transfer to field to receive cheque by virtual it professional referance docs number 7338 -->
      <div class="form-group">
        {!! Form::label('to_account', __( 'account.receive_cheque' ) .":*", ['class' => 'to-account-label']) !!}
        {!! Form::select('to_account', $to_accounts, null, ['class' => 'form-control select2', 'required', 'placeholder' => __('lang_v1.please_select') ]); !!}
      </div>

      <div class="form-group">
        {!! Form::label('amount', __( 'sale.amount' ) .":*") !!}
        {!! Form::text('amount', 0, ['class' => 'form-control input_number', 'required','placeholder' => __(
        'sale.amount' ) ]); !!}
      </div>

      <!-- added pd-field-number for hide and remove required field when transfer option select by virtual it professional referance docs number 7338 -->   
      <div class="form-group pd-field-number">
        {!! Form::label('cheque_number', __( 'lang_v1.cheque_number' ) .":*") !!}
        {!! Form::text('cheque_number', null, ['class' => 'form-control input_number cq-number', 'required','placeholder' => __(
        'lang_v1.cheque_number' ) ]); !!}
      </div>

      <div class="form-group">
        <label class="date_label">{{  __( 'lang_v1.cheque_date' ) .":*" }}</label>
        <div class="input-group date" id='od_datetimepicker'>
          {{-- S-627: this defaulted to the integer 0, so the form opened
               showing "0" in a required date box and the user had to clear it
               before the calendar would accept anything. Now it opens on the
               current date, like every other Finance form. --}}
          {!! Form::text('operation_date', @format_datetime('now'), [
            'class' => 'form-control',
            'id' => 'finance_transfer_operation_date',
            'required',
            'autocomplete' => 'off',
            'placeholder' => __( 'messages.date' ),
          ]); !!}
          <span class="input-group-addon">
            <span class="glyphicon glyphicon-calendar"></span>
          </span>
        </div>
      </div>
      
      <div class="clearfix"></div>
      
      <div class="row">
        <div class="col-md-6">
            <label>
                <input type="radio" name="transfer_or_cheque" class="transfer_or_cheque" value="cheque">
                @lang('account.cheques')
            </label>
        </div>
        <div class="col-md-6">
            <label>
                <input type="radio" name="transfer_or_cheque" class="transfer_or_cheque" value="transfer" checked>
                @lang('account.transfer')
            </label>
        </div>
      </div>
      <hr>
      
      @if(!empty($package_details['show_post_dated_cheque']))
          <div class="col-md-6 text-left pd-fields" >
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('post_dated_cheque', '1', false,
                        [ 'class' => 'input-icheck','id' => 'post_dated_cheque']); !!} {{ __( 'account.post_dated_cheque' ) }}
                    </label>
                </div>
            </div>
            
            @if(!empty($package_details['update_post_dated_cheque']))
            <div class="col-md-6 text-left pd-fields" >
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('update_post_dated_cheque', '1', false,
                        [ 'class' => 'input-icheck','id' => 'update_post_dated_cheque']); !!} {{ __( 'account.update_post_dated_cheque' ) }}
                    </label>
                </div>
            </div>
            @endif
            
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
  .swal-title{
    color: red;
  }

  /* Keep the calendar inside the modal and clickable, as on the deposit forms. */
  .account_model .modal-dialog,
  .account_model .modal-content,
  .account_model .modal-body {
    overflow: visible !important;
  }

  .account_model .bootstrap-datetimepicker-widget {
    z-index: 10660 !important;
    pointer-events: auto !important;
  }

  .account_model .bootstrap-datetimepicker-widget [data-action],
  .account_model .bootstrap-datetimepicker-widget td.day {
    pointer-events: auto !important;
    cursor: pointer !important;
  }

  #od_datetimepicker .input-group-addon {
    cursor: pointer;
  }
</style>

{{-- S-627: the Transfer form was never given the size reduction the deposit
     forms got, and the new toolbar button makes it far more visible. --}}
{{-- LA-1189: width reduced by a further 40% (72% -> 43.2%). --}}
@include('finance::account.partials.modal_compact', ['compactWidth' => '43.2%'])

{{-- S-627: same hardened date binding as the deposit forms. --}}
@include('finance::account.partials.date_picker_bind')
@include('finance::account.partials.transaction_confirm')

<script type="text/javascript">
  function financeTransferBalanceRestricted() {
      var raw = String($('#fund_transfer_form #check_insufficient').val() || '').toLowerCase();
      return raw === '1' || raw === 'true';
  }

  function financeTransferAmountValue() {
      var raw = String($('#fund_transfer_form [name="amount"]').val() || '0').replace(/,/g, '');
      return parseFloat(raw) || 0;
  }

  function financeTransferShowBalanceError(message) {
      var $form = $('#fund_transfer_form');
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

  function financeTransferClearBalanceError() {
      $('#fund_transfer_form .finance-insufficient-balance-alert').hide().text('');
  }

  $(document).ready( function(){
      var $transferForm = $('#fund_transfer_form');
      var $modal = $transferForm.closest('.modal');

      // LA-1176 #2: initialise, never re-initialise - see deposit.blade.php.
      $transferForm.find('.select2').not('.select2-hidden-accessible').each(function() {
        $(this).select2({
          width: '100%',
          dropdownParent: $modal.length ? $modal : $transferForm
        });
      });

      window.financeBindDatePicker({
        picker: $transferForm.find('#od_datetimepicker'),
        input: $transferForm.find('input[name="operation_date"]'),
        format: moment_date_format + ' ' + moment_time_format,
        {{-- LA-1176 #1: no widgetParent - see partials/date_picker_bind.blade.php. --}}
        hideOnPick: true
      });
  });

  @if(empty($from_account))
  /*
   * S-627 #4: picker mode only - show the balance of whichever account is
   * chosen, and stop it being picked as the destination too.
   *
   * postFundTransfer already rejects a transfer where from and to match, but
   * disabling the option means the user finds out before they fill the rest
   * of the form in rather than after they submit it.
   */
  $(document).off('change.financeTransferFrom', '#fund_transfer_form #from_account')
    .on('change.financeTransferFrom', '#fund_transfer_form #from_account', function () {
      var accountId = $(this).val();
      var $form = $('#fund_transfer_form');
      var groupId = $form.find('#account_group_id').val();

      // S723 #1: changing Transfer From must rebuild Transfer To from the
      // server so the selected source account is not merely disabled/greyed
      // out - it is absent from the destination list altogether.
      if (groupId) {
        $form.find('#account_group_id').trigger('change.financeTransferGroup');
      } else {
        $form.find('#to_account').val('').trigger('change.select2');
      }

      if (!accountId) {
        $form.find('.account_balance').text('');
        $form.find('#account_balance').val(0);
        $form.find('#check_insufficient').val(0);
        return;
      }

      $.ajax({
        method: 'get',
        url: '/finance/get-account-balance/' + encodeURIComponent(accountId),
        cache: false,
        success: function (result) {
          $form.find('.account_balance')
            .text("@lang('account.balance')" + ': ' + __number_f(result.balance, false));
          $form.find('#account_balance').val(result.balance);
          $form.find('#check_insufficient').val(result.check_insufficient_balance ? 1 : 0);
          financeTransferValidatePreview();
        },
        error: function () {
          // A balance we cannot read must not block the transfer; the server
          // re-checks it on submit anyway.
          $form.find('.account_balance').text('');
        }
      });
    });
  @endif

  //added add/remove functionality for transfer and cheque option by virtual it professional referance docs number 7338
  /*
   * S675: Save did nothing on the Transfer form.
   *
   * cheque_number is marked `required` in the markup and sits inside
   * .pd-field-number, which this handler HIDES when "transfer" is chosen. The
   * handler removed `required` at the same time - but it only runs on CHANGE.
   *
   * So the mode was never applied on load. IS2067 now defaults this Finance
   * screen to a real Account Transfer; Cheque remains an explicit option. If
   * a hidden cheque field is still required, the
   * required-but-empty cheque_number blocks submission. When the field is
   * hidden, the browser cannot even focus it to complain: Chrome logs "An
   * invalid form control with name='cheque_number' is not focusable" to the
   * console and silently refuses to submit. From the user's side, clicking Save
   * simply does nothing - exactly as reported.
   *
   * Two changes: the rule is applied on LOAD as well as on change, and hiding a
   * field now always clears its required flag so a hidden control can never
   * block the form.
   */
  function applyTransferMode() {
      var $form = $('#fund_transfer_form');
      var mode = $form.find('.transfer_or_cheque:checked').val() || 'transfer';

      if (mode === 'transfer') {
          $form.find('.pd-fields').hide();
          $form.find('.pd-field-number').hide();
          $form.find('.cq-number').removeAttr('required').prop('required', false).val('');
          $form.find('.date_label').text("{{__( 'account.transaction_date' )}}:*" );
          $form.find('.to-account-label').text('Transfer To:*');
      } else {
          $form.find('.pd-fields').show();
          $form.find('.pd-field-number').show();
          $form.find('.cq-number').attr('required', true).prop('required', true);
          $form.find('.date_label').text("{{__( 'lang_v1.cheque_date' )}}:*" );
          $form.find('.to-account-label').text("{{__( 'account.receive_cheque' )}}:*" );
      }
  }

  $(document).off('change.financeTransferMode', '#fund_transfer_form .transfer_or_cheque')
      .on('change.financeTransferMode', '#fund_transfer_form .transfer_or_cheque', applyTransferMode);

  // Apply the current mode as soon as the form is on screen.
  applyTransferMode();

  /*
   * Belt and braces: whatever the mode, never let a HIDDEN required control
   * silently block submission. If one is found it is cleared, and the form is
   * allowed to submit.
   */
  // Selector, not the $transferForm variable - that is scoped to the ready()
  // block further up and is not visible here.
  $(document).on('submit', '#fund_transfer_form', function () {
      $(this).find('[required]').each(function () {
          if (!$(this).is(':visible')) {
              $(this).removeAttr('required').prop('required', false);
          }
      });
  });
  
  {{--
    S-627 #4: the insufficient-balance block stays EXACTLY as it was for the
    row-level Transfer, and is off in picker mode.

    In picker mode $group_name is null - there is no account yet - so the old
    condition `null != 'Bank Account'` would have been true and the check would
    have run against the hidden account_balance field's initial 0, disabling
    Submit for every amount above zero. Rather than guess a group client-side,
    picker mode shows the balance and lets postFundTransfer do the enforcing,
    which is the same call the Cash/Card deposit form makes.
  --}}
  function financeTransferValidatePreview() {
      var $form = $('#fund_transfer_form');
      var amount = financeTransferAmountValue();
      var available = parseFloat($form.find('#account_balance').val()) || 0;
      var sourceAccountId = $form.find('[name="from_account"]').val();

      if (financeTransferBalanceRestricted() && sourceAccountId && amount > available + 0.000001) {
          $form.find('.finance-insufficient-balance-alert')
              .text('Insufficient Balance: the selected Transfer From account does not have enough balance for this transaction.')
              .show();
      } else {
          financeTransferClearBalanceError();
      }

      // The authoritative check runs immediately before submit on the server.
      $form.find('.submit_btn').prop('disabled', false);
  }

  $(document).off('change.financeS735TransferAmount keyup.financeS735TransferAmount', '#fund_transfer_form [name="amount"]')
      .on('change.financeS735TransferAmount keyup.financeS735TransferAmount', '#fund_transfer_form [name="amount"]', financeTransferValidatePreview);
  // S735: mandatory reconfirmation for both the toolbar Transfer and
  // Account -> Action -> Transfer paths.
  $(document).off('submit.financeS735Confirm', '#fund_transfer_form')
      .on('submit.financeS735Confirm', '#fund_transfer_form', function(e) {
          var $form = $(this);

          if ($form.data('finance-s735-confirmed')) {
              $form.removeData('finance-s735-confirmed');
              $form.find('[name="finance_confirmation"]').val('1');
              financeTransferClearBalanceError();
              return true;
          }

          e.preventDefault();
          e.stopImmediatePropagation();

          var amount = financeTransferAmountValue();
          var sourceAccountId = $form.find('[name="from_account"]').val();
          var sourceName = $.trim(
              $form.find('#from_account option:selected').text()
              || $form.find('.selected_account').first().text()
          ) || 'Selected account';
          var destinationName = $.trim($form.find('#to_account option:selected').text()) || 'Selected account';
          var operationDate = $form.find('[name="operation_date"]').val();
          var mode = $form.find('.transfer_or_cheque:checked').val() || 'transfer';

          function askForConfirmation() {
              var confirmation = (typeof window.financeConfirmTransaction === 'function')
                  ? window.financeConfirmTransaction({
                      title: 'Confirm Transaction',
                      rows: [
                          {label: 'Transaction', value: mode === 'cheque' ? 'Cheque Transfer' : 'Transfer'},
                          {label: 'Transfer From', value: sourceName},
                          {label: 'Transfer To', value: destinationName},
                          {label: 'Amount', value: window.financeConfirmAmount ? window.financeConfirmAmount(amount) : amount, highlight: true},
                          {label: 'Date & Time', value: operationDate}
                      ],
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

          if (!financeTransferBalanceRestricted() || !sourceAccountId || amount <= 0) {
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
                  $form.find('#check_insufficient').val(result.check_insufficient_balance ? 1 : 0);

                  if (!financeTransferBalanceRestricted()) {
                      financeTransferClearBalanceError();
                      askForConfirmation();
                      return;
                  }

                  if (amount > available + 0.000001) {
                      financeTransferShowBalanceError(
                          'Insufficient Balance: ' + sourceName + ' has only ' +
                          (window.financeConfirmAmount ? window.financeConfirmAmount(available) : available.toFixed(2)) +
                          ' available, but ' +
                          (window.financeConfirmAmount ? window.financeConfirmAmount(amount) : amount.toFixed(2)) +
                          ' is required. Please reduce the amount or add funds to this account and try again.'
                      );
                      return;
                  }

                  financeTransferClearBalanceError();
                  askForConfirmation();
              },
              error: function() {
                  // The server-side S735 guard remains authoritative.
                  askForConfirmation();
              }
          });

          return false;
      });

  $(document).off('change.financeTransferGroup', '#fund_transfer_form #account_group_id')
    .on('change.financeTransferGroup', '#fund_transfer_form #account_group_id', function () {
      var $form = $('#fund_transfer_form');
      var groupId = $(this).val();
      var fromAccountId = $form.find('[name="from_account"]').val() || '';
      var $toAccount = $form.find('#to_account');

      if (!groupId) {
        $toAccount.empty().append('<option value="">Please Select</option>').trigger('change.select2');
        return;
      }

      $.ajax({
        method: 'get',
        url: '/finance/get-account-by-group-id/' + encodeURIComponent(groupId),
        data: { exclude_account_id: fromAccountId },
        dataType: 'html',
        cache: false,
        success: function(result) {
          $toAccount.empty().append(result);

          // Client-side belt-and-braces protection.  Even if an older cached
          // server response is returned, Transfer From can never remain in the
          // destination dropdown.
          if (fromAccountId) {
            $toAccount.find('option[value="' + fromAccountId + '"]').remove();
          }
          $toAccount.val('').trigger('change.select2');
        },
        error: function() {
          $toAccount.empty().append('<option value="">Please Select</option>').trigger('change.select2');
          toastr.error('Could not load accounts for the selected group.');
        }
      });
    });

  // Apply S723 exclusion if the modal opens with an already selected group
  // (row-level Action -> Transfer as well as toolbar Transfer).
  if ($('#fund_transfer_form #account_group_id').val()) {
    $('#fund_transfer_form #account_group_id').trigger('change.financeTransferGroup');
  }
</script>
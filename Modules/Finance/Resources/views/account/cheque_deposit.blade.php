<div class="modal-dialog" role="document" style="width: 60%">
    <div class="modal-content">
  
      {!! Form::open(['url' => route('finance.list-accounts.live.cheque-deposit.store'), 'method' => 'post', 'id' => 'deposit_form',
      'enctype' => 'multipart/form-data' ]) !!}
      {!! Form::hidden('finance_confirmation', 0, ['id' => 'finance_confirmation']) !!}
  
      <div class="modal-header text-center">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">@lang( 'account.cheque_deposit' )</h4>
      </div>
  
      <div class="modal-body">
          <div class="alert alert-danger finance-insufficient-balance-alert" style="display:none; font-weight:700;"></div>
          <div class="col-md-4">
            <div class="form-group" style="margin-top: 28px;">
                <strong>@lang('account.selected_account')</strong>:
                {{$account->name}}
                {!! Form::hidden('account_id', $account->id) !!}
              </div>
          </div>
        <!-- TKNeal Removed Balance field from this positoin -->
          <div class="col-md-4">
            <div class="form-group">
                {!! Form::label('operation_date', __( 'account.transaction_date' ) .":*") !!}
                {!! Form::text('operation_date', null, ['class' => 'form-control pull-right transaction_date', 'id' => 'transaction_date', 'required','placeholder' => __(
                'account.transaction_date' ) ]); !!}
            </div>
          </div>
          <div class="clearfix"></div>
          <div class="row">
              <div class="col-sm-6">
                <div class="form-group">
                  {!! Form::label('transaction_date_range_cheque_deposit', __('report.date_range') . ':') !!}
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                    {!! Form::text('transaction_date_range_cheque_deposit', null, ['class' => 'form-control', 'readonly', 'placeholder' => __('report.date_range')]) !!}
                  </div>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  {!! Form::label('transaction_date_range_cheque_deposit_created', __('report.created_on') . ':') !!}
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                    {!! Form::text('transaction_date_range_cheque_deposit_created', null, ['class' => 'form-control', 'readonly', 'placeholder' => __('report.created_on')]) !!}
                  </div>
                </div>
              </div>
              
              <div class="col-sm-4">
                    <div class="form-group">
                      {!! Form::label('customer_cheque_no', __('lang_v1.customer_cheque_number').':') !!}
                      <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-exchange"></i></span><!-- @eng START 13/2 -->
                        {!! Form::select('customer_cheque_no', [], null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.all'), 'id' => "cheque_customer_cheque_no"]) !!}
                      </div><!-- @eng END 13/2 -->
                </div>
            </div>
            
            <div class="col-sm-4">
                <div class="form-group">
                  {!! Form::label('customer_amount', __('lang_v1.amount').':') !!}
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-exchange"></i></span><!-- @eng START 13/2 -->
                    {!! Form::select('customer_amount', [], null, ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('lang_v1.all'), 'id' => "cheque_customer_amount"]) !!}
                  </div> <!-- @eng END 13/2 -->
                </div>
            </div>
              
          </div>
          
          
            
         
          <div class="clearfix"></div>
          <table class="table table-bordered table-striped" id="cheque_list_table">
            <thead>
             <tr>
             <th>@lang('account.select')</th>
             <th>@lang('lang_v1.name')</th>
             <th>@lang('account.cheque_no')</th>
             <th>@lang('account.cheque_date')</th>
             <th>@lang('account.bank')</th>
             <th>@lang('account.amount')</th>
            </tr>
          </thead>
          <tbody></tbody>
          </table>

          <div class="clearfix"></div>
        
        <div class="col-md-12 text-center">
            <div class="checkbox">
                <label>
                    {!! Form::checkbox('encash', '1', false,
                    [ 'class' => 'input-icheck','id' => 'encash']); !!} {{ __( 'account.encash' ) }}
                </label>
            </div>
        </div>
     
        <div class="form-group">
          {!! Form::label('from_account', __( 'account.deposit_to' ) .":") !!}
          {!! Form::select('from_account', $to_accounts, null, ['class' => 'form-control select2', 'required', 'placeholder' =>
          __('messages.please_select'), 'required' ]); !!}
        </div>
        @if(!empty($mpcs_module))
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
  
  @include('finance::account.partials.transaction_confirm')

  <script type="text/javascript">
    $(document).ready(function () {
        var $form = $('#deposit_form');
        var $modal = $form.closest('.account_model');
        var $table = $form.find('#cheque_list_table');
        var $dateRange = $('#transaction_date_range_cheque_deposit');
        var $createdRange = $('#transaction_date_range_cheque_deposit_created');
        var rowsRequest = null;
        var filtersRequest = null;
        var requestSequence = 0;
        var displayDateFormat = (typeof window.moment_date_format === 'string' && window.moment_date_format)
            ? window.moment_date_format
            : 'DD/MM/YYYY';
        var displayTimeFormat = (typeof window.moment_time_format === 'string' && window.moment_time_format)
            ? window.moment_time_format
            : 'hh:mm A';

        function rangeValue($input) {
            var picker = $input.data('daterangepicker');
            if (!$input.length || !$.trim($input.val()) || !picker) {
                return {start: '', end: ''};
            }

            return {
                start: picker.startDate.format('YYYY-MM-DD'),
                end: picker.endDate.format('YYYY-MM-DD')
            };
        }

        function currentFilters() {
            var chequeRange = rangeValue($dateRange);
            var createdOnRange = rangeValue($createdRange);

            return {
                payment_type: 'cheque',
                start_date: chequeRange.start,
                end_date: chequeRange.end,
                start_date_created: createdOnRange.start,
                end_date_created: createdOnRange.end,
                cheque_no: $form.find('#cheque_customer_cheque_no').val() || '',
                amount: $form.find('#cheque_customer_amount').val() || ''
            };
        }

        function replaceSelectOptions($select, values, currentValue) {
            $select.empty().append($('<option/>', {
                value: '',
                text: '@lang("lang_v1.all")'
            }));

            $.each(values || [], function (_, value) {
                var text = $.trim(String(value == null ? '' : value));
                if (text !== '') {
                    $select.append($('<option/>', {value: text, text: text}));
                }
            });

            if (currentValue && $select.find('option').filter(function () {
                return String($(this).val()) === String(currentValue);
            }).length) {
                $select.val(currentValue);
            } else {
                $select.val('');
            }

            $select.trigger('change.select2');
        }

        function loadFilterOptions(filters) {
            if (filtersRequest && filtersRequest.readyState !== 4) {
                filtersRequest.abort();
            }

            var $chequeSelect = $form.find('#cheque_customer_cheque_no');
            var $amountSelect = $form.find('#cheque_customer_amount');
            var selectedCheque = $chequeSelect.val() || '';
            var selectedAmount = $amountSelect.val() || '';
            var optionFilters = $.extend({}, filters, {
                field: '',
                cheque_no: '',
                amount: ''
            });

            filtersRequest = $.ajax({
                method: 'GET',
                url: '{{ route('finance.list-accounts.live.cheque-deposit.filter-options') }}',
                data: optionFilters,
                dataType: 'json',
                cache: false,
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
            }).done(function (result) {
                var data = result && result.data ? result.data : {};
                replaceSelectOptions($chequeSelect, data.cheque_numbers || [], selectedCheque);
                replaceSelectOptions($amountSelect, data.amounts || [], selectedAmount);
            });
        }

        function initialiseChequeCheckboxes() {
            if (!$.fn.iCheck) return;

            $table.find('.input-icheck').each(function () {
                var $checkbox = $(this);
                if (!$checkbox.parent().hasClass('icheckbox_square-blue')) {
                    $checkbox.iCheck({
                        checkboxClass: 'icheckbox_square-blue',
                        radioClass: 'iradio_square-blue',
                        increaseArea: '20%'
                    });
                }
            });
        }

        function loadChequeRows(rebuildFilters) {
            var filters = currentFilters();
            var sequence = ++requestSequence;

            if (rowsRequest && rowsRequest.readyState !== 4) {
                rowsRequest.abort();
            }

            $table.find('tbody').html(
                '<tr><td colspan="6" class="text-center">' +
                    '<i class="fa fa-spinner fa-spin"></i> @lang("messages.processing")' +
                '</td></tr>'
            );

            if (rebuildFilters !== false) {
                loadFilterOptions(filters);
            }

            rowsRequest = $.ajax({
                method: 'GET',
                url: '{{ route('finance.list-accounts.live.cheque-list') }}',
                data: filters,
                dataType: 'html',
                cache: false,
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'}
            }).done(function (result) {
                if (sequence !== requestSequence) return;
                $table.find('tbody').empty().append(result);
                initialiseChequeCheckboxes();
            }).fail(function (xhr, status) {
                if (status === 'abort' || sequence !== requestSequence) return;

                var message = (xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.message))
                    ? (xhr.responseJSON.msg || xhr.responseJSON.message)
                    : 'Unable to load saved cheque details.';
                var $cell = $('<td/>', {
                    colspan: 6,
                    class: 'text-center text-danger',
                    text: message
                });
                $table.find('tbody').empty().append($('<tr/>').append($cell));
            });
        }

        // Cheque Deposit must be reconfirmed before posting. The server then
        // performs the authoritative per-source Cheques-in-Hand balance check.
        // Cheques in Hand is a protected group and cannot be overpaid.
        $form.off('submit.financeChequeDepositGuard').on('submit.financeChequeDepositGuard', function(e) {
            var $currentForm = $(this);
            var $submitBtn = $currentForm.find('.submit_btn');

            if ($currentForm.data('finance-s735-confirmed')) {
                $currentForm.removeData('finance-s735-confirmed');
                $currentForm.find('[name="finance_confirmation"]').val('1');
                $submitBtn.prop('disabled', true)
                    .html('<i class="fa fa-spinner fa-spin"></i> @lang("messages.processing")');
                return true;
            }

            e.preventDefault();
            e.stopImmediatePropagation();

            var selectedCount = $currentForm.find('input[name="select_cheques[]"]:checked').length;
            var totalAmount = 0;
            $currentForm.find('input[name="select_cheques[]"]:checked').each(function() {
                var $row = $(this).closest('tr');
                totalAmount += parseFloat($row.attr('data-cheque-amount')) || 0;
            });

            var destinationName = $currentForm.find('#encash').is(':checked')
                ? 'Cash'
                : $.trim($currentForm.find('#from_account option:selected').text());
            var operationDate = $currentForm.find('[name="operation_date"]').val();

            var confirmation = (typeof window.financeConfirmTransaction === 'function')
                ? window.financeConfirmTransaction({
                    title: 'Confirm Transaction',
                    rows: [
                        {label: 'Transaction', value: 'Cheque Deposit'},
                        {label: 'Selected Cheques', value: selectedCount},
                        {label: 'Deposit To', value: destinationName || 'Selected account'},
                        {label: 'Amount', value: window.financeConfirmAmount ? window.financeConfirmAmount(totalAmount) : totalAmount, highlight: true},
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

                $currentForm.data('finance-s735-confirmed', true);
                $currentForm.find('[name="finance_confirmation"]').val('1');
                $currentForm.trigger('submit');
            });

            return false;
        });

        $('.select2').select2();
        $('#transaction_date').datetimepicker({
            format: displayDateFormat + ' ' + displayTimeFormat,
            defaultDate: moment()
        });

        $('#encash').off('change.financeChequeDeposit').on('change.financeChequeDeposit', function() {
            $('#from_account').prop('disabled', $(this).is(':checked'));
        });

        // IS2258 #3: Cheque Deposit is an OUTSTANDING work list. Never hide a
        // cheque just because its cheque date / created date is outside the
        // current month. Both ranges therefore start blank; blank means all
        // outstanding cheques. The user may narrow the list explicitly.
        var globalRangeSettings = (typeof window.dateRangeSettings === 'object' && window.dateRangeSettings)
            ? window.dateRangeSettings
            : {};
        var chequeRangeSettings = $.extend(true, {}, globalRangeSettings, {
            autoUpdateInput: false,
            parentEl: $modal.length ? '.account_model' : 'body',
            locale: $.extend({}, globalRangeSettings.locale || {}, {cancelLabel: 'Clear'})
        });

        function bindOptionalRange($input) {
            if (!$.fn.daterangepicker) return;

            if ($input.data('daterangepicker')) {
                $input.data('daterangepicker').remove();
            }
            $input.daterangepicker(chequeRangeSettings);
            $input.val('');

            $input.off('apply.daterangepicker.financeChequeDeposit')
                .on('apply.daterangepicker.financeChequeDeposit', function(ev, picker) {
                    $(this).val(
                        picker.startDate.format(displayDateFormat) + ' ~ ' +
                        picker.endDate.format(displayDateFormat)
                    );
                    picker.hide();
                    loadChequeRows(true);
                });

            $input.off('cancel.daterangepicker.financeChequeDeposit')
                .on('cancel.daterangepicker.financeChequeDeposit', function() {
                    $(this).val('');
                    loadChequeRows(true);
                });
        }

        bindOptionalRange($dateRange);
        bindOptionalRange($createdRange);

        $('#cheque_customer_cheque_no, #cheque_customer_amount')
            .off('change.financeChequeDeposit')
            .on('change.financeChequeDeposit', function() {
                loadChequeRows(false);
            });

        // The AJAX-loaded form owns its request lifecycle. This avoids depending
        // on parent-page globals and prevents an older request from replacing the
        // rows for the range the user most recently selected.
        loadChequeRows(true);
    });
  </script>

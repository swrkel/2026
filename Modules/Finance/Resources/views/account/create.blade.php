<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => url('/finance/account'), 'method' => 'post', 'id' => 'payment_account_form'
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'account.add_account' )</h4>
        </div>

        <div class="modal-body">
             <div class="form-group">
                {!! Form::label('location_id', __( 'lang_v1.location' ) .":") !!}
                {!! Form::select('location_id', $business_locations, null, ['style' => 'width: 100%', 'class' => 'form-control select2']) !!}
            </div>
            
            <div class="form-group">
                {!! Form::label('name', __( 'lang_v1.name' ) .":*") !!}
                {!! Form::text('name', null, ['class' => 'form-control', 'required','placeholder' => __( 'lang_v1.name'
                ) ]); !!}
            </div>

            <div class="form-group">
                {!! Form::label('account_type_id', __( 'account.account_type' ) .":") !!}
                <select name="account_type_id" class="form-control select2" id="account_type_id" required>
                    <option value="">@lang('messages.please_select')</option>
                    @foreach($account_types as $account_type)
                    <optgroup label="{{$account_type->name}}">
                        <option value="{{$account_type->id}}">{{$account_type->name}}</option>
                        @foreach($account_type->sub_types as $sub_type)
                        <option value="{{$sub_type->id}}">{{$sub_type->name}}</option>
                        @endforeach
                    </optgroup>
                    @endforeach
                </select>
            </div>
            
             <div class="form-group is_property hide">
                {!! Form::label('is_property', __( 'lang_v1.is_property' ) .":") !!}
                {!! Form::select('is_property', ['1'=>'Yes','0'=>'No'], 0, ['placeholder' => 
                __('messages.please_select'), 'style' => 'width: 100%', 'class' => 'form-control select2', 'data-minimum-results-for-search'=>'3' ]) !!}
            </div>

            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('is_main_account', 1, false, ['class' => 'input-icheck check_main_or_sub', 'id' => 'is_main_account']); !!}
                        {{__('lang_v1.main_account_dsc')}}
                    </label>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('sub_type', 1, false, ['class' => 'input-icheck check_main_or_sub', 'id' => 'sub_type']); !!}
                        {{__('lang_v1.sub_type')}}
                    </label>
                </div>
            </div>
            <div class="form-group show_in_balance_sheet">
                {!! Form::label('show_in_balance_sheet', __( 'lang_v1.show_in_bal_sheet' ) .":") !!}
                {!! Form::select('show_in_balance_sheet', ['1'=>'Yes','0'=>'No'], 1, ['placeholder' => 
                __('messages.please_select'), 'style' => 'width: 100%', 'class' => 'form-control select2','required', 'data-minimum-results-for-search'=>'3' ]) !!}
            </div>
            <div class="form-group parent_account hide">
                {!! Form::label('parent_account_id', __( 'lang_v1.parent_account' ) .":") !!}
                {!! Form::select('parent_account_id', $parentAccounts, null, ['placeholder' =>
                __('messages.please_select'), 'style' => 'width: 100%', 'class' => 'form-control select2', 'data-minimum-results-for-search'=>'1' ]) !!}
            </div>
  

            <div class="form-group asset_type">
                {!! Form::label('asset_type', 'Account Group:') !!}
                <select name="asset_type" id="asset_type" class="form-control select2" style="width: 100%;">
                    <option value="">Select Account Type first</option>
                </select>
            </div>

            <div class="form-group business_bank_account hide">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('is_business_bank_account', 1, false, ['class' => 'input-icheck']); !!}
                        {{ __( 'account.business_bank_account' ) }}
                    </label>
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('account_number', __( 'account.account_number' ) .":*") !!}
                {!! Form::text('account_number', null, ['class' => 'form-control', 'required', 'maxlength' => 191, 'autocomplete' => 'off', 'placeholder' => __(
                'account.account_number' ) ]); !!}
                <small class="text-muted">Auto-loaded from Super Admin → Default Accounts → Account Numbers. You may edit it before saving.</small>
            </div>
            <div id="part_need_cheque" class="form-group form-md-radios" style="display: none">
                {!! Form::label('show_cheque', 'Show in Cheque Writing Module:') !!}
                <div class="md-radio-list">
                  <div class="md-radio">
                    <input type="radio" id="radio1" name="is_need_cheque" value="Y"/>
                    <label for="radio1">
                    <span></span>
                    <span class="check"></span>
                    <span class="box"></span>
                    Yes </label>
                  </div>
                  <div class="md-radio">
                    <input type="radio" id="radio2" name="is_need_cheque" checked value="N"/>
                    <label for="radio2">
                    <span></span>
                    <span class="check"></span>
                    <span class="box"></span>
                    No </label>
                  </div>
                </div>
              </div>
            <div class="form-group opening_balance_div hide">
                {!! Form::label('opening_balance', __( 'account.opening_balance' ) .":") !!}
                {!! Form::text('opening_balance', 0, ['class' => 'form-control input_number','placeholder' => __(
                'account.opening_balance' ) ]); !!}
            </div>


            <div class="form-group">
                {!! Form::label('note', __( 'brand.note' )) !!}
                {!! Form::textarea('note', null, ['class' => 'form-control', 'placeholder' => __( 'brand.note' ), 'rows'
                => 4]); !!}
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" id="finance_account_save_btn" class="btn btn-primary finance-account-submit" data-saving-text="Saving...">@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script>
    function showHideCheque(){
        var selected = $('#asset_type option:selected');
        var groupName = $.trim(selected.text()).toLowerCase();
        var chequeEnabled = selected.data('show-cheque') == 'Y' || groupName == 'bank account';
        if (chequeEnabled) {
            $('#part_need_cheque').show();
        } else {
            $('#part_need_cheque').hide();
            $('input[name="is_need_cheque"][value="N"]').prop('checked', true);
        }
    }

    function showHideBusinessBank(){
        if ($.trim($('#asset_type option:selected').text()) == 'Bank Account'){
            $('.business_bank_account').removeClass('hide');
        }else{
            $('.business_bank_account').addClass('hide');
            $('input[name="is_business_bank_account"]').prop('checked', false);
        }
    }

    /*
     * 19 Sep 2026 - Add Account one-click save support.
     *
     * Selecting Account Type starts three Finance lookups (Account Group,
     * Account Number and Parent Accounts).  Previously a fast click on Save
     * could arrive while one of those requests was still running.  Client
     * validation then saw an empty auto-filled field and silently rejected the
     * first click; a second click worked after the lookup had completed.
     *
     * Return every jqXHR and keep one combined promise on the Add Account form.
     * The List Accounts submit owner can now accept the FIRST click immediately,
     * show Saving..., wait only for an in-flight lookup, then continue the same
     * save automatically.  The user never has to click Save a second time.
     */
    /*
     * IS2292 - Account Groups are delivered with the freshly-loaded Add modal.
     *
     * Do not make a second AJAX request after Account Type changes. That older
     * dependency could be intercepted by a stale/legacy Finance route and left
     * the dropdown showing "Unable to load Account Groups". Local switching is
     * immediate and keeps the Add form fully inside the Finance module.
     */
    var financeAccountGroupsByType = @json($account_groups_by_type ?? []);

    function reloadAccountGroups(account_type_id, selected_group_id) {
        var $select = $('#asset_type');
        $select.empty();

        if (!account_type_id) {
            $select.append($('<option>', {
                value: '',
                text: 'Select Account Type first'
            })).trigger('change');
            return $.Deferred().resolve().promise();
        }

        var rows = financeAccountGroupsByType[String(account_type_id)] || financeAccountGroupsByType[account_type_id] || [];

        if (!rows.length) {
            $select.append($('<option>', {
                value: '',
                text: 'No Account Groups for selected Account Type — optional'
            }));
        } else {
            $select.append($('<option>', {
                value: '',
                text: @json(__('messages.please_select'))
            }));

            $.each(rows, function(_, row) {
                var $option = $('<option>', {
                    value: row.id,
                    text: row.name
                }).attr('data-show-cheque', row.show_cheque || 'N');

                $select.append($option);
            });
        }

        if (selected_group_id) {
            $select.val(String(selected_group_id));
        }

        $select.trigger('change');
        return $.Deferred().resolve().promise();
    }

    function reloadAccountNumber(account_type_id) {
        if (!account_type_id) {
            $('#account_number').prop('readonly', false).val('');
            return $.Deferred().resolve().promise();
        }
        return $.ajax({
            method: 'get',
            url: '/finance/account-number/' + account_type_id,
            success: function(result) {
                $('#account_number').prop('readonly', false);
                $('#account_number').val(result.account_no || '');
            }
        });
    }

    function reloadParentAccounts(account_type_id) {
        if (!account_type_id) {
            $('#parent_account_id').html('<option value="">Please Select</option>').trigger('change');
            return $.Deferred().resolve().promise();
        }
        return $.ajax({
            method: 'get',
            url: '/finance/get-parent-account-by-type/' + account_type_id,
            data: { location_id: $('#location_id').val() },
            success: function(result) {
                $('#parent_account_id').html(result).trigger('change.select2');
            }
        });
    }

    function financeAccountLookupSettled(request) {
        var settled = $.Deferred();
        $.when(request).always(function(){
            settled.resolve();
        });
        return settled.promise();
    }

    $(document).ready(function(){
        $('.select2').select2();
        showHideCheque();
        showHideBusinessBank();

        $('#account_type_id').on('change', function(){
            var account_type_id = $(this).val();
            if(account_type_id == "{{$fixed_acc_id}}"){
                $('.is_property').removeClass('hide');
                $('#is_property').prop('required', true);
            }else{
                $('.is_property').addClass('hide');
                $('#is_property').prop('required', false).val('0').trigger('change');
            }
            var $form = $('#payment_account_form');
            var groupRequest = reloadAccountGroups(account_type_id, null);
            var numberRequest = reloadAccountNumber(account_type_id);
            var parentRequest = reloadParentAccounts(account_type_id);
            var lookupPromise = $.when(
                financeAccountLookupSettled(groupRequest),
                financeAccountLookupSettled(numberRequest),
                financeAccountLookupSettled(parentRequest)
            );

            $form
                .data('finance-account-lookups-pending', true)
                .data('finance-account-lookup-promise', lookupPromise);

            lookupPromise.always(function(){
                // Only clear the pending flag for the most recent lookup set.
                if ($form.data('finance-account-lookup-promise') === lookupPromise) {
                    $form.data('finance-account-lookups-pending', false);
                }
            });
        });

        $('#asset_type').on('change', function(){
            showHideCheque();
            showHideBusinessBank();
        });
    });

    // Finance Add Account owns these two iCheck controls.  Native .change()
    // alone is not reliable because iCheck updates the visible wrapper first.
    // Keep them mutually exclusive and update the dependent fields immediately.
    var financeMainSubSyncing = false;
    var $financeMainAccount = $('#is_main_account');
    var $financeSubAccount = $('#sub_type');

    function financeSetAccountCheck($input, checked) {
        if (!$input.length) return;
        if ($.fn.iCheck && $input.parent().hasClass('icheckbox_square-blue')) {
            $input.iCheck(checked ? 'check' : 'uncheck');
        } else {
            $input.prop('checked', checked);
        }
    }

    function financeRefreshMainSubState(source) {
        if (financeMainSubSyncing) return;
        financeMainSubSyncing = true;

        var mainChecked = $financeMainAccount.prop('checked');
        var subChecked = $financeSubAccount.prop('checked');

        if (source === 'main' && mainChecked && subChecked) {
            financeSetAccountCheck($financeSubAccount, false);
            subChecked = false;
        } else if (source === 'sub' && subChecked && mainChecked) {
            financeSetAccountCheck($financeMainAccount, false);
            mainChecked = false;
        }

        if (subChecked) {
            $('.opening_balance_div').removeClass('hide');
            $('.parent_account').removeClass('hide');
            $('.show_in_balance_sheet').addClass('hide');
        } else if (mainChecked) {
            $('.opening_balance_div').addClass('hide');
            $('.parent_account').addClass('hide');
            $('.show_in_balance_sheet').removeClass('hide');
            $('#parent_account_id').val('').trigger('change');
        } else {
            $('.opening_balance_div').addClass('hide');
            $('.parent_account').addClass('hide');
            $('.show_in_balance_sheet').removeClass('hide');
            $('#parent_account_id').val('').trigger('change');
        }

        financeMainSubSyncing = false;
    }

    $financeMainAccount.off('.financeMainSub')
        .on('ifChecked.financeMainSub ifUnchecked.financeMainSub change.financeMainSub', function(){
            financeRefreshMainSubState('main');
        });
    $financeSubAccount.off('.financeMainSub')
        .on('ifChecked.financeMainSub ifUnchecked.financeMainSub change.financeMainSub', function(){
            financeRefreshMainSubState('sub');
        });
    financeRefreshMainSubState('init');

    $('#parent_account_id').change(function(){
        var parent_id = $(this).val();
        var parentAccounts = @json($parentAccountsData);
        const thisParentAccount = parentAccounts.find(obj => obj.id == parent_id);
        if (thisParentAccount) {
          $('#asset_type').val(thisParentAccount.asset_type).trigger('change');
        }
    });

    $('#payment_account_form').validate();

</script>
<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open(['url' => action('AccountController@store'), 'method' => 'post', 'id' => 'payment_account_form'
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
                {!! Form::label('asset_type', __( 'account.account_group' ) .":") !!}
                <select name="asset_type" id="asset_type" class="form-control select2" style="width: 100%;">
                    <option value="">@lang('messages.please_select')</option>
                    @foreach($account_groups as $account_group)
                        <option value="{{ $account_group->id }}" data-show-cheque="{{ $account_group->reg_cheque }}">{{ $account_group->name }}</option>
                    @endforeach
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
                {!! Form::text('account_number', null, ['class' => 'form-control', 'required','placeholder' => __(
                'account.account_number' ) ]); !!}
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
            <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
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

    function reloadAccountGroups(account_type_id, selected_group_id) {
        if (!account_type_id) {
            $('#asset_type').html('<option value="">Please Select</option>').trigger('change');
            return;
        }
        $.ajax({
            method: 'get',
            url: '/get-account-groups/' + account_type_id,
            success: function(result) {
                $('#asset_type').html(result);
                if (selected_group_id) {
                    $('#asset_type').val(selected_group_id);
                }
                $('#asset_type').trigger('change');
            }
        });
    }

    function reloadAccountNumber(account_type_id) {
        if (!account_type_id) {
            $('#account_number').prop('readonly', false).val('');
            return;
        }
        $.ajax({
            method: 'get',
            url: '/accounting-module/account-number/' + account_type_id,
            success: function(result) {
                $('#account_number').prop('readonly', result.disable == 1);
                $('#account_number').val(result.account_no || '');
            }
        });
    }

    function reloadParentAccounts(account_type_id) {
        if (!account_type_id) {
            $('#parent_account_id').html('<option value="">Please Select</option>').trigger('change');
            return;
        }
        $.ajax({
            method: 'get',
            url: '/accounting-module/get-parent-account-by-type/' + account_type_id,
            data: { location_id: $('#location_id').val() },
            success: function(result) {
                $('#parent_account_id').html(result).trigger('change.select2');
            }
        });
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
            reloadAccountGroups(account_type_id, null);
            reloadAccountNumber(account_type_id);
            reloadParentAccounts(account_type_id);
        });

        $('#asset_type').on('change', function(){
            showHideCheque();
            showHideBusinessBank();
        });
    });

    $('#is_main_account').change(function(){
        if($(this).prop('checked') == true){
            $('.opening_balance_div').addClass('hide');
            $('.parent_account').addClass('hide');
            $('.show_in_balance_sheet').removeClass('hide');
        }else{
            $('.opening_balance_div').removeClass('hide');
            $('.parent_account').removeClass('hide');
            $('.show_in_balance_sheet').addClass('hide');
        }
    });

    $('#sub_type').change(function(){
        if($(this).prop('checked') == true){
            $('.opening_balance_div').removeClass('hide');
            $('.parent_account').removeClass('hide');
            $('.show_in_balance_sheet').addClass('hide');
        }else{
            $('.opening_balance_div').addClass('hide');
            $('.parent_account').addClass('hide');
            $('.show_in_balance_sheet').removeClass('hide');
            $('#parent_account_id').val('').trigger('change');
        }
    });

    $('#parent_account_id').change(function(){
        var parent_id = $(this).val();
        var parentAccounts = @json($parentAccountsData);
        const thisParentAccount = parentAccounts.find(obj => obj.id == parent_id);
        if (thisParentAccount) {
          $('#asset_type').val(thisParentAccount.asset_type).trigger('change');
        }
    });

    $('#payment_account_form').validate();

    $('.check_main_or_sub').change(function(){
        $('.check_main_or_sub').not(this).prop('checked', false);
    });
</script>
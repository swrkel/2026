<div class="modal-dialog" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => action('AccountController@update', [$account->id]),
            'method' => 'put',
            'id' => 'edit_payment_account_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('account.edit_account')</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('location_id', __('lang_v1.location') . ':') !!}
                {!! Form::select('location_id', $business_locations, $account->location_id, [
                    'style' => 'width: 100%',
                    'class' => 'form-control select2',
                ]) !!}
            </div>

            <div class="form-group">
                {!! Form::label('name', __('lang_v1.name') . ':*') !!}
                {!! Form::text('name', $account->name, [
                    'class' => 'form-control',
                    'required',
                    'placeholder' => __('lang_v1.name'),
                ]) !!}
            </div>

            <div class="form-group">
                {!! Form::label('account_type_id', __('account.account_type') . ':') !!}
                <select name="account_type_id" class="form-control select2" id="account_type_id" required>
                    <option value="">@lang('messages.please_select')</option>
                    @foreach ($account_types as $account_type)
                        <optgroup label="{{ $account_type->name }}">
                            <option value="{{ $account_type->id }}" {{ (int) $account->account_type_id === (int) $account_type->id ? 'selected' : '' }}>
                                {{ $account_type->name }}
                            </option>
                            @foreach ($account_type->sub_types as $sub_type)
                                <option value="{{ $sub_type->id }}" {{ (int) $account->account_type_id === (int) $sub_type->id ? 'selected' : '' }}>
                                    {{ $sub_type->name }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div class="form-group is_property hide">
                {!! Form::label('is_property', __('lang_v1.is_property') . ':') !!}
                {!! Form::select('is_property', ['1' => 'Yes', '0' => 'No'], $account->is_property ?? 0, [
                    'placeholder' => __('messages.please_select'),
                    'style' => 'width: 100%',
                    'class' => 'form-control select2',
                    'data-minimum-results-for-search' => '3',
                ]) !!}
            </div>

            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('is_main_account', 1, !empty($account->is_main_account), [
                            'class' => 'input-icheck check_main_or_sub',
                            'id' => 'is_main_account',
                        ]) !!}
                        {{ __('lang_v1.main_account_dsc') }}
                    </label>
                </div>
            </div>

            <div class="form-group">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('sub_type', 1, !empty($account->parent_account_id), [
                            'class' => 'input-icheck check_main_or_sub',
                            'id' => 'sub_type',
                        ]) !!}
                        {{ __('lang_v1.sub_type') }}
                    </label>
                </div>
            </div>

            <div class="form-group show_in_balance_sheet {{ !empty($account->is_main_account) ? '' : 'hide' }}">
                {!! Form::label('show_in_balance_sheet', __('lang_v1.show_in_bal_sheet') . ':') !!}
                {!! Form::select('show_in_balance_sheet', ['1' => 'Yes', '0' => 'No'], $account->show_in_balance_sheet, [
                    'placeholder' => __('messages.please_select'),
                    'style' => 'width: 100%',
                    'class' => 'form-control select2',
                    'required',
                    'data-minimum-results-for-search' => '3',
                ]) !!}
            </div>

            <div class="form-group parent_account {{ !empty($account->parent_account_id) ? '' : 'hide' }}">
                {!! Form::label('parent_account_id', __('lang_v1.parent_account') . ':') !!}
                {!! Form::select('parent_account_id', $parent_accounts, $account->parent_account_id, [
                    'placeholder' => __('messages.please_select'),
                    'style' => 'width: 100%',
                    'class' => 'form-control select2',
                    'data-minimum-results-for-search' => '1',
                ]) !!}
            </div>

            <div class="form-group asset_type">
                {!! Form::label('asset_type', __('account.account_group') . ':') !!}
                <select name="asset_type" id="asset_type" class="form-control select2" style="width: 100%;">
                    <option value="">@lang('messages.please_select')</option>
                    @foreach($account_groups as $account_group)
                        <option value="{{ $account_group->id }}" data-show-cheque="{{ $account_group->reg_cheque }}" {{ (int) $account->asset_type === (int) $account_group->id ? 'selected' : '' }}>{{ $account_group->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group business_bank_account {{ !empty($selected_account_group) && $selected_account_group->name == 'Bank Account' ? '' : 'hide' }}">
                <div class="checkbox">
                    <label>
                        {!! Form::checkbox('is_business_bank_account', 1, !empty($account->is_business_bank_account), ['class' => 'input-icheck']) !!}
                        {{ __('account.business_bank_account') }}
                    </label>
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('account_number', __('account.account_number') . ':*') !!}
                {!! Form::text('account_number', $account->account_number, [
                    'class' => 'form-control',
                    'required',
                    'placeholder' => __('account.account_number'),
                ]) !!}
            </div>

            <div id="part_need_cheque" class="form-group form-md-radios" style="{{ !empty($selected_account_group) && !empty($selected_account_group->show_cheque) && $selected_account_group->show_cheque == 'Y' ? '' : 'display: none' }}">
                {!! Form::label('show_cheque', 'Show in Cheque Writing Module:') !!}
                <div class="md-radio-list">
                    <div class="md-radio">
                        <input type="radio" id="radio1" name="is_need_cheque" value="Y" {{ $account->is_need_cheque === 'Y' ? 'checked' : '' }} />
                        <label for="radio1">
                            <span></span>
                            <span class="check"></span>
                            <span class="box"></span>
                            Yes
                        </label>
                    </div>
                    <div class="md-radio">
                        <input type="radio" id="radio2" name="is_need_cheque" value="N" {{ $account->is_need_cheque !== 'Y' ? 'checked' : '' }} />
                        <label for="radio2">
                            <span></span>
                            <span class="check"></span>
                            <span class="box"></span>
                            No
                        </label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('note', __('brand.note')) !!}
                {!! Form::textarea('note', $account->note, [
                    'class' => 'form-control',
                    'placeholder' => __('brand.note'),
                    'rows' => 4,
                ]) !!}
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.update')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div>
</div>

<script>
    (function () {
        function showHideCheque() {
            var selected = $('#asset_type option:selected');
            var groupName = $.trim(selected.text()).toLowerCase();
            if (selected.data('show-cheque') == 'Y' || groupName == 'bank account') {
                $('#part_need_cheque').show();
            } else {
                $('#part_need_cheque').hide();
                $('input[name="is_need_cheque"][value="N"]').prop('checked', true);
            }
        }

        function showHideBusinessBank() {
            if ($('#asset_type option:selected').text().trim() == 'Bank Account') {
                $('.business_bank_account').removeClass('hide');
            } else {
                $('.business_bank_account').addClass('hide');
            }
        }

        __select2($('.account_model .select2'));
        $('.account_model .input-icheck').iCheck({
            checkboxClass: 'icheckbox_square-blue',
            radioClass: 'iradio_square-blue',
        });

        $('#account_type_id').trigger('change');
        showHideCheque();
        showHideBusinessBank();

        $('#account_type_id, #asset_type').change(function () {
            showHideCheque();
            showHideBusinessBank();
        });

        $('#is_main_account').on('ifChanged', function (event) {
            if ($(event.target).is(':checked')) {
                $('.parent_account').addClass('hide');
                $('.show_in_balance_sheet').removeClass('hide');
            } else {
                $('.parent_account').removeClass('hide');
                $('.show_in_balance_sheet').addClass('hide');
            }
        });

        $('#sub_type').on('ifChanged', function (event) {
            if ($(event.target).is(':checked')) {
                $('.parent_account').removeClass('hide');
            } else {
                $('.parent_account').addClass('hide');
                $('#parent_account_id').val('').trigger('change');
            }
        });
    })();
</script>

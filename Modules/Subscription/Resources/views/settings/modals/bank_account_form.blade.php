<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <form method="post"
            action="{{ isset($account)
                ? action('\Modules\Subscription\Http\Controllers\SubscriptionBankAccountController@update', $account->id)
                : action('\Modules\Subscription\Http\Controllers\SubscriptionBankAccountController@store') }}"
            id="add_bank_account_form"
            class="bank_account_form">
            @csrf
            @if(isset($account))
                @method('PUT')
            @endif

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    @lang('subscription::lang.bank_account')
                    @if(isset($account))
                        - @lang('messages.edit')
                    @else
                        - @lang('messages.add')
                    @endif
                </h4>
            </div>

            <div class="modal-body">
                <div class="row">
                    {{-- Template Name --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="template_name">@lang('subscription::lang.template_name') *</label>
                            <input type="text" class="form-control" id="template_name" name="template_name" 
                                placeholder="@lang('subscription::lang.template_name')"
                                value="{{ $account->template_name ?? old('template_name') }}"
                                required>
                        </div>
                    </div>

                    {{-- Account Name --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ac_name">@lang('subscription::lang.account_name') *</label>
                            <input type="text" class="form-control" id="ac_name" name="ac_name" 
                                placeholder="@lang('subscription::lang.account_name')"
                                value="{{ $account->ac_name ?? old('ac_name') }}"
                                required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Account Number --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="ac_no">@lang('subscription::lang.account_number') *</label>
                            <input type="text" class="form-control" id="ac_no" name="ac_no" 
                                placeholder="@lang('subscription::lang.account_number')"
                                value="{{ $account->ac_no ?? old('ac_no') }}"
                                required>
                        </div>
                    </div>

                    {{-- Bank Name --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bank">@lang('subscription::lang.bank_name') *</label>
                            <input type="text" class="form-control" id="bank" name="bank" 
                                placeholder="@lang('subscription::lang.bank_name')"
                                value="{{ $account->bank ?? old('bank') }}"
                                required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Branch --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="branch">@lang('subscription::lang.branch') *</label>
                            <input type="text" class="form-control" id="branch" name="branch" 
                                placeholder="@lang('subscription::lang.branch')"
                                value="{{ $account->branch ?? old('branch') }}"
                                required>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">@lang('subscription::lang.status') *</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="enabled" {{ (isset($account) && $account->status == 'enabled') || old('status') == 'enabled' ? 'selected' : '' }}>
                                    @lang('subscription::lang.enabled')
                                </option>
                                <option value="disabled" {{ (isset($account) && $account->status == 'disabled') || old('status') == 'disabled' ? 'selected' : '' }}>
                                    @lang('subscription::lang.disabled')
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" id="save_bank_account_btn">
                    @if(isset($account))
                        @lang('messages.update')
                    @else
                        @lang('messages.save')
                    @endif
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    @lang('messages.close')
                </button>
            </div>
        </form>
    </div>
</div>
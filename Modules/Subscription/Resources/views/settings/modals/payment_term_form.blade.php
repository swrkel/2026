<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <form method="post"
            action="{{ isset($payment_term)
                ? action('\Modules\Subscription\Http\Controllers\SubscriptionPaymentTermController@update', $payment_term->id)
                : action('\Modules\Subscription\Http\Controllers\SubscriptionPaymentTermController@store') }}"
            id="add_payment_term_form"
            class="payment_term_form">
            @csrf
            @if(isset($payment_term))
                @method('PUT')
            @endif

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    @lang('subscription::lang.payment_term')
                    @if(isset($payment_term))
                        - @lang('messages.edit')
                    @else
                        - @lang('messages.add')
                    @endif
                </h4>
            </div>

            <div class="modal-body">
                <div class="row">
                    {{-- Name --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name">@lang('subscription::lang.name') *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                placeholder="@lang('subscription::lang.payment_term_name')"
                                value="{{ $payment_term->name ?? old('name') }}"
                                required>
                        </div>
                    </div>

                    {{-- Status --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">@lang('subscription::lang.status') *</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="enabled" {{ (isset($payment_term) && $payment_term->status == 'enabled') || old('status') == 'enabled' ? 'selected' : '' }}>
                                    @lang('subscription::lang.enabled')
                                </option>
                                <option value="disabled" {{ (isset($payment_term) && $payment_term->status == 'disabled') || old('status') == 'disabled' ? 'selected' : '' }}>
                                    @lang('subscription::lang.disabled')
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Terms --}}
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="terms">@lang('subscription::lang.terms') *</label>
                            <textarea class="form-control" id="terms" name="terms" 
                                placeholder="@lang('subscription::lang.payment_terms_details')"
                                rows="6"
                                required>{{ $payment_term->terms ?? old('terms') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" id="save_payment_term_btn">
                    @if(isset($payment_term))
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
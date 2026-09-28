<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <form method="post"
            action="{{ isset($prefix)
                ? action('\Modules\Subscription\Http\Controllers\SubscriptionInvoicePrefixController@update', $prefix->id)
                : action('\Modules\Subscription\Http\Controllers\SubscriptionInvoicePrefixController@store') }}"
            id="add_invoice_prefix_form"
            class="invoice_prefix_form">
            @csrf
            @if(isset($prefix))
                @method('PUT')
            @endif

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    @lang('subscription::lang.invoice_prefix')
                    @if(isset($prefix))
                        - @lang('messages.edit')
                    @else
                        - @lang('messages.add')
                    @endif
                </h4>
            </div>

            <div class="modal-body">
                <div class="row">
                    {{-- User Selection --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="user_id">@lang('subscription::lang.user') *</label>
                            {{-- <select class="form-control select2" id="user_id" name="user_id" required> --}}
                            <select class="form-control select2" id="user_ids" name="user_ids[]" {{ isset($prefix) ? '' : 'multiple' }} required>
                                <option value="">@lang('subscription::lang.please_select')</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}"
                                        {{ (isset($prefix) && (int) $prefix->user_id === (int) $user->id) || collect(old('user_ids', []))->contains($user->id) ? 'selected' : '' }}>
                                        {{ $user->username }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Prefix --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="prefix">@lang('subscription::lang.prefix') *</label>
                            <input type="text" class="form-control" id="prefix" name="prefix" 
                                placeholder="@lang('subscription::lang.prefix')"
                                value="{{ $prefix->prefix ?? old('prefix') }}"
                                required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Current Number --}}
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="current_number">@lang('subscription::lang.starting_number') *</label>
                            <input type="number" class="form-control" id="current_number" name="current_number" 
                                placeholder="@lang('subscription::lang.starting_number')"
                                value="{{ $prefix->current_number ?? old('current_number', 1) }}"
                                min="1"
                                required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" id="save_invoice_prefix_btn">
                    @if(isset($prefix))
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

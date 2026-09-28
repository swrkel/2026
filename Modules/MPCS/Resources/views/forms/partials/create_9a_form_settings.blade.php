<div class="modal-dialog" role="document" style="width: 85%;">
    <div class="modal-content">
        {!! Form::open(['route' => 'form9a-settings.store', 'method' => 'post', 'id' => 'add_9a_form_settings' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'mpcs::lang.add_form_9_a_settings' )</h4>
        </div>

        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
            <div class="col-md-12"><br />

                <!-- Top Row: Opening Date and Form Starting Number -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.opening_date')</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="datepicker" name="datepicker" data-date-format="yyyy/mm/dd">
                                <div class="input-group-addon">
                                    <i class="fa fa-calendar-o"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.form_starting_number') <span class="required" aria-required="true">*</span></label>
                            <input type="text" name="form_starting_number" class="form-control" required>
                        </div>
                    </div>
                </div>

                <!-- Ref Previous Form Number -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.ref_previous_form_number')</label>
                            <input type="text" name="ref_previous_form_number" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Total Sale up to Previous Day -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.total_sale_up_to_previous_day')</label>
                            <input type="text" name="total_sale_up_to_previous_day" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Previous Day Cash Sale -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.previous_day_cash_sale')</label>
                            <input type="text" name="previous_day_cash_sale" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.previous_day_card_sale')</label>
                            <input type="text" name="previous_day_card_sale" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Previous Day Credit Sale -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.previous_day_credit_sale')</label>
                            <input type="text" name="previous_day_credit_sale" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Three Column Section: Sub Categories, Sales, and Receipts -->
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <!-- Column Headers -->
                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-md-3">
                                <h5 style="color: #0099ff; font-weight: bold; margin-bottom: 0;">Product Sub Category</h5>
                            </div>
                            <div class="col-md-3">
                                <h5 style="color: #0099ff; font-weight: bold; margin-bottom: 0;">Cash Previous Day<br><span style="font-weight: normal; font-size: 12px;">(Row 3)</span></h5>
                            </div>
                            <div class="col-md-3">
                                <h5 style="color: #0099ff; font-weight: bold; margin-bottom: 0;">Credit Previous Day<br><span style="font-weight: normal; font-size: 12px;">(Row 4)</span></h5>
                            </div>
                            <div class="col-md-3">
                                <h5 style="color: #0099ff; font-weight: bold; margin-bottom: 0;">Receipts Section<br><span style="font-weight: normal; font-size: 12px;">Previous Day</span></h5>
                            </div>
                        </div>

                        <!-- Sub-Categories List with Dashed Lines -->
                        @forelse($sub_categories ?? [] as $index => $category)
                            <div class="row" style="margin-bottom: 12px; padding-bottom: 8px;">
                                <div class="col-md-3">
                                    <div style="font-weight: 500; padding-top: 8px;">
                                        {{ $category->name }}
                                    </div>
                                    <input type="hidden" name="sub_categories[{{ $index }}][id]" value="{{ $category->id }}">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="sub_categories[{{ $index }}][cash_previous_day]" class="form-control input-sm" placeholder="0.00" style="border-top: none; border-left: none; border-right: none; border-bottom: 1px dashed #ccc; border-radius: 0;">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="sub_categories[{{ $index }}][credit_previous_day]" class="form-control input-sm" placeholder="0.00" style="border-top: none; border-left: none; border-right: none; border-bottom: 1px dashed #ccc; border-radius: 0;">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="sub_categories[{{ $index }}][receipts_previous_day]" class="form-control input-sm" placeholder="0.00" style="border-top: none; border-left: none; border-right: none; border-bottom: 1px dashed #ccc; border-radius: 0;">
                                </div>
                            </div>
                        @empty
                            <div class="row">
                                <div class="col-md-12" class="text-center">
                                    <p class="text-center">No sub-categories available</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Payments Section -->
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <h4 style="color: #0099ff; margin-bottom: 15px;">@lang('mpcs::lang.payment_section')</h4>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.previous_day_cash')</label>
                            <input type="text" name="previous_day_cash" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.previous_day_cheques_cards')</label>
                            <input type="text" name="previous_day_cheques_cards" class="form-control">
                        </div>
                    </div>
                </div>

                {{-- Manual entry - Business Bank Accounts (Previous Day) --}}
                @if(!empty($business_bank_accounts) && count($business_bank_accounts) > 0)
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-12">
                            <hr style="border-top: 2px solid #0099ff;">
                            <h4 style="color:#0099ff; font-weight: bold; margin-bottom:15px;">
                                Previous Day (Bank Accounts)
                            </h4>
                        </div>
                    </div>
                    <div class="row">
                        @foreach($business_bank_accounts as $account)
                            <div class="col-md-6" style="margin-bottom:12px;">
                                <div class="form-group">
                                    <label class="control-label">{{ $account->name }}</label>
                                    <input type="text"
                                           name="pre_day_bank_accounts[{{ $account->id }}]"
                                           class="form-control input-sm input_number"
                                           placeholder="0.00"
                                           value="{{ $bank_manual[$account->id] ?? '' }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Manual entry - Card Accounts (Previous Day) --}}
                @if(!empty($card_accounts) && count($card_accounts) > 0)
                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-12">
                            <hr style="border-top: 2px solid #0099ff;">
                            <h4 style="color:#0099ff; font-weight: bold; margin-bottom:15px;">
                                Previous Day (Card Accounts)
                            </h4>
                        </div>
                    </div>
                    <div class="row">
                        @foreach($card_accounts as $account)
                            <div class="col-md-6" style="margin-bottom:12px;">
                                <div class="form-group">
                                    <label class="control-label">{{ $account->name }}</label>
                                    <input type="text"
                                           name="pre_day_card_accounts[{{ $account->id }}]"
                                           class="form-control input-sm input_number"
                                           placeholder="0.00"
                                           value="{{ $card_manual[$account->id] ?? '' }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.previous_day_total')</label>
                            <input type="text" name="previous_day_total" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.previous_day_balance_in_hand')</label>
                            <input type="text" name="previous_day_balance_in_hand" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.previous_day_grand_total')</label>
                            <input type="text" name="previous_day_grand_total" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('mpcs::lang.no_of_rows_to_show_per_page')</label>
                            <input type="number" name="no_of_rows_to_show_per_page" class="form-control" placeholder="10">
                        </div>
                    </div>
                </div>

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
    $('#datepicker').datepicker('setDate', new Date());
</script>

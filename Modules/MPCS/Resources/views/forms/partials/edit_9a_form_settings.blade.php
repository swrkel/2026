<div class="modal-dialog" role="document" style="width: 85%;">
    <div class="modal-content">
        {!! Form::open(['route' => ['form9a-settings.update', $settings->id], 'method' => 'post', 'id' => 'update_9a_form_settings' ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'mpcs::lang.edit_form_9_a_settings' )</h4>
        </div>

                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
            <div class="col-md-12"><br />

                <div class="row">
                    <!-- Date and Time -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.date_and_time')</label>
                        {!! Form::date('datepicker', date('Y-m-d', strtotime($settings->date)), [
                            'class' => 'form-control',
                            'placeholder' => __( 'mpcs::lang.date_and_time' ),
                            'required',
                            'id' => 'datepicker'
                        ]) !!}
                    </div>
                </div>

                
                    <!-- Form Starting Number -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.form_starting_number') <span class="required" aria-required="true">*</span></label>
                        {!! Form::text('form_starting_number', $settings->starting_number, [
                            'class' => 'form-control',
                            'placeholder' => __( 'mpcs::lang.form_starting_number' ),
                            'required',
                            'id' => 'form_starting_number'
                        ]); !!}
                    </div>
                </div>

                
                </div>

                <div class="row">
                    <!-- Ref Previous Form Number -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.ref_previous_form_number') <span class="required" aria-required="true">*</span></label>
                        {!! Form::text('ref_previous_form_number', $settings->ref_pre_form_number, [
                            'class' => 'form-control',
                            'placeholder' => __( 'mpcs::lang.ref_previous_form_number' ),
                            'required',
                            'id' => 'ref_previous_form_number'
                        ]); !!}
                    </div>
                </div>

                
                    <!-- Total Sale up to Previous Day -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.total_sale_up_to_previous_day') <span class="required" aria-required="true">*</span></label>
                        {!! Form::number('total_sale_up_to_previous_day', $settings->total_sale_to_pre, [
                            'class' => 'form-control',
                            'placeholder' => __( 'mpcs::lang.total_sale_up_to_previous_day' ),
                            'required',
                            'id' => 'total_sale_up_to_previous_day'
                        ]); !!}
                    </div>
                </div>

                
                </div>

                <div class="row">
                    <!-- Previous Day Cash Sale -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.previous_day_cash_sale') <span class="required" aria-required="true">*</span></label>
                        {!! Form::number('previous_day_cash_sale', $settings->pre_day_cash_sale, [
                            'class' => 'form-control',
                            'placeholder' => __( 'mpcs::lang.previous_day_cash_sale' ),
                            'required',
                            'id' => 'previous_day_cash_sale'
                        ]); !!}
                    </div>
                </div>

                
                    <!-- Previous Day Card Sale -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.previous_day_card_sale') <span class="required" aria-required="true">*</span></label>
                        {!! Form::number('previous_day_card_sale', $settings->pre_day_card_sale, [
                            'class' => 'form-control',
                            'placeholder' => __( 'mpcs::lang.previous_day_card_sale' ),
                            'required',
                            'id' => 'previous_day_card_sale'
                        ]); !!}
                    </div>
                </div>

                
                </div>

                <div class="row">
                    <!-- Previous Day Credit Sale -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.previous_day_credit_sale') <span class="required" aria-required="true">*</span></label>
                        {!! Form::number('previous_day_credit_sale', $settings->pre_day_credit_sale, [
                            'class' => 'form-control',
                            'placeholder' => __( 'mpcs::lang.previous_day_credit_sale' ),
                            'required',
                            'id' => 'previous_day_credit_sale'
                        ]); !!}
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
                            @php
                                $cash_val = '';
                                $credit_val = '';
                                $receipts_val = '';
                                foreach($sub_categories_data as $data) {
                                    if((int)$data['id'] === (int)$category->id) {
                                        $cash_val     = $data['cash_previous_day'] ?? ($data['sales_previous_day'] ?? '');
                                        $credit_val   = $data['credit_previous_day'] ?? '';
                                        $receipts_val = $data['receipts_previous_day'] ?? '';
                                        break;
                                    }
                                }
                            @endphp
                            <div class="row" style="margin-bottom: 12px; padding-bottom: 8px;">
                                <div class="col-md-3">
                                    <div style="font-weight: 500; padding-top: 8px;">
                                        {{ $category->name }}
                                    </div>
                                    <input type="hidden" name="sub_categories[{{ $index }}][id]" value="{{ $category->id }}">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="sub_categories[{{ $index }}][cash_previous_day]" class="form-control input-sm" value="{{ $cash_val }}" placeholder="0.00" style="border-top: none; border-left: none; border-right: none; border-bottom: 1px dashed #ccc; border-radius: 0;">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="sub_categories[{{ $index }}][credit_previous_day]" class="form-control input-sm" value="{{ $credit_val }}" placeholder="0.00" style="border-top: none; border-left: none; border-right: none; border-bottom: 1px dashed #ccc; border-radius: 0;">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" name="sub_categories[{{ $index }}][receipts_previous_day]" class="form-control input-sm" value="{{ $receipts_val }}" placeholder="0.00" style="border-top: none; border-left: none; border-right: none; border-bottom: 1px dashed #ccc; border-radius: 0;">
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
                    <!-- Previous Day Cash -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.previous_day_cash') <span class="required" aria-required="true">*</span></label>
                        {!! Form::number('previous_day_cash', $settings->pre_day_cash, [
                            'class' => 'form-control',
                            'placeholder' => __( 'mpcs::lang.previous_day_cash' ),
                            'required',
                            'id' => 'previous_day_cash'
                        ]); !!}
                    </div>
                </div>

                
                    <!-- Previous Day Cheques / Cards -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.previous_day_cheques_cards') <span class="required" aria-required="true">*</span></label>
                        {!! Form::number('previous_day_cheques_cards', $settings->pre_day_cheques, [
                            'class' => 'form-control',
                            'placeholder' => __( 'mpcs::lang.previous_day_cheques_cards' ),
                            'required',
                            'id' => 'previous_day_cheques_cards'
                        ]); !!}
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
                            @php
                                $value = $bank_manual[$account->id] ?? '';
                            @endphp
                            <div class="col-md-6" style="margin-bottom:12px;">
                                <div class="form-group">
                                    <label class="control-label">{{ $account->name }}</label>
                                    <input type="text"
                                           name="pre_day_bank_accounts[{{ $account->id }}]"
                                           class="form-control input-sm input_number"
                                           value="{{ $value }}"
                                           placeholder="0.00">
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
                            @php
                                $value = $card_manual[$account->id] ?? '';
                            @endphp
                            <div class="col-md-6" style="margin-bottom:12px;">
                                <div class="form-group">
                                    <label class="control-label">{{ $account->name }}</label>
                                    <input type="text"
                                           name="pre_day_card_accounts[{{ $account->id }}]"
                                           class="form-control input-sm input_number"
                                           value="{{ $value }}"
                                           placeholder="0.00">
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

            

                <div class="row">
                    <!-- Previous Day Total (Payments Section) -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.previous_day_total') <span class="required" aria-required="true">*</span></label>
                        {!! Form::number('previous_day_total', $settings->pre_day_total, [
                            'class' => 'form-control',
                            'placeholder' => __( 'mpcs::lang.previous_day_total' ),
                            'required',
                            'id' => 'previous_day_total'
                        ]); !!}
                    </div>
                </div>

                
                    <!-- Previous Day Balance in Hand (Payments Section) -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.previous_day_balance_in_hand') <span class="required" aria-required="true">*</span></label>
                        {!! Form::number('previous_day_balance_in_hand', $settings->pre_day_balance, [
                            'class' => 'form-control',
                            'placeholder' => __( 'mpcs::lang.previous_day_balance_in_hand' ),
                            'required',
                            'id' => 'previous_day_balance_in_hand'
                        ]); !!}
                    </div>
                </div>

                
                </div>

                <div class="row">
                    <!-- Previous Day Grand Total (Payments Section) -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.previous_day_grand_total') <span class="required" aria-required="true">*</span></label>
                        {!! Form::number('previous_day_grand_total', $settings->pre_day_grand_total, [
                            'class' => 'form-control',
                            'placeholder' => __( 'mpcs::lang.previous_day_grand_total' ),
                            'required',
                            'id' => 'previous_day_grand_total'
                        ]); !!}
                    </div>
                </div>
                
                    <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('mpcs::lang.no_of_rows_to_show_per_page') <span class="required" aria-required="true">*</span></label>
                        {!! Form::number('no_of_rows_to_show_per_page', $settings->no_of_rows_per_page, [
                            'class' => 'form-control',
                            'placeholder' => __( 'mpcs::lang.no_of_rows_to_show_per_page' ),
                            'required',
                            'id' => 'no_of_rows_to_show_per_page'
                        ]); !!}
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

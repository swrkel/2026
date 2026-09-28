<section class="content" style="padding-top:0;">
    <div class="row">
        {!! Form::open([
            'url' => action('\Modules\PetroGeneral\Http\Controllers\IssueCustomerBillSettingController@store'),
            'method' => 'post',
            'id' => 'customer_bill_printer_setting_form'
        ]) !!}

        <div class="col-md-12"> <br
            <h4>@lang('petrogeneral::lang.customer_bill_printer_setting')</h4>

            <div class="row">
                <!-- LEFT SIDE -->
                <div class="col-md-8">
                    <!-- Pump -->
                    <div class="checkbox">
                        <label>
                            <input type="hidden" name="show_pump" value="0">
                            <input type="checkbox" name="show_pump" value="1"
                                @if(empty($issueCustomerBillSetting) || $issueCustomerBillSetting->show_pump == 1) checked @endif>
                            @lang('petrogeneral::lang.pump')
                        </label>
                    </div>

                    <!-- Pump Operator -->
                    <div class="checkbox">
                        <label>
                            <input type="hidden" name="show_pump_operator" value="0">
                            <input type="checkbox" name="show_pump_operator" value="1"
                                @if(empty($issueCustomerBillSetting) || $issueCustomerBillSetting->show_pump_operator == 1) checked @endif>
                            @lang('petrogeneral::lang.pump_operator')
                        </label>
                    </div>

                    <!-- Print Option -->
                    <div class="form-group col-md-6" style="padding-left:0;">
                        {!! Form::label('print_option', __('petrogeneral::lang.print_option') . ':*') !!}
                        {!! Form::select('print_option', [
                            1 => 'A4 / A5 Paper',
                            2 => 'POS Bill 80 mm'
                        ], $issueCustomerBillSetting->print_option ?? 1, [
                            'class' => 'form-control',
                            'required'
                        ]) !!}
                    </div>
                </div>

                <!-- RIGHT SIDE -->
                <div class="col-md-4">
                    <!-- Customer Ledger -->
                    <div class="checkbox" style="margin-top: 25px;">
                        <label>
                            <input type="hidden" name="update_customer_ledger" value="0">
                            <input type="checkbox" name="update_customer_ledger" value="1"
                                @if(optional($issueCustomerBillSetting)->update_customer_ledger == 1) checked @endif>
                            Need to show in the Customer Ledger & update the customer balance instantly?
                        </label>
                    </div>

                    <!-- Accounts Receivable (Disabled) -->
                    <div class="checkbox" style="margin-top: 20px; color:#999;">
                        <label>
                            <input type="checkbox" disabled>
                            Need to show in the Accounts Receivable Account?
                        </label>
                    </div>
                </div>
            </div>

            <div class="clearfix"></div>
            <br>

            <button type="submit" class="btn btn-primary">
                @lang('petrogeneral::lang.save')
            </button>
        </div>

        {!! Form::close() !!}
    </div>
</section>

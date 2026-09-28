<div class="pos-tab-content">
     <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-sm-12"><h3>Supplier & Purchase Prefix</h3></div>
 <div class="col-sm-4">
            <div class="form-group">
                @php
                    $supplier_prefix = !empty($business->ref_no_prefixes['supplier']) ? $business->ref_no_prefixes['supplier'] : '';
                    $supplier_starting_no = !empty($business->ref_no_starting_number['supplier']) ? $business->ref_no_starting_number['supplier'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[supplier]', __('lang_v1.supplier') . ':') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[supplier]', $supplier_prefix, ['class' => 'form-control']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[supplier]', $supplier_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $purchase_prefix = '';
                    if(!empty($business->ref_no_prefixes['purchase'])){
                        $purchase_prefix = $business->ref_no_prefixes['purchase'];
                    }
                    $purchase_starting_number = '';
                    if(!empty($business->ref_no_starting_number['purchase'])){
                        $purchase_starting_number = $business->ref_no_starting_number['purchase'];
                    }
                @endphp
                {!! Form::label('ref_no_prefixes[purchase]', __('lang_v1.purchase_order') . ':') !!}
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[purchase]', $purchase_prefix, ['class' => 'form-control']); !!}
                </div>
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[purchase]', $purchase_starting_number, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <!-- purchase payment -->
          <div class="col-sm-4">
            <div class="form-group">
                @php
                    $purchase_payment = '';
                    if(!empty($business->ref_no_prefixes['purchase_payment'])){
                        $purchase_payment = $business->ref_no_prefixes['purchase_payment'];
                    }
                    $purchase_payment_starting_no = '';
                    if(!empty($business->ref_no_starting_number['purchase_payment'])){
                        $purchase_payment_starting_no = $business->ref_no_starting_number['purchase_payment'];
                    }
                @endphp
                {!! Form::label('ref_no_prefixes[purchase_payment]', __('lang_v1.purchase_payment') . ':') !!}
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[purchase_payment]', $purchase_payment, ['class' => 'form-control']); !!}
                </div>
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[purchase_payment]', $purchase_payment_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
<!-- purchase return -->
 <div class="col-sm-4">
            <div class="form-group">
                @php
                    $purchase_return = '';
                    if(!empty($business->ref_no_prefixes['purchase_return'])){
                        $purchase_return = $business->ref_no_prefixes['purchase_return'];
                    }
                    $purchase_return_stating_no = '';
                    if(!empty($business->ref_no_starting_number['purchase_return'])){
                        $purchase_return_stating_no = $business->ref_no_starting_number['purchase_return'];
                    }
                @endphp
                {!! Form::label('ref_no_prefixes[purchase_return]', __('lang_v1.purchase_return') . ':') !!}
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[purchase_return]', $purchase_return, ['class' => 'form-control']); !!}
                </div>
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[purchase_return]',  $purchase_return_stating_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        @php
                            $supplier_add_purchase_prefix = 'POP';
                            if(!empty($business->ref_no_prefixes['supplier_add_purchase'])){
                                $supplier_add_purchase_prefix = $business->ref_no_prefixes['supplier_add_purchase'];
                            }
                            $supplier_add_purchase_starting_number = '';
                            if(!empty($business->ref_no_starting_number['supplier_add_purchase'])){
                                $supplier_add_purchase_starting_number = $business->ref_no_starting_number['supplier_add_purchase'];
                            }
                        @endphp
                        {!! Form::label('ref_no_prefixes[supplier_add_purchase]', __('lang_v1.supplier_add_purchase_prefix') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.prefix')
                            </span>
                        {!! Form::text('ref_no_prefixes[supplier_add_purchase]', $supplier_add_purchase_prefix, ['class' => 'form-control', 'readonly' => 'readonly']); !!}
                        </div>
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.starting_number')
                            </span>
                        {!! Form::text('ref_no_starting_number[supplier_add_purchase]', $supplier_add_purchase_starting_number, ['class' => 'form-control']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        @php
                            $supplier_list_purchase = 'POLP';
                            if(!empty($business->ref_no_prefixes['supplier_list_purchase'])){
                                $supplier_list_purchase = $business->ref_no_prefixes['supplier_list_purchase'];
                            }
                            $supplier_list_purchase_stating_no = '';
                            if(!empty($business->ref_no_starting_number['supplier_list_purchase'])){
                                $supplier_list_purchase_stating_no = $business->ref_no_starting_number['supplier_list_purchase'];
                            }
                        @endphp
                        {!! Form::label('ref_no_prefixes[supplier_list_purchase]', __('lang_v1.supplier_list_purchase_prefix') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.prefix')
                            </span>
                        {!! Form::text('ref_no_prefixes[supplier_list_purchase]', $supplier_list_purchase, ['class' => 'form-control', 'readonly' => 'readonly']); !!}
                        </div>
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.starting_number')
                            </span>
                        {!! Form::text('ref_no_starting_number[supplier_list_purchase]',  $supplier_list_purchase_stating_no, ['class' => 'form-control']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        @php
                            $customer_list_purchase_prefix = '';
                            if(!empty($business->ref_no_prefixes['customer_list_purchase'])){
                                $customer_list_purchase_prefix = $business->ref_no_prefixes['customer_list_purchase'];
                            }
                            $customer_list_purchase_stating_number = '';
                            if(!empty($business->ref_no_starting_number['customer_list_purchase'])){
                                $customer_list_purchase_stating_number = $business->ref_no_starting_number['customer_list_purchase'];
                            }
                        @endphp
                        {!! Form::label('ref_no_prefixes[customer_list_purchase]', __('lang_v1.customer_list_purchase_prefix') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.prefix')
                            </span>
                        {!! Form::text('ref_no_prefixes[customer_list_purchase]', $customer_list_purchase_prefix, ['class' => 'form-control']); !!}
                        </div>
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.starting_number')
                            </span>
                        {!! Form::text('ref_no_starting_number[customer_list_purchase]', $customer_list_purchase_stating_number, ['class' => 'form-control']); !!}
                        </div>
                    </div>
                </div>
                
               
            </div>  
        </div> 
         <div class="col-sm-12"><h3>Customer  Purchases Prefix</h3></div> 
<div class="col-sm-4">
            <div class="form-group">
                @php
                    $customer_prefix = !empty($business->ref_no_prefixes['customer']) ? $business->ref_no_prefixes['customer'] : '';
                    $customer_starting_no = !empty($business->ref_no_starting_number['customer']) ? $business->ref_no_starting_number['supplier'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[customer]', __('lang_v1.customer') . ':') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[customer]', $customer_prefix, ['class' => 'form-control']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[customer]', $customer_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <!-- Quatation -->
          <div class="col-sm-4 ">
            <div class="form-group">
                @php
                    $quotation_no_prefix = !empty($business->ref_no_prefixes['quotation_no']) ? $business->ref_no_prefixes['quotation_no'] : '';
                    $quotation_no_starting_no = !empty($business->ref_no_starting_number['quotation_no']) ? $business->ref_no_starting_number['quotation_no'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[quotation_no]', __('lang_v1.quotation') . ':') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[quotation_no]', $quotation_no_prefix, ['class' => 'form-control']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[quotation_no]', $quotation_no_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>

        <!-- sell payment -->
           <div class="col-sm-4">
            <div class="form-group">
                @php
                    $sell_payment = '';
                    if(!empty($business->ref_no_prefixes['sell_payment'])){
                        $sell_payment = $business->ref_no_prefixes['sell_payment'];
                    }
                    $sell_payment_starting_no = '';
                    if(!empty($business->ref_no_starting_number['sell_payment'])){
                        $sell_payment_starting_no = $business->ref_no_starting_number['sell_payment'];
                    }
                @endphp
                {!! Form::label('ref_no_prefixes[sell_payment]', __('lang_v1.sell_payment') . ':') !!}
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[sell_payment]', $sell_payment, ['class' => 'form-control']); !!}
                </div>
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[sell_payment]', $sell_payment_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <!-- sell return -->
<div class="col-sm-4">
            <div class="form-group">
                @php
                    $sell_return_prefix = '';
                    if(!empty($business->ref_no_prefixes['sell_return'])){
                        $sell_return_prefix = $business->ref_no_prefixes['sell_return'];
                    }
                    $sell_return_starting_no = '';
                    if(!empty($business->ref_no_starting_number['sell_return'])){
                        $sell_return_starting_no = $business->ref_no_starting_number['sell_return'];
                    }
                @endphp
                {!! Form::label('ref_no_prefixes[sell_return]', __('lang_v1.sell_return') . ':') !!}
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[sell_return]', $sell_return_prefix, ['class' => 'form-control']); !!}
                </div>
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[sell_return]',  $sell_return_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <!-- customer payment -->
         <div class="col-sm-4">
                    <div class="form-group">
                        @php
                            $customer_payment_prefix = 'CPP';
                            if(!empty($business->ref_no_prefixes['customer_payment'])){
                                $customer_payment_prefix = $business->ref_no_prefixes['customer_payment'];
                            }
                            $customer_payment_starting_number = '';
                            if(!empty($business->ref_no_starting_number['customer_payment'])){
                                $customer_payment_starting_number = $business->ref_no_starting_number['customer_payment'];
                            }
                        @endphp
                        {!! Form::label('ref_no_prefixes[customer_payment]', __('lang_v1.customer_payment_prefix') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.prefix')
                            </span>
                        {!! Form::text('ref_no_prefixes[customer_payment]', $customer_payment_prefix, ['class' => 'form-control', 'readonly' => 'readonly']); !!}
                        </div>
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.starting_number')
                            </span>
                        {!! Form::text('ref_no_starting_number[customer_payment]', $customer_payment_starting_number, ['class' => 'form-control']); !!}
                        </div>
                    </div>
                </div>
                <!-- customer payment bulk -->

                <div class="col-sm-4">
                    <div class="form-group">
                        @php
                            $customer_bulk_payment = 'CPB';
                            if(!empty($business->ref_no_prefixes['customer_bulk_payment'])){
                                $customer_bulk_payment = $business->ref_no_prefixes['customer_bulk_payment'];
                            }
                            $customer_bulk_payment_stating_no = '';
                            if(!empty($business->ref_no_starting_number['customer_bulk_payment'])){
                                $customer_bulk_payment_stating_no = $business->ref_no_starting_number['customer_bulk_payment'];
                            }
                        @endphp
                        {!! Form::label('ref_no_prefixes[customer_bulk_payment]', __('lang_v1.customer_bulk_payment_prefix') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.prefix')
                            </span>
                        {!! Form::text('ref_no_prefixes[customer_bulk_payment]', $customer_bulk_payment, ['class' => 'form-control', 'readonly' => 'readonly']); !!}
                        </div>
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.starting_number')
                            </span>
                        {!! Form::text('ref_no_starting_number[customer_bulk_payment]',  $customer_bulk_payment_stating_no, ['class' => 'form-control']); !!}
                        </div>
                    </div>
                </div>
                <!-- customer payment list statement -->
 <div class="col-sm-4">
                    <div class="form-group">
                        @php
                            $customer_list_statement_prefix = 'CSTP';
                            if(!empty($business->ref_no_prefixes['customer_list_statement'])){
                                $customer_list_statement_prefix = $business->ref_no_prefixes['customer_list_statement'];
                            }
                            $customer_list_statement_stating_number = '';
                            if(!empty($business->ref_no_starting_number['customer_list_statement'])){
                                $customer_list_statement_stating_number = $business->ref_no_starting_number['customer_list_statement'];
                            }
                        @endphp
                        {!! Form::label('ref_no_prefixes[customer_list_statement]', __('lang_v1.customer_list_statement_prefix') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.prefix')
                            </span>
                        {!! Form::text('ref_no_prefixes[customer_list_statement]', $customer_list_statement_prefix, ['class' => 'form-control', 'readonly' => 'readonly']); !!}
                        </div>
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.starting_number')
                            </span>
                        {!! Form::text('ref_no_starting_number[customer_list_statement]', $customer_list_statement_stating_number, ['class' => 'form-control']); !!}
                        </div>
                    </div>
                </div>
                <!-- cus list vat -->
                 
                <div class="col-sm-4">
                    <div class="form-group">
                        @php
                            $customer_vat_statement_prefix = 'CVSTP';
                            if(!empty($business->ref_no_prefixes['customer_vat_statement'])){
                                $customer_vat_statement_prefix = $business->ref_no_prefixes['customer_vat_statement'];
                            }
                            $customer_vat_statement_stating_number = '';
                            if(!empty($business->ref_no_starting_number['customer_vat_statement'])){
                                $customer_vat_statement_stating_number = $business->ref_no_starting_number['customer_vat_statement'];
                            }
                        @endphp
                        {!! Form::label('ref_no_prefixes[customer_vat_statement]', __('lang_v1.customer_vat_statement_prefix') . ':') !!}
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.prefix')
                            </span>
                        {!! Form::text('ref_no_prefixes[customer_vat_statement]', $customer_vat_statement_prefix, ['class' => 'form-control', 'readonly' => 'readonly']); !!}
                        </div>
                        <div class="input-group">
                            <span class="input-group-addon">
                                @lang('lang_v1.starting_number')
                            </span>
                        {!! Form::text('ref_no_starting_number[customer_vat_statement]', $customer_vat_statement_stating_number, ['class' => 'form-control']); !!}
                        </div>
                    </div>
                </div>
         <div class="col-sm-12"><h3>Settlement Prefix</h3></div> 
<!-- Settlement -->
 <div class="col-sm-4">
            <div class="form-group">
                @php
                    $settlement_prefix = !empty($business->ref_no_prefixes['settlement']) ? $business->ref_no_prefixes['settlement'] : '';
                    $settlement_starting_no = !empty($business->ref_no_starting_number['settlement']) ? $business->ref_no_starting_number['settlement'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[settlement]', __('lang_v1.settlement') . ':') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[settlement]', $settlement_prefix, ['class' => 'form-control']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[settlement]', $settlement_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
     <div class="col-sm-4">
            <div class="form-group">
                @php
                    $settlement_prefix = !empty($business->ref_no_prefixes['settlement_pd']) ? $business->ref_no_prefixes['settlement_pd'] : '';
                    $settlement_starting_no = !empty($business->ref_no_starting_number['settlement_pd']) ? $business->ref_no_starting_number['settlement_pd'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[settlement_pd]', __('lang_v1.settlement_pd') . ':') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[settlement_pd]', $settlement_prefix, ['class' => 'form-control']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[settlement_pd]', $settlement_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <!-- Excess Commission  -->
    

        
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $excess_commission_prefix = !empty($business->ref_no_prefixes['excess_commission']) ? $business->ref_no_prefixes['excess_commission'] : '';
                    $excess_commission_starting_no = !empty($business->ref_no_starting_number['excess_commission']) ? $business->ref_no_starting_number['excess_commission'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[excess_commission]', __('lang_v1.excess_commission') . ':') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[excess_commission]', $excess_commission_prefix, ['class' => 'form-control']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[excess_commission]', $excess_commission_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <!-- Shortage Recover  -->

        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $shortage_recover_prefix = !empty($business->ref_no_prefixes['shortage_recover']) ? $business->ref_no_prefixes['shortage_recover'] : '';
                    $shortage_recover_starting_no = !empty($business->ref_no_starting_number['shortage_recover']) ? $business->ref_no_starting_number['shortage_recover'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[shortage_recover]', __('lang_v1.shortage_recover') . ':') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[shortage_recover]', $shortage_recover_prefix, ['class' => 'form-control']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[shortage_recover]', $shortage_recover_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <!-- Settlement Customer Payment  -->

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('ref_no_prefixes[settlement_customer_payment]', __('lang_v1.settlement_customer_payment') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">@lang('lang_v1.prefix')</span>
                    {!! Form::text('ref_no_prefixes[settlement_customer_payment]', 'SW-CP', ['class' => 'form-control', 'readonly']) !!}
                </div>
                <div class="input-group">
                    <span class="input-group-addon">@lang('lang_v1.starting_number')</span>
                    {!! Form::text('ref_no_starting_number[settlement_customer_payment]', '1', ['class' => 'form-control', 'readonly']) !!}
                </div>
            </div>
        </div>
        <!-- Settlement Expense  -->
         

        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('ref_no_prefixes[settlement_expense]', __('lang_v1.settlement_expense') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">@lang('lang_v1.prefix')</span>
                    {!! Form::text('ref_no_prefixes[settlement_expense]', 'SW-EXP', ['class' => 'form-control', 'readonly']) !!}
                </div>
                <div class="input-group">
                    <span class="input-group-addon">@lang('lang_v1.starting_number')</span>
                    {!! Form::text('ref_no_starting_number[settlement_expense]', '1', ['class' => 'form-control', 'readonly']) !!}
                </div>
            </div>
        </div>
        <!-- Settlement SW  -->
       <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('ref_no_prefixes[settlement_sw]', 'Settlement SW:') !!}
                <div class="input-group">
                    <span class="input-group-addon">@lang('lang_v1.prefix')</span>
                    {!! Form::text('ref_no_prefixes[settlement_sw]', 'SET-SW', ['class' => 'form-control', 'readonly']) !!}
                </div>
                <div class="input-group">
                    <span class="input-group-addon">@lang('lang_v1.starting_number')</span>
                    {!! Form::text('ref_no_starting_number[settlement_sw]', '1', ['class' => 'form-control', 'readonly']) !!}
                </div>
            </div>
        </div>
        <div class="col-sm-12"><h3>Stock Prefix</h3></div>

        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $stock_transfer_prefix = '';
                    if(!empty($business->ref_no_prefixes['stock_transfer'])){
                        $stock_transfer_prefix = $business->ref_no_prefixes['stock_transfer'];
                    }
                    $stock_transfer_stating_number = '';
                    if(!empty($business->ref_no_starting_number['stock_transfer'])){
                        $stock_transfer_stating_number = $business->ref_no_starting_number['stock_transfer'];
                    }
                @endphp
                {!! Form::label('ref_no_prefixes[stock_transfer]', __('lang_v1.stock_transfer') . ':') !!}
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[stock_transfer]', $stock_transfer_prefix, ['class' => 'form-control']); !!}
                </div>
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[stock_transfer]', $stock_transfer_stating_number, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $stock_adjustment_prefix = '';
                    if(!empty($business->ref_no_prefixes['stock_adjustment'])){
                        $stock_adjustment_prefix = $business->ref_no_prefixes['stock_adjustment'];
                    }
                    $stock_adjustment_starting_number = '';
                    if(!empty($business->ref_no_starting_number['stock_adjustment'])){
                        $stock_adjustment_starting_number = $business->ref_no_starting_number['stock_adjustment'];
                    }
                @endphp
                {!! Form::label('ref_no_prefixes[stock_adjustment]', __('stock_adjustment.stock_adjustment') . ':') !!}
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[stock_adjustment]', $stock_adjustment_prefix, ['class' => 'form-control']); !!}
                </div>
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[stock_adjustment]', $stock_adjustment_starting_number, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
          <div class="col-sm-12"><h3>Expenses Prefix</h3></div> 

        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $expenses_prefix = '';
                    if(!empty($business->ref_no_prefixes['expense'])){
                        $expenses_prefix = $business->ref_no_prefixes['expense'];
                    }
                    $expenses_starting_no = '';
                    if(!empty($business->ref_no_starting_number['expense'])){
                        $expenses_starting_no = $business->ref_no_starting_number['expense'];
                    }
                @endphp
                {!! Form::label('ref_no_prefixes[expense]', __('expense.expenses') . ':') !!}
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[expense]', $expenses_prefix, ['class' => 'form-control']); !!}
                </div>
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[expense]', $expenses_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $expense_payment = '';
                    if(!empty($business->ref_no_prefixes['expense_payment'])){
                        $expense_payment = $business->ref_no_prefixes['expense_payment'];
                    }
                    $expense_payment_starting_no = '';
                    if(!empty($business->ref_no_starting_number['expense_payment'])){
                        $expense_payment_starting_no = $business->ref_no_starting_number['expense_payment'];
                    }
                @endphp
                {!! Form::label('ref_no_prefixes[expense_payment]', __('lang_v1.expense_payment') . ':') !!}
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[expense_payment]', $expense_payment, ['class' => 'form-control']); !!}
                </div>
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[expense_payment]', $expense_payment_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-12"><h3>Fleet Prefix</h3></div> 

        <div class="col-sm-4  @if($get_permissions['fleet_module'] == 0) hide  @endif">
            <div class="form-group">
                @php
                    $route_no_prefix = !empty($business->ref_no_prefixes['route_no']) ? $business->ref_no_prefixes['route_no'] : '';
                    $route_no_starting_no = !empty($business->ref_no_starting_number['route_no']) ? $business->ref_no_starting_number['route_no'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[route_no]', __('lang_v1.route_no') . ':') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[route_no]', $route_no_prefix, ['class' => 'form-control']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[route_no]', $route_no_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-12"><h3>General Prefix</h3></div>        
        
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $contacts_prefix = '';
                    if(!empty($business->ref_no_prefixes['contacts'])){
                        $contacts_prefix = $business->ref_no_prefixes['contacts'];
                    }
                    $contacts_starting_no = '';
                    if(!empty($business->ref_no_starting_number['contacts'])){
                        $contacts_starting_no = $business->ref_no_starting_number['contacts'];
                    }
                @endphp
                {!! Form::label('ref_no_prefixes[contacts]', __('contact.contacts') . ':') !!}
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[contacts]', $contacts_prefix, ['class' => 'form-control']); !!}
                </div>
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[contacts]', $contacts_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
       
      
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $business_location_prefix = '';
                    if(!empty($business->ref_no_prefixes['business_location'])){
                        $business_location_prefix = $business->ref_no_prefixes['business_location'];
                    }
                    $business_location_starting_number = '';
                    if(!empty($business->ref_no_starting_number['business_location'])){
                        $business_location_starting_number = $business->ref_no_starting_number['business_location'];
                    }
                @endphp
                {!! Form::label('ref_no_prefixes[business_location]', __('business.business_location') . ':') !!}
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[business_location]', $business_location_prefix, ['class' => 'form-control']); !!}
                </div>
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[business_location]',  $business_location_starting_number, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $username_prefix = !empty($business->ref_no_prefixes['username']) ? $business->ref_no_prefixes['username'] : '';
                    $username_strating_no = !empty($business->ref_no_starting_number['username']) ? $business->ref_no_starting_number['username'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[username]', __('business.username') . ':') !!}
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[username]', $username_prefix, ['class' => 'form-control']); !!}
                </div>
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[username]',  $username_strating_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $subscription_prefix = !empty($business->ref_no_prefixes['subscription']) ? $business->ref_no_prefixes['subscription'] : '';
                    $subscription_starting_no = !empty($business->ref_no_starting_number['subscription']) ? $business->ref_no_starting_number['subscription'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[subscription]', __('lang_v1.subscription_no') . ':') !!}
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[subscription]', $subscription_prefix, ['class' => 'form-control']); !!}
                </div>
                 <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[subscription]', $subscription_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
          
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $security_deposit_prefix = !empty($business->ref_no_prefixes['security_deposit']) ? $business->ref_no_prefixes['security_deposit'] : '';
                    $security_deposit_starting_no = !empty($business->ref_no_starting_number['security_deposit']) ? $business->ref_no_starting_number['security_deposit'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[security_deposit]', __('lang_v1.security_deposit') . ':') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[security_deposit]', $security_deposit_prefix, ['class' => 'form-control']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[security_deposit]', $security_deposit_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $refund_security_deposit_prefix = !empty($business->ref_no_prefixes['refund_security_deposit']) ? $business->ref_no_prefixes['refund_security_deposit'] : '';
                    $refund_security_deposit_starting_no = !empty($business->ref_no_starting_number['refund_security_deposit']) ? $business->ref_no_starting_number['refund_security_deposit'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[refund_security_deposit]', __('lang_v1.refund_security_deposit') . ':') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[refund_security_deposit]', $refund_security_deposit_prefix, ['class' => 'form-control']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[refund_security_deposit]', $refund_security_deposit_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4  @if($get_permissions['fleet_module'] == 0) hide  @endif">
            <div class="form-group">
                @php
                    $employee_no_prefix = !empty($business->ref_no_prefixes['employee_no']) ? $business->ref_no_prefixes['employee_no'] : '';
                    $employee_no_starting_no = !empty($business->ref_no_starting_number['employee_no']) ? $business->ref_no_starting_number['employee_no'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[employee_no]', __('lang_v1.employee_no') . ':') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[employee_no]', $employee_no_prefix, ['class' => 'form-control']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[employee_no]', $employee_no_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>

            {{--
                8030: Finance Module numbering.

                Cash Deposit, Cheque to Realize, Cheque Deposit, Card Deposit and
                Transfers each get their own series. The number is generated on
                save and shown in the description column of the account books and
                ledgers.

                Follows the same markup as every other prefix on this page, so the
                existing save handles it: BusinessController persists whatever is
                posted under ref_no_prefixes[] and ref_no_starting_number[], with
                no change needed there.

                Defaults are pre-filled (CAD, CHR, CHD, CDD, TFR) so numbering
                works immediately, and remain editable.
            --}}
            <div class="row">
                <div class="col-sm-12"><h3>Finance Module Numbering</h3></div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $finance_cash_deposit_prefix = !empty($business->ref_no_prefixes['finance_cash_deposit']) ? $business->ref_no_prefixes['finance_cash_deposit'] : 'CAD';
                    $finance_cash_deposit_starting_no = !empty($business->ref_no_starting_number['finance_cash_deposit']) ? $business->ref_no_starting_number['finance_cash_deposit'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[finance_cash_deposit]', 'Cash Deposit:') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[finance_cash_deposit]', $finance_cash_deposit_prefix, ['class' => 'form-control finance-numbering-prefix', 'data-prefix-key' => 'finance_cash_deposit']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[finance_cash_deposit]', $finance_cash_deposit_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $finance_cheque_realize_prefix = !empty($business->ref_no_prefixes['finance_cheque_realize']) ? $business->ref_no_prefixes['finance_cheque_realize'] : 'CHR';
                    $finance_cheque_realize_starting_no = !empty($business->ref_no_starting_number['finance_cheque_realize']) ? $business->ref_no_starting_number['finance_cheque_realize'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[finance_cheque_realize]', 'Cheque to Realize:') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[finance_cheque_realize]', $finance_cheque_realize_prefix, ['class' => 'form-control finance-numbering-prefix', 'data-prefix-key' => 'finance_cheque_realize']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[finance_cheque_realize]', $finance_cheque_realize_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $finance_cheque_deposit_prefix = !empty($business->ref_no_prefixes['finance_cheque_deposit']) ? $business->ref_no_prefixes['finance_cheque_deposit'] : 'CHD';
                    $finance_cheque_deposit_starting_no = !empty($business->ref_no_starting_number['finance_cheque_deposit']) ? $business->ref_no_starting_number['finance_cheque_deposit'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[finance_cheque_deposit]', 'Cheque Deposit:') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[finance_cheque_deposit]', $finance_cheque_deposit_prefix, ['class' => 'form-control finance-numbering-prefix', 'data-prefix-key' => 'finance_cheque_deposit']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[finance_cheque_deposit]', $finance_cheque_deposit_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $finance_card_deposit_prefix = !empty($business->ref_no_prefixes['finance_card_deposit']) ? $business->ref_no_prefixes['finance_card_deposit'] : 'CDD';
                    $finance_card_deposit_starting_no = !empty($business->ref_no_starting_number['finance_card_deposit']) ? $business->ref_no_starting_number['finance_card_deposit'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[finance_card_deposit]', 'Card Deposit:') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[finance_card_deposit]', $finance_card_deposit_prefix, ['class' => 'form-control finance-numbering-prefix', 'data-prefix-key' => 'finance_card_deposit']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[finance_card_deposit]', $finance_card_deposit_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $finance_transfer_prefix = !empty($business->ref_no_prefixes['finance_transfer']) ? $business->ref_no_prefixes['finance_transfer'] : 'TFR';
                    $finance_transfer_starting_no = !empty($business->ref_no_starting_number['finance_transfer']) ? $business->ref_no_starting_number['finance_transfer'] : '';
                @endphp
                {!! Form::label('ref_no_prefixes[finance_transfer]', 'Transfers:') !!}
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.prefix')
					</span>
                {!! Form::text('ref_no_prefixes[finance_transfer]', $finance_transfer_prefix, ['class' => 'form-control finance-numbering-prefix', 'data-prefix-key' => 'finance_transfer']); !!}
                </div>
                <div class="input-group">
					<span class="input-group-addon">
						@lang('lang_v1.starting_number')
					</span>
                {!! Form::text('ref_no_starting_number[finance_transfer]', $finance_transfer_starting_no, ['class' => 'form-control']); !!}
                </div>
            </div>
        </div>
            </div>
        
             
       

        
    </div>
</div>
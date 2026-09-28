@php
    use Carbon\Carbon;
    $colspan = 0;
    
    // Get font sizes from configuration with defaults
    $font_sizes = [
        'statement_title' => $customer_statement_report['statement_title_size'] ?? 20,
        'business_name' => $customer_statement_report['statement_title_size'] ?? 20, // Same as title
        'business_address' => $customer_statement_report['business_address_size'] ?? 16,
        'business_mobile' => $customer_statement_report['business_mobile_size'] ?? 16,
        'date_range' => $customer_statement_report['date_range_size'] ?? 16,
        'invoice_no' => $customer_statement_report['invoice_no_size'] ?? 16,
        'customer_label' => $customer_statement_report['customer_label_size'] ?? 16,
        'customer_name' => $customer_statement_report['customer_name_size'] ?? 16,
        'customer_address' => $customer_statement_report['customer_address_size'] ?? 14,
        'customer_email' => $customer_statement_report['customer_email_size'] ?? 14,
        'customer_mobile' => $customer_statement_report['customer_mobile_size'] ?? 14,
        'customer_tax' => $customer_statement_report['customer_tax_size'] ?? 14,
        'printed_on' => $customer_statement_report['printed_on_size'] ?? 14,
        'copy_label' => $customer_statement_report['copy_label_size'] ?? 20,
        'table_headers' => $customer_statement_report['date_size'] ?? 14, // Using date header size for all headers
        'table_data' => $customer_statement_report['table_data_size'] ?? 12,
        'beginning_balance_label' => $customer_statement_report['beginning_balance_size'] ?? 14,
        'beginning_balance_value' => $customer_statement_report['beginning_balance_value_size'] ?? 14,
        'balance_label' => $customer_statement_report['balance_label_size'] ?? 14,
        'balance_value' => $customer_statement_report['balance_value_size'] ?? 14,
        'statement_note' => $customer_statement_report['statement_note_size'] ?? 14,
        'signature' => $customer_statement_report['signature_size'] ?? 14,
        'total' => $customer_statement_report['total_size'] ?? 14,
    ];
@endphp


<style>
    .statement-payment-row > td {
        border-left: 0 !important;
        border-right: 0 !important;
    }
    .statement-payment-detail-cell {
        font-weight: 600;
        text-align: left;
        white-space: normal;
    }
</style>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div id="report_div">
                <div id="print_header_div">
                    <style>
                        @media print {
                            #report_print_div {-webkit-print-color-adjust: exact;}
                        }
                        .bg_color {
                            background: #8F3A84 !important;
                            font-size: {{ $font_sizes['customer_label'] }}px !important;
                            color: #fff !important;
                            print-color-adjust: exact;
                        }

                        .text-center {
                            text-align: center;
                        }

                        #customer_detail_table th {
                            background: #8F3A84 !important;
                            color: #fff !important;
                            print-color-adjust: exact;
                        }
                        
                        #customer_statement_table th {
                            background: #8F3A84 !important;
                            color: #fff !important;
                            print-color-adjust: exact;
                            font-size: {{ $font_sizes['table_headers'] }}px !important;
                        }

                        #customer_detail_table>tbody>tr:nth-child(2n+1)>td,
                        #customer_detail_table>tbody>tr:nth-child(2n+1)>th {
                            background-color: #F3BDEB !important;
                            print-color-adjust: exact;
                        }
                        .uppercase {
                          text-transform: uppercase;
                        }
                        
                        /* Font size styles */
                        .statement-title {
                            font-size: {{ $font_sizes['statement_title'] }}px !important;
                        }
                        .business-name {
                            font-size: {{ $font_sizes['business_name'] }}px !important;
                        }
                        .business-address {
                            font-size: {{ $font_sizes['business_address'] }}px !important;
                        }
                        .business-mobile {
                            font-size: {{ $font_sizes['business_mobile'] }}px !important;
                        }
                        .date-range {
                            font-size: {{ $font_sizes['date_range'] }}px !important;
                        }
                        .invoice-no {
                            font-size: {{ $font_sizes['invoice_no'] }}px !important;
                        }
                        .customer-name {
                            font-size: {{ $font_sizes['customer_name'] }}px !important;
                        }
                        .customer-address {
                            font-size: {{ $font_sizes['customer_address'] }}px !important;
                        }
                        .customer-email {
                            font-size: {{ $font_sizes['customer_email'] }}px !important;
                        }
                        .customer-mobile {
                            font-size: {{ $font_sizes['customer_mobile'] }}px !important;
                        }
                        .customer-tax {
                            font-size: {{ $font_sizes['customer_tax'] }}px !important;
                        }
                        .printed-on {
                            font-size: {{ $font_sizes['printed_on'] }}px !important;
                        }
                        .copy-label {
                            font-size: {{ $font_sizes['copy_label'] }}px !important;
                        }
                        .table-data {
                            font-size: {{ $font_sizes['table_data'] }}px !important;
                        }
                        .beginning-balance-label {
                            font-size: {{ $font_sizes['beginning_balance_label'] }}px !important;
                        }
                        .beginning-balance-value {
                            font-size: {{ $font_sizes['beginning_balance_value'] }}px !important;
                        }
                        .balance-label {
                            font-size: {{ $font_sizes['balance_label'] }}px !important;
                        }
                        .balance-value {
                            font-size: {{ $font_sizes['balance_value'] }}px !important;
                        }
                        .statement-note {
                            font-size: {{ $font_sizes['statement_note'] }}px !important;
                        }
                        .signature {
                            font-size: {{ $font_sizes['signature'] }}px !important;
                        }
                        .total {
                            font-size: {{ $font_sizes['total'] }}px !important;
                        }
                    </style>
                    @php
                    $currency_precision = !empty($business_details->currency_precision) ?
                    $business_details->currency_precision : 2;
                    @endphp
                    
                    @if(!empty($logo) && $logo->alignment == "Left")
                        <table style="width: 100%">
                            <tr>
                                @if(!empty($logo) && !empty($logo->logo))
                                <td width="10%">
                                    <img src="{{url($logo->logo)}}" class="img img-responsive center-block"
                                		height="75" width="75">
                                </td>
                                @endif
                                <td  class="text-center" width="90%">
                                    <p class="text-center uppercase">
                                        <strong class="statement-title">@lang('contact.customer_statement')<br>
                                        @if($logo->business_name == 1)
                                            <span class="business-name">{{$contact->business->name}}</span>
                                        @endif
                                        </strong><br>
                                        @if($logo->business_address == 1)
                                            <span class="business-address">{{$location_details->city}},
                                            {{$location_details->state}}</span>
                                        @endif
                                        <br>
                                        @if($logo->mobile_no == 1)
                                            <span class="business-mobile">{!! $location_details->mobile !!}</span>
                                        @endif    
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td  class="text-center" colspan="2">
                                    <p class="text-center date-range" style="color: #8F3A84 !important;print-color-adjust: exact;">
                                        <strong>@lang('contact.date_range_from') {{date('d M Y',strtotime($start_date))}} @lang('contact.to') {{date('d M Y',strtotime($end_date))}}</strong></p>
                                </td>
                            </tr>
                        </table>
                            
                        @elseif(!empty($logo) && $logo->alignment == "Right")
                            <table style="width: 100%">
                            <tr>
                                
                                <td  class="text-center" width="90%">
                                    <p class="text-center uppercase">
                                        <strong class="statement-title">@lang('contact.customer_statement')<br>
                                        @if($logo->business_name == 1)
                                            <span class="business-name">{{$contact->business->name}}</span>
                                        @endif
                                        </strong><br>
                                        @if($logo->business_address == 1)
                                            <span class="business-address">{{$location_details->city}},
                                            {{$location_details->state}}</span>
                                        @endif
                                        <br>
                                        @if($logo->mobile_no == 1)
                                            <span class="business-mobile">{!! $location_details->mobile !!}</span>
                                        @endif    
                                    </p>
                                </td>
                                
                                @if(!empty($logo) && !empty($logo->logo))
                                <td width="10%">
                                    <img src="{{url($logo->logo)}}" class="img img-responsive center-block"
                                		height="75" width="75">
                                </td>
                                @endif
                            </tr>
                            <tr>
                                <td  class="text-center" colspan="2">
                                    <p class="text-center date-range" style="color: #8F3A84 !important;print-color-adjust: exact;">
                                        <strong>@lang('contact.date_range_from') {{date('d M Y',strtotime($start_date))}} @lang('contact.to') {{date('d M Y',strtotime($end_date))}}</strong></p>
                                </td>
                            </tr>
                        </table>
                        @else
                            <table style="width: 100%">
                            @if(!empty($logo) && !empty($logo->logo))
                            <tr>
                                <td class="text-center" width="100%">
                                    <img src="{{url($logo->logo)}}" class="img img-responsive center-block"
                                		height="75" width="75">
                                </td>
                            </tr>
                            @endif
                            <tr>
                                <td  class="text-center" width="100%">
                                    <p class="text-center uppercase">
                                        <strong class="statement-title">@lang('contact.customer_statement')<br>
                                        @if($logo->business_name == 1)
                                            <span class="business-name">{{$contact->business->name}}</span>
                                        @endif
                                        </strong><br>
                                        @if($logo->business_address == 1)
                                            <span class="business-address">{{$location_details->city}},
                                            {{$location_details->state}}</span>
                                        @endif
                                        <br>
                                        @if($logo->mobile_no == 1)
                                            <span class="business-mobile">{!! $location_details->mobile !!}</span>
                                        @endif    
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td  class="text-center" colspan="2">
                                    <p class="text-center date-range" style="color: #8F3A84 !important;print-color-adjust: exact;">
                                        <strong>@lang('contact.date_range_from') {{date('d M Y',strtotime($start_date))}} @lang('contact.to') {{date('d M Y',strtotime($end_date))}}</strong></p>
                                </td>
                            </tr>
                        </table>
                        @endif
                        
                    <table style="width: 100%">
                        <tr>
                            <td>
                                <div class="col-md-6 col-sm-6 col-xs-6 @if(!empty($for_pdf)) width-50 f-left @endif" style="float: left">
                                    <h4 class="modal-title invoice-no" id="modalTitle"><b>@lang('lang_v1.invoice_no'):</b>
                                        {{ $statement->statement_no }}
                                    </h4>
                                    <p class="bg_color" style="width: 40%; margin-top: 5px;">@lang('contact.customer'):</p>
                                    <p><strong class="customer-name">{{$contact->name}}</strong>
                                    <!-- <br>  -->
                                        @if($logo->contact_no == 1)
                                            <span class="customer-address">{!! $contact->contact_address !!}</span>
                                        @endif
                                        @if($logo->email == 1)
                                            @if(!empty($contact->email))
                                                <br><span class="customer-email">@lang('business.email'): {{$contact->email}}</span>
                                            @endif
                                        @endif
                                        
                                        @if($logo->mobile_no == 1)
                                            <br><span class="customer-mobile">@lang('contact.mobile'): {{$contact->mobile}}</span>
                                        @endif
                                        
                                        @if(!empty($contact->tax_number)) 
                                            <br><span class="customer-tax">@lang('contact.tax_no'): {{$contact->tax_number}}</span>
                                        @endif
                                        <br>
                                        <strong class="printed-on">@lang('contact.printed_on'): </strong>{{date('d M Y H:m')}}
                                    </p>
                                </div>
                            </td>
                            
                            <td style="text-align: right; color: #FF0000 !important;print-color-adjust: exact;">
                                @if($reprint_no > 0) 
                                    <span class="copy-label">@lang('contact.copy') - {{$reprint_no}}</span>
                                @endif
                            </td>
                            
                        </tr>
                    </table>
                </div>
                
                <div class="row" style="margin-top: 0x;">
                    <div class="col-md-12">
                        <table class="table table-bordered table-striped" id="customer_statement_table">
                            <thead>
                                <tr>
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['date']))
                                        @php $colspan++ @endphp
                                        <th>@lang('contact.date')</th>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['location']))
                                        @php $colspan++ @endphp
                                        <th>@lang('contact.location')</th>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['invoice_no']))
                                        @php $colspan++ @endphp
                                        <th>@lang('contact.invoice_no')</th>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['route']))
                                        @php $colspan++ @endphp
                                        <th>@lang('contact.route')</th>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['vehicle']))
                                        @php $colspan++ @endphp
                                        <th>@lang('contact.vehicle')</th>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['customer_reference']))
                                        @php $colspan++ @endphp
                                        <th>@lang('contact.customer_reference')</th>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['customer_po']))
                                        @php $colspan++ @endphp
                                        <th>@lang('lang_v1.customer_po_no')</th>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['voucher_date']))
                                        @php $colspan++ @endphp
                                        <th>@lang('contact.voucher_order_date')</th>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['product']))
                                        @php $colspan++ @endphp
                                        <th>@lang('contact.product')</th>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['qty']))
                                        @php $colspan++ @endphp
                                        <th>@lang('contact.qty')</th>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['unit_price']))
                                        @php $colspan++ @endphp
                                        <th>@lang('contact.unit_price')</th>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['invoice_amount']))
                                        @php $colspan++ @endphp
                                        <th>@lang('contact.invoice_amount')</th>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['due_amount']))
                                        @php $colspan++ @endphp
                                        <th>@lang('contact.due_amount')</th>
                                    @endif
                                </tr>
                            </thead>

                             <tbody>
                                <tr>
                                    <td colspan="{{($colspan - 1)}}">
                                        <span class="beginning-balance-label">@lang('lang_v1.beginning_balance')</span>
                                    </td>
                                    <td>
                                        <span class="beginning-balance-value">{{@num_format($ledger_details['beginning_balance'])}}</span>
                                    </td>
                                </tr>
                                
                                @php
    $payment_date_column = (empty($customer_statement_report) || !empty($customer_statement_report['date'])) ? 1 : 0;
    $payment_amount_column = (empty($customer_statement_report) || !empty($customer_statement_report['invoice_amount'])) ? 1 : 0;
    $payment_due_column = (empty($customer_statement_report) || !empty($customer_statement_report['due_amount'])) ? 1 : 0;
    $payment_detail_colspan = max(1, $colspan - $payment_date_column - $payment_amount_column - $payment_due_column);
@endphp
@php $total = 0;$due = $ledger_details['beginning_balance']; @endphp
                                @foreach ($statement_details as $item)
                                    @php 
                                        if ($item->type == 'payment' || $item->type == 'customer_payment') {
                                            $amount = -$item->invoice_amount;
                                        } else {
                                            $amount = $item->invoice_amount;
                                        }
                                        
                                        $total += $amount; 
                                        $due += $amount;
                                    @endphp
                                <tr class="{{ in_array($item->type, ['payment','customer_payment']) ? 'statement-payment-row' : '' }}">
                                    @if($item->type == 'transaction')
                                        @if(empty($customer_statement_report) || !empty($customer_statement_report['date']))
                                            <td class="table-data">{{@format_date($item->date)}}</td>
                                        @endif
                                        
                                        @if(empty($customer_statement_report) || !empty($customer_statement_report['location']))
                                            <td class="table-data">{{$item->location}}</td>
                                        @endif
                                        
                                        @if(empty($customer_statement_report) || !empty($customer_statement_report['invoice_no']))
                                            <td class="table-data">{{$item->invoice_no}}</td>
                                        @endif
                                    
                                        @if(empty($customer_statement_report) || !empty($customer_statement_report['route']))
                                            <td class="table-data">{{$item->route_name}}</td>
                                        @endif
                                        
                                        @if(empty($customer_statement_report) || !empty($customer_statement_report['vehicle']))
                                            <td class="table-data">{{$item->vehicle_number}}</td>
                                        @endif
                                        
                                        @if(empty($customer_statement_report) || !empty($customer_statement_report['customer_reference']))
                                            <td class="table-data customer-reference">{{$item->customer_reference}}</td>
                                        @endif
                                        
                                        @if(empty($customer_statement_report) || !empty($customer_statement_report['customer_po']))
                                            <td class="table-data" style="width: calc(55% / {{ $colspan }});">{{$item->order_no}}</td>
                                        @endif
                                        
                                        @if(empty($customer_statement_report) || !empty($customer_statement_report['voucher_date']))
                                            <td class="table-data" style="width: calc(50% / {{ $colspan }});">{{ \Carbon\Carbon::parse($item->order_date)->format('Y-m-d') }}</td>
                                        @endif
                                        
                                        @if(empty($customer_statement_report) || !empty($customer_statement_report['product']))
                                            <td class="table-data">{{$item->product}}</td>
                                        @endif
                                        
                                        @if(empty($customer_statement_report) || !empty($customer_statement_report['qty']))
                                            <td class="table-data" style="width: calc(110% / {{ $colspan }});">{{@format_quantity($item->qty)}}</td>
                                        @endif
                                        
                                        @if(empty($customer_statement_report) || !empty($customer_statement_report['unit_price']))
                                            <td class="table-data">{{@num_format($item->unit_price)}}</td>
                                        @endif
                                    @else
                                        @if(empty($customer_statement_report) || !empty($customer_statement_report['date']))
                                            <td class="table-data">{{@format_date($item->date)}}</td>
                                        @endif
                                       <td colspan="{{ $payment_detail_colspan }}" class="table-data statement-payment-detail-cell" style="border-left:0 !important;border-right:0 !important;">
                                        {{ $item->payment_description ?? __('contact.payment') }}
                			            @if($item->type == 'customer_payment')
                			                {{__('contact.ref_no')." ".$item->invoice_no;}}
                			            @endif
                                        
                                       </td>
                                        
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['invoice_amount']))
                                        <td class="table-data" style="width: calc(90% / {{ $colspan }});">{{@num_format($amount)}}</td>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['due_amount']))
                                        <td class="table-data" style="width: calc(90% / {{ $colspan }});">{{@num_format($due)}}</td>
                                    @endif
                                
                                </tr>
                                @endforeach
                                <tr>
                                    <th colspan="{{empty($customer_statement_report) ? 11 : $colspan-2}}"></th>
                                    <th class="balance-label">@lang('contact.balance')</th>
                                    <th class="balance-value">{{@num_format($due)}}</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <hr>
                
                @if(!empty($logo) && !empty($logo->statement_note) && $logo->text_position == 'above')
                    <div class="col-xs-12 text-center">
                        <p class="statement-note">{{$logo->statement_note}}</p>
                    </div>
                @endif
                
                 <div class="row" style="height: 100px !important;"></div>
                
                <table width="100%">
                    <tr>
                        <th class="width-50">
                            <strong class="signature">@lang('contact.signature') :...............................................</strong>
                        </th>
                        <th  class="width-50">
                            <strong class="total">@lang('contact.total'): {{@num_format($total)}}</strong>
                        </th>
                    </tr>
                </table>
                
                 <div class="row" style="height: 100px !important;"></div>
                
                @if(!empty($logo) && !empty($logo->statement_note) && $logo->text_position == 'below')
                    <div class="col-xs-12 text-center">
                        <p class="statement-note">{{$logo->statement_note}}</p>
                    </div>
                @endif
                
            </div>
        </div>
    </div>
    
    @if(!empty($reports_footer))
        <style>
            #footer {
                display: none;
                margin-top: 50px !important;
            }
        
            @media print {
                #footer {
                    display: block !important;
                    position: fixed;
                    bottom: -1mm;
                    width: 100%;
                    text-align: center;
                    font-size: 12px;
                    color: #333;
                }
            }
        </style>

        <div id="footer">
            {{ ($reports_footer->value) }}
        </div>
    @endif

</section>
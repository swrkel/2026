@php
    $customer_statement_report = $customer_statement_report ?? [];
    $colspan = 0;
    
    // Get font sizes from configuration with defaults
    $font_sizes = [
        'statement_title' => $customer_statement_report['statement_title_size'] ?? 20,
        'business_name' => $customer_statement_report['statement_title_size'] ?? 20,
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
        'table_headers' => $customer_statement_report['date_size'] ?? 14,
        'table_data' => $customer_statement_report['table_data_size'] ?? 12,
        'beginning_balance_label' => $customer_statement_report['beginning_balance_size'] ?? 14,
        'beginning_balance_value' => $customer_statement_report['beginning_balance_value_size'] ?? 14,
        'balance_label' => $customer_statement_report['balance_label_size'] ?? 14,
        'balance_value' => $customer_statement_report['balance_value_size'] ?? 14,
        'statement_note' => $customer_statement_report['statement_note_size'] ?? 14,
        'signature' => $customer_statement_report['signature_size'] ?? 14,
        'total' => $customer_statement_report['total_size'] ?? 14,
    ];

    $summaryColumns = [
        'date',
        'location',
        'invoice_no',
        'route',
        'vehicle',
        'customer_reference',
        'customer_po',
        'voucher_date',
        'product',
        'qty',
        'unit_price',
        'invoice_amount',
        'due_amount',
    ];
    $visibleSummaryColumns = array_values(array_filter($summaryColumns, function ($key) use ($customer_statement_report) {
        return empty($customer_statement_report) || !empty($customer_statement_report[$key]);
    }));
    $showInvoiceAmount = in_array('invoice_amount', $visibleSummaryColumns, true);
    $showDueAmount = in_array('due_amount', $visibleSummaryColumns, true);
    $footerLabelColspan = count($visibleSummaryColumns) - (($showInvoiceAmount ? 1 : 0) + ($showDueAmount ? 1 : 0));
@endphp

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
                            font-size: {{ $font_sizes['customer_label'] }}px;
                            color: #000 !important;
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
                            color: #000 !important;
                            print-color-adjust: exact;
                            font-size: {{ $font_sizes['table_headers'] }}px;
                        }
                        
                        #customer_statement_table td {
                            font-size: {{ $font_sizes['table_data'] }}px;
                        }

                        #customer_detail_table>tbody>tr:nth-child(2n+1)>td,
                        #customer_detail_table>tbody>tr:nth-child(2n+1)>th {
                            background-color: #F3BDEB !important;
                            print-color-adjust: exact;
                        }
                        .uppercase {
                          text-transform: uppercase;
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
                                        <strong style="font-size: {{ $font_sizes['statement_title'] }}px;">@lang('contact.customer_statement')
                                        @if($logo->business_name == 1)
                                            <br><span style="font-size: {{ $font_sizes['business_name'] }}px;">{{$contact->business->name}}</span><br>
                                        @endif
                                        </strong>
                                        @if($logo->business_address == 1)
                                            <span style="font-size: {{ $font_sizes['business_address'] }}px;">{{$location_details->city}},
                                            {{$location_details->state}}</span><br>
                                        @endif
                                        
                                        @if($logo->mobile_no == 1)
                                            <span style="font-size: {{ $font_sizes['business_mobile'] }}px;">{!! $location_details->mobile !!}</span>
                                        @endif    
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td  class="text-center" colspan="2">
                                    <p class="text-center" style="color: #8F3A84 !important;print-color-adjust: exact; font-size: {{ $font_sizes['date_range'] }}px;">
                                        <strong>@lang('contact.date_range_from') {{date('d M Y',strtotime($start_date))}} @lang('contact.to') {{date('d M Y',strtotime($end_date))}}</strong></p>
                                </td>
                            </tr>
                        </table>
                            
                    @elseif(!empty($logo) && $logo->alignment == "Right")
                        <table style="width: 100%">
                        <tr>
                            
                            <td  class="text-center" width="90%">
                                <p class="text-center uppercase">
                                    <strong style="font-size: {{ $font_sizes['statement_title'] }}px;">@lang('contact.customer_statement')
                                    @if($logo->business_name == 1)
                                        <br><span style="font-size: {{ $font_sizes['business_name'] }}px;">{{$contact->business->name}}</span><br>
                                    @endif
                                    </strong>
                                    @if($logo->business_address == 1)
                                        <span style="font-size: {{ $font_sizes['business_address'] }}px;">{{$location_details->city}},
                                        {{$location_details->state}}</span><br>
                                    @endif
                                    
                                    @if($logo->mobile_no == 1)
                                        <span style="font-size: {{ $font_sizes['business_mobile'] }}px;">{!! $location_details->mobile !!}</span>
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
                                <p class="text-center" style="color: #8F3A84 !important;print-color-adjust: exact; font-size: {{ $font_sizes['date_range'] }}px;">
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
                                    <strong style="font-size: {{ $font_sizes['statement_title'] }}px;">@lang('contact.customer_statement')
                                    @if(!empty($logo) && $logo->business_name == 1)
                                        <br><span style="font-size: {{ $font_sizes['business_name'] }}px;">{{$contact->business->name}}</span><br>
                                    @endif
                                    </strong>
                                    @if(!empty($logo) && $logo->business_address == 1)
                                        <span style="font-size: {{ $font_sizes['business_address'] }}px;">{{$location_details->city}},
                                        {{$location_details->state}}</span><br>
                                    @endif
                                    
                                    @if(!empty($logo) && $logo->mobile_no == 1)
                                        <span style="font-size: {{ $font_sizes['business_mobile'] }}px;">{!! $location_details->mobile !!}</span>
                                    @endif     
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td  class="text-center" colspan="2">
                                <p class="text-center" style="color: #8F3A84 !important;print-color-adjust: exact; font-size: {{ $font_sizes['date_range'] }}px;">
                                    <strong>@lang('contact.date_range_from') {{date('d M Y',strtotime($start_date))}} @lang('contact.to') {{date('d M Y',strtotime($end_date))}}</strong></p>
                            </td>
                        </tr>
                    </table>
                    @endif
                    
                        
                    
                    
                    <table style="width: 100%">
                        <tr>
                            
                            <td>
                                <div class="col-md-6 col-sm-6 col-xs-6 @if(!empty($for_pdf)) width-50 f-left @endif" style="float: left">
                                    <h4 class="modal-title" id="modalTitle" style="font-size: {{ $font_sizes['invoice_no'] }}px;"><b>@lang('lang_v1.invoice_no'):</b>
                                        {{ $statement->statement_no }}
                                    </h4>
                                    <p class="bg_color" style="width: 40%; margin-top: 20px;">Customer:</p>
                                    <p>
                                        <strong style="font-size: {{ $font_sizes['customer_name'] }}px;">{{$contact->name}}</strong><br> 
                                        @if($logo->contact_no == 1)
                                            <span style="font-size: {{ $font_sizes['customer_address'] }}px;">{!! $contact->contact_address !!}</span>
                                        @endif
                                        @if($logo->email == 1)
                                            @if(!empty($contact->email))
                                                <br><span style="font-size: {{ $font_sizes['customer_email'] }}px;">@lang('business.email'): {{$contact->email}}</span> 
                                            @endif
                                        @endif
                                        
                                        @if($logo->mobile_no == 1)
                                            <br><span style="font-size: {{ $font_sizes['customer_mobile'] }}px;">@lang('contact.mobile'): {{$contact->mobile}}</span>
                                        @endif
                                        
                                        @if(!empty($contact->tax_number)) 
                                            <br><span style="font-size: {{ $font_sizes['customer_tax'] }}px;">@lang('contact.tax_no'): {{$contact->tax_number}}</span>
                                        @endif
                                        <br>
                                        <strong style="font-size: {{ $font_sizes['printed_on'] }}px;">@lang('contact.printed_on'): </strong><span style="font-size: {{ $font_sizes['printed_on'] }}px;">{{date('d M Y H:m')}}</span>
                                    </p>
                                </div>
                            </td>
                            
                            <td style="text-align: right; font-size: {{ $font_sizes['copy_label'] }}px; color: #FF0000 !important;print-color-adjust: exact;">
                                @if($reprint_no > 0) @lang('contact.copy') - {{$reprint_no}} @endif
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
                                @php $total = 0; @endphp
                                @foreach ($statement_details as $item)
                                    @php $total += $item->invoice_amount; @endphp
                                <tr>
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['date']))
                                        <td>{{@format_date($item->date)}}</td>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['location']))
                                        <td>{{$item->location}}</td>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['invoice_no']))
                                        <td>{{$item->invoice_no}}</td>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['route']))
                                        <td>{{$item->route_name}}</td>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['vehicle']))
                                        <td>{{$item->vehicle_number}}</td>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['customer_reference']))
                                        <td>{{$item->customer_reference}}</td>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['customer_po']))
                                        <td>{{$item->order_no}}</td>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['voucher_date']))
                                        <td>{{$item->order_date}}</td>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['product']))
                                        <td>{{$item->product}}</td>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['qty']))
                                        <td>{{@format_quantity($item->qty)}}</td>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['unit_price']))
                                        <td>{{@num_format($item->unit_price)}}</td>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['invoice_amount']))
                                        <td>{{@num_format($item->invoice_amount)}}</td>
                                    @endif
                                    
                                    @if(empty($customer_statement_report) || !empty($customer_statement_report['due_amount']))
                                        <td>{{@num_format($item->due_amount)}}</td>
                                    @endif
                                
                                </tr>
                                @endforeach
                            </tbody>
                             <tfoot>
                                <tr>
                                    @if($showInvoiceAmount || $showDueAmount)
                                        <th colspan="{{ max(1, $footerLabelColspan) }}" style="font-size: {{ $font_sizes['total'] }}px;">@lang('contact.total')</th>
                                        @if($showInvoiceAmount)
                                            <th style="font-size: {{ $font_sizes['total'] }}px;">{{@num_format($total)}}</th>
                                        @endif
                                        @if($showDueAmount)
                                            <th style="font-size: {{ $font_sizes['total'] }}px;">{{@num_format($total_balance_due ?? 0)}}</th>
                                        @endif
                                    @else
                                        <th colspan="{{ max(1, count($visibleSummaryColumns)) }}" style="font-size: {{ $font_sizes['total'] }}px;">
                                            @lang('contact.total'): {{@num_format($total)}}
                                        </th>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <hr>
                
                @if(!empty($logo) && !empty($logo->statement_note) && $logo->text_position == 'above')
                    <div class="col-xs-12 text-center">
                        <p style="font-size: {{ $font_sizes['statement_note'] }}px;">{{$logo->statement_note}}</p>
                    </div>
                @endif
                
                <div class="row" style="height: 100px !important;"></div>
                
                <table width="100%">
                    <tr>
                        <th class="width-50">
                            <strong style="font-size: {{ $font_sizes['signature'] }}px;">@lang('contact.signature') :...............................................</strong>
                        </th>
                        <th  class="width-50">
                            <strong style="font-size: {{ $font_sizes['total'] }}px;">@lang('contact.total'): {{@num_format($total)}}</strong>
                        </th>
                    </tr>
                </table>
                
                 <div class="row" style="height: 100px !important;"></div>
                
                @if(!empty($logo) && !empty($logo->statement_note) && $logo->text_position == 'below')
                    <div class="col-xs-12 text-center">
                        <p style="font-size: {{ $font_sizes['statement_note'] }}px;">{{$logo->statement_note}}</p>
                    </div>
                @endif
                
                <div class="row" style="height: 100px !important;"></div>
                
               
                
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

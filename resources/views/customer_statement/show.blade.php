@php
    use App\ReportConfiguration;
    $business_id = request()->session()->get('user.business_id');
    $customer_statement = ReportConfiguration::where('business_id',$business_id)->where('name','customer_statement_report')->first();
    $customer_statement_report = !empty($customer_statement) ? json_decode($customer_statement->configurations,true) : [];
    $colspan = 0;
    $paid_customer_statement = \App\TransactionPayment::where('linked_customer_statement',$id)->count();
    
    $pacakge_details = [];
    $subscription = Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
    if (!empty($subscription)) {
        $pacakge_details = $subscription->package_details;
    }
	
	$columnMap = [
		0 => 'date',
		1 => 'invoice_no',
		2 => 'route',
		3 => 'vehicle',
		4 => 'customer_reference',
		5 => 'customer_po',
		6 => 'voucher_date',
		7 => 'product',
		8 => 'qty',
		9 => 'unit_price',
		10 => 'invoice_amount',
		11 => 'due_amount',
	];
	
	$columns = [
		'date' => __('contact.date'),
		'invoice_no' => __('contact.invoice_no'),
		'route' => __('contact.route'),
		'vehicle' => __('contact.vehicle'),
		'customer_reference' => __('contact.customer_reference'),
		'customer_po' => __('lang_v1.customer_po_no'),
		'voucher_date' => __('contact.voucher_order_date'),
		'product' => __('contact.product'),
		'qty' => __('contact.qty'),
		'unit_price' => __('contact.unit_price'),
		'invoice_amount' => __('contact.invoice_amount'),
		'due_amount' => __('contact.due_amount'),
	];
	
	$selectedCols = [];
	if(!empty($visibleCols)) {
		foreach($visibleCols as $index) {
			if(isset($columnMap[$index])) {
				$selectedCols[$columnMap[$index]] = true;
			}
		}
	}

	// If column visibility indices are passed from list screen, respect them for modal/print/export.
	if (!empty($selectedCols)) {
		foreach (array_keys($columns) as $columnKey) {
			$customer_statement_report[$columnKey] = !empty($selectedCols[$columnKey]) ? 1 : 0;
		}
	}

	$visibleSummaryColumns = array_values(array_filter(array_keys($columns), function ($key) use ($customer_statement_report) {
		return empty($customer_statement_report) || !empty($customer_statement_report[$key]);
	}));
	$showInvoiceAmount = in_array('invoice_amount', $visibleSummaryColumns, true);
	$showDueAmount = in_array('due_amount', $visibleSummaryColumns, true);
	$footerLabelColspan = count($visibleSummaryColumns) - (($showInvoiceAmount ? 1 : 0) + ($showDueAmount ? 1 : 0));
@endphp

<div class="modal-dialog modal-xl no-print" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-12">

                    <style>
                        @media print {

                            .dt-buttons,
                            .dataTables_length,
                            .dataTables_filter,
                            .dataTables_info,
                            .dataTables_paginate {
                                display: none;
                            }

                            .customer_details_div {
                                display: none;
                            }
                        }
                    </style>
                    <div class="col-md-12">
                        <style>
                            .bg_color {
                                background: #357ca5;
                                font-size: 20px;
                                color: #fff;
                            }

                            .text-center {
                                text-align: center;
                            }

                            #customer_detail_table th {
                                background: #357ca5;
                                color: #fff;
                            }

                            #customer_detail_table>tbody>tr:nth-child(2n+1)>td,
                            #customer_detail_table>tbody>tr:nth-child(2n+1)>th {
                                background-color: rgba(89, 129, 255, 0.3);
                            }
                        </style>
                        
                        @if($paid_customer_statement > 0)
                            @if(auth()->user()->can('contact.delete_customer_statement') && (!empty($pacakge_details['contact.delete_customer_statement']) || !array_key_exists('contact.delete_customer_statement',$pacakge_details)))
                                <a data-href="{{action('CustomerStatementController@destroyPayments', [$id])}}" class="delete_customer_statement btn btn-danger pull-right"><i class="fa fa-trash"></i>{{ __("lang_v1.delete_payments") }}</a>
                            @endif
                        @endif

                        @php
                        $currency_precision = !empty($business_details->currency_precision) ?
                        $business_details->currency_precision : 2;
                        @endphp
                        
                        @if(!empty($logo) && $logo->alignment == "Left")
                            <div class="row">
                                @if(!empty($logo) && !empty($logo->logo))
                                <div class="col-md-1">
                                    <img src="{{url($logo->logo)}}" class="img img-responsive center-block"
                                		height="100" width="100">
                                </div>
                                @endif
                                <div class="col-md-11 col-sm-11 @if(!empty($for_pdf)) text-center @endif">
                                    <p class="text-center">
                                        <strong>{{$contact->business->name}}</strong><br>{{$location_details->city}},
                                        {{$location_details->state}}<br>{!!
                                        $location_details->mobile !!}</p>
                                    <hr>
                                </div>
                            </div>
                        @elseif(!empty($logo) && $logo->alignment == "Right")
                            <div class="row">
                                
                                <div class="col-md-11 col-sm-11 @if(!empty($for_pdf)) text-center @endif">
                                    <p class="text-center">
                                        <strong>{{$contact->business->name}}</strong><br>{{$location_details->city}},
                                        {{$location_details->state}}<br>{!!
                                        $location_details->mobile !!}</p>
                                    <hr>
                                </div>
                                
                                @if(!empty($logo) && !empty($logo->logo))
                                <div class="col-md-1">
                                    <img src="{{url($logo->logo)}}" class="img img-responsive center-block"
                                		height="100" width="100">
                                </div>
                                @endif
                            </div>
                        @else
                        
                            <div class="row">
                                
                                @if(!empty($logo) && !empty($logo->logo))
                                <div class="col-md-12">
                                    <img src="{{url($logo->logo)}}" class="img img-responsive center-block"
                                		height="100" width="100">
                                </div>
                                @endif
                                
                                <div class="col-md-12 col-sm-12 @if(!empty($for_pdf)) text-center @endif">
                                    <p class="text-center">
                                        <strong>{{$contact->business->name}}</strong><br>{{$location_details->city}},
                                        {{$location_details->state}}<br>{!!
                                        $location_details->mobile !!}</p>
                                    <hr>
                                </div>
                                
                            </div>
                        
                        @endif

                        <div class="col-md-6 col-sm-6 col-xs-6 @if(!empty($for_pdf)) width-50 f-left @endif">
                            <h4 class="modal-title" id="modalTitle"><b>@lang('lang_v1.invoice_no'):</b>
                                {{ $statement->statement_no }}
                            </h4>
                            <p class="bg_color" style="width: 40%; margin-top: 20px;">@lang('lang_v1.to'):</p>
                            <p><strong>{{$contact->name}} </strong><br> {!! $contact->contact_address !!}
                                @if(!empty($contact->email))
                                <br>@lang('business.email'): {{$contact->email}} @endif
                                <br>@lang('contact.mobile'): {{$contact->mobile}}
                                @if(!empty($contact->tax_number)) <br>@lang('contact.tax_no'): {{$contact->tax_number}}
                                @endif
                            </p>
                        </div>
                       
                    </div>

                    <div class="row" style="margin-top: 20px;">
                        <div class="col-md-12">
                            <table class="table table-bordered table-striped" id="customer_statement_table">
                            <thead>
                                <tr>
									@php $colspan = 0; @endphp
									@foreach($columns as $key => $label)
										@if(empty($customer_statement_report) || !empty($customer_statement_report[$key]))
											@php $colspan++ @endphp
											<th>{{ $label }}</th>
										@endif
									@endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @php $total = 0; @endphp
                                @foreach ($statement_details as $item)
                                    @php $total += $item->invoice_amount; @endphp
                                <tr>
                                    @foreach($columns as $key => $label)
										@if(empty($customer_statement_report) || !empty($customer_statement_report[$key]))
											@if($key == 'due_amount')
												<td>{{ @num_format($item->balance_due ?? 0) }}</td>
											@elseif($key == 'route')
												<td>{{ $item->route_name ?? '' }}</td>
											@elseif($key == 'vehicle')
												<td>{{ $item->vehicle_number ?? '' }}</td>
											@elseif($key == 'customer_po')
												<td>{{ $item->order_no ?? '' }}</td>
											@elseif($key == 'voucher_date')
												<td>{{ $item->order_date ?? '' }}</td>
											@elseif(in_array($key, ['qty', 'unit_price', 'invoice_amount']))
												<td>{{ @num_format($item->$key ?? 0) }}</td>
											@else
												<td>{{ $item->$key ?? '' }}</td>
											@endif
										@endif
									@endforeach
                                
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    @if($showInvoiceAmount || $showDueAmount)
                                        <th colspan="{{ max(1, $footerLabelColspan) }}">@lang('contact.total')</th>
                                        @if($showInvoiceAmount)
                                            <th>{{@num_format($total)}}</th>
                                        @endif
                                        @if($showDueAmount)
                                            <th>{{@num_format($total_balance_due ?? 0)}}</th>
                                        @endif
                                    @else
                                        <th colspan="{{ max(1, count($visibleSummaryColumns)) }}">
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
                            <p>{{$logo->statement_note}}</p>
                        </div>
                    @endif
                    
                    <table width="100%" style="margin-top: 30px; ">
                        <tr>
                            <th class="width-50">
                                <strong>@lang('contact.signature') :...............................................</strong>
                            </th>
                            <th  class="width-50">
                                @lang('contact.total'): {{@num_format($total)}}<br>
                                <span style="color: red;"><strong>Balance Due: {{@num_format($total_balance_due ?? 0)}}</strong></span>
                            </th>
                        </tr>
                    </table>
                    
                    @if(!empty($logo) && !empty($logo->statement_note) && $logo->text_position == 'below')
                        <div class="col-xs-12 text-center">
                            <p>{{$logo->statement_note}}</p>
                        </div>
                    @endif
                

                </div>
            </div>
        </div>
    </div>
</div>

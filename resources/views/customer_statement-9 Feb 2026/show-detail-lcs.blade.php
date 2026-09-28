@php
	$columnMap = [
		1 => 'date_printed',
		2 => 'date_from',
		3 => 'date_to',
		4 => 'customer',
		5 => 'statement_no',
		6 => 'statement_amount',
		7 => 'payment_status',
		8 => 'added_by',
		9 => 'description',
	];
	
	$columns = [
		'date_printed' => __('contact.date_printed'),
		'date_from' => __('contact.date_from'),
		'date_to' => __('contact.date_to'),
		'customer' => __('contact.customer'),
		'statement_no' => __('contact.statement_no'),
		'statement_amount' => __('contact.statement_amount'),
		'payment_status' => __('contact.payment_status'),
		'added_by' => __('contact.added_by'),
		'description' => __('contact.description')
	];
	
	$selectedCols = [];
	if(!empty($visibleCols)) {
		foreach($visibleCols as $index) {
			if(isset($columnMap[$index])) {
				$selectedCols[$columnMap[$index]] = true;
			}
		}
	}
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
                                {{ $row['statement_no'] }}
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
										@if(empty($selectedCols) || !empty($selectedCols[$key]))
											@php $colspan++ @endphp
											<th>{{ $label }}</th>
										@endif
									@endforeach
                                </tr>
                            </thead>

                            <tbody>
                                <tr>
									@foreach($columns as $key => $label)
										@if(empty($selectedCols) || !empty($selectedCols[$key]))
											<td>{!! $row[$key] ?? '' !!}</td>
										@endif
									@endforeach
								</tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    
                    <hr>

                    

                    {{-- حماية: لو ما وصل المتغيّر لأي سبب، خليه Collection فاضي --}}
@php $billLines = isset($billLines) ? collect($billLines) : collect(); @endphp

{{-- Bill-wise details --}}
<h5 class="mt-3" style="margin-left:24px; margin-bottom:5px;">Bill-wise Details</h5>

<table class="table table-sm table-bordered">
  <thead>
    <tr>
      <th>#</th>
      <th>Invoice No</th>
      <th>Date</th>
      <th>Description</th>
      <th class="text-end">Net</th>
      <th class="text-end">Paid</th>
      <th class="text-end">Due</th>
    </tr>
  </thead>

  <tbody>
    @php $i=1; $totalNet=0; $totalPaid=0; $totalDue=0; @endphp

    @forelse($billLines as $b)
      @php
        $totalNet  += (float) $b['net'];
        $totalPaid += (float) $b['paid'];
        $totalDue  += (float) $b['due'];
      @endphp
      <tr>
        <td>{{ $i++ }}</td>
        <td>{{ $b['invoice_no'] }}</td>
        <td>{{ $b['date'] ?? '-' }}</td>
        <td>{{ $b['note'] ?: '-' }}</td>
        <td class="text-end">
            <span class="display_currency" data-currency_symbol="true">{{ $b['net'] }}</span>
        </td>
        <td class="text-end">
            <span class="display_currency" data-currency_symbol="true">{{ $b['paid'] }}</span>
        </td>
        <td class="text-end">
            <span class="display_currency" data-currency_symbol="true">{{ $b['due'] }}</span>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="7" class="text-center text-muted">No bill details for this statement.</td>
      </tr>
    @endforelse
  </tbody>

  @if($billLines->count())
  <tfoot>
    <tr>
      <th colspan="4" class="text-end">Totals:</th>
      <th class="text-end"><span class="display_currency" data-currency_symbol="true">{{ $totalNet }}</span></th>
      <th class="text-end"><span class="display_currency" data-currency_symbol="true">{{ $totalPaid }}</span></th>
      <th class="text-end"><span class="display_currency" data-currency_symbol="true">{{ $totalDue }}</span></th>
    </tr>
  </tfoot>
  @endif
</table>
                    
                    <table width="100%" style="margin-top: 30px; ">
                        <tr>
                            <th class="width-50">
                                <strong>@lang('contact.signature') :...............................................</strong>
                            </th>
                        </tr>
                    </table>





                    
                </div>
            </div>
        </div>
    </div>
</div>




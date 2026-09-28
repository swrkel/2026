@php
    use App\ReportConfiguration;
    $business_id = request()->session()->get('user.business_id');
    $customer_statement = ReportConfiguration::where('business_id',$business_id)->where('name','customer_statement_report')->first();
    $customer_statement_report = !empty($customer_statement) ? json_decode($customer_statement->configurations,true) : [];
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
@endphp

@php
$currency_precision = !empty($business_details->currency_precision) ?
$business_details->currency_precision : 2;
@endphp

<table>
    <tr>
        <td style="font-size: {{ $font_sizes['customer_name'] }}px;">
            {{$contact->name}}
        </td>
    </tr>
</table>
<table>
    <thead>
        <tr>
            @if(empty($customer_statement_report) || !empty($customer_statement_report['date']))
                @php $colspan++ @endphp
                <th style="font-size: {{ $font_sizes['table_headers'] }}px;">@lang('contact.date')</th>
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['location']))
                @php $colspan++ @endphp
                <th style="font-size: {{ $font_sizes['table_headers'] }}px;">@lang('contact.location')</th>
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['invoice_no']))
                @php $colspan++ @endphp
                <th style="font-size: {{ $font_sizes['table_headers'] }}px;">@lang('contact.invoice_no')</th>
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['route']))
                @php $colspan++ @endphp
                <th style="font-size: {{ $font_sizes['table_headers'] }}px;">@lang('contact.route')</th>
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['vehicle']))
                @php $colspan++ @endphp
                <th style="font-size: {{ $font_sizes['table_headers'] }}px;">@lang('contact.vehicle')</th>
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['customer_reference']))
                @php $colspan++ @endphp
                <th style="font-size: {{ $font_sizes['table_headers'] }}px;">@lang('contact.customer_reference')</th>
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['customer_po']))
                @php $colspan++ @endphp
                <th style="font-size: {{ $font_sizes['table_headers'] }}px;">@lang('lang_v1.customer_po_no')</th>
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['voucher_date']))
                @php $colspan++ @endphp
                <th style="font-size: {{ $font_sizes['table_headers'] }}px;">@lang('contact.voucher_order_date')</th>
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['product']))
                @php $colspan++ @endphp
                <th style="font-size: {{ $font_sizes['table_headers'] }}px;">@lang('contact.product')</th>
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['qty']))
                @php $colspan++ @endphp
                <th style="font-size: {{ $font_sizes['table_headers'] }}px;">@lang('contact.qty')</th>
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['unit_price']))
                @php $colspan++ @endphp
                <th style="font-size: {{ $font_sizes['table_headers'] }}px;">@lang('contact.unit_price')</th>
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['invoice_amount']))
                @php $colspan++ @endphp
                <th style="font-size: {{ $font_sizes['table_headers'] }}px;">@lang('contact.invoice_amount')</th>
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['due_amount']))
                @php $colspan++ @endphp
                <th style="font-size: {{ $font_sizes['table_headers'] }}px;">@lang('contact.due_amount')</th>
            @endif
        </tr>
    </thead>

     <tbody>
        <tr>
            <td colspan="{{($colspan - 1)}}" style="font-size: {{ $font_sizes['beginning_balance_label'] }}px;">@lang('lang_v1.beginning_balance')</td>
            <td style="font-size: {{ $font_sizes['beginning_balance_value'] }}px;">{{@num_format($ledger_details['beginning_balance'])}}
            </td>
        </tr>
        
        @php
            use Modules\Petro\Entities\CustomerPayment;
            use App\TransactionPayment;
            use App\Transaction;
        @endphp
        @php $total = 0;$due = $ledger_details['beginning_balance']; @endphp
        @foreach ($statement_details as $item)
            @php 
                if($item->type == 'payment' || $item->type == 'customer_payment'){
                    $amount = -$item->invoice_amount;
                    
                    if($item->type == 'payment'){
                        $pmt_methods = TransactionPayment::leftjoin('accounts','transaction_payments.account_id','accounts.id')->where('transaction_payments.id',$item->statement_id)->select(['transaction_payments.*','accounts.name as account_name'])->first();
                    }
                }else{
                    $amount = $item->invoice_amount;
                }
                
                $total += $amount; 
                $due += $amount;
            @endphp
        <tr>
            @if($item->type == 'transaction')
                @if(empty($customer_statement_report) || !empty($customer_statement_report['date']))
                    <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{@format_date($item->date)}}</td>
                @endif
                
                @if(empty($customer_statement_report) || !empty($customer_statement_report['location']))
                    <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{$item->location}}</td>
                @endif
                
                @if(empty($customer_statement_report) || !empty($customer_statement_report['invoice_no']))
                    <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{$item->invoice_no}}</td>
                @endif
            
                @if(empty($customer_statement_report) || !empty($customer_statement_report['route']))
                    <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{$item->route_name}}</td>
                @endif
                
                @if(empty($customer_statement_report) || !empty($customer_statement_report['vehicle']))
                    <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{$item->vehicle_number}}</td>
                @endif
                
                @if(empty($customer_statement_report) || !empty($customer_statement_report['customer_reference']))
                    <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{$item->customer_reference}}</td>
                @endif
                
                @if(empty($customer_statement_report) || !empty($customer_statement_report['customer_po']))
                    <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{$item->order_no}}</td>
                @endif
                
                @if(empty($customer_statement_report) || !empty($customer_statement_report['voucher_date']))
                    <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{$item->order_date}}</td>
                @endif
                
                @if(empty($customer_statement_report) || !empty($customer_statement_report['product']))
                    <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{$item->product}}</td>
                @endif
                
                @if(empty($customer_statement_report) || !empty($customer_statement_report['qty']))
                    <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{@format_quantity($item->qty)}}</td>
                @endif
                
                @if(empty($customer_statement_report) || !empty($customer_statement_report['unit_price']))
                    <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{@num_format($item->unit_price)}}</td>
                @endif
            @else
                @if(empty($customer_statement_report) || !empty($customer_statement_report['date']))
                    <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{@format_date($item->date)}}</td>
                @endif
                
               <td colspan="{{($colspan - 3)}}" style="font-size: {{ $font_sizes['table_data'] }}px;">
                {{__('contact.payment')}}<br>
                @if(!empty($pmt_methods))
	                {{ucfirst($pmt_methods->method)}}
	                
	                @if(strtolower($pmt_methods->method) == 'bank' || strtolower($pmt_methods->method) == 'bank_transfer' || strtolower($pmt_methods->method) == 'cheque')
	                    <br>
	                    @if(!empty($pmt_methods->account_name))
	                        {!! __('contact.bank_name')." ".$pmt_methods->account_name."<br>" !!}
	                    @endif
	                    
	                    @if(!empty($pmt_methods->cheque_number))
	                        {!! __('contact.cheque_number')." ".$pmt_methods->cheque_number."<br>" !!}
	                    @endif
	                    
	                    @if(!empty($pmt_methods->cheque_date))
	                        {!! __('contact.cheque_date')." ".@format_date($pmt_methods->cheque_date."<br>") !!}
	                    @endif
	                    
	                @endif
	            @endif
	            @if($item->type == 'customer_payment')
	                {{__('contact.ref_no')." ".$item->invoice_no;}}
	            @endif
                
               </td>
                
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['invoice_amount']))
                <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{@num_format($amount)}}</td>
            @endif
            
            @if(empty($customer_statement_report) || !empty($customer_statement_report['due_amount']))
                <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{@num_format($due)}}</td>
            @endif
        
        </tr>
        @endforeach
    </tbody>
</table>
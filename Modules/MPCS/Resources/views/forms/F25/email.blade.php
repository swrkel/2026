<p>Hello,</p>
<p>Please find attached the F25 form <strong>{{ $header->form_no }}</strong>.</p>

<h3>Summary Details:</h3>
<ul>
    <li><strong>Form No:</strong> {{ $header->form_no }}</li>
    <li><strong>Date:</strong> {{ @format_date($header->transaction_date) }}</li>
    <li><strong>Delivery Location:</strong> 
        {{ !empty($header->delivery_code) ? str_pad((string) $header->delivery_code, 4, '0', STR_PAD_LEFT) : '' }}
        {{ !empty($header->delivery_location_name) ? ' - ' . $header->delivery_location_name : '' }}
    </li>
</ul>

<p>Thank you.</p>

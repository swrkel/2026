@php
  use App\ReportConfiguration;
  $business_id = request()->session()->get('user.business_id');
  $customer_statement = ReportConfiguration::where('business_id',$business_id)->where('name','customer_statement_report')->first();
  $customer_statement_report = !empty($customer_statement) ? json_decode($customer_statement->configurations,true) : [];
  
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

  // labels للترجمة
  $labels = [
    'date'               => __('messages.date'),
    'customer'           => __('contact.customer'),
    'location'           => 'Location',
    'invoice_no'         => __('sale.invoice_no'),
    'route_name'         => 'Route',
    'vehicle_no'         => __('contact.vehicle'),
    'customer_reference' => 'Customer Reference',
    'customer_po_no'     => 'Customer P/O No',
    'voucher_order_date' => 'Voucher Order Date',
    'product'            => __('sale.product'),
    'qty'                => __('lang_v1.qty'),
    'unit_price'         => __('unit_price'),
    'invoice_amount'     => __('sale.invoice_amount'),
    'due_amount'         => 'Balance Due',
  ];

  // أسماء الأعمدة الظاهرة القادمة من الكنترولر
  $cols = $visible_cols ?? [];

  // إن لم تصل، اعرض الكل
  if (empty($cols)) {
    $cols = array_keys($labels);
  }

  // Respect both runtime visible columns (from action request) and
  // saved report column-visibility settings.
  $showCol = function (string $k) use ($cols, $customer_statement_report) {
    $settingKeys = [
        'date'               => 'date',
        'location'           => 'location',
        'invoice_no'         => 'invoice_no',
        'route_name'         => 'route',
        'vehicle_no'         => 'vehicle',
        'customer_reference' => 'customer_reference',
        'customer_po_no'     => 'customer_po',
        'voucher_order_date' => 'voucher_date',
        'product'            => 'product',
        'qty'                => 'qty',
        'unit_price'         => 'unit_price',
        'invoice_amount'     => 'invoice_amount',
        'due_amount'         => 'due_amount',
    ];
    $visible_by_request = in_array($k, $cols, true);
    if (request()->has('columns')) {
        return $visible_by_request;
    }
    $cfgKey = $settingKeys[$k] ?? null;
    $visible_by_setting = true;
    if (!empty($customer_statement_report) && $cfgKey && array_key_exists($cfgKey, $customer_statement_report)) {
        $visible_by_setting = !empty($customer_statement_report[$cfgKey]) && $customer_statement_report[$cfgKey] !== '0';
    }
    return $visible_by_request && $visible_by_setting;
  };

  // Filter cols with the same visibility rule so headers/body/footers stay aligned.
  // We use array_keys($labels) to preserve the standard fixed column order.
  $cols = array_values(array_filter(array_keys($labels), fn($k) => $showCol($k)));

  // حساب الأعمدة قبل خانتي المبالغ
  $colsBeforeAmounts = collect(['date','customer','location','invoice_no','route_name','vehicle_no','customer_reference','customer_po_no','voucher_order_date','product','qty','unit_price'])
      ->filter(fn($k) => $showCol($k))
      ->count();

  $sumInv = 0.0;
  $sumDue = 0.0;
@endphp

<table>
  <thead>
    <tr>
      @foreach ($cols as $k)
        <th style="font-size: {{ $font_sizes['table_headers'] }}px;">{{ $labels[$k] ?? $k }}</th>
      @endforeach
    </tr>
  </thead>
  <tbody>
    @forelse($bill_lines as $line)
      @php
        $sumInv += (float) ($line['invoice_amount'] ?? 0);
        $sumDue += (float) ($line['due_amount'] ?? 0);
      @endphp
      <tr>
        @foreach ($cols as $k)
          @if (in_array($k, ['qty','unit_price','invoice_amount','due_amount'], true))
            <td style="text-align:right; font-size: {{ $font_sizes['table_data'] }}px;">
              @if($k === 'invoice_amount' || $k === 'due_amount')
                {{ @num_format((float)($line[$k] ?? 0)) }}
              @else
                {{ is_numeric($line[$k] ?? null) ? number_format((float)$line[$k], 2, '.', ',') : e($line[$k] ?? '') }}
              @endif
            </td>
          @else
            <td style="font-size: {{ $font_sizes['table_data'] }}px;">
              {{ $k === 'customer' ? ($contact->name ?? '') : e($line[$k] ?? '') }}
            </td>
          @endif
        @endforeach
      </tr>
    @empty
      <tr>
        <td colspan="{{ max(1, count($cols)) }}" style="text-align:center; font-size: {{ $font_sizes['table_data'] }}px;">— @lang('no_records_found') —</td>
      </tr>
    @endforelse
  </tbody>
  <tfoot>
    <tr>
      {{-- خلايا فارغة قبل عمودَي المبالغ إن كانا ظاهرين --}}
      @php
        $hasInv = $showCol('invoice_amount');
        $hasDue = $showCol('due_amount');
        $empties = count($cols) - ($hasInv ? 1 : 0) - ($hasDue ? 1 : 0);
      @endphp

      @for($i=0; $i<$empties; $i++)
        <th></th>
      @endfor

      @if($hasInv)
        <th style="text-align:right; font-size: {{ $font_sizes['total'] }}px;"><strong>Balance Due</strong></th>
      @endif
      @if($hasDue)
        <th style="text-align:right; font-size: {{ $font_sizes['total'] }}px;"><strong>{{ @num_format($sumDue) }}</strong></th>
      @endif
    </tr>
  </tfoot>
</table>

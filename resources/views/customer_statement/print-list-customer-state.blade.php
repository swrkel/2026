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

    $contactMobile = $contact->mobile ?? '—';
    $contactTax    = $contact->tax_number ?? '—';

    // ===== Column Visibility for detail table (Print) =====
    // خريطة ترتيب الأعمدة القادمة من Column visibility
    $detailColMap = [
        1 => 'date',
        2 => 'location',
        3 => 'invoice_no',
        4 => 'route_name',
        5 => 'vehicle_no',
        6 => 'customer_reference',
        7 => 'customer_po_no',
        8 => 'voucher_order_date',
        9 => 'product',
        10 => 'qty',
        11 => 'unit_price',
        12 => 'invoice_amount',
        13 => 'due_amount',
    ];

    // بناء selectedDetailCols[$key] = true للأعمدة المراد إظهارها
    $selectedDetailCols = [];
    if (!empty($visibleCols)) {
        foreach ($visibleCols as $idx) {
            if (isset($detailColMap[$idx])) {
                $selectedDetailCols[$detailColMap[$idx]] = true;
            }
        }
    }

    // دالة مساعدة: إن لم تصل columns نعرض كل الأعمدة، مع احترام إعدادات التقرير.
    $showCol = function (string $k) use ($selectedDetailCols, $customer_statement_report) {
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
        $visible_by_request = empty($selectedDetailCols) ? true : !empty($selectedDetailCols[$k]);
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

    // عدد الأعمدة قبل عمودَي المبالغ (إجمالي/مستحق)
    $colsBeforeAmounts = collect(['date','location','invoice_no','route_name','vehicle_no','customer_reference','customer_po_no','voucher_order_date','product','qty','unit_price'])
        ->filter(fn($k) => $showCol($k))->count();
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ __('contact.customer_statement') }} – {{ $row['customer'] ?? '' }}</title>
<style>
  *{box-sizing:border-box}
  body{font-family:Arial,Helvetica,sans-serif;margin:18px}
  .text-end{text-align:right}
  .bg_color{background:#357ca5;color:#fff;padding:4px 8px;display:inline-block;border-radius:2px}
  .header-line{margin:8px 0 14px;border:0;border-top:1px solid #ddd}
  table{width:100%;border-collapse:collapse}
  th,td{border:1px solid #ddd;padding:6px 8px;vertical-align:top}
  th{background:#f6f6f6}
</style>
</head>
<body>

  {{-- رأس الفاتورة: الشعار + بيانات النشاط --}}
  <div style="text-align:center; margin-bottom:16px;">
    @if(!empty($logo) && !empty($logo->logo))
      <img src="{{ url($logo->logo) }}" alt="logo" width="120" height="60"
           style="display:block;margin:0 auto 6px;object-fit:contain">
    @endif

    <div style="display:inline-block;text-align:center;line-height:1.5">
      <strong style="font-size: {{ $font_sizes['business_name'] }}px;">{{ $contact->business->name ?? '' }}</strong><br>
      <span style="font-size: {{ $font_sizes['business_address'] }}px;">{{ $location_details->city ?? '' }}, {{ $location_details->state ?? '' }}</span><br>
      <span style="font-size: {{ $font_sizes['business_mobile'] }}px;">{!! $location_details->mobile ?? '' !!}</span>
    </div>
  </div>

  <hr class="header-line">

  {{-- عنوان الفاتورة + بيانات العميل --}}
  <h4 style="margin:0 0 8px; font-size: {{ $font_sizes['invoice_no'] }}px;">
    <b>@lang('lang_v1.invoice_no'):</b> {{ $row['statement_no'] ?? '' }}
  </h4>

  <div class="bg_color" style="margin-top:10px; font-size: {{ $font_sizes['customer_label'] }}px;">@lang('lang_v1.to'):</div>
  <p style="margin:8px 0 0">
    <strong style="font-size: {{ $font_sizes['customer_name'] }}px;">{{ $contact->name }}</strong><br>
    <span style="font-size: {{ $font_sizes['customer_address'] }}px;">{!! $contact->contact_address !!}</span>
    @if(!empty($contact->email))
      <br><span style="font-size: {{ $font_sizes['customer_email'] }}px;">@lang('business.email'): {{ $contact->email }}</span>
    @endif
    <br><span style="font-size: {{ $font_sizes['customer_mobile'] }}px;">@lang('contact.mobile'): {{ $contactMobile }}</span>
    <br><span style="font-size: {{ $font_sizes['customer_tax'] }}px;">@lang('contact.tax_no'): {{ $contactTax }}</span>
  </p>

  {{-- جدول التفاصيل مع احترام Column Visibility --}}
  <div style="margin-top:8px;overflow:auto">
    <table>
      <thead>
        <tr>
          @if($showCol('date'))           <th style="width:110px; font-size: {{ $font_sizes['table_headers'] }}px;">@lang('messages.date')</th> @endif
          @if($showCol('location'))       <th style="width:120px; font-size: {{ $font_sizes['table_headers'] }}px;">Location</th> @endif
          @if($showCol('invoice_no'))     <th style="width:120px; font-size: {{ $font_sizes['table_headers'] }}px;">@lang('sale.invoice_no')</th> @endif
          @if($showCol('route_name'))     <th style="width:120px; font-size: {{ $font_sizes['table_headers'] }}px;">Route</th> @endif
          @if($showCol('vehicle_no'))     <th style="width:120px; font-size: {{ $font_sizes['table_headers'] }}px;">@lang('contact.vehicle')</th> @endif
          @if($showCol('customer_reference')) <th style="width:140px; font-size: {{ $font_sizes['table_headers'] }}px;">Customer Reference</th> @endif
          @if($showCol('customer_po_no')) <th style="width:140px; font-size: {{ $font_sizes['table_headers'] }}px;">Customer P/O No</th> @endif
          @if($showCol('voucher_order_date')) <th style="width:130px; font-size: {{ $font_sizes['table_headers'] }}px;">Voucher Order Date</th> @endif
          @if($showCol('product'))        <th style="width:130px; font-size: {{ $font_sizes['table_headers'] }}px;">@lang('sale.product')</th> @endif
          @if($showCol('qty'))            <th style="width:90px; font-size: {{ $font_sizes['table_headers'] }}px;"  class="text-end">@lang('lang_v1.qty')</th> @endif
          @if($showCol('unit_price'))     <th style="width:110px; font-size: {{ $font_sizes['table_headers'] }}px;" class="text-end">@lang('unit_price')</th> @endif
          @if($showCol('invoice_amount')) <th style="width:130px; font-size: {{ $font_sizes['table_headers'] }}px;" class="text-end">@lang('sale.invoice_amount')</th> @endif
          @if($showCol('due_amount'))     <th style="width:130px; font-size: {{ $font_sizes['table_headers'] }}px;" class="text-end">Due Amount</th> @endif
        </tr>
      </thead>
      <tbody>
        @php $sumInv=0.0; $sumDue=0.0; @endphp
        @forelse($billLines as $line)
          @php
            $sumInv += (float) $line['invoice_amount'];
            $sumDue += (float) $line['due_amount'];
          @endphp
          <tr>
            @if($showCol('date'))           <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{ e($line['date']) }}</td> @endif
            @if($showCol('location'))       <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{ e($line['location']) }}</td> @endif
            @if($showCol('invoice_no'))     <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{ e($line['invoice_no']) }}</td> @endif
            @if($showCol('route_name'))     <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{ e($line['route_name']) }}</td> @endif
            @if($showCol('vehicle_no'))     <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{ e($line['vehicle_no']) }}</td> @endif
            @if($showCol('customer_reference')) <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{ e($line['customer_reference']) }}</td> @endif
            @if($showCol('customer_po_no')) <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{ e($line['customer_po_no']) }}</td> @endif
            @if($showCol('voucher_order_date')) <td style="font-size: {{ $font_sizes['table_data'] }}px;">{{ e($line['voucher_order_date']) }}</td> @endif
            @if($showCol('product'))        <td style="word-break:break-word; font-size: {{ $font_sizes['table_data'] }}px;">{{ e($line['product']) }}</td> @endif

            @if($showCol('qty'))
              <td class="text-end" style="font-size: {{ $font_sizes['table_data'] }}px;">
                {{ is_numeric($line['qty']) ? number_format((float)$line['qty'],2,'.',',') : e($line['qty']) }}
              </td>
            @endif

            @if($showCol('unit_price'))
              <td class="text-end" style="font-size: {{ $font_sizes['table_data'] }}px;">
                {{ is_numeric($line['unit_price']) ? number_format((float)$line['unit_price'],2,'.',',') : e($line['unit_price']) }}
              </td>
            @endif

            @if($showCol('invoice_amount'))
              <td class="text-end" style="font-size: {{ $font_sizes['table_data'] }}px;">
                <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ (float)$line['invoice_amount'] }}">
                  {{ @num_format((float)$line['invoice_amount']) }}
                </span>
              </td>
            @endif

            @if($showCol('due_amount'))
              <td class="text-end" style="font-size: {{ $font_sizes['table_data'] }}px;">
                <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ (float)$line['due_amount'] }}">
                  {{ @num_format((float)$line['due_amount']) }}
                </span>
              </td>
            @endif
          </tr>
        @empty
          <tr>
            <td colspan="{{ max(1, $colsBeforeAmounts + ($showCol('invoice_amount')?1:0) + ($showCol('due_amount')?1:0)) }}"
                class="text-end" style="font-size: {{ $font_sizes['table_data'] }}px;">— @lang('no_records_found') —</td>
          </tr>
        @endforelse
      </tbody>
      <tfoot>
        @php
          $showInvAmt = $showCol('invoice_amount');
          $showDueAmt = $showCol('due_amount');
          $footerDueTotal = isset($total_balance_due) ? (float) $total_balance_due : (float) $sumDue;
          $amountColumns = ($showInvAmt ? 1 : 0) + ($showDueAmt ? 1 : 0);
          $labelColspan = max(1, $colsBeforeAmounts + ($showInvAmt && !$showDueAmt ? 0 : ($showInvAmt ? 1 : 0)));
        @endphp
        <tr>
          <th colspan="{{ $labelColspan }}" class="text-end" style="font-size: {{ $font_sizes['total'] }}px;">
            <strong>Balance Due</strong>
          </th>
          <th class="text-end" style="font-size: {{ $font_sizes['total'] }}px;">
            <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ $footerDueTotal }}">
              <strong>{{ @num_format($footerDueTotal) }}</strong>
            </span>
          </th>
        </tr>
      </tfoot>
    </table>
  </div>

  {{-- توقيع --}}
  <table style="width:100%;margin-top:24px;border:none">
    <tr><td style="border:none; font-size: {{ $font_sizes['signature'] }}px;"><strong>@lang('contact.signature') :</strong> ....................................................</td></tr>
  </table>

  {{-- فوتر التقارير (اختياري) --}}
  @php $reports_footer = \App\System::where('key','admin_reports_footer')->first(); @endphp
  @if(!empty($reports_footer))
    <style>
      #footer{display:none;margin-top:50px}
      @media print{#footer{display:block!important;position:fixed;bottom:-1mm;width:100%;text-align:center;font-size:12px;color:#333}}
    </style>
    <div id="footer">{{ $reports_footer->value }}</div>
  @endif

  <script>window.addEventListener('load',()=>window.print());</script>
</body>
</html>

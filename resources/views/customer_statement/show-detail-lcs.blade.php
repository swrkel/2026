@php
    use App\ReportConfiguration;
    $business_id = request()->session()->get('user.business_id');
    $customer_statement_cfg = ReportConfiguration::where('business_id', $business_id)
        ->where('name', 'customer_statement_report')
        ->first();
    $customer_statement_report = !empty($customer_statement_cfg)
        ? json_decode($customer_statement_cfg->configurations, true)
        : [];

    // ===== خريطة أعمدة الهيدر (معلومات البيان) تبقى كما هي إن احتجتها لاحقًا =====
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
        'date_printed'    => __('contact.date_printed'),
        'date_from'       => __('contact.date_from'),
        'date_to'         => __('contact.date_to'),
        'customer'        => __('contact.customer'),
        'statement_no'    => __('contact.statement_no'),
        'statement_amount'=> __('contact.statement_amount'),
        'payment_status'  => __('contact.payment_status'),
        'added_by'        => __('contact.added_by'),
        'description'     => __('contact.description'),
    ];

    $selectedCols = [];
    if (!empty($visibleCols)) {
        foreach ($visibleCols as $index) {
            if (isset($columnMap[$index])) {
                $selectedCols[$columnMap[$index]] = true;
            }
        }
    }

    $contactMobile = $contact->mobile ?? null;
    $contactTax    = $contact->tax_number ?? null;

    // ===== Column Visibility for detail table (View) =====
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

    $selectedDetailCols = [];
    if (!empty($visibleCols)) {
        foreach ($visibleCols as $idx) {
            if (isset($detailColMap[$idx])) {
                $selectedDetailCols[$detailColMap[$idx]] = true;
            }
        }
    }

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
            'due_amount'         => 'due_amount'
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

    $colsBeforeAmounts = collect(['date','location','invoice_no','route_name','vehicle_no','customer_reference','customer_po_no','voucher_order_date','product','qty','unit_price'])
    ->filter(fn($k) => $showCol($k))->count();
@endphp

<div class="modal-dialog modal-xl no-print" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>

    <div class="modal-body">
      <div class="row">
        <div class="col-md-12">

          <style>
            @media print{
              .dt-buttons,.dataTables_length,.dataTables_filter,
              .dataTables_info,.dataTables_paginate,.customer_details_div{display:none}
            }
            .bg_color{background:#357ca5;font-size:20px;color:#fff}
            .text-center{text-align:center}
            #customer_detail_table th{background:#357ca5;color:#fff}
            #customer_detail_table>tbody>tr:nth-child(2n+1)>td,
            #customer_detail_table>tbody>tr:nth-child(2n+1)>th{background-color:rgba(89,129,255,.3)}
            .text-end{text-align:right}
          </style>

          {{-- رأس المودال: شعار + بيانات العمل --}}
          @if (!empty($logo) && $logo->alignment == 'Left')
            <div class="row">
              @if (!empty($logo) && !empty($logo->logo))
                <div class="col-md-1">
                  <img src="{{ url($logo->logo) }}" class="img img-responsive center-block" height="100" width="100">
                </div>
              @endif
              <div class="col-md-11 col-sm-11 @if (!empty($for_pdf)) text-center @endif">
                <p class="text-center">
                  <strong>{{ $contact->business->name }}</strong><br>
                  {{ $location_details->city }}, {{ $location_details->state }}<br>
                  {!! $location_details->mobile !!}
                </p>
                <hr>
              </div>
            </div>
          @elseif(!empty($logo) && $logo->alignment == 'Right')
            <div class="row">
              <div class="col-md-11 col-sm-11 @if (!empty($for_pdf)) text-center @endif">
                <p class="text-center">
                  <strong>{{ $contact->business->name }}</strong><br>
                  {{ $location_details->city }}, {{ $location_details->state }}<br>
                  {!! $location_details->mobile !!}
                </p>
                <hr>
              </div>
              @if (!empty($logo) && !empty($logo->logo))
                <div class="col-md-1">
                  <img src="{{ url($logo->logo) }}" class="img img-responsive center-block" height="100" width="100">
                </div>
              @endif
            </div>
          @else
            <div class="row">
              @if (!empty($logo) && !empty($logo->logo))
                <div class="col-md-12">
                  <img src="{{ url($logo->logo) }}" class="img img-responsive center-block" height="100" width="100">
                </div>
              @endif
              <div class="col-md-12 col-sm-12 @if (!empty($for_pdf)) text-center @endif">
                <p class="text-center">
                  <strong>{{ $contact->business->name }}</strong><br>
                  {{ $location_details->city }}, {{ $location_details->state }}<br>
                  {!! $location_details->mobile !!}
                </p>
                <hr>
              </div>
            </div>
          @endif

          {{-- عنوان الفاتورة + بيانات العميل --}}
          <div class="col-md-6 col-sm-6 col-xs-6 @if (!empty($for_pdf)) width-50 f-left @endif">
            <h4 class="modal-title" id="modalTitle">
              <b>@lang('lang_v1.invoice_no'):</b> {{ $row['statement_no'] }}
            </h4>

            <p class="bg_color" style="width:40%;margin-top:20px">@lang('lang_v1.to'):</p>
            <p>
              <strong>{{ $contact->name }}</strong><br>
              {!! $contact->contact_address !!}
              @if (!empty($contact->email))
                <br>@lang('business.email'): {{ $contact->email }}
              @endif
              <br>@lang('contact.mobile'): {{ !empty($contactMobile) ? $contactMobile : '—' }}
              <br>@lang('contact.tax_no'): {{ !empty($contactTax) ? $contactTax : '—' }}
            </p>
          </div>

        </div>
      </div>

      <div class="row" style="margin-top:20px">
        <div class="col-md-12"></div>
      </div>

      <hr>

      {{-- جدول التفاصيل مع احترام Column Visibility --}}
      <div class="table-responsive" style="overflow:auto">
        <table class="table table-bordered table-striped table-sm" style="table-layout:fixed">
          <thead>
            <tr class="bg-light">
              @if($showCol('date'))           <th style="width:110px">@lang('messages.date')</th> @endif
              @if($showCol('customer'))
                  <th style="width:150px">@lang('contact.customer')</th>
              @endif
              @if($showCol('location'))       <th style="width:120px">Location</th> @endif
              @if($showCol('invoice_no'))     <th style="width:120px">@lang('sale.invoice_no')</th> @endif
              @if($showCol('route_name'))     <th style="width:120px">Route</th> @endif
              @if($showCol('vehicle_no'))     <th style="width:120px">@lang('contact.vehicle')</th> @endif
              @if($showCol('customer_reference')) <th style="width:140px">Customer Reference</th> @endif
              @if($showCol('customer_po_no')) <th style="width:140px">Customer P/O No</th> @endif
              @if($showCol('voucher_order_date')) <th style="width:130px">Voucher Order Date</th> @endif
              @if($showCol('product'))        <th style="width:100px">@lang('sale.product')</th> @endif
              @if($showCol('qty'))            <th style="width:90px"  class="text-end">@lang('lang_v1.qty')</th> @endif
              @if($showCol('unit_price'))     <th style="width:110px" class="text-end">@lang('unit_price')</th> @endif
              @if($showCol('invoice_amount')) <th style="width:130px" class="text-end">@lang('sale.invoice_amount')</th> @endif
              @if($showCol('due_amount'))     <th style="width:120px" class="text-end">Due Amount</th> @endif
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
                @if($showCol('date'))           <td>{{ e($line['date']) }}</td> @endif
                @if($showCol('customer'))
                    <td>{{ $contact->name }}</td>
                @endif
                @if($showCol('location'))       <td>{{ e($line['location']) }}</td> @endif
                @if($showCol('invoice_no'))     <td>{{ e($line['invoice_no']) }}</td> @endif
                @if($showCol('route_name'))     <td>{{ e($line['route_name']) }}</td> @endif
                @if($showCol('vehicle_no'))     <td>{{ e($line['vehicle_no']) }}</td> @endif
                @if($showCol('customer_reference')) <td>{{ e($line['customer_reference']) }}</td> @endif
                @if($showCol('customer_po_no')) <td>{{ e($line['customer_po_no']) }}</td> @endif
                @if($showCol('voucher_order_date')) <td>{{ e($line['voucher_order_date']) }}</td> @endif
                @if($showCol('product'))        <td style="word-break:break-word">{{ e($line['product']) }}</td> @endif

                @if($showCol('qty'))        <td class="text-end">{{ is_numeric($line['qty']) ? number_format((float)$line['qty'], 2, '.', ',') : e($line['qty']) }}</td> @endif
                @if($showCol('unit_price'))  <td class="text-end">{{ is_numeric($line['unit_price']) ? number_format((float)$line['unit_price'], 2, '.', ',') : e($line['unit_price']) }}</td> @endif

                @if($showCol('invoice_amount'))
                  <td class="text-end">
                    <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ (float) $line['invoice_amount'] }}">
                      {{ @num_format((float) $line['invoice_amount']) }}
                    </span>
                    
                  </td>
                @endif
                @if($showCol('due_amount'))
                  <td class="text-end">
                    <span class="display_currency" data-currency_symbol="true" data-orig-value="{{ (float) $line['due_amount'] }}">
                      {{ @num_format((float) $line['due_amount']) }}
                    </span>
                  </td>
                @endif
              </tr>
            @empty
              <tr>
                <td colspan="{{ max(1, $colsBeforeAmounts + ($showCol('invoice_amount')?1:0) + ($showCol('due_amount')?1:0)) }}"
                    class="text-center text-muted">— @lang('no_records_found') —</td>
              </tr>
            @endforelse
          </tbody>
          <tfoot>
            @php
              $showInvAmt = $showCol('invoice_amount');
              $showDueAmt = $showCol('due_amount');
            @endphp
            <tr class="fw-bold">
              {{-- خلايا فارغة قبل عمودَي المبالغ --}}
              @for($i=0; $i<$colsBeforeAmounts; $i++)
                <td class="text-end"></td>
              @endfor

              @if($showInvAmt)
                <td class="text-end">
                  <strong class="display_currency" data-currency_symbol="true" data-orig-value="{{ $sumInv }}">
                    {{ @num_format($sumInv) }}
                  </strong>
                </td>
              @endif

              @if($showDueAmt)
                <td class="text-end">
                  <strong class="display_currency" data-currency_symbol="true" data-orig-value="{{ $sumDue }}">
                    {{ @num_format($sumDue) }}
                  </strong>
                </td>
              @endif
            </tr>
          </tfoot>
        </table>
      </div>

      <table width="100%" style="margin-top:30px;">
        <tr>
          <th class="width-50">
            <strong>@lang('contact.signature') :...............................................</strong>
          </th>
        </tr>
      </table>

    </div>
  </div>
</div>

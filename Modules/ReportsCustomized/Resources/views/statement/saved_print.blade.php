@extends('layouts.app')
@section('title', __('LIOC Statement'))

@push('css')
<style>
    .invoice-table { border-collapse: collapse; width: 100%; border-top: 2px solid #d22; }
    .invoice-table th, .invoice-table td { border: 2px solid #d22; padding: 8px; vertical-align: middle; }
    .invoice-table thead th { font-weight: 700; color: #900; text-align: center; }
    .business-header { text-align: center; margin-bottom: 16px; }
    @media print {
        .no-print, .content-header, .main-sidebar, .main-header, .navbar { display: none !important; }
    }
</style>
@endpush

@section('content')
<section class="content">
    <div class="row no-print">
        <div class="col-md-12">
            <button type="button" class="btn btn-default" onclick="window.print();"><i class="fa fa-print"></i> @lang('messages.print')</button>
        </div>
    </div>

    <div class="row" style="margin-top: 12px;">
        <div class="business-header col-md-12">
            <h2>{{ $business->name ?? 'Business Name' }}</h2>
            <div>
                @if($location)
                    {{ $location->landmark ?? $location->address_1 ?? '' }}
                    @if($location->city || $location->state || $location->country)
                        {{ ', ' . implode(', ', array_filter([$location->city, $location->state, $location->country])) }}
                    @endif
                @endif
            </div>
            <div>T.P : {{ $location->mobile ?? $location->alternate_number ?? '' }}</div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th colspan="2" style="text-align: left;">BILL REF: {{ $saved->bill_ref_display }}</th>
                        <th colspan="4" style="text-align: right;">PERIOD: {{ $period_display }}</th>
                    </tr>
                    <tr>
                        <th style="width:50px;">S/No</th>
                        <th>Name</th>
                        <th>Amount</th>
                        <th>Order Date</th>
                        <th>Vehicle/Order No</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $i => $line)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ $line['name'] ?? '' }}</td>
                        <td class="text-right">{{ number_format($line['amount'] ?? 0, 2) }}</td>
                        <td>{{ isset($line['order_date']) ? \Carbon\Carbon::parse($line['order_date'])->format('d.m.Y') : '' }}</td>
                        <td>{{ $line['vehicle'] ?? '' }}</td>
                        <td>{{ $line['description'] ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="text-right">
                            @php
                                $cv = '';
                                if ($saved->bill_ref_display && preg_match('/\(([^)]*)\)\s*$/', $saved->bill_ref_display, $m)) {
                                    $cv = $m[1];
                                }
                            @endphp
                            {{ $cv }}
                        </td>
                        <td class="text-right">{{ number_format($total_amount, 2) }}</td>
                        <td>
                            @if(count($lines))
                                {{ \Carbon\Carbon::parse(collect($lines)->max('order_date'))->format('d.m.Y') }}
                            @endif
                        </td>
                        <td></td>
                        <td>{{ $saved->bill_ref_display }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="row" style="margin-top: 40px;">
        <div class="col-md-12 text-center">
            <p><strong>Manager Signature</strong></p>
        </div>
    </div>

    @if(!empty($report_footer))
    <div class="row" style="margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px;">
        <div class="col-md-12 text-center" style="color: red;">
            {!! $report_footer !!}
        </div>
    </div>
    @endif
</section>
@if(!empty($autoprint))
<script>
    $(function () {
        setTimeout(function () { window.print(); }, 300);
    });
</script>
@endif
@endsection

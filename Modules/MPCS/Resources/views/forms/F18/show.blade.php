@extends('layouts.app')
@section('title', 'F18 Form – View')

@php
    $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();
    $qp = $qty_precision ?? 2;
    $fmt = fn($v) => number_format($v, 2);
@endphp

@push('styles')
<style>
@media print {
    @page {
        margin: 0;
        size: auto;
    }
    body {
        margin: 0;
        padding: 10px;
    }
    .content {
        padding-top: 0 !important;
    }
    .no-print {
        display: none !important;
    }
    .box {
        border: none !important;
        box-shadow: none !important;
    }
    .box-body {
        padding: 10px !important;
    }
}
</style>
@endpush

@section('content')
<section class="content" style="padding-top:10px;">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-body">

                    {{-- Top right: Back button --}}
                    <div class="no-print" style="text-align:right; margin-bottom:6px;">
                        <a href="{{ url('/mpcs/F18') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>

                    {{-- Header --}}
                    <div style="text-align:center; margin-bottom:6px;">
                        <h4 style="font-weight:700; margin:0;">{{ $business->name }}</h4>
                        <div>Goods Exchange Note</div>
                        <div style="font-size:13px; color:#555;">{{ $header->form_date }}</div>
                    </div>

                    <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:13px;">
                        <div><strong>Location:</strong> {{ $from_location->name ?? '—' }}</div>
                        <div style="text-align:right;">
                            <strong>F 18 No:</strong> {{ $header->form_no }}<br>
                            <strong>Date:</strong> {{ $header->form_date }}
                        </div>
                    </div>

                    <div style="margin-bottom:8px; font-size:13px;">
                        <strong>Transferred to:</strong> {{ $to_location_text ?? '—' }}
                    </div>

                    {{-- Table --}}
                    <table class="table table-bordered" style="font-size:12px; border-color:#999;">
                        <thead style="background:#f5f5f5;">
                            <tr>
                                <th rowspan="3" style="vertical-align:middle;text-align:center;">#</th>
                                <th rowspan="3" style="vertical-align:middle;text-align:center;">Description</th>
                                <th rowspan="3" style="vertical-align:middle;text-align:center;">Qty</th>
                                <th colspan="4" class="text-center">Issued Location</th>
                                <th colspan="4" class="text-center">Received Location</th>
                                <th rowspan="3" style="vertical-align:middle;text-align:center;">Office Use</th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-center">Purchase Price</th>
                                <th colspan="2" class="text-center">Sale Price</th>
                                <th colspan="2" class="text-center">Purchase Price</th>
                                <th colspan="2" class="text-center">Sale Price</th>
                            </tr>
                            <tr>
                                <th class="text-center">Unit</th><th class="text-center">Total</th>
                                <th class="text-center">Unit</th><th class="text-center">Total</th>
                                <th class="text-center">Unit</th><th class="text-center">Total</th>
                                <th class="text-center">Unit</th><th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($details as $i => $row)
                            <tr>
                                <td class="text-center">{{ $i + 1 }}</td>
                                <td>{{ $row->product->name ?? '—' }}</td>
                                <td class="text-right">{{ number_format($row->qty, $qp) }}</td>
                                <td class="text-right">{{ $fmt($row->issued_purchase_unit_price) }}</td>
                                <td class="text-right">{{ $fmt($row->issued_purchase_total) }}</td>
                                {{-- IS2028: was issued_purchase_unit_price, with a
                                     comment claiming the purchase price was wanted
                                     here. The ticket asks for the related unit SALE
                                     price, and the Total cell beside it is already
                                     computed from issued_sale_unit_price. --}}
                                <td class="text-right">{{ $fmt($row->issued_sale_unit_price) }}</td>
                                <td class="text-right">{{ $fmt($row->issued_sale_total) }}</td>
                                <td class="text-right">{{ $fmt($row->received_purchase_unit_price) }}</td>
                                <td class="text-right">{{ $fmt($row->received_purchase_total) }}</td>
                                <td class="text-right">{{ $fmt($row->received_sale_unit_price) }}</td>
                                <td class="text-right">{{ $fmt($row->received_sale_total) }}</td>
                                <td></td>
                            </tr>
                            @empty
                            <tr><td colspan="12" class="text-center text-muted">No items.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-right">Total</th>
                                <th class="text-center">—</th>
                                <th class="text-right">{{ $fmt($details->sum('issued_purchase_total')) }}</th>
                                <th class="text-center">—</th>
                                <th class="text-right">{{ $fmt($details->sum('issued_sale_total')) }}</th>
                                <th class="text-center">—</th>
                                <th class="text-right">{{ $fmt($details->sum('received_purchase_total')) }}</th>
                                <th class="text-center">—</th>
                                <th class="text-right">{{ $fmt($details->sum('received_sale_total')) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>

                    {{-- Signatures --}}
                    <div style="display:flex; gap:10px; margin-top:30px;">
                        @foreach(['Prepared By','Approved By','Handed Over By','Received By','Date'] as $sig)
                        <div style="flex:1; text-align:center;">
                            <div style="border-bottom:1px dashed #777; height:22px; margin-bottom:3px;"></div>
                            <small>{{ $sig }}</small>
                        </div>
                        @endforeach
                    </div>

                    {{-- System standard report footer --}}
                    @if (!empty($reports_footer) && !empty($reports_footer->value))
                        <div style="margin-top:30px; width:100%; text-align:left; font-size:12px; color:#333; padding:10px 0 0 10px; border-top:1px solid #eee;">
                            {!! $reports_footer->value !!}
                        </div>
                    @endif

                    <div style="margin-top:10px;" class="no-print">
                        <a href="{{ url('/mpcs/F18') }}" class="btn btn-default btn-sm">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                        <button onclick="window.open('{{ url('/mpcs/F18/' . $header->id . '/print') }}', '_blank')" class="btn btn-primary btn-sm">
                            <i class="fa fa-print"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@if(!empty($auto_print))
@section('javascript')
<script>window.onload = function () { window.print(); };</script>
@endsection
@endif
@endsection

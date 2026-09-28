@extends('RiceMill::layout')
@section('rcm-title','Milling / production Operation')
@section('rcm-actions')
    <a class="rcm-btn" href="{{ route('rice-mill.production.index') }}"><i class="fa fa-list"></i> Production History</a>
@endsection
@section('rcm-content')
@php
    $isNewOperation = $isNewOperation ?? !$batch->exists;
@endphp
<form method="post" action="{{ $isNewOperation ? route('rice-mill.production.store') : route('rice-mill.production.complete',$batch->id) }}" id="rcm-production-complete-form" data-has-old-output="{{ old('outputs') ? '1' : '0' }}">
@csrf

<div class="rcm-card">
    <details class="rcm-collapsible-section" open>
        <summary class="rcm-section-title"><i class="fa fa-sliders"></i> Milling / Production Details <small>Click to collapse / expand</small></summary>
        <div class="rcm-collapsible-body">
            @if(!$isNewOperation && $batch->batch_no)
                <div class="rcm-help-text" style="margin-bottom:10px"><strong>Batch:</strong> {{ $batch->batch_no }}</div>
            @endif
            @php
                $selectedProductionStoreId = old('store_id', $batch->store_id ?: ($defaultStoreId ?? ''));
            @endphp
            <div class="rcm-form-grid rcm-location-store-group rcm-location-mill-group" data-rcm-default-first-store="1">
                <div class="rcm-field"><label>Date</label><input type="datetime-local" name="started_at" value="{{ old('started_at', optional($batch->started_at)->format('Y-m-d\TH:i') ?: now()->format('Y-m-d\TH:i')) }}"></div>
                <div class="rcm-field"><label>Location</label><select class="rcm-searchable rcm-location-select" name="location_id"><option value="">Select location</option>@foreach($locations as $x)<option value="{{ $x['id'] }}" {{ (string) old('location_id',$batch->location_id) === (string) $x['id'] ? 'selected' : '' }}>{{ $x['name'] }}</option>@endforeach</select></div>
                <div class="rcm-field"><label>Mill</label><select class="rcm-searchable rcm-mill-select" name="mill_id"><option value="">Select Mill</option>@foreach($mills as $m)<option value="{{ $m->id }}" data-location-id="{{ $m->location_id ?? '' }}" {{ (string) old('mill_id',$batch->mill_id) === (string) $m->id ? 'selected' : '' }}>{{ $m->name }}</option>@endforeach</select></div>
                <div class="rcm-field"><label>Store</label><select class="rcm-searchable rcm-store-select" name="store_id"><option value="">Select store</option>@foreach($stores as $x)<option value="{{ $x['id'] }}" data-location-id="{{ $x['location_id'] ?? '' }}" {{ (string) $selectedProductionStoreId === (string) $x['id'] ? 'selected' : '' }}>{{ $x['name'] }}</option>@endforeach</select></div>
            </div>
            <div class="rcm-field" style="margin-top:10px"><label>Note</label><textarea name="note">{{ old('note',$batch->note) }}</textarea></div>
        </div>
    </details>
</div>

<div class="rcm-card">
    <div class="rcm-section-title">Paddy Inputs</div>
    <div id="prod-inputs">
        <div data-rcm-production-input-row class="rcm-form-grid">
            <div class="rcm-field">
                <label>Paddy Lot</label>
                <select name="inputs[0][paddy_lot_id]" class="rcm-searchable rcm-production-lot" required>
                    @foreach($lots as $l)
                        @php
                            $mappedRiceProducts = $products->where('paddy_variety_id', $l->paddy_variety_id)->values();
                        @endphp
                        <option value="{{ $l->id }}" {{ (string) old('inputs.0.paddy_lot_id') === (string) $l->id ? 'selected' : '' }}
                            data-variety-id="{{ $l->paddy_variety_id }}"
                            data-rice="{{ $l->expected_rice_yield_percent ?? 0 }}"
                            data-broken="{{ $l->expected_broken_rice_percent ?? 0 }}"
                            data-bran="{{ $l->expected_bran_percent ?? 0 }}"
                            data-husk="{{ $l->expected_husk_percent ?? 0 }}"
                            data-loss="{{ $l->expected_process_loss_percent ?? 0 }}"
                            data-rice-product-count="{{ $mappedRiceProducts->count() }}"
                            data-rice-product-id="{{ $mappedRiceProducts->count() === 1 ? $mappedRiceProducts->first()->id : '' }}"
                            data-rice-product-name="{{ $mappedRiceProducts->count() === 1 ? $mappedRiceProducts->first()->name : '' }}">
                            {{ $l->lot_no }} - {{ $l->paddy_code ? $l->paddy_code.' - ' : '' }}{{ $l->paddy_name }} - {{ number_format($l->balance_qty,$rcmQuantityPrecision) }} kg
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="rcm-field"><label>Quantity (kg)</label><input type="number" step="{{ $rcmQuantityStep }}" min="{{ $rcmQuantityStep }}" name="inputs[0][quantity]" class="rcm-production-input-qty" value="{{ old('inputs.0.quantity') }}" required></div>
        </div>
    </div>
    <div class="rcm-help-text" id="rcm-production-yield-help">Mapped Output Products below use the selected Paddy Lot's yield settings for Rice / Broken Rice / Bran / Husk where applicable. All quantities remain editable.</div>
</div>

<div class="rcm-card">
    <div class="rcm-section-title">Output Type</div>
    <div class="rcm-help-text" style="margin-bottom:8px">Only Products mapped in Rice Mill → Settings → Product Category Mapping → Out Put Type are shown here. The old fixed “Other” row has been removed.</div>
    <div id="rcm-production-product-help" class="rcm-help-text" style="margin-bottom:12px">Select the Paddy Lot and input quantity. Applicable mapped Output Product quantities are suggested from Paddy Variety Settings; the saved Out Put Type mapping remains the Product source of truth.</div>

    @if(empty($outputTypes ?? []))
        <div class="rcm-alert rcm-alert-danger">
            <i class="fa fa-exclamation-circle"></i>
            <span>No Out Put Type Products are mapped. Please configure Settings → Product Category Mapping → Out Put Type before completing production.</span>
        </div>
    @else
        <div id="prod-outputs" class="rcm-production-output-table">
            <div class="rcm-production-output-head"><div>Output Product</div><div>Quantity (kg)</div></div>
            @foreach($outputTypes as $index => $outputType)
                @php
                    $yieldKey = (string)($outputType['yield_key'] ?? '');
                    $riceProductId = (int)($outputType['rice_product_id'] ?? 0);
                    $oldSourceId = (int) old('outputs.'.$index.'.source_product_id', $outputType['source_product_id']);
                    $oldQty = old('outputs.'.$index.'.quantity', 0);
                @endphp
                <div class="rcm-production-output-row"
                     data-output-type="{{ $outputType['output_type'] }}"
                     data-source-product-id="{{ $outputType['source_product_id'] }}"
                     data-rice-product-id="{{ $riceProductId ?: '' }}"
                     data-output-product-name="{{ $outputType['name'] }}">
                    <div class="rcm-output-type-label">
                        {{ !empty($outputType['code']) ? $outputType['code'].' - ' : '' }}{{ $outputType['name'] }}
                        <input type="hidden" name="outputs[{{ $index }}][source_product_id]" value="{{ $oldSourceId }}">
                    </div>
                    <div class="rcm-field">
                        <input type="number"
                               step="{{ $rcmQuantityStep }}"
                               min="0"
                               name="outputs[{{ $index }}][quantity]"
                               class="rcm-production-output-qty"
                               @if($yieldKey !== '') data-yield-key="{{ $yieldKey }}" @endif
                               value="{{ $oldQty }}"
                               required>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@include('RiceMill::partials.operational-payment',[
    'paymentTitle'=>'Payment',
    'paymentMap'=>$productionPaymentMap ?? [],
    'paymentHelp'=>'Optional payment for Milling / Production. Payment Accounts follow the selected Payment Method mapping for this business.'
])

<div class="rcm-card">
    <div class="rcm-section-title">Production Costs</div>
    <div class="rcm-form-grid">
        <div class="rcm-field"><label>Cost Type</label><input name="costs[0][cost_type]" value="Labour"></div><div class="rcm-field"><label>Amount</label><input type="number" step="{{ $rcmCurrencyStep }}" name="costs[0][amount]" value="{{ old('costs.0.amount',0) }}"></div>
        <div class="rcm-field"><label>Cost Type</label><input name="costs[1][cost_type]" value="Electricity / Fuel"></div><div class="rcm-field"><label>Amount</label><input type="number" step="{{ $rcmCurrencyStep }}" name="costs[1][amount]" value="{{ old('costs.1.amount',0) }}"></div>
    </div>
    <br><button class="rcm-btn" {{ empty($outputTypes ?? []) ? 'disabled' : '' }}>Complete Milling / Production & Post Stock</button>
</div>
</form>
@endsection

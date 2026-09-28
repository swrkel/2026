@extends('RiceMill::layout')
@section('rcm-title','Receive Paddy / Weighbridge Entry')
@section('rcm-content')
<form method="post" action="{{ route('rice-mill.receipts.store') }}">
    @csrf
    <div class="rcm-card">
        <div class="rcm-section-title">Automatic Numbers</div>
        <div class="rcm-form-grid rcm-number-preview-grid">
            <div class="rcm-field"><label>Weighbridge No</label><input value="{{ $weighbridgePreview }}" readonly></div>
            <div class="rcm-field"><label>Receipt No</label><input value="{{ $receiptPreview }}" readonly></div>
            <div class="rcm-field"><label>Stock Lot No</label><input id="rcm-stock-lot-preview" value="{{ optional($varieties->first())->lot_next_preview }}" readonly></div>
        </div>
        <div class="rcm-help-text">The Stock Lot Number is generated automatically from <strong>PD + Paddy Variety Code + that variety's own next number</strong>.</div>
    </div>

    <div class="rcm-card">
        <div class="rcm-form-grid rcm-location-store-group" data-rcm-default-first-store="1">
            <div class="rcm-field">
                <label>Related Purchase Order</label>
                <select id="rcm-related-purchase" class="rcm-searchable" name="purchase_id" data-details-base="{{ url('/rice-mill/receipts/purchase-details') }}">
                    <option value="">Manual / No Related Purchase Order</option>
                    @foreach($purchases as $p)<option value="{{ $p->id }}" {{ (string) old('purchase_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->purchase_no }}</option>@endforeach
                </select>
                <small id="rcm-related-purchase-help">Select an approved Purchase Order to auto-load its supplier, Paddy Variety and available defaults. All loaded fields remain editable.</small>
            </div>
            <div class="rcm-field"><label>Supplier</label><select id="rcm-receive-supplier" class="rcm-searchable" name="supplier_id" required><option value="">Select</option>@foreach($suppliers as $s)<option value="{{ $s['id'] }}" {{ (string) old('supplier_id') === (string) $s['id'] ? 'selected' : '' }}>{{ $s['name'] }}</option>@endforeach</select></div>
            <div class="rcm-field"><label>Paddy Variety</label><select id="rcm-receive-variety" class="rcm-searchable" name="paddy_variety_id" required>@foreach($varieties as $v)<option value="{{ $v->id }}" data-moisture="{{ $v->default_moisture_percent }}" data-foreign="{{ $v->foreign_matter_limit_percent }}" data-grade="{{ $v->quality_grade }}" data-lot-preview="{{ $v->lot_next_preview }}" {{ (string) old('paddy_variety_id') === (string) $v->id ? 'selected' : '' }}>{{ $v->code }} - {{ $v->name }}</option>@endforeach</select></div>
            <div class="rcm-field"><label>Vehicle No</label><input name="vehicle_no" value="{{ old('vehicle_no') }}"></div>
            <div class="rcm-field"><label>Gross Weight (kg)</label><input id="rcm-receive-gross" type="number" step="{{ $rcmQuantityStep }}" name="gross_weight" value="{{ old('gross_weight') }}" required></div>
            <div class="rcm-field"><label>Tare Weight (kg)</label><input id="rcm-receive-tare" type="number" step="{{ $rcmQuantityStep }}" name="tare_weight" value="{{ old('tare_weight') }}" required></div>
            <div class="rcm-field"><label>Moisture %</label><input id="rcm-receive-moisture" type="number" step="0.001" name="moisture_percent" value="{{ old('moisture_percent') }}"><small>Auto-loaded from the selected Paddy Variety; editable for the actual reading.</small></div>
            <div class="rcm-field"><label>Foreign Matter %</label><input id="rcm-receive-foreign" type="number" step="0.001" name="foreign_matter_percent" value="{{ old('foreign_matter_percent') }}"><small>Auto-loaded from the Paddy Variety setting; editable for the actual reading.</small></div>
            <div class="rcm-field"><label>Configured limit: %</label><input id="rcm-receive-configured-limit" type="number" step="0.001" name="foreign_matter_limit_percent" value="{{ old('foreign_matter_limit_percent') }}"><small>Auto-loaded from Paddy Variety Settings as the limit applied to this receipt; editable if an authorised exception is required.</small></div>
            <div class="rcm-field"><label>Quality Grade</label><input id="rcm-receive-grade" name="quality_grade" value="{{ old('quality_grade') }}"></div>
            <div class="rcm-field"><label>Received At</label><input type="datetime-local" name="received_at" value="{{ old('received_at', $receivedAtDefault ?? now()->format('Y-m-d\TH:i')) }}"></div>
            <div class="rcm-field"><label>Location</label><select id="rcm-receive-location" class="rcm-searchable rcm-location-select" name="location_id"><option value="">Select location</option>@foreach($locations as $x)<option value="{{ $x['id'] }}" {{ (string) old('location_id') === (string) $x['id'] ? 'selected' : '' }}>{{ $x['name'] }}</option>@endforeach</select></div>
            <div class="rcm-field"><label>Store</label><select id="rcm-receive-store" class="rcm-searchable rcm-store-select" name="store_id"><option value="">Select store</option>@foreach($stores as $x)<option value="{{ $x['id'] }}" data-location-id="{{ $x['location_id'] ?? '' }}" {{ (string) old('store_id', $defaultStoreId ?? '') === (string) $x['id'] ? 'selected' : '' }}>{{ $x['name'] }}</option>@endforeach</select></div>
        </div>
        <div class="rcm-alert rcm-alert-info" id="rcm-purchase-weight-note" style="display:none;margin-top:12px">The Purchase Order stores Net Weight, not separate Gross/Tare values. Gross Weight is initialized from the purchased Net Weight and Tare Weight to 0.000; enter the actual weighbridge values if different.</div>
        <div class="rcm-field" style="margin-top:10px"><label>Note</label><textarea name="note">{{ old('note') }}</textarea></div>
    </div>

    @include('RiceMill::partials.operational-payment',[
        'paymentTitle'=>'Payment',
        'paymentMap'=>$receiptPaymentMap ?? [],
        'paymentHelp'=>'Optional payment recorded with this Paddy receipt. Payment Accounts follow the selected Payment Method mapping for this business.'
    ])

    <div class="rcm-card">
        <button class="rcm-btn">Save Receipt & Create Paddy Lot</button>
    </div>
</form>

{{-- Preserve the original Receive Paddy records/details directly below the current entry page. --}}
@include('RiceMill::receipts.partials.receipt-list',['rows'=>$rows])
@endsection

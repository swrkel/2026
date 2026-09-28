@php
    $v = $variety ?? null;
    $paddyProducts = $paddyProducts ?? [];
    $selectedProductId = old('paddy_product_id', optional($v)->paddy_product_id);

    // Existing varieties created before the Product link column was introduced
    // are matched by Product Name + Code so Edit opens with the correct option.
    if (!$selectedProductId && $v) {
        foreach ($paddyProducts as $candidate) {
            if (
                strcasecmp(trim((string)($candidate['name'] ?? '')), trim((string)optional($v)->name)) === 0 &&
                strcasecmp(trim((string)($candidate['code'] ?? '')), trim((string)optional($v)->code)) === 0
            ) {
                $selectedProductId = (int)$candidate['id'];
                break;
            }
        }
    }

    $selectedCode = old('code', optional($v)->code);
    if ($selectedProductId) {
        foreach ($paddyProducts as $candidate) {
            if ((int)($candidate['id'] ?? 0) === (int)$selectedProductId) {
                $selectedCode = $candidate['code'] ?? $selectedCode;
                break;
            }
        }
    }
@endphp
<div class="rcm-form-grid">
    <div class="rcm-field">
        <label>Paddy Variety Name</label>
        <select class="rcm-searchable rcm-paddy-variety-product-select"
                name="paddy_product_id"
                data-placeholder="Type to search Paddy Product"
                required>
            <option value="">Select Paddy Product</option>
            @foreach($paddyProducts as $product)
                <option value="{{ $product['id'] }}"
                        data-code="{{ $product['code'] }}"
                        {{ (string)$selectedProductId === (string)$product['id'] ? 'selected' : '' }}>
                    {{ $product['name'] }}
                </option>
            @endforeach
        </select>
        @if(empty($paddyProducts))
            <small class="rcm-text-danger">No Paddy products are available. First map the Paddy Product Category in Product Category Mapping and ensure the products exist in Products New.</small>
        @else
            <small>Type to filter or use the dropdown scroll / keyboard arrows. Only Products New products from the mapped Paddy category are shown.</small>
        @endif
    </div>

    <div class="rcm-field">
        <label>Paddy Variety Code</label>
        <input name="code"
               value="{{ $selectedCode }}"
               maxlength="20"
               readonly
               data-rcm-paddy-variety-code
               required>
        <small>Loaded automatically from the selected Products New SKU. Manual changes are not allowed.</small>
    </div>

    <div class="rcm-field"><label>Default / Standard Moisture %</label><input type="number" step="0.001" min="0" max="100" name="default_moisture_percent" value="{{ old('default_moisture_percent', optional($v)->default_moisture_percent) }}"></div>
    <div class="rcm-field"><label>Foreign Matter Limit %</label><input type="number" step="0.001" min="0" max="100" name="foreign_matter_limit_percent" value="{{ old('foreign_matter_limit_percent', optional($v)->foreign_matter_limit_percent) }}"></div>
    <div class="rcm-field"><label>Expected Rice Yield %</label><input type="number" step="0.001" min="0" max="100" name="expected_rice_yield_percent" value="{{ old('expected_rice_yield_percent', optional($v)->expected_rice_yield_percent) }}"></div>
    <div class="rcm-field"><label>Expected Broken Rice %</label><input type="number" step="0.001" min="0" max="100" name="expected_broken_rice_percent" value="{{ old('expected_broken_rice_percent', optional($v)->expected_broken_rice_percent) }}"></div>
    <div class="rcm-field"><label>Expected Bran %</label><input type="number" step="0.001" min="0" max="100" name="expected_bran_percent" value="{{ old('expected_bran_percent', optional($v)->expected_bran_percent) }}"></div>
    <div class="rcm-field"><label>Expected Husk %</label><input type="number" step="0.001" min="0" max="100" name="expected_husk_percent" value="{{ old('expected_husk_percent', optional($v)->expected_husk_percent) }}"></div>
    <div class="rcm-field"><label>Expected Process Loss %</label><input type="number" step="0.001" min="0" max="100" name="expected_process_loss_percent" value="{{ old('expected_process_loss_percent', optional($v)->expected_process_loss_percent) }}"></div>
    <div class="rcm-field"><label>Quality Grade</label><input name="quality_grade" value="{{ old('quality_grade', optional($v)->quality_grade) }}" maxlength="50"></div>
    <div class="rcm-field"><label>Stock Lot Prefix</label><input value="PD-{{ $selectedCode ?: '{VARIETY CODE}' }}-" readonly data-rcm-paddy-variety-prefix><small>Generated automatically as PD + Paddy Variety Code.</small></div>
    <div class="rcm-field"><label>Stock Lot Opening Number</label><input type="number" min="1" name="lot_opening_number" value="{{ old('lot_opening_number', optional($v)->lot_opening_number ?: 1) }}" {{ !empty($lockOpening) ? 'readonly' : '' }}><small>{{ !empty($lockOpening) ? 'Locked because this variety is already used in transactions.' : 'Enter the opening number before the first transaction for this variety.' }}</small></div>
</div>

@php
    $p = $product ?? null;
    $riceMasterProducts = $riceMasterProducts ?? [];
    $selectedProductId = old(
        'products_new_product_id',
        optional($p)->source_product_id ?? optional($p)->products_new_product_id
    );

    // Compatibility for Rice Mill rows created before the Products New link
    // column existed: match the historical Rice Mill row by exact Name + SKU.
    if (!$selectedProductId && $p) {
        foreach ($riceMasterProducts as $candidate) {
            if (
                strcasecmp(trim((string)($candidate['name'] ?? '')), trim((string)optional($p)->name)) === 0 &&
                strcasecmp(trim((string)($candidate['code'] ?? '')), trim((string)optional($p)->code)) === 0
            ) {
                $selectedProductId = (int)$candidate['id'];
                break;
            }
        }
    }

    $selectedCode = old('code', optional($p)->code);
    if ($selectedProductId) {
        foreach ($riceMasterProducts as $candidate) {
            if ((int)($candidate['id'] ?? 0) === (int)$selectedProductId) {
                $selectedCode = $candidate['code'] ?? $selectedCode;
                break;
            }
        }
    }
@endphp
<div class="rcm-form-grid">
    <div class="rcm-field">
        <label>Rice Product Name</label>
        <select class="rcm-searchable rcm-rice-product-select"
                name="products_new_product_id"
                data-placeholder="Type to search Products New Rice Product"
                required>
            <option value="">Select Rice Product</option>
            @foreach($riceMasterProducts as $masterProduct)
                <option value="{{ $masterProduct['id'] }}"
                        data-code="{{ $masterProduct['code'] }}"
                        {{ (string)$selectedProductId === (string)$masterProduct['id'] ? 'selected' : '' }}>
                    {{ $masterProduct['name'] }}
                </option>
            @endforeach
        </select>
        @if(empty($riceMasterProducts))
            <small class="rcm-text-danger">No Rice products are available. First map the Rice Product Category in Product Category Mapping and ensure the products are available in Products New.</small>
        @else
            <small>Products are loaded only from the mapped Rice category in Products New. Type to filter or use the dropdown scroll / keyboard arrows.</small>
        @endif
    </div>

    <div class="rcm-field">
        <label>Code / SKU</label>
        <input name="code"
               value="{{ $selectedCode }}"
               maxlength="40"
               readonly
               data-rcm-rice-product-code
               required>
        <small>Loaded automatically from Products New. Manual changes are not allowed.</small>
    </div>

    <div class="rcm-field"><label>Rice Type</label><input name="rice_type" value="{{ old('rice_type', optional($p)->rice_type) }}"></div>
    <div class="rcm-field">
        <label>Related Paddy Variety</label>
        <select class="rcm-searchable" name="paddy_variety_id">
            <option value="">Not mapped</option>
            @foreach(($varieties ?? collect()) as $v)
                <option value="{{ $v->id }}" {{ (string) old('paddy_variety_id', optional($p)->paddy_variety_id) === (string) $v->id ? 'selected' : '' }}>{{ $v->code }} - {{ $v->name }}</option>
            @endforeach
        </select>
        <small>This mapping lets Complete Milling Batch auto-load the Rice Product from the selected Paddy Lot.</small>
    </div>
</div>

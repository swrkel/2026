<tr class="product_row">
    <td>
        {{ $variation_name }}
    </td>
    <td class="d-flex align-items-center">
        <button type="button" class="btn btn-sm btn-outline-danger qty-decrease">−</button>
        <input type="text" class="form-control input_number quantity_input mx-1 text-center"
            value="{{ number_format($quantity, 2, '.', '') }}" name="parts[{{ $variation_id }}][quantity]"
            style="width: 70px;">
        <button type="button" class="btn btn-sm btn-outline-secondary qty-increase">+</button>
        <span class="ms-2">{{ $unit }}</span>
    </td>

    <td>
        <input type="text" class="form-control input_number price_input"
            value="{{ number_format($unit_price ?? 0, 2, '.', '') }}" name="parts[{{ $variation_id }}][unit_price]"
            readonly>
    </td>
    <td>
        <input type="text" class="form-control input_number"
            value="{{ number_format($purchase_price ?? 0, 2, '.', '') }}"
            name="parts[{{ $variation_id }}][purchase_price]" readonly>
    </td>
    <td>
        <input type="text" class="form-control input_number subtotal_input"
            value="{{ number_format(($quantity ?? 0) * ($unit_price ?? 0), 2, '.', '') }}"
            name="parts[{{ $variation_id }}][subtotal]" readonly>
    </td>
    <td>
        <input type="text" class="form-control input_number tax_input"
            value="{{ number_format(($quantity ?? 0) * ($unit_price ?? 0) * (($tax_rate ?? 0) / 100), 2, '.', '') }}"
            name="parts[{{ $variation_id }}][tax]" readonly>
        <input type="hidden" class="tax_rate_input" value="{{ $tax_rate ?? 0 }}">
    </td>
    <td>
        <input type="text" class="form-control input_number total_input"
            value="{{ number_format(($quantity ?? 0) * ($unit_price ?? 0) * (1 + ($tax_rate ?? 0) / 100), 2, '.', '') }}"
            name="parts[{{ $variation_id }}][total]" readonly>
    </td>
    <td class="text-center">
        <i class="fas fa-times remove_product_row cursor-pointer" aria-hidden="true"></i>
    </td>
</tr>

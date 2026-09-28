const productRowTemplate = (product, rowIndex) => `
<tr class="product_row" data-row_index="${rowIndex}">
    <td>
        ${product.product_name}

        <input type="hidden" class="enable_sr_no" value="${
          product.enable_sr_no
        }">
        <input type="hidden" class="product_type" name="products[${rowIndex}][product_type]" value="${
  product.product_type
}">

        <div>
            <i class="fa fa-commenting cursor-pointer text-primary add-pos-row-description" 
               data-toggle="modal"
               data-target="#row_description_modal_${rowIndex}"></i>
        </div>

        <!-- Description Modal -->
        <div class="modal fade row_description_modal in" id="row_description_modal_${rowIndex}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        <h4 class="modal-title">${product.product_name} - ${
  product.sub_sku
}</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="products[${rowIndex}][sell_line_note]" rows="3">${
  product.sell_line_note ?? ""
}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade row_edit_product_price_model in" id="row_edit_product_price_modal_{{ $row_count }}"
            tabindex="-1" role="dialog">
            ${renderEditRowModal(product, rowIndex)}
        </div>
    </td>

    <!-- Quantity Column -->
    <td class="text-center">
        <input type="hidden" name="products[${rowIndex}][product_id]" class="product_id" value="${
  product.product_id
}">
        <input type="hidden" name="products[${rowIndex}][variation_id]" class="row_variation_id" value="${
  product.variation_id
}">
        <input type="hidden" name="products[${rowIndex}][enable_stock]" value="${
  product.enable_stock
}">
        <input type="hidden" name="products[${rowIndex}][product_unit_id]" value="${
  product.unit_id
}">
        <input type="hidden" class="base_unit_multiplier" name="products[${rowIndex}][base_unit_multiplier]" value="1">
        <input type="hidden" class="hidden_base_unit_sell_price" value="${
          product.sell_price_inc_tax
        }">
        <input type="hidden" class="unit_price_is_tax_inclusive" value="">

        ${renderQuantityInput(product, rowIndex)}
        <br>
        ${renderSubUnitSelect(product, rowIndex)}
    </td>

    <!-- Unit Price Column -->
    <td class="text-center">
        <input type="text" class="form-control pos_unit_price input_number"
            name="products[${rowIndex}][unit_price]" value="${
  product.sell_price_inc_tax
}">
    </td>

    <!-- Discount Type -->
    <td>
        <select name="products[${rowIndex}][line_discount_type]" class="form-control row_discount_type_table">
            <option value="fixed">Fixed</option>
            <option value="percentage">Percentage</option>
        </select>
    </td>

    <!-- Discount Amount -->
    <td>
        <input type="text" class="form-control input_number row_discount_amount_table"
            name="products[${rowIndex}][line_discount_amount]" value="${
  product.line_discount_amount ?? "0.00"
}">
    </td>

    <!-- Price Inc Tax -->
    <td class="text-center price_inc_tax_column">
        <input type="hidden" class="pos_unit_price_inc_tax" value="${num_format(
          product.sell_price_inc_tax
        )}">
        <input type="hidden" name="products[${rowIndex}][sell_price_inc_tax]" value="${num_format(
  product.sell_price_inc_tax
)}">
        <span class="display_currency price_inc_tax_display">${num_format(
          product.sell_price_inc_tax
        )}</span>
    </td>

    <!-- Subtotal -->
    <td class="text-center">
        <input type="hidden" class="form-control pos_line_total input_number"
            name="products[${rowIndex}][subtotal]" value="${num_format(
  product.sell_price_inc_tax
)}">
        <span class="display_currency pos_line_total_text" id="subtotal_text_{{ $row_count }}"
            data-currency_symbol="true">${num_format(
              product.sell_price_inc_tax
            )}</span>
    </td>

    <!-- Remove Button -->
    <td class="text-center">
        <i class="fa fa-close text-danger pos_remove_row cursor-pointer"></i>
    </td>
</tr>

<!-- Hidden tax fields -->
<input type="hidden" name="products[${rowIndex}][tax_id]" value="${
  product.tax_id
}" class="tax_id">
<input type="hidden" name="products[${rowIndex}][item_tax]" value="${
  product.item_tax
}" class="item_tax">
`;

function num_format(
  number,
  decimals = 2,
  dec_point = ".",
  thousands_sep = ","
) {
  if (isNaN(number) || number === null) number = 0;
  const n = parseFloat(number).toFixed(decimals);
  const parts = n.split(".");
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousands_sep);
  return parts.join(dec_point);
}

function renderSubUnitSelect(product, rowCount) {
  const subUnits = product.sub_units;
  if (!subUnits || Object.keys(subUnits).length === 0) {
    return `<span>${product.unit}</span>`;
  }

  let html = `<br><select name="products[${rowCount}][sub_unit_id]" class="form-control input-sm sub_unit">`;

  Object.entries(subUnits).forEach(([key, value]) => {
    const selected = product.sub_unit_id == key ? "selected" : "";

    html += `
            <option value="${key}"
                data-multiplier="${value.multiplier}"
                data-unit_price="${value.unit_price ?? ""}"
                data-unit_name="${value.name}"
                data-allow_decimal="${value.allow_decimal}"
                ${selected}>
                ${value.name}
            </option>
        `;
  });

  html += `</select>`;

  return html;
}

function renderQuantityInput(product, rowIndex) {
  const min = 0.01;
  const step = 0.01;
  const decimal = 1;

  const html = `
<input type="text"
    class="form-control pos_quantity input_number mousetrap input_quantity"
    name="products[${rowIndex}][quantity]"
    value="${product.quantity_ordered || product.quantity || 1}"
    data-min="${min}"
    step="${step}"
    inputmode="decimal"
    pattern="^\\d*\\.?\\d*$"
    data-decimal="${decimal}"
    data-allow-overselling="true"
    data-rule-required="true"
    data-msg-required="This field is required"
>
`;
  return html;
}

function renderEditRowModal(product, rowIndex) {
  return `
<div class="modal fade row_edit_product_price_modal" id="row_edit_product_price_modal_${rowIndex}" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">

            <!-- Header -->
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>

                <div class="col-md-4">
                    <h4 class="modal-title">${product.product_name} - ${
    product.sub_sku
  }</h4>
                </div>
                <div class="col-md-4">
                    <span style="color:red;font-weight:bold;">Qty: ${
                      product.qty_available ?? 0
                    }</span>
                </div>
                <div class="col-md-3">
                    <span style="color:red;font-weight:bold;">P.Price: ${
                      product.purchase_price ?? 0
                    }</span>
                </div>
                <div class="col-md-4">
                    <span style="color:red;font-weight:bold;">Supplier: ${
                      product.supplier_name ?? ""
                    }</span>
                </div>
                <div class="col-md-4">
                    <span style="color:red;font-weight:bold;">Rack: ${
                      product.rack_number ?? ""
                    }</span>
                </div>
            </div>

            <!-- Body -->
            <div class="modal-body">
                <div class="row">

                    <!-- Unit Price -->
                    <div class="form-group col-xs-12">
                        <label>Unit Price</label>
                        <input type="text" 
                            class="form-control pos_unit_price input_number"
                            data-row="${rowIndex}"
                            value="${product.default_sell_price}">
                        <span style="color:red;font-size:14px;font-weight:bold;" 
                              class="error_price_${rowIndex}"></span>
                    </div>

                    <!-- Discount type -->
                    <div class="form-group col-xs-12 col-sm-6">
                        <label>Discount Type</label>
                        <select class="form-control row_discount_type" data-row="${rowIndex}">
                            <option value="fixed">Fixed</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>

                    <!-- Discount amount -->
                    <div class="form-group col-xs-12 col-sm-6">
                        <label>Discount Amount</label>
                        <input type="text" 
                            class="form-control input_number row_discount_amount"
                            data-row="${rowIndex}" value="0">
                    </div>

                    <!-- Description -->
                    <div class="form-group col-xs-12">
                        <label>Description</label>
                        <textarea class="form-control" 
                                  name="products[${rowIndex}][sell_line_note]" 
                                  rows="3">${
                                    product.sell_line_note ?? ""
                                  }</textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>
    `;
}

function renderOfflineProductSuggestion(products) {
  const container = $("#product_list_body");
  container.html("");
  if (!products || products.length === 0) {
    container.html(`
            <input type="hidden" id="no_products_found">
            <div class="col-md-12">
                <h4 class="text-center">No products to display</h4>
            </div>
        `);
    return;
  }

  products.forEach((product) => {
    const media =
      product.media && product.media.length > 0
        ? product.media[0].display_url
        : product.product_image
        ? "/uploads/img/" + product.product_image
        : "/img/default.png";

    const html = `
            <div class="col-md-3 col-xs-4 product_list no-print">
                <div class="product_box bg-gray" 
                    data-toggle="tooltip" data-placement="bottom"
                    data-variation_id="${product.id}" 
                    data-product_id="${product.product_id}" 
                    title="${product.product_name} (${product.sub_sku})">
                    <div class="image-container">
                        <img src="${media}" alt="Product Image">
                    </div>
                    <div class="text text-muted text-uppercase">
                        <small>${product.product_name}</small>
                    </div>
                    <small class="text-muted">(${product.sub_sku})</small>
                </div>
            </div>
        `;
    container.append(html);
  });
}

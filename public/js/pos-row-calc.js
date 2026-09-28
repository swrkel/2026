(function () {
  if (window.__posRowCalcLoaded) {
    return;
  }
  window.__posRowCalcLoaded = true;
  console.log("[pos-row-calc] loaded");

  function num(v) {
    return v == null
      ? 0
      : parseFloat(
          String(v)
            .replace(/,/g, "")
            .replace(/[^0-9.\-]+/g, "")
        ) || 0;
  }

  function inferItemTaxRate($tr, unitEx) {
    let itemTaxRate = 0;
    const taxEl = $tr.find(".tax_id");

    if (taxEl.is("select")) {
      itemTaxRate = num(taxEl.find(":selected").data("rate"));
    } else {
      itemTaxRate = num(taxEl.data("rate"));
    }

    if (!itemTaxRate && unitEx > 0) {
      const originalUnitIncTax = num(
        $tr.find(".original_pos_unit_price_inc_tax").val() ||
          $tr.find("input.pos_unit_price_inc_tax").val()
      );

      if (originalUnitIncTax > unitEx) {
        itemTaxRate = ((originalUnitIncTax - unitEx) / unitEx) * 100;
      }
    }

    if (!taxEl.is("select") && itemTaxRate) {
      taxEl.attr("data-rate", itemTaxRate);
    }

    return itemTaxRate;
  }


  window.recalcRow = function ($tr) {
    const qty = num($tr.find('input[name*="[quantity]"]').val()) || 1;
    const baseEx = num($tr.find(".hidden_base_unit_sell_price").val());
    const mult = num($tr.find(".base_unit_multiplier").val()) || 1;

    const unitEx = baseEx * mult;
    const itemTaxRate = inferItemTaxRate($tr, unitEx);
    const orderTaxRate = num($("#tax_calculation_amount").val()) || 0;
    const unitIncItemTax = unitEx * (1 + itemTaxRate / 100);
    const unitIncAllTax = unitIncItemTax * (1 + orderTaxRate / 100);

    const discType = String($tr.find(".row_discount_type_table").val() || "fixed").toLowerCase();
    const discVal = num($tr.find(".row_discount_amount_table").val());

    // Discount reduces only the subtotal, NEVER the unit price inc tax.
    // The original price per unit is always preserved for display and DB storage.
    let lineDiscountTotal;

    if (discType === "percentage") {
      lineDiscountTotal = (unitIncAllTax * qty) * (discVal / 100);
    } else {
      // Fixed discount is a flat amount for the whole line
      lineDiscountTotal = discVal;
    }

    if (lineDiscountTotal < 0) lineDiscountTotal = 0;

    const grossLineTotal = unitIncAllTax * qty;
    const lineVisual = Math.max(grossLineTotal - lineDiscountTotal, 0);

    // Internal line total (exc order tax, BEFORE discount) for billing calculation
    const lineInternal = unitIncItemTax * qty;

    // Price inc. tax display: ALWAYS the original unit price (no discount applied)
    const safeUnitPriceDisplay = unitIncAllTax;

    $tr.find(".pos_line_total").val(lineInternal.toFixed(2));
    __write_number($tr.find(".pos_line_total"), lineInternal);
    
    if (typeof __currency_trans_from_en === "function") {
      $tr.find(".pos_line_total_text").text(__currency_trans_from_en(lineVisual, true));
      $tr.find(".price_inc_tax_display").text(__currency_trans_from_en(safeUnitPriceDisplay, true));
    } else {
      $tr.find(".pos_line_total_text").text(lineVisual.toFixed(2));
      $tr.find(".price_inc_tax_display").text(safeUnitPriceDisplay.toFixed(2));
    }
    __write_number($tr.find(".pos_line_total_discount"), lineDiscountTotal);

    // Always write the ORIGINAL unit_price_inc_tax (item tax only, no order tax)
    // to the hidden inputs so the form always submits the undiscounted value.
    if (typeof __write_number === "function") {
      __write_number($tr.find("input.pos_unit_price_inc_tax"), unitIncItemTax);
      __write_number($tr.find('input[name^="products"][name$="[unit_price_inc_tax]"]'), unitIncItemTax);
    } else {
      $tr.find("input.pos_unit_price_inc_tax").val(unitIncItemTax.toFixed(2));
      $tr.find('input[name^="products"][name$="[unit_price_inc_tax]"]').val(unitIncItemTax.toFixed(2));
    }

    if (typeof pos_total_row === "function") {
      pos_total_row();
    }


    if (typeof __currency_convert_recursively === "function") {
      __currency_convert_recursively($("#pos_table"));
    }
  };


  // Recalculate only the row you touch
  $(document).on(
    "input change",
    "tr.product_row .row_discount_type_table, \
     tr.product_row .row_discount_amount_table, \
     tr.product_row .pos_quantity, \
     tr.product_row .tax, \
     tr.product_row .sub_unit", // unit change updates multiplier
    function () {
      recalcRow($(this).closest("tr.product_row"));
    }
  );

  // Initialize current rows
  $(function () {
    $("tr.product_row").each(function () {
      recalcRow($(this));
    });
  });
})();

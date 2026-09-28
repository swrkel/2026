(function () {
    var rowIndex = 0;
    function money(value) { return (Number(value || 0)).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    function num(value) { return Number(String(value || '0').replace(/,/g, '')) || 0; }
    function lineHtml(i) {
        return '<tr>'+
            '<td><input type="hidden" name="lines['+i+'][product_id]" class="vs-product-id"><input type="hidden" name="lines['+i+'][variation_id]" class="vs-variation-id"><input type="text" name="lines['+i+'][item_name]" class="form-control vs-item-name" placeholder="Search product or enter custom item" required></td>'+
            '<td><input type="text" name="lines['+i+'][description]" class="form-control"></td>'+
            '<td><input type="number" step="0.0001" name="lines['+i+'][quantity]" class="form-control text-right vs-calc vs-qty" value="1"></td>'+
            '<td><input type="number" step="0.0001" name="lines['+i+'][unit_price]" class="form-control text-right vs-calc vs-unit" value="0"></td>'+
            '<td><input type="number" step="0.0001" name="lines['+i+'][discount_amount]" class="form-control text-right vs-calc vs-discount" value="0"></td>'+
            '<td><input type="number" step="0.0001" name="lines['+i+'][tax_amount]" class="form-control text-right vs-calc vs-tax" value="0"></td>'+
            '<td class="text-right vs-line-total">0.00</td>'+
            '<td><button type="button" class="btn btn-danger btn-xs vs-remove"><i class="fa fa-trash"></i></button></td>'+
            '</tr>';
    }
    function recalc() {
        var subtotal = 0, discount = 0, tax = 0, total = 0;
        $('#vehicle_lines_table tbody tr').each(function () {
            var $tr = $(this), qty = num($tr.find('.vs-qty').val()), unit = num($tr.find('.vs-unit').val()), disc = num($tr.find('.vs-discount').val()), tx = num($tr.find('.vs-tax').val());
            var gross = qty * unit, lineTotal = Math.max(0, gross - disc + tx);
            subtotal += gross; discount += disc; tax += tx; total += lineTotal;
            $tr.find('.vs-line-total').text(money(lineTotal));
        });
        $('#vs_subtotal').text(money(subtotal)); $('#vs_discount_total').text(money(discount)); $('#vs_tax_total').text(money(tax)); $('#vs_total_amount').text(money(total));
    }
    function attachProductSearch($input) {
        if (!$.fn.select2 || !window.vehicleServiceProductSearchUrl) { return; }
        $input.select2({
            tags: true,
            width: '100%',
            ajax: { url: window.vehicleServiceProductSearchUrl, dataType: 'json', delay: 250, data: function (params) { return {q: params.term}; }, processResults: function (data) { return data; } },
            createTag: function (params) { return {id: params.term, text: params.term, newTag: true}; }
        }).on('select2:select', function (e) {
            var data = e.params.data || {}, $tr = $(this).closest('tr');
            $tr.find('.vs-item-name').val(data.text || data.id || '');
            if (!data.newTag) { $tr.find('.vs-product-id').val(data.id || ''); $tr.find('.vs-variation-id').val(data.variation_id || ''); if (data.unit_price !== undefined) { $tr.find('.vs-unit').val(data.unit_price); } }
            else { $tr.find('.vs-product-id,.vs-variation-id').val(''); }
            recalc();
        });
    }
    function addLine() { var $row = $(lineHtml(rowIndex++)); $('#vehicle_lines_table tbody').append($row); attachProductSearch($row.find('.vs-item-name')); recalc(); }
    $(document).on('click', '#add_vehicle_line', addLine);
    $(document).on('click', '.vs-remove', function () { $(this).closest('tr').remove(); recalc(); });
    $(document).on('input change', '.vs-calc', recalc);
    $(function () { if ($('#vehicle_lines_table tbody tr').length === 0) { addLine(); } });
})();

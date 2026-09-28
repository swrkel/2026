(function ($) {
    'use strict';
    $(document).on('click', '#bs014_add_line', function () {
        $('#bs014_pos_lines_table tbody').append('<tr><td><select class="form-control"><option value="service">Service</option><option value="product">Product</option></select></td><td><input class="form-control"></td><td><input class="form-control" value="1"></td><td><input class="form-control" value="0.00"></td><td>0.00</td><td><button class="btn btn-danger btn-sm bs014-remove-line">X</button></td></tr>');
    });
    $(document).on('click', '.bs014-remove-line', function () { $(this).closest('tr').remove(); });
})(jQuery);

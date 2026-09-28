{{--
    Petro General / Dip Management (v2) - Dip Resetting form behaviour.

    These four handlers drive the Add Dip Resetting modal. They are copied
    verbatim from the working legacy screen,
        Resources/views/dip_management/index.blade.php
    (the #add_reset_tank_id, #reset_new_dip, #adjustment_type and
    #dip_resetting_form blocks), so the form behaves identically on both pages.

    They are all delegated from $(document), which is why they can be moved: the
    modal markup is fetched over AJAX and does not exist when this runs.

    The legacy page is deliberately NOT edited. It works today, its own copies of
    these handlers stay where they are, and the two pages are never open at the
    same time - so there is no double binding.

    The single difference from the legacy copy is the save callback. The legacy
    page calls dip_resetting_table.ajax.reload() on a DataTable that only exists
    there; this page renders its lists server-side, so it reloads instead, which
    refreshes both the Resettings and Readings tabs.
--}}
<script id="pg-dip-resetting-form-js">
(function ($) {
    'use strict';

    if (window.__pgDipResettingFormLoaded) {
        return;
    }
    window.__pgDipResettingFormLoaded = true;

    // Tank chosen -> fill product, current quantity and the current dip difference.
    $(document).on('change.pgDipReset', '#add_reset_tank_id', function () {
        $.ajax({
            method: 'get',
            url: '/petro-general/get-tank-balance-by-id/' + $(this).val(),
            data: {},
            dataType: 'json',
            success: function (result) {
                $('#product_name').val(result.product.name);
                $('#current_qty').val(result.current_stock);

                if (result.current_diff == '0.000' || result.reset_new_dip == '0.00') {
                    $('#current_dip_difference').val(result.reset_new_dip);
                } else {
                    $('#current_dip_difference').val((result.current_diff_for_reseting).toFixed(2));
                }
            }
        });
    });

    // New dip entered -> work out the adjustment quantity and its direction,
    // then load the matching inventory adjustment accounts.
    $(document).on('change.pgDipReset', '#reset_new_dip', function () {
        var quantity_presicion = $('#quantity_presicion').val();
        var current_qty = $('#current_qty').val();
        var new_dip_qty = $(this).val();
        var qty_to_adjust = new_dip_qty - current_qty;
        var type = '';

        /*
         * Type is a select2 now, so setting .val() alone would leave the OLD
         * label on screen. change.select2 repaints it without firing the
         * #adjustment_type handler below, which would otherwise request the
         * account list a second time for the same direction.
         */
        type = parseInt(qty_to_adjust) < 0 ? 'decrease' : 'increase';

        $('#adjustment_type').val(type).trigger('change.select2');

        $.ajax({
            method: 'get',
            url: '/petro-general/inventory-adjustment-account',
            data: { type: type },
            contentType: 'html',
            success: function (result) {
                $('#inventory_adjustment_account').empty().append(result);
            }
        });

        $('#qty_to_adjust').val(qty_to_adjust.toFixed(quantity_presicion));
    });

    // Direction changed by hand -> reload the accounts for that direction.
    $(document).on('change.pgDipReset', '#adjustment_type', function () {
        var type = $(this).val() === 'decrease' ? 'decrease' : 'increase';

        $.ajax({
            method: 'get',
            url: '/petro-general/inventory-adjustment-account',
            data: { type: type },
            contentType: 'html',
            success: function (result) {
                $('#inventory_adjustment_account').empty().append(result);
            }
        });
    });

    $(document).on('submit.pgDipReset', '#dip_resetting_form', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $save = $form.find('.add_dip_resetting_btn');

        // A dip resetting writes a stock adjustment, so a double submit would
        // post the correction twice.
        $save.prop('disabled', true);

        $.ajax({
            method: 'post',
            url: '/petro-general/save-resetting-dip',
            data: {
                location_id: $form.find('#location_id').val(),
                tank_id: $form.find('#add_reset_tank_id').val(),
                inventory_adjustment_account: $form.find('#inventory_adjustment_account').val(),
                meter_reset_form_no: $form.find('input[name=meter_reset_form_no]').val(),
                date_and_time: $form.find('input[name=date_and_time]').val(),
                transaction_date: $form.find('input[name=transaction_date]').val(),
                current_qty: $form.find('input[name=current_qty]').val(),
                current_dip_difference: $form.find('input[name=current_dip_difference]').val(),
                reset_new_dip: $form.find('input[name=reset_new_dip]').val(),
                adjustment_type: $form.find('#adjustment_type').val(),
                reason: $form.find('#reason').val()
            },
            success: function (result) {
                if (result.success == 1) {
                    toastr.success(result.msg);
                    $('.pg_dip_modal').modal('hide').empty();

                    // Both tabs are rendered server-side, so a reload is what
                    // shows the new row in Resettings and its reading in Readings.
                    window.location.reload();
                    return;
                }

                toastr.error(result.msg);
                $save.prop('disabled', false);
            },
            error: function () {
                toastr.error('{{ __('messages.something_went_wrong') }}');
                $save.prop('disabled', false);
            }
        });

        return false;
    });
})(jQuery);
</script>

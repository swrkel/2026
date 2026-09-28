<script type="text/javascript">
$(document).ready(function () {
    if ($.fn.select2) {
        $('.select2').select2({width: '100%'});
    }
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({autoclose: true, format: 'yyyy-mm-dd', todayHighlight: true});
    }

    function toggleCommissionValue() {
        var value = $('.commission_type').val();
        if (value === 'none' || value === '') {
            $('.commission_ap_div').hide();
            $('[name="commission_ap"]').val('');
        } else {
            $('.commission_ap_div').show();
        }
    }
    $(document).on('change', '.commission_type', toggleCommissionValue);
    toggleCommissionValue();
});
</script>

(function ($) {
    'use strict';

    function membershipReportFilters() {
        return {
            date_from: $('#membership_report_date_from').val(),
            date_to: $('#membership_report_date_to').val(),
            region_id: $('#membership_report_region_id').val(),
            status_id: $('#membership_report_status_id').val()
        };
    }

    $(document).ready(function () {
        if (!$('#membership_member_register_report_table').length) {
            return;
        }

        var table = $('#membership_member_register_report_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/membership/reports/member-register',
                data: membershipReportFilters
            },
            columns: [
                { data: 'member_no', name: 'member_no' },
                { data: 'member_name', name: 'member_name' },
                { data: 'region', name: 'region' },
                { data: 'membership_type', name: 'membership_type' },
                { data: 'status', name: 'status' },
                { data: 'date_joined', name: 'date_joined' },
                { data: 'share_value', name: 'share_value', className: 'text-right' }
            ]
        });

        $(document).on('change', '#membership_report_date_from, #membership_report_date_to, #membership_report_region_id, #membership_report_status_id', function () {
            table.ajax.reload();
        });
    });
})(jQuery);

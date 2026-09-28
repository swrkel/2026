(function ($) {
    'use strict';

    function membershipReportFilters() {
        return {
            date_from: $('#membership_report_date_from').val(),
            date_to: $('#membership_report_date_to').val()
        };
    }

    $(document).on('shown.bs.tab', 'a[href="#points_summary_report_tab"]', function () {
        var $table = $('#membership_points_summary_report_table');
        if (!$table.length || $.fn.DataTable.isDataTable($table)) {
            return;
        }

        $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/membership/reports/points-summary',
                data: membershipReportFilters
            },
            columns: [
                { data: 'transaction_date', name: 'transaction_date' },
                { data: 'member_no', name: 'member_no' },
                { data: 'member_name', name: 'member_name' },
                { data: 'points', name: 'points', className: 'text-right' },
                { data: 'note', name: 'note' }
            ]
        });
    });
})(jQuery);

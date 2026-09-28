(function ($) {
    'use strict';

    function membershipReportFilters() {
        return {
            date_from: $('#membership_report_date_from').val(),
            date_to: $('#membership_report_date_to').val()
        };
    }

    $(document).on('shown.bs.tab', 'a[href="#card_issue_report_tab"]', function () {
        var $table = $('#membership_card_issue_report_table');
        if (!$table.length || $.fn.DataTable.isDataTable($table)) {
            return;
        }

        $table.DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/membership/reports/card-issue',
                data: membershipReportFilters
            },
            columns: [
                { data: 'issue_date', name: 'issue_date' },
                { data: 'member_no', name: 'member_no' },
                { data: 'member_name', name: 'member_name' },
                { data: 'card_number', name: 'card_number' }
            ]
        });
    });
})(jQuery);

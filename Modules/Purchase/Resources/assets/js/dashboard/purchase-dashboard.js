/* PUR-007 Purchase Dashboard */
$(function () {
    function purchaseModuleUrl(path) {
        var base = (window.PurchaseModuleBaseUrl || '/purchase').replace(/\/$/, '');
        return base + '/' + String(path || '').replace(/^\//, '');
    }

    function dashboardFilters() {
        return {
            start_date: $('#purchase_dashboard_start_date').val(),
            end_date: $('#purchase_dashboard_end_date').val()
        };
    }

    function formatAmount(value) {
        value = parseFloat(value || 0);
        return value.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function loadSummary() {
        $.get(purchaseModuleUrl('dashboard/widgets/summary'), dashboardFilters(), function (response) {
            $('#purchase_dashboard_purchase_count').text((response.purchase_count || 0).toLocaleString());
            $('#purchase_dashboard_purchase_total').text(formatAmount(response.purchase_total));
            $('#purchase_dashboard_paid_total').text(formatAmount(response.paid_total));
            $('#purchase_dashboard_outstanding_total').text(formatAmount(response.outstanding_total));
            $('#purchase_dashboard_outstanding_widget').text(formatAmount(response.outstanding_total));
        });
    }

    function loadRecentPurchases() {
        $.get(purchaseModuleUrl('dashboard/widgets/recent-purchases'), dashboardFilters(), function (rows) {
            var tbody = $('#purchase_dashboard_recent_purchases tbody');
            tbody.empty();

            $.each(rows || [], function (i, row) {
                tbody.append(
                    '<tr>' +
                    '<td>' + (row.transaction_date || '') + '</td>' +
                    '<td>' + (row.ref_no || '') + '</td>' +
                    '<td class="text-right">' + formatAmount(row.final_total) + '</td>' +
                    '<td>' + (row.payment_status || '') + '</td>' +
                    '</tr>'
                );
            });
        });
    }

    function reloadDashboard() {
        loadSummary();
        loadRecentPurchases();
    }

    $(document).on('change', '.purchase-dashboard-filter', reloadDashboard);

    reloadDashboard();
});

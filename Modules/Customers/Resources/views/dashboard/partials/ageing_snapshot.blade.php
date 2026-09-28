<div class="box box-warning customers-dashboard-box">
    <div class="box-header with-border">
        <h3 class="box-title">Aging Snapshot</h3>
    </div>
    <div class="box-body table-responsive no-padding">
        <table class="table table-condensed">
            <tr><th>Current</th><td class="customers-money">{{ number_format($aging['current'] ?? 0, 2) }}</td></tr>
            <tr><th>1 - 30 Days</th><td class="customers-money">{{ number_format($aging['days_1_30'] ?? 0, 2) }}</td></tr>
            <tr><th>31 - 60 Days</th><td class="customers-money">{{ number_format($aging['days_31_60'] ?? 0, 2) }}</td></tr>
            <tr><th>61 - 90 Days</th><td class="customers-money">{{ number_format($aging['days_61_90'] ?? 0, 2) }}</td></tr>
            <tr><th>Over 90 Days</th><td class="customers-money">{{ number_format($aging['over_90'] ?? 0, 2) }}</td></tr>
        </table>
    </div>
</div>

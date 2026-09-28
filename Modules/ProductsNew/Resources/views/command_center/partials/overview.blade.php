<div class="row">
    <div class="col-md-6"><h4>Sales Intelligence</h4><p>30 day sales: <strong>{{ $workspace['sales']['sales_30_days'] }}</strong></p><p>30 day value: <strong>{{ number_format($workspace['sales']['sales_value_30_days'],4) }}</strong></p></div>
    <div class="col-md-6"><h4>Purchasing Intelligence</h4><p>Preferred supplier: <strong>{{ $workspace['purchasing']['preferred_supplier'] ?: '-' }}</strong></p><p>Lead time: <strong>{{ $workspace['purchasing']['lead_time_days'] }} days</strong></p></div>
</div>

<div class="row">
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box customers-kpi-card">
            <span class="info-box-icon bg-aqua"><i class="fa fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Customers</span>
                <span class="info-box-number customers-number">{{ number_format($summary['total_customers'] ?? 0) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box customers-kpi-card">
            <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Active Customers</span>
                <span class="info-box-number customers-number">{{ number_format($summary['active_customers'] ?? 0) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box customers-kpi-card">
            <span class="info-box-icon bg-yellow"><i class="fa fa-credit-card"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Credit Customers</span>
                <span class="info-box-number customers-number">{{ number_format($summary['credit_customers'] ?? ($credit_summary['credit_customer_count'] ?? 0)) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box customers-kpi-card">
            <span class="info-box-icon bg-red"><i class="fa fa-balance-scale"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Outstanding</span>
                <span class="info-box-number customers-money">{{ number_format($summary['outstanding_total'] ?? 0, 2) }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box customers-kpi-card">
            <span class="info-box-icon bg-purple"><i class="fa fa-shopping-cart"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Monthly Sales</span>
                <span class="info-box-number customers-money">{{ number_format($summary['monthly_sales'] ?? 0, 2) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box customers-kpi-card">
            <span class="info-box-icon bg-teal"><i class="fa fa-money"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Monthly Collections</span>
                <span class="info-box-number customers-money">{{ number_format($summary['monthly_collections'] ?? 0, 2) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box customers-kpi-card">
            <span class="info-box-icon bg-orange"><i class="fa fa-sliders"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Credit Limit Total</span>
                <span class="info-box-number customers-money">{{ number_format($summary['credit_limit_total'] ?? 0, 2) }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box customers-kpi-card">
            <span class="info-box-icon bg-maroon"><i class="fa fa-clock-o"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Last Payment</span>
                <span class="info-box-number" style="font-size:16px;">{{ !empty($summary['last_payment_date']) ? date('Y-m-d', strtotime($summary['last_payment_date'])) : '-' }}</span>
            </div>
        </div>
    </div>
</div>

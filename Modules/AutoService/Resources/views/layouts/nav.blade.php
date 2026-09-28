<div class="box box-solid auto-service-nav">
    <div class="box-body">
        <a class="btn btn-primary btn-sm" href="{{ route('autoservice.dashboard') }}">Dashboard</a>
        <a class="btn btn-success btn-sm" href="{{ route('autoservice.command_centre.index') }}">Command Centre</a>
        <a class="btn btn-info btn-sm" href="{{ route('autoservice.receptions.index') }}">Reception</a>
        <a class="btn btn-primary btn-sm" href="{{ route('autoservice.vehicles.index') }}">Vehicles</a>
        <a class="btn btn-primary btn-sm" href="{{ route('autoservice.jobs.index') }}">Jobs</a>
        <a class="btn btn-warning btn-sm" href="{{ route('autoservice.workshop.index') }}">Workshop</a>
        <a class="btn btn-warning btn-sm" href="{{ route('autoservice.service_flow.index') }}">Service Flow</a>
        <a class="btn btn-success btn-sm" href="{{ route('autoservice.quality_control.index') }}">QC</a>
        <a class="btn btn-success btn-sm" href="{{ route('autoservice.deliveries.index') }}">Delivery</a>
        <a class="btn btn-success btn-sm" href="{{ route('autoservice.billing_delivery.index') }}">Billing & Delivery</a>
        <a class="btn btn-primary btn-sm" href="{{ route('autoservice.estimates.index') }}">Estimates</a>
        <a class="btn btn-primary btn-sm" href="{{ route('autoservice.invoices.index') }}">Invoices</a>
        <a class="btn btn-primary btn-sm" href="{{ route('autoservice.payments.index') }}">Payments</a>
        <a class="btn btn-primary btn-sm" href="{{ route('autoservice.reports.index') }}">Reports</a>
        <a class="btn btn-info btn-sm" href="{{ route('autoservice.customer_care.index') }}">Customer Care</a>
        <a class="btn btn-primary btn-sm" href="{{ route('autoservice.settings.index') }}">Settings</a>

        <div class="btn-group">
            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                More <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <li><a href="{{ route('autoservice.workspace.index') }}">Advisor Workspace</a></li>
                <li><a href="{{ route('autoservice.appointments.index') }}">Appointments</a></li>
                <li><a href="{{ route('autoservice.inspections.index') }}">Inspections</a></li>
                <li><a href="{{ route('autoservice.parts_labour.index') }}">Parts & Labour</a></li>
                <li><a href="{{ route('autoservice.inventory_control.index') }}">Inventory Control</a></li>
                <li><a href="{{ route('autoservice.workshop_planning.index') }}">Workshop Planning</a></li>
                @if (Route::has('autoservice.dealer_enterprise.index'))
                    <li><a href="{{ route('autoservice.dealer_enterprise.index') }}">Dealer Enterprise</a></li>
                @endif
                <li><a href="{{ route('autoservice.mechanics.index') }}">Mechanics</a></li>
                <li><a href="{{ route('autoservice.packages.index') }}">Packages</a></li>
                <li><a href="{{ route('autoservice.whiteboard.index') }}">Whiteboard</a></li>
                <li><a href="{{ route('autoservice.mechanic_dashboard.index') }}">Mechanic Dashboard</a></li>
                <li><a href="{{ route('autoservice.bays.index') }}">Bays</a></li>
                <li><a href="{{ route('autoservice.communications.index') }}">Communications</a></li>
                <li><a href="{{ route('autoservice.calendar.index') }}">Calendar</a></li>
                <li><a href="{{ route('autoservice.documents.index') }}">Documents</a></li>
                <li><a href="{{ route('autoservice.approvals.index') }}">Approvals</a></li>
                <li><a href="{{ route('autoservice.notifications.index') }}">Notifications</a></li>
                <li><a href="{{ route('autoservice.customer_portal.lookup') }}">Customer Lookup</a></li>
                <li><a href="{{ route('autoservice.customer_portal.login') }}">Customer Status Login</a></li>
                <li><a href="{{ route('autoservice.reminders.index') }}">Reminders</a></li>
                <li><a href="{{ route('autoservice.completion.index') }}">Completion Check</a></li>
                <li><a href="{{ route('autoservice.production_audit.index') }}">Production Audit</a></li>
                <li><a href="{{ route('autoservice.stabilization.index') }}">Stabilization Centre</a></li>
                <li><a href="{{ route('autoservice.deployment_diagnostics.index') }}">Deployment Diagnostics</a></li>
                <li><a href="{{ route('autoservice.enterprise_integration.index') }}">Enterprise Integration</a></li>
                <li><a href="{{ route('autoservice.ui_standardization.index') }}">UI Standardization & Performance</a></li>
            </ul>
        </div>
    </div>
</div>

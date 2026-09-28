{{-- Reference only. Use ONLY if the host sidebar does not auto-discover Config/sidebar.php. --}}
@if(auth()->check() && (!method_exists(auth()->user(),'can') || auth()->user()->can('audit.view')))
<li class="treeview {{ request()->is('audit*') ? 'active' : '' }}">
    <a href="#"><i class="fa fa-shield"></i> <span>Audit</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
    <ul class="treeview-menu">
        <li><a href="{{ route('audit.dashboard') }}">Dashboard</a></li>
        <li><a href="{{ route('audit.run.index') }}">Run Audit</a></li>
        <li><a href="{{ route('audit.findings.index') }}">Audit Findings</a></li>
        <li><a href="{{ route('audit.rules.index') }}">Audit Rules</a></li>
        <li><a href="{{ route('audit.schedules.index') }}">Audit Schedules</a></li>
        <li><a href="{{ route('audit.reports.index') }}">Reports</a></li>
    </ul>
</li>
@endif

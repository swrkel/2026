{{-- ERP Experience Framework V4 Style Guide Sample --}}
<x-erp.page-header title="ERP Experience Framework" subtitle="V4 Toolbar, DataTable, Cards and Action Dropdown Foundation" />

<x-erp.toolbar>
    <x-slot name="left">
        <input class="form-control input-sm" placeholder="Search">
        <x-erp.button variant="primary" icon="fa fa-search">Search</x-erp.button>
    </x-slot>
    <x-slot name="right">
        <x-erp.button variant="success" icon="fa fa-plus">Add</x-erp.button>
        <x-erp.button variant="default" icon="fa fa-refresh">Refresh</x-erp.button>
    </x-slot>
</x-erp.toolbar>

<div class="erp-exf-kpi-grid">
    <x-erp.kpi-card label="Today" value="128" icon="fa fa-line-chart" />
    <x-erp.kpi-card label="Pending" value="24" icon="fa fa-clock-o" />
    <x-erp.kpi-card label="Completed" value="104" icon="fa fa-check" />
</div>

<x-erp.card title="Component Notes">
    <p>Use these components gradually while fixing modules. Do not rewrite all modules at once.</p>
    <x-erp.status type="success">Stable Foundation</x-erp.status>
</x-erp.card>

<div class="box box-default customers-dashboard-box">
    <div class="box-header with-border">
        <h3 class="box-title">Customers Navigation</h3>
    </div>
    <div class="box-body">
        @include('customers::layouts.menu', ['customer_menu' => $customer_menu ?? []])
    </div>
</div>

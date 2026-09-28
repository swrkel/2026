<div class="box box-primary">
    <div class="box-header with-border">
        @includeIf('suppliers::suppliers.partials.list-toolbar')
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped supplier-financial-table" width="100%">
            <thead>
                <tr>
                    <th>@lang('suppliers::lang.actions')</th>
                    <th>@lang('suppliers::lang.date')</th>
                    <th>@lang('suppliers::lang.reference_no')</th>
                    <th>@lang('suppliers::lang.description')</th>
                    <th>@lang('suppliers::lang.status')</th>
                    <th>@lang('suppliers::lang.payment_status')</th>
                    <th class="text-right">@lang('suppliers::lang.amount')</th>
                    <th class="text-right">@lang('suppliers::lang.balance')</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

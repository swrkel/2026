<div class="box box-primary">
    <div class="box-header with-border">
        @includeIf('suppliers::suppliers.partials.list-toolbar')
    </div>
    {{-- IS1967: supplier-financial-table-wrap lets the CSS lift the overflow that
         Bootstrap's .table-responsive applies, which was clipping the Action
         dropdown. A class here is reliable; the :has() selector it replaces is
         not supported by every browser in use. --}}
    <div class="box-body table-responsive supplier-financial-table-wrap">
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

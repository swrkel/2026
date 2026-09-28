<div class="tab-pane {{ $active_tab == 'transactions' ? 'active' : '' }}" id="pg_transactions">
    <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">@lang('petrogeneral::lang.tank_transactions')</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped pg-table" id="pg_tank_transactions_table">
                <thead><tr><th>@lang('petrogeneral::lang.tank')</th><th class="text-right">@lang('petrogeneral::lang.quantity')</th></tr></thead>
                <tbody>
                    @forelse($transaction_summary as $row)
                        <tr><td>{{ $row->tank_id ?? '' }}</td><td class="text-right">{{ @num_format($row->total_qty ?? 0) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted">@lang('petrogeneral::lang.no_records_found')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

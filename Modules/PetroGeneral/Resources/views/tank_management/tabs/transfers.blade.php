<div class="tab-pane {{ $active_tab == 'transfers' ? 'active' : '' }}" id="pg_transfers">
    <div class="box box-info">
        <div class="box-header with-border"><h3 class="box-title">@lang('petrogeneral::lang.tank_transfers')</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped pg-table" id="pg_tank_transfers_table">
                <thead><tr><th>@lang('petrogeneral::lang.date')</th><th>@lang('petrogeneral::lang.reference_no')</th><th>@lang('petrogeneral::lang.from_tank')</th><th>@lang('petrogeneral::lang.to_tank')</th><th class="text-right">@lang('petrogeneral::lang.quantity')</th></tr></thead>
                <tbody>
                    @forelse($transfers as $transfer)
                        <tr><td>{{ $transfer->transaction_date ?? $transfer->date ?? '' }}</td><td>{{ $transfer->ref_no ?? $transfer->reference_no ?? '' }}</td><td>{{ $transfer->from_tank_id ?? '' }}</td><td>{{ $transfer->to_tank_id ?? '' }}</td><td class="text-right">{{ @num_format($transfer->quantity ?? $transfer->qty ?? 0) }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">@lang('petrogeneral::lang.no_records_found')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

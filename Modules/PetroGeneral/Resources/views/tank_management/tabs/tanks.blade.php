<div class="tab-pane {{ $active_tab == 'tanks' ? 'active' : '' }}" id="pg_tanks">
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">@lang('petrogeneral::lang.tanks')</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped pg-table" id="pg_tanks_table">
                <thead><tr><th>@lang('petrogeneral::lang.tank_no')</th><th>@lang('petrogeneral::lang.tank_name')</th><th>@lang('petrogeneral::lang.product')</th><th class="text-right">@lang('petrogeneral::lang.storage_volume')</th></tr></thead>
                <tbody>
                    @forelse($tanks as $tank)
                        <tr><td>{{ $tank->fuel_tank_number ?? '' }}</td><td>{{ $tank->fuel_tank_name ?? '' }}</td><td>{{ $tank->product_name ?? '' }}</td><td class="text-right">{{ @num_format($tank->storage_volume ?? 0) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">@lang('petrogeneral::lang.no_records_found')</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
